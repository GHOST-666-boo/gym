<?php

namespace App\Services\Cleanup;

use App\Services\Cleanup\Contracts\FileModificationServiceInterface;
use App\Services\Cleanup\Models\FileModificationPlan;
use App\Services\Cleanup\Models\FileModificationResult;
use App\Services\Cleanup\Models\ReferenceUpdate;
use PhpParser\Error;
use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class FileModificationService implements FileModificationServiceInterface
{
    private $parser;
    private $printer;
    private $nodeFinder;
    
    public function __construct()
    {
        $this->parser = (new ParserFactory)->createForNewestSupportedVersion();
        $this->printer = new Standard();
        $this->nodeFinder = new NodeFinder();
    }
    
    public function executeModifications(FileModificationPlan $plan): FileModificationResult
    {
        $startTime = microtime(true);
        $result = new FileModificationResult(true, $plan->filePath);
        
        try {
            // Validate the plan first
            $validationErrors = $this->validateModifications($plan);
            if (!empty($validationErrors)) {
                foreach ($validationErrors as $error) {
                    $result->addError($error);
                }
                return $result;
            }
            
            // Create backup if requested
            if ($plan->createBackup) {
                $backupPath = $this->createFileBackup($plan->filePath);
                $result->backupPath = $backupPath;
            }
            
            $originalSize = File::size($plan->filePath);
            
            // Execute modifications in order
            if (!empty($plan->importsToRemove)) {
                if (!$this->removeUnusedImports($plan->filePath, $plan->importsToRemove)) {
                    $result->addError('Failed to remove unused imports');
                    return $result;
                }
                $result->addModification('imports', 'Removed ' . count($plan->importsToRemove) . ' unused imports');
            }
            
            if (!empty($plan->variablesToRemove)) {
                if (!$this->removeUnusedVariables($plan->filePath, $plan->variablesToRemove)) {
                    $result->addError('Failed to remove unused variables');
                    return $result;
                }
                $result->addModification('variables', 'Removed ' . count($plan->variablesToRemove) . ' unused variables');
            }
            
            if (!empty($plan->methodsToRemove)) {
                if (!$this->removeUnusedMethods($plan->filePath, $plan->methodsToRemove)) {
                    $result->addError('Failed to remove unused methods');
                    return $result;
                }
                $result->addModification('methods', 'Removed ' . count($plan->methodsToRemove) . ' unused methods');
            }
            
            if (!empty($plan->referenceUpdates)) {
                if (!$this->updateMethodReferences($plan->referenceUpdates)) {
                    $result->addError('Failed to update method references');
                    return $result;
                }
                $result->addModification('references', 'Updated ' . count($plan->referenceUpdates) . ' method references');
            }
            
            // Calculate metrics
            $newSize = File::size($plan->filePath);
            $result->bytesReduced = $originalSize - $newSize;
            $result->executionTime = microtime(true) - $startTime;
            
        } catch (\Exception $e) {
            $result->addError('Unexpected error: ' . $e->getMessage());
            Log::error('File modification failed', [
                'file' => $plan->filePath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        
        return $result;
    }    
 
    public function removeUnusedImports(string $filePath, array $unusedImports): bool
    {
        try {
            $code = File::get($filePath);
            
            // Use simple string replacement for now to avoid NodeTraverser issues
            $lines = explode("\n", $code);
            $modifiedLines = [];
            
            foreach ($lines as $line) {
                $shouldRemove = false;
                
                // Check if this line contains a use statement for an unused import
                if (preg_match('/^\s*use\s+([^;]+);/', $line, $matches)) {
                    $importPart = trim($matches[1]);
                    
                    // Handle multiple imports in one line (use A, B, C;)
                    if (strpos($importPart, ',') !== false) {
                        $imports = array_map('trim', explode(',', $importPart));
                        $remainingImports = [];
                        
                        foreach ($imports as $import) {
                            $cleanImport = trim($import, ' \\');
                            if (!in_array($cleanImport, $unusedImports)) {
                                $remainingImports[] = $import;
                            }
                        }
                        
                        if (empty($remainingImports)) {
                            $shouldRemove = true;
                        } else if (count($remainingImports) < count($imports)) {
                            // Reconstruct the line with remaining imports
                            $line = 'use ' . implode(', ', $remainingImports) . ';';
                        }
                    } else {
                        // Single import
                        $cleanImport = trim($importPart, ' \\');
                        if (in_array($cleanImport, $unusedImports)) {
                            $shouldRemove = true;
                        }
                    }
                }
                
                if (!$shouldRemove) {
                    $modifiedLines[] = $line;
                }
            }
            
            $newCode = implode("\n", $modifiedLines);
            return File::put($filePath, $newCode) !== false;
            
        } catch (\Exception $e) {
            Log::error('Failed to remove unused imports', [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    public function removeUnusedVariables(string $filePath, array $unusedVariables): bool
    {
        try {
            $code = File::get($filePath);
            
            // Use regex-based approach for variable removal
            $lines = explode("\n", $code);
            $modifiedLines = [];
            
            foreach ($lines as $line) {
                $shouldRemove = false;
                
                // Check for variable assignments
                foreach ($unusedVariables as $varName) {
                    // Pattern to match variable assignments like $varName = ...;
                    if (preg_match('/^\s*\$' . preg_quote($varName, '/') . '\s*=/', $line)) {
                        $shouldRemove = true;
                        break;
                    }
                }
                
                if (!$shouldRemove) {
                    $modifiedLines[] = $line;
                }
            }
            
            $newCode = implode("\n", $modifiedLines);
            return File::put($filePath, $newCode) !== false;
            
        } catch (\Exception $e) {
            Log::error('Failed to remove unused variables', [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    public function removeUnusedMethods(string $filePath, array $unusedMethods): bool
    {
        try {
            $code = File::get($filePath);
            
            // Use regex-based approach for method removal
            foreach ($unusedMethods as $methodName) {
                // Pattern to match method declarations (public, private, protected, static, etc.)
                $pattern = '/^\s*(public|private|protected)?\s*(static)?\s*function\s+' . preg_quote($methodName, '/') . '\s*\([^)]*\)\s*\{[^}]*\}/ms';
                
                // More comprehensive pattern to handle nested braces
                $lines = explode("\n", $code);
                $newLines = [];
                $inMethod = false;
                $braceCount = 0;
                $methodStartPattern = '/^\s*(public|private|protected)?\s*(static)?\s*function\s+' . preg_quote($methodName, '/') . '\s*\(/';
                
                foreach ($lines as $line) {
                    if (!$inMethod && preg_match($methodStartPattern, $line)) {
                        $inMethod = true;
                        $braceCount = substr_count($line, '{') - substr_count($line, '}');
                        
                        // If the method is on a single line, skip it entirely
                        if ($braceCount <= 0) {
                            $inMethod = false;
                            continue;
                        }
                        continue;
                    }
                    
                    if ($inMethod) {
                        $braceCount += substr_count($line, '{') - substr_count($line, '}');
                        if ($braceCount <= 0) {
                            $inMethod = false;
                        }
                        continue;
                    }
                    
                    $newLines[] = $line;
                }
                
                $code = implode("\n", $newLines);
            }
            
            return File::put($filePath, $code) !== false;
            
        } catch (\Exception $e) {
            Log::error('Failed to remove unused methods', [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }    

    public function updateMethodReferences(array $referenceUpdates): bool
    {
        try {
            $fileGroups = [];
            
            // Group updates by file
            foreach ($referenceUpdates as $update) {
                if ($update instanceof ReferenceUpdate) {
                    $fileGroups[$update->filePath][] = $update;
                }
            }
            
            // Process each file
            foreach ($fileGroups as $filePath => $updates) {
                if (!File::exists($filePath)) {
                    Log::warning('File not found for reference update', ['file' => $filePath]);
                    continue;
                }
                
                $content = File::get($filePath);
                $originalContent = $content;
                
                // Apply all updates for this file
                foreach ($updates as $update) {
                    $pattern = '/' . preg_quote($update->oldReference, '/') . '/';
                    $content = preg_replace($pattern, $update->newReference, $content);
                }
                
                // Only write if content changed
                if ($content !== $originalContent) {
                    if (File::put($filePath, $content) === false) {
                        Log::error('Failed to write updated references', ['file' => $filePath]);
                        return false;
                    }
                }
            }
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to update method references', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
    
    public function createFileBackup(string $filePath): string
    {
        if (!File::exists($filePath)) {
            throw new \InvalidArgumentException("File does not exist: {$filePath}");
        }
        
        $backupDir = storage_path('app/cleanup-backups');
        if (!File::isDirectory($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }
        
        $timestamp = now()->format('Y-m-d_H-i-s');
        $fileName = basename($filePath);
        $backupPath = $backupDir . '/' . $timestamp . '_' . $fileName;
        
        if (!File::copy($filePath, $backupPath)) {
            throw new \RuntimeException("Failed to create backup for: {$filePath}");
        }
        
        return $backupPath;
    }
    
    public function restoreFromBackup(string $filePath, string $backupPath): bool
    {
        try {
            if (!File::exists($backupPath)) {
                Log::error('Backup file not found', ['backup' => $backupPath]);
                return false;
            }
            
            return File::copy($backupPath, $filePath);
            
        } catch (\Exception $e) {
            Log::error('Failed to restore from backup', [
                'file' => $filePath,
                'backup' => $backupPath,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Remove a single import from a file
     */
    public function removeImport(array $import): bool
    {
        if (!isset($import['file']) || !isset($import['import'])) {
            return false;
        }
        
        return $this->removeUnusedImports($import['file'], [$import['import']]);
    }
    
    /**
     * Remove a single method from a file
     */
    public function removeMethod(array $method): bool
    {
        if (!isset($method['file']) || !isset($method['method'])) {
            return false;
        }
        
        return $this->removeUnusedMethods($method['file'], [$method['method']]);
    }
    
    /**
     * Remove a single variable from a file
     */
    public function removeVariable(array $variable): bool
    {
        if (!isset($variable['file']) || !isset($variable['variable'])) {
            return false;
        }
        
        return $this->removeUnusedVariables($variable['file'], [$variable['variable']]);
    }

    public function validateModifications(FileModificationPlan $plan): array
    {
        $errors = [];
        
        // Check if file exists
        if (!File::exists($plan->filePath)) {
            $errors[] = "File does not exist: {$plan->filePath}";
            return $errors;
        }
        
        // Check if file is writable
        if (!File::isWritable($plan->filePath)) {
            $errors[] = "File is not writable: {$plan->filePath}";
        }
        
        // Check if file is a PHP file for PHP-specific operations
        $isPhpFile = pathinfo($plan->filePath, PATHINFO_EXTENSION) === 'php';
        
        if (!$isPhpFile && (!empty($plan->importsToRemove) || !empty($plan->methodsToRemove))) {
            $errors[] = "Cannot perform PHP-specific operations on non-PHP file: {$plan->filePath}";
        }
        
        // Validate that the file can be parsed if it's PHP
        if ($isPhpFile) {
            try {
                $code = File::get($plan->filePath);
                $ast = $this->parser->parse($code);
                if ($ast === null) {
                    $errors[] = "Failed to parse PHP file: {$plan->filePath}";
                }
            } catch (Error $e) {
                $errors[] = "PHP syntax error in file {$plan->filePath}: " . $e->getMessage();
            }
        }
        
        // Validate reference updates
        foreach ($plan->referenceUpdates as $update) {
            if (!($update instanceof ReferenceUpdate)) {
                $errors[] = "Invalid reference update object";
                continue;
            }
            
            if (!File::exists($update->filePath)) {
                $errors[] = "Reference update target file does not exist: {$update->filePath}";
            }
        }
        
        return $errors;
    }
}