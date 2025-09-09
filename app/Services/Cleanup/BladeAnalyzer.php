<?php

namespace App\Services\Cleanup;

use App\Services\Cleanup\Contracts\BladeAnalyzerInterface;
use App\Services\Cleanup\Models\BladeTemplateAnalysis;
use Illuminate\Support\Facades\File;

class BladeAnalyzer implements BladeAnalyzerInterface
{
    /**
     * Parse a Blade template file and extract analysis data
     */
    public function parseTemplate(string $filePath): BladeTemplateAnalysis
    {
        if (!File::exists($filePath)) {
            throw new \InvalidArgumentException("File not found: {$filePath}");
        }

        $content = File::get($filePath);
        
        $analysis = new BladeTemplateAnalysis($filePath, [
            'content' => $content,
            'components' => $this->extractComponents($content),
            'variables' => $this->extractVariables($content),
            'includes' => $this->extractIncludes($content),
            'sections' => $this->extractSections($content),
            'htmlStructures' => $this->extractHtmlStructures($content)
        ]);

        return $analysis;
    }

    /**
     * Find unused components across all templates
     */
    public function findUnusedComponents(): array
    {
        $viewsPath = resource_path('views');
        $componentFiles = $this->getAllComponentFiles($viewsPath);
        $usedComponents = $this->getAllUsedComponents($viewsPath);
        
        $unusedComponents = [];
        
        foreach ($componentFiles as $componentFile) {
            $componentName = $this->getComponentNameFromFile($componentFile);
            if (!in_array($componentName, $usedComponents)) {
                $unusedComponents[] = $componentFile;
            }
        }
        
        return $unusedComponents;
    }

    /**
     * Find duplicate HTML structures across templates
     */
    public function findDuplicateStructures(array $analyses): array
    {
        $duplicates = [];
        $structureMap = [];
        
        foreach ($analyses as $analysis) {
            if (!$analysis instanceof BladeTemplateAnalysis) {
                continue;
            }
            
            foreach ($analysis->htmlStructures as $structure) {
                $hash = $this->generateStructureHash($structure);
                
                if (!isset($structureMap[$hash])) {
                    $structureMap[$hash] = [];
                }
                
                $structureMap[$hash][] = [
                    'file' => $analysis->filePath,
                    'structure' => $structure
                ];
            }
        }
        
        // Find structures that appear in multiple files
        foreach ($structureMap as $hash => $occurrences) {
            if (count($occurrences) > 1) {
                $duplicates[] = [
                    'hash' => $hash,
                    'occurrences' => $occurrences,
                    'similarity_score' => $this->calculateSimilarityScore($occurrences),
                    'complexity_score' => $this->calculateComplexityScore($occurrences[0]['structure']),
                    'refactoring_priority' => $this->calculateRefactoringPriority($occurrences)
                ];
            }
        }
        
        // Also find similar (not identical) structures
        $similarDuplicates = $this->findSimilarStructures($analyses);
        $duplicates = array_merge($duplicates, $similarDuplicates);
        
        // Sort by refactoring priority
        usort($duplicates, function($a, $b) {
            return $b['refactoring_priority'] <=> $a['refactoring_priority'];
        });
        
        return $duplicates;
    }

    /**
     * Extract component candidates from duplicate structures
     */
    public function extractComponentCandidates(array $analyses): array
    {
        $duplicates = $this->findDuplicateStructures($analyses);
        $candidates = [];
        
        foreach ($duplicates as $duplicate) {
            if ($duplicate['similarity_score'] > 0.8) { // High similarity threshold
                $candidates[] = [
                    'suggested_name' => $this->suggestComponentName($duplicate),
                    'occurrences' => $duplicate['occurrences'],
                    'potential_savings' => count($duplicate['occurrences']) - 1,
                    'structure' => $duplicate['occurrences'][0]['structure']
                ];
            }
        }
        
        return $candidates;
    }

    /**
     * Extract component usage from Blade content
     */
    private function extractComponents(string $content): array
    {
        $components = [];
        
        // Match <x-component-name> syntax
        preg_match_all('/<x-([a-zA-Z0-9\-_.]+)(?:\s[^>]*)?\/?>/i', $content, $matches);
        if (!empty($matches[1])) {
            $components = array_merge($components, $matches[1]);
        }
        
        // Match @component() directives
        preg_match_all('/@component\([\'"]([^\'"]+)[\'"]\)/i', $content, $matches);
        if (!empty($matches[1])) {
            $components = array_merge($components, $matches[1]);
        }
        
        return array_unique($components);
    }

    /**
     * Extract variables from Blade content
     */
    private function extractVariables(string $content): array
    {
        $variables = [];
        
        // Match {{ $variable }} syntax
        preg_match_all('/\{\{\s*\$([a-zA-Z_][a-zA-Z0-9_]*(?:->[a-zA-Z_][a-zA-Z0-9_]*)*(?:\([^)]*\))?(?:\[[^\]]*\])*)\s*\}\}/', $content, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $match) {
                // Extract base variable name (before -> or [ or ()
                $baseVar = preg_replace('/[->(\[].*/', '', $match);
                $variables[] = $baseVar;
            }
        }
        
        // Match {!! $variable !!} syntax
        preg_match_all('/\{!!\s*\$([a-zA-Z_][a-zA-Z0-9_]*(?:->[a-zA-Z_][a-zA-Z0-9_]*)*(?:\([^)]*\))?(?:\[[^\]]*\])*)\s*!!\}/', $content, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $match) {
                $baseVar = preg_replace('/[->(\[].*/', '', $match);
                $variables[] = $baseVar;
            }
        }
        
        // Match @if($variable) and similar directives
        preg_match_all('/@(?:if|unless|isset|empty|foreach|for|while)\s*\(\s*\$([a-zA-Z_][a-zA-Z0-9_]*)/i', $content, $matches);
        if (!empty($matches[1])) {
            $variables = array_merge($variables, $matches[1]);
        }
        
        // Match foreach loop variables (as $item)
        preg_match_all('/@foreach\s*\([^)]*\s+as\s+\$([a-zA-Z_][a-zA-Z0-9_]*)/i', $content, $matches);
        if (!empty($matches[1])) {
            $variables = array_merge($variables, $matches[1]);
        }
        
        return array_unique($variables);
    }

    /**
     * Extract includes and extends from Blade content
     */
    private function extractIncludes(string $content): array
    {
        $includes = [];
        
        // Match @include() directives - handle both with and without parameters
        preg_match_all('/@include\s*\(\s*[\'"]([^\'"]+)[\'"]\s*(?:,\s*[^)]+)?\s*\)/i', $content, $matches);
        if (!empty($matches[1])) {
            $includes = array_merge($includes, $matches[1]);
        }
        
        // Match @extends() directives
        preg_match_all('/@extends\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/i', $content, $matches);
        if (!empty($matches[1])) {
            $includes = array_merge($includes, $matches[1]);
        }
        
        return array_unique($includes);
    }

    /**
     * Extract sections from Blade content
     */
    private function extractSections(string $content): array
    {
        $sections = [];
        
        // Match @section() directives - handle both inline and block sections
        preg_match_all('/@section\s*\(\s*[\'"]([^\'"]+)[\'"]\s*(?:,\s*[^)]+)?\s*\)/i', $content, $matches);
        if (!empty($matches[1])) {
            $sections = array_merge($sections, $matches[1]);
        }
        
        // Match @yield() directives
        preg_match_all('/@yield\s*\(\s*[\'"]([^\'"]+)[\'"]\s*(?:,\s*[^)]+)?\s*\)/i', $content, $matches);
        if (!empty($matches[1])) {
            $sections = array_merge($sections, $matches[1]);
        }
        
        // Match @push() directives
        preg_match_all('/@push\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/i', $content, $matches);
        if (!empty($matches[1])) {
            $sections = array_merge($sections, $matches[1]);
        }
        
        return array_unique($sections);
    }

    /**
     * Extract HTML structures for duplicate detection
     */
    private function extractHtmlStructures(string $content): array
    {
        $structures = [];
        
        // Remove Blade directives and variables for structure analysis
        $cleanContent = preg_replace('/\{\{.*?\}\}/', '{{VAR}}', $content);
        $cleanContent = preg_replace('/\{!!.*?!!\}/', '{!!VAR!!}', $cleanContent);
        $cleanContent = preg_replace('/@[a-zA-Z]+\([^)]*\)/', '@DIRECTIVE', $cleanContent);
        
        // Extract div structures with classes
        preg_match_all('/<div[^>]*class=[\'"]([^\'"]+)[\'"][^>]*>.*?<\/div>/s', $cleanContent, $matches);
        if (!empty($matches[0])) {
            foreach ($matches[0] as $i => $match) {
                $structures[] = [
                    'type' => 'div_structure',
                    'classes' => $matches[1][$i],
                    'content' => $this->normalizeHtmlStructure($match),
                    'size' => strlen($match)
                ];
            }
        }
        
        // Extract section structures
        preg_match_all('/<section[^>]*>.*?<\/section>/s', $cleanContent, $matches);
        if (!empty($matches[0])) {
            foreach ($matches[0] as $match) {
                $structures[] = [
                    'type' => 'section_structure',
                    'content' => $this->normalizeHtmlStructure($match),
                    'size' => strlen($match)
                ];
            }
        }
        
        return $structures;
    }

    /**
     * Get all component files in the views directory
     */
    private function getAllComponentFiles(string $viewsPath): array
    {
        $componentPath = $viewsPath . '/components';
        if (!File::exists($componentPath)) {
            return [];
        }
        
        return File::allFiles($componentPath);
    }

    /**
     * Get all used components across all templates
     */
    private function getAllUsedComponents(string $viewsPath): array
    {
        $usedComponents = [];
        $allFiles = File::allFiles($viewsPath);
        
        foreach ($allFiles as $file) {
            if ($file->getExtension() === 'php') {
                $content = File::get($file->getPathname());
                $components = $this->extractComponents($content);
                $usedComponents = array_merge($usedComponents, $components);
            }
        }
        
        return array_unique($usedComponents);
    }

    /**
     * Get component name from file path
     */
    private function getComponentNameFromFile($file): string
    {
        $relativePath = str_replace(resource_path('views/components/'), '', $file->getPathname());
        $componentName = str_replace(['/', '.blade.php'], ['.', ''], $relativePath);
        
        return $componentName;
    }

    /**
     * Generate a hash for HTML structure comparison
     */
    private function generateStructureHash(array $structure): string
    {
        $normalized = $this->normalizeHtmlStructure($structure['content']);
        return md5($normalized);
    }

    /**
     * Normalize HTML structure for comparison
     */
    private function normalizeHtmlStructure(string $html): string
    {
        // Remove whitespace and normalize structure
        $normalized = preg_replace('/\s+/', ' ', $html);
        $normalized = trim($normalized);
        
        // Remove dynamic content but keep structure
        $normalized = preg_replace('/\{\{.*?\}\}/', '{{VAR}}', $normalized);
        $normalized = preg_replace('/\{!!.*?!!\}/', '{!!VAR!!}', $normalized);
        
        return $normalized;
    }

    /**
     * Calculate similarity score between structure occurrences
     */
    private function calculateSimilarityScore(array $occurrences): float
    {
        if (count($occurrences) < 2) {
            return 0.0;
        }
        
        $baseStructure = $occurrences[0]['structure']['content'];
        $totalSimilarity = 0;
        $comparisons = 0;
        
        for ($i = 1; $i < count($occurrences); $i++) {
            $compareStructure = $occurrences[$i]['structure']['content'];
            $similarity = $this->calculateStringSimilarity($baseStructure, $compareStructure);
            $totalSimilarity += $similarity;
            $comparisons++;
        }
        
        return $comparisons > 0 ? $totalSimilarity / $comparisons : 0.0;
    }

    /**
     * Calculate string similarity between two HTML structures
     */
    private function calculateStringSimilarity(string $str1, string $str2): float
    {
        $normalized1 = $this->normalizeHtmlStructure($str1);
        $normalized2 = $this->normalizeHtmlStructure($str2);
        
        similar_text($normalized1, $normalized2, $percent);
        
        return $percent / 100;
    }

    /**
     * Suggest a component name based on duplicate structure
     */
    private function suggestComponentName(array $duplicate): string
    {
        $structure = $duplicate['occurrences'][0]['structure'];
        
        // Try to extract meaningful name from classes or content
        if (isset($structure['classes'])) {
            $classes = explode(' ', $structure['classes']);
            $meaningfulClass = $this->findMeaningfulClass($classes);
            if ($meaningfulClass) {
                return $this->convertToComponentName($meaningfulClass);
            }
        }
        
        // Fallback to generic name based on structure type
        $type = $structure['type'] ?? 'component';
        return str_replace('_structure', '-component', $type);
    }

    /**
     * Find meaningful class name for component naming
     */
    private function findMeaningfulClass(array $classes): ?string
    {
        $meaningfulPrefixes = ['product', 'card', 'button', 'form', 'nav', 'header', 'footer', 'section'];
        
        foreach ($classes as $class) {
            foreach ($meaningfulPrefixes as $prefix) {
                if (strpos($class, $prefix) === 0) {
                    return $class;
                }
            }
        }
        
        return null;
    }

    /**
     * Convert class name to component name format
     */
    private function convertToComponentName(string $className): string
    {
        // Convert kebab-case or snake_case to component name
        $name = str_replace(['_', '-'], ' ', $className);
        $name = ucwords($name);
        $name = str_replace(' ', '', $name);
        
        // Convert to kebab-case for component naming
        $name = preg_replace('/([a-z])([A-Z])/', '$1-$2', $name);
        $name = strtolower($name);
        
        return $name . '-component';
    }

    /**
     * Find similar (not identical) HTML structures
     */
    private function findSimilarStructures(array $analyses): array
    {
        $similarDuplicates = [];
        $allStructures = [];
        
        // Collect all structures with their source files
        foreach ($analyses as $analysis) {
            if (!$analysis instanceof BladeTemplateAnalysis) {
                continue;
            }
            
            foreach ($analysis->htmlStructures as $structure) {
                $allStructures[] = [
                    'file' => $analysis->filePath,
                    'structure' => $structure
                ];
            }
        }
        
        // Compare each structure with every other structure
        for ($i = 0; $i < count($allStructures); $i++) {
            $similarGroup = [$allStructures[$i]];
            
            for ($j = $i + 1; $j < count($allStructures); $j++) {
                $similarity = $this->calculateStructuralSimilarity(
                    $allStructures[$i]['structure'],
                    $allStructures[$j]['structure']
                );
                
                // If similarity is high but not identical (to avoid duplicating exact matches)
                if ($similarity > 0.7 && $similarity < 0.99) {
                    $similarGroup[] = $allStructures[$j];
                }
            }
            
            // If we found similar structures, add to duplicates
            if (count($similarGroup) > 1) {
                $similarDuplicates[] = [
                    'hash' => 'similar_' . md5(serialize($similarGroup)),
                    'occurrences' => $similarGroup,
                    'similarity_score' => $this->calculateAverageSimilarity($similarGroup),
                    'complexity_score' => $this->calculateComplexityScore($similarGroup[0]['structure']),
                    'refactoring_priority' => $this->calculateRefactoringPriority($similarGroup),
                    'type' => 'similar'
                ];
            }
        }
        
        return $this->deduplicateSimilarGroups($similarDuplicates);
    }

    /**
     * Calculate structural similarity between two HTML structures
     */
    private function calculateStructuralSimilarity(array $structure1, array $structure2): float
    {
        // Compare structure types
        if ($structure1['type'] !== $structure2['type']) {
            return 0.0;
        }
        
        // Normalize both structures
        $normalized1 = $this->normalizeHtmlStructure($structure1['content']);
        $normalized2 = $this->normalizeHtmlStructure($structure2['content']);
        
        // Calculate similarity using multiple metrics
        $textSimilarity = $this->calculateStringSimilarity($normalized1, $normalized2);
        $classSimilarity = $this->calculateClassSimilarity($structure1, $structure2);
        $sizeSimilarity = $this->calculateSizeSimilarity($structure1, $structure2);
        
        // Weighted average of different similarity metrics
        return ($textSimilarity * 0.5) + ($classSimilarity * 0.3) + ($sizeSimilarity * 0.2);
    }

    /**
     * Calculate class similarity between structures
     */
    private function calculateClassSimilarity(array $structure1, array $structure2): float
    {
        $classes1 = $this->extractClassesFromStructure($structure1);
        $classes2 = $this->extractClassesFromStructure($structure2);
        
        if (empty($classes1) && empty($classes2)) {
            return 1.0;
        }
        
        if (empty($classes1) || empty($classes2)) {
            return 0.0;
        }
        
        $intersection = array_intersect($classes1, $classes2);
        $union = array_unique(array_merge($classes1, $classes2));
        
        return count($intersection) / count($union);
    }

    /**
     * Calculate size similarity between structures
     */
    private function calculateSizeSimilarity(array $structure1, array $structure2): float
    {
        $size1 = $structure1['size'] ?? strlen($structure1['content']);
        $size2 = $structure2['size'] ?? strlen($structure2['content']);
        
        $maxSize = max($size1, $size2);
        $minSize = min($size1, $size2);
        
        return $maxSize > 0 ? $minSize / $maxSize : 1.0;
    }

    /**
     * Extract CSS classes from HTML structure
     */
    private function extractClassesFromStructure(array $structure): array
    {
        $classes = [];
        
        if (isset($structure['classes'])) {
            $classes = explode(' ', $structure['classes']);
        } else {
            // Extract classes from content
            preg_match_all('/class=[\'"]([^\'"]+)[\'"]/', $structure['content'], $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $classString) {
                    $classes = array_merge($classes, explode(' ', $classString));
                }
            }
        }
        
        return array_filter(array_unique($classes));
    }

    /**
     * Calculate complexity score for a structure
     */
    private function calculateComplexityScore(array $structure): float
    {
        $content = $structure['content'];
        
        // Count HTML elements
        $elementCount = substr_count($content, '<') - substr_count($content, '</');
        
        // Count CSS classes
        preg_match_all('/class=[\'"]([^\'"]+)[\'"]/', $content, $matches);
        $classCount = 0;
        if (!empty($matches[1])) {
            foreach ($matches[1] as $classString) {
                $classCount += count(explode(' ', $classString));
            }
        }
        
        // Count Blade directives
        $directiveCount = preg_match_all('/@[a-zA-Z]+/', $content);
        
        // Count variables
        $variableCount = preg_match_all('/\{\{.*?\}\}/', $content);
        
        // Calculate complexity score (higher = more complex)
        return ($elementCount * 2) + ($classCount * 1) + ($directiveCount * 3) + ($variableCount * 1);
    }

    /**
     * Calculate refactoring priority based on occurrences and complexity
     */
    private function calculateRefactoringPriority(array $occurrences): float
    {
        $occurrenceCount = count($occurrences);
        $complexity = $this->calculateComplexityScore($occurrences[0]['structure']);
        
        // Priority = (number of duplicates - 1) * complexity
        // Subtract 1 because the first occurrence doesn't need to be replaced
        return ($occurrenceCount - 1) * $complexity;
    }

    /**
     * Calculate average similarity for a group of similar structures
     */
    private function calculateAverageSimilarity(array $similarGroup): float
    {
        if (count($similarGroup) < 2) {
            return 0.0;
        }
        
        $totalSimilarity = 0;
        $comparisons = 0;
        
        for ($i = 0; $i < count($similarGroup); $i++) {
            for ($j = $i + 1; $j < count($similarGroup); $j++) {
                $similarity = $this->calculateStructuralSimilarity(
                    $similarGroup[$i]['structure'],
                    $similarGroup[$j]['structure']
                );
                $totalSimilarity += $similarity;
                $comparisons++;
            }
        }
        
        return $comparisons > 0 ? $totalSimilarity / $comparisons : 0.0;
    }

    /**
     * Remove duplicate similar groups (when structures appear in multiple groups)
     */
    private function deduplicateSimilarGroups(array $similarDuplicates): array
    {
        $deduplicated = [];
        $processedHashes = [];
        
        foreach ($similarDuplicates as $duplicate) {
            // Create a signature for this group based on file paths
            $signature = $this->createGroupSignature($duplicate['occurrences']);
            
            if (!in_array($signature, $processedHashes)) {
                $deduplicated[] = $duplicate;
                $processedHashes[] = $signature;
            }
        }
        
        return $deduplicated;
    }

    /**
     * Create a unique signature for a group of occurrences
     */
    private function createGroupSignature(array $occurrences): string
    {
        $files = array_map(function($occurrence) {
            return $occurrence['file'];
        }, $occurrences);
        
        sort($files);
        return md5(implode('|', $files));
    }}
