<?php

namespace App\Services\Cleanup;

use App\Services\Cleanup\Contracts\CodeRefactoringServiceInterface;
use App\Services\Cleanup\Models\RefactoringPlan;
use App\Services\Cleanup\Models\RefactoringResult;
use App\Services\Cleanup\Models\ComponentExtractionSuggestion;
use App\Services\Cleanup\Models\MethodExtractionSuggestion;
use App\Services\Cleanup\Models\ReferenceUpdate;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class CodeRefactoringService implements CodeRefactoringServiceInterface
{
    private FileModificationService $fileModificationService;
    
    public function __construct(FileModificationService $fileModificationService)
    {
        $this->fileModificationService = $fileModificationService;
    }
    
    public function executeRefactoring(RefactoringPlan $plan): RefactoringResult
    {
        $startTime = microtime(true);
        $result = new RefactoringResult(true);
        
        try {
            // Validate the plan first
            $validationErrors = $this->validateRefactoring($plan);
            if (!empty($validationErrors)) {
                foreach ($validationErrors as $error) {
                    $result->addError($error);
                }
                return $result;
            }
            
            // Create backups if requested
            if ($plan->createBackups) {
                $this->createBackupsForPlan($plan, $result);
            }
            
            // Execute component extractions
            if (!empty($plan->componentExtractions)) {
                $componentResult = $this->extractComponents($plan->componentExtractions);
                $this->mergeResults($result, $componentResult);
                
                if (!$componentResult->success) {
                    return $result;
                }
            }
            
            // Execute method extractions
            if (!empty($plan->methodExtractions)) {
                $methodResult = $this->extractMethods($plan->methodExtractions);
                $this->mergeResults($result, $methodResult);
                
                if (!$methodResult->success) {
                    return $result;
                }
            }
            
            // Execute method consolidations
            if (!empty($plan->methodConsolidations)) {
                $consolidationResult = $this->consolidateMethods($plan->methodConsolidations);
                $this->mergeResults($result, $consolidationResult);
                
                if (!$consolidationResult->success) {
                    return $result;
                }
            }
            
            // Update references
            if (!empty($plan->referenceUpdates)) {
                if (!$this->updateReferences($plan->referenceUpdates)) {
                    $result->addError('Failed to update method references');
                    return $result;
                }
                $result->addRefactoring('references', 'Updated ' . count($plan->referenceUpdates) . ' method references');
            }
            
            $result->executionTime = microtime(true) - $startTime;
            
        } catch (\Exception $e) {
            $result->addError('Unexpected error during refactoring: ' . $e->getMessage());
            Log::error('Refactoring failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        
        return $result;
    } 
   
    public function extractComponents(array $componentSuggestions): RefactoringResult
    {
        $result = new RefactoringResult(true);
        
        try {
            foreach ($componentSuggestions as $suggestion) {
                if (!($suggestion instanceof ComponentExtractionSuggestion)) {
                    $result->addError('Invalid component extraction suggestion');
                    continue;
                }
                
                // Create the new component file
                $componentPath = $suggestion->generateComponentPath();
                if ($this->createComponentFile($suggestion)) {
                    $result->addFileCreated($componentPath);
                    $result->addRefactoring(
                        'component_extraction',
                        "Extracted component '{$suggestion->suggestedName}'",
                        [
                            'component_path' => $componentPath,
                            'duplicates_removed' => count($suggestion->occurrences)
                        ]
                    );
                    
                    // Update source files to use the new component
                    $this->replaceWithComponentUsage($suggestion, $result);
                    
                    $result->duplicatesRemoved += count($suggestion->occurrences);
                } else {
                    $result->addError("Failed to create component file: {$suggestion->componentPath}");
                }
            }
            
        } catch (\Exception $e) {
            $result->addError('Component extraction failed: ' . $e->getMessage());
        }
        
        return $result;
    }
    
    public function extractMethods(array $methodSuggestions): RefactoringResult
    {
        $result = new RefactoringResult(true);
        
        try {
            foreach ($methodSuggestions as $suggestion) {
                if (!($suggestion instanceof MethodExtractionSuggestion)) {
                    $result->addError('Invalid method extraction suggestion');
                    continue;
                }
                
                // Create the helper class file
                if ($this->createHelperClassFile($suggestion)) {
                    $result->addFileCreated($suggestion->suggestedFilePath);
                    $result->addRefactoring(
                        'method_extraction',
                        "Extracted method '{$suggestion->methodName}' to '{$suggestion->suggestedClassName}'",
                        [
                            'helper_class' => $suggestion->suggestedClassName,
                            'helper_path' => $suggestion->suggestedFilePath,
                            'duplicates_removed' => $suggestion->getDuplicateCount()
                        ]
                    );
                    
                    // Replace duplicate methods with calls to the helper
                    $this->replaceWithMethodCalls($suggestion, $result);
                    
                    $result->duplicatesRemoved += $suggestion->getDuplicateCount();
                    $result->linesReduced += $suggestion->getEstimatedSavings();
                } else {
                    $result->addError("Failed to create helper class file: {$suggestion->suggestedFilePath}");
                }
            }
            
        } catch (\Exception $e) {
            $result->addError('Method extraction failed: ' . $e->getMessage());
        }
        
        return $result;
    }
    
    public function consolidateMethods(array $duplicateMethods): RefactoringResult
    {
        $result = new RefactoringResult(true);
        
        try {
            // Group methods by similarity
            $methodGroups = $this->groupSimilarMethods($duplicateMethods);
            
            foreach ($methodGroups as $group) {
                if (count($group) < 2) {
                    continue; // Skip if not actually duplicated
                }
                
                // Choose the best implementation as the canonical one
                $canonicalMethod = $this->selectCanonicalMethod($group);
                
                // Remove duplicates and update references
                foreach ($group as $method) {
                    if ($method !== $canonicalMethod) {
                        $this->removeDuplicateMethod($method, $canonicalMethod, $result);
                    }
                }
                
                $result->addRefactoring(
                    'method_consolidation',
                    "Consolidated " . count($group) . " duplicate methods into canonical implementation",
                    [
                        'canonical_method' => $canonicalMethod['signature'],
                        'duplicates_removed' => count($group) - 1
                    ]
                );
                
                $result->duplicatesRemoved += count($group) - 1;
            }
            
        } catch (\Exception $e) {
            $result->addError('Method consolidation failed: ' . $e->getMessage());
        }
        
        return $result;
    }
    
    public function updateReferences(array $referenceUpdates): bool
    {
        try {
            return $this->fileModificationService->updateMethodReferences($referenceUpdates);
        } catch (\Exception $e) {
            Log::error('Failed to update references during refactoring', [
                'error' => $e->getMessage(),
                'updates' => count($referenceUpdates)
            ]);
            return false;
        }
    }  
  
    public function generateComponentSuggestions(array $duplicateBlocks): array
    {
        $suggestions = [];
        
        foreach ($duplicateBlocks as $block) {
            if (!isset($block['pattern']) || !isset($block['locations'])) {
                continue;
            }
            
            $suggestion = new ComponentExtractionSuggestion(
                'extracted-component-' . uniqid(),
                $block['locations'],
                count($block['locations']),
                ['content' => $block['pattern']],
                50,
                []
            );
            
            $suggestions[] = $suggestion;
        }
        
        return $suggestions;
    }
    
    public function generateMethodSuggestions(array $duplicateMethods): array
    {
        $suggestions = [];
        
        foreach ($duplicateMethods as $methodGroup) {
            if (!isset($methodGroup['methods']) || count($methodGroup['methods']) < 2) {
                continue;
            }
            
            $primaryMethod = $methodGroup['methods'][0];
            $duplicateLocations = array_slice($methodGroup['methods'], 1);
            
            $suggestion = new MethodExtractionSuggestion(
                $primaryMethod['file'],
                $primaryMethod['name'],
                $primaryMethod['code'],
                $duplicateLocations
            );
            
            $suggestions[] = $suggestion;
        }
        
        return $suggestions;
    }
    
    public function validateRefactoring(RefactoringPlan $plan): array
    {
        $errors = [];
        
        // Validate component extractions
        foreach ($plan->componentExtractions as $suggestion) {
            if (!($suggestion instanceof ComponentExtractionSuggestion)) {
                $errors[] = 'Invalid component extraction suggestion object';
                continue;
            }
            
            // Check if affected files exist
            foreach ($suggestion->getAffectedFiles() as $file) {
                if (!File::exists($file)) {
                    $errors[] = "Source file does not exist: {$file}";
                }
            }
            
            $targetDir = dirname($suggestion->generateComponentPath());
            if (!File::isDirectory($targetDir) && !File::makeDirectory($targetDir, 0755, true)) {
                $errors[] = "Cannot create target directory: {$targetDir}";
            }
        }
        
        // Validate method extractions
        foreach ($plan->methodExtractions as $suggestion) {
            if (!($suggestion instanceof MethodExtractionSuggestion)) {
                $errors[] = 'Invalid method extraction suggestion object';
                continue;
            }
            
            if (!File::exists($suggestion->sourceFile)) {
                $errors[] = "Source file does not exist: {$suggestion->sourceFile}";
            }
            
            $targetDir = dirname($suggestion->suggestedFilePath);
            if (!File::isDirectory($targetDir) && !File::makeDirectory($targetDir, 0755, true)) {
                $errors[] = "Cannot create target directory: {$targetDir}";
            }
        }
        
        // Validate reference updates
        foreach ($plan->referenceUpdates as $update) {
            if (!($update instanceof ReferenceUpdate)) {
                $errors[] = 'Invalid reference update object';
                continue;
            }
            
            if (!File::exists($update->filePath)) {
                $errors[] = "Reference update target file does not exist: {$update->filePath}";
            }
        }
        
        return $errors;
    }
    
    private function createBackupsForPlan(RefactoringPlan $plan, RefactoringResult $result): void
    {
        $filesToBackup = [];
        
        // Collect all files that will be modified
        foreach ($plan->componentExtractions as $suggestion) {
            $filesToBackup = array_merge($filesToBackup, $suggestion->getAffectedFiles());
        }
        
        foreach ($plan->methodExtractions as $suggestion) {
            $filesToBackup[] = $suggestion->sourceFile;
            foreach ($suggestion->duplicateLocations as $location) {
                if (isset($location['file'])) {
                    $filesToBackup[] = $location['file'];
                }
            }
        }
        
        foreach ($plan->referenceUpdates as $update) {
            $filesToBackup[] = $update->filePath;
        }
        
        // Create backups for unique files
        $uniqueFiles = array_unique($filesToBackup);
        foreach ($uniqueFiles as $filePath) {
            if (File::exists($filePath)) {
                try {
                    $backupPath = $this->fileModificationService->createFileBackup($filePath);
                    $result->addBackup($filePath, $backupPath);
                } catch (\Exception $e) {
                    $result->addError("Failed to create backup for {$filePath}: " . $e->getMessage());
                }
            }
        }
    }
    
    private function mergeResults(RefactoringResult $target, RefactoringResult $source): void
    {
        if (!$source->success) {
            $target->success = false;
        }
        
        $target->refactoringsApplied = array_merge($target->refactoringsApplied, $source->refactoringsApplied);
        $target->errors = array_merge($target->errors, $source->errors);
        $target->filesCreated = array_merge($target->filesCreated, $source->filesCreated);
        $target->filesModified = array_merge($target->filesModified, $source->filesModified);
        $target->duplicatesRemoved += $source->duplicatesRemoved;
        $target->linesReduced += $source->linesReduced;
    }
    
    private function createComponentFile(ComponentExtractionSuggestion $suggestion): bool
    {
        try {
            $componentPath = $suggestion->generateComponentPath();
            $componentContent = $this->generateComponentContent($suggestion);
            
            // Ensure directory exists
            $directory = dirname($componentPath);
            if (!File::isDirectory($directory)) {
                File::makeDirectory($directory, 0755, true);
            }
            
            return File::put($componentPath, $componentContent) !== false;
            
        } catch (\Exception $e) {
            Log::error('Failed to create component file', [
                'path' => $suggestion->generateComponentPath(),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    private function createHelperClassFile(MethodExtractionSuggestion $suggestion): bool
    {
        try {
            $classContent = $this->generateHelperClassContent($suggestion);
            
            // Ensure directory exists
            $directory = dirname($suggestion->suggestedFilePath);
            if (!File::isDirectory($directory)) {
                File::makeDirectory($directory, 0755, true);
            }
            
            return File::put($suggestion->suggestedFilePath, $classContent) !== false;
            
        } catch (\Exception $e) {
            Log::error('Failed to create helper class file', [
                'path' => $suggestion->suggestedFilePath,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    private function generateComponentContent(ComponentExtractionSuggestion $suggestion): string
    {
        // Generate Blade component content
        $content = "@props([";
        
        // Add props based on variables found in the pattern
        $htmlPattern = $suggestion->structure['content'] ?? '';
        $variables = $this->extractVariablesFromPattern($htmlPattern);
        foreach ($variables as $variable) {
            $content .= "'{$variable}' => null,\n    ";
        }
        
        $content = rtrim($content, ",\n    ") . "\n])\n\n";
        $content .= $htmlPattern;
        
        return $content;
    }
    
    private function generateHelperClassContent(MethodExtractionSuggestion $suggestion): string
    {
        $namespace = $this->getNamespaceFromPath($suggestion->suggestedFilePath);
        
        $content = "<?php\n\nnamespace {$namespace};\n\n";
        $content .= "class {$suggestion->suggestedClassName}\n{\n";
        $content .= "    {$suggestion->visibility}";
        
        if ($suggestion->isStatic) {
            $content .= " static";
        }
        
        $content .= " function {$suggestion->suggestedMethodName}(";
        
        // Add parameters
        $paramStrings = [];
        foreach ($suggestion->parameters as $param) {
            $paramStrings[] = $param['type'] . ' $' . $param['name'];
        }
        $content .= implode(', ', $paramStrings);
        
        $content .= ")";
        
        if ($suggestion->returnType !== 'mixed') {
            $content .= ": {$suggestion->returnType}";
        }
        
        $content .= "\n    {\n";
        $content .= "        " . str_replace("\n", "\n        ", trim($suggestion->methodCode));
        $content .= "\n    }\n}\n";
        
        return $content;
    }   
 
    private function replaceWithComponentUsage(ComponentExtractionSuggestion $suggestion, RefactoringResult $result): void
    {
        // Replace duplicate HTML blocks with component usage
        foreach ($suggestion->occurrences as $occurrence) {
            if (!isset($occurrence['file'])) {
                continue;
            }
            
            try {
                $content = File::get($occurrence['file']);
                
                // Generate component usage
                $componentUsage = $suggestion->generateComponentUsage();
                
                // Replace the duplicate block
                if (isset($occurrence['content'])) {
                    $content = str_replace($occurrence['content'], $componentUsage, $content);
                    
                    if (File::put($occurrence['file'], $content) !== false) {
                        $result->addFileModified($occurrence['file']);
                    }
                }
                
            } catch (\Exception $e) {
                $result->addError("Failed to update component usage in {$occurrence['file']}: " . $e->getMessage());
            }
        }
    }
    
    private function replaceWithMethodCalls(MethodExtractionSuggestion $suggestion, RefactoringResult $result): void
    {
        // Replace duplicate methods with calls to the helper class
        foreach ($suggestion->duplicateLocations as $location) {
            if (!isset($location['file'])) {
                continue;
            }
            
            try {
                $content = File::get($location['file']);
                
                // Generate method call
                $methodCall = $this->generateMethodCall($suggestion, $location);
                
                // Replace the duplicate method
                if (isset($location['code'])) {
                    $content = str_replace($location['code'], $methodCall, $content);
                    
                    if (File::put($location['file'], $content) !== false) {
                        $result->addFileModified($location['file']);
                    }
                }
                
            } catch (\Exception $e) {
                $result->addError("Failed to update method call in {$location['file']}: " . $e->getMessage());
            }
        }
    }
    
    private function groupSimilarMethods(array $duplicateMethods): array
    {
        $groups = [];
        
        foreach ($duplicateMethods as $method) {
            $signature = $this->getMethodSignature($method);
            
            if (!isset($groups[$signature])) {
                $groups[$signature] = [];
            }
            
            $groups[$signature][] = $method;
        }
        
        return array_values($groups);
    }
    
    private function selectCanonicalMethod(array $methodGroup): array
    {
        // Select the method with the most comprehensive implementation
        $bestMethod = $methodGroup[0];
        $bestScore = $this->scoreMethodImplementation($bestMethod);
        
        foreach (array_slice($methodGroup, 1) as $method) {
            $score = $this->scoreMethodImplementation($method);
            if ($score > $bestScore) {
                $bestMethod = $method;
                $bestScore = $score;
            }
        }
        
        return $bestMethod;
    }
    
    private function removeDuplicateMethod(array $method, array $canonicalMethod, RefactoringResult $result): void
    {
        try {
            if (!isset($method['file']) || !isset($method['name'])) {
                return;
            }
            
            // Remove the duplicate method and replace with a call to canonical
            $referenceUpdate = new ReferenceUpdate(
                $method['file'],
                $method['name'],
                $this->generateCanonicalMethodCall($canonicalMethod),
                $method['line'] ?? 0
            );
            
            if ($this->fileModificationService->updateMethodReferences([$referenceUpdate])) {
                $result->addFileModified($method['file']);
            }
            
        } catch (\Exception $e) {
            $result->addError("Failed to remove duplicate method in {$method['file']}: " . $e->getMessage());
        }
    }
    
    private function extractVariablesFromPattern(string $pattern): array
    {
        $variables = [];
        
        // Extract Blade variables like {{ $variable }}
        if (preg_match_all('/\{\{\s*\$(\w+)/', $pattern, $matches)) {
            $variables = array_merge($variables, $matches[1]);
        }
        
        // Extract Blade directives with variables
        if (preg_match_all('/@\w+\([^)]*\$(\w+)/', $pattern, $matches)) {
            $variables = array_merge($variables, $matches[1]);
        }
        
        return array_unique($variables);
    }
    
    private function getNamespaceFromPath(string $filePath): string
    {
        // Convert file path to namespace
        $relativePath = str_replace(app_path(), '', $filePath);
        $relativePath = str_replace(['/', '\\', '.php'], ['\\', '\\', ''], $relativePath);
        $relativePath = trim($relativePath, '\\');
        
        return 'App\\' . $relativePath;
    }
    
    private function generateComponentUsage(ComponentExtractionSuggestion $suggestion, array $location): string
    {
        $componentName = kebab_case($suggestion->componentName);
        $usage = "<x-{$componentName}";
        
        // Add props based on variables in the location
        if (isset($location['variables'])) {
            foreach ($location['variables'] as $variable => $value) {
                $usage .= " :{$variable}=\"{$value}\"";
            }
        }
        
        $usage .= " />";
        
        return $usage;
    }
    
    private function generateMethodCall(MethodExtractionSuggestion $suggestion, array $location): string
    {
        $className = $suggestion->suggestedClassName;
        $methodName = $suggestion->suggestedMethodName;
        
        if ($suggestion->isStatic) {
            return "{$className}::{$methodName}()";
        } else {
            return "(new {$className}())->{$methodName}()";
        }
    }
    
    private function getMethodSignature(array $method): string
    {
        return ($method['name'] ?? '') . '_' . md5($method['code'] ?? '');
    }
    
    private function scoreMethodImplementation(array $method): int
    {
        $score = 0;
        
        // Score based on code length (more comprehensive)
        $score += strlen($method['code'] ?? '') / 10;
        
        // Score based on documentation
        if (isset($method['docblock']) && !empty($method['docblock'])) {
            $score += 50;
        }
        
        // Score based on error handling
        if (strpos($method['code'] ?? '', 'try') !== false) {
            $score += 30;
        }
        
        return (int) $score;
    }
    
    /**
     * Refactor a single duplicate code block
     */
    public function refactorDuplicate(array $duplicate): bool
    {
        try {
            if (isset($duplicate['type']) && $duplicate['type'] === 'component') {
                $suggestion = new ComponentExtractionSuggestion(
                    $duplicate['name'] ?? 'extracted-component-' . uniqid(),
                    $duplicate['occurrences'] ?? [],
                    count($duplicate['occurrences'] ?? []),
                    $duplicate['structure'] ?? [],
                    $duplicate['estimatedSavings'] ?? 0,
                    $duplicate['variables'] ?? []
                );
                
                $result = $this->extractComponents([$suggestion]);
                return $result->success;
            } else {
                // Handle method duplicates
                $suggestion = $this->generateMethodSuggestions([$duplicate]);
                if (!empty($suggestion)) {
                    $result = $this->extractMethods($suggestion);
                    return $result->success;
                }
            }
            
            return false;
        } catch (\Exception $e) {
            Log::error('Failed to refactor duplicate', [
                'duplicate' => $duplicate,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Create a single component
     */
    public function createComponent(array $component): bool
    {
        try {
            $suggestion = new ComponentExtractionSuggestion(
                $component['name'] ?? 'extracted-component-' . uniqid(),
                $component['occurrences'] ?? [],
                count($component['occurrences'] ?? []),
                $component['structure'] ?? [],
                $component['estimatedSavings'] ?? 0,
                $component['variables'] ?? []
            );
            
            $result = $this->extractComponents([$suggestion]);
            return $result->success;
        } catch (\Exception $e) {
            Log::error('Failed to create component', [
                'component' => $component,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    private function generateCanonicalMethodCall(array $canonicalMethod): string
    {
        $className = $canonicalMethod['class'] ?? 'UnknownClass';
        $methodName = $canonicalMethod['name'] ?? 'unknownMethod';
        
        return "{$className}::{$methodName}()";
    }
}