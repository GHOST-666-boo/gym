<?php

namespace App\Services\Cleanup;

use App\Services\Cleanup\Contracts\CssAnalyzerInterface;
use App\Services\Cleanup\Models\CssFileAnalysis;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class CssAnalyzer implements CssAnalyzerInterface
{
    public function parseFile(string $filePath): CssFileAnalysis
    {
        if (!File::exists($filePath)) {
            throw new \InvalidArgumentException("CSS file not found: {$filePath}");
        }
        
        try {
            $content = File::get($filePath);
            
            return new CssFileAnalysis($filePath, [
                'classes' => $this->extractClasses($content),
                'ids' => $this->extractIds($content),
                'rules' => $this->extractRules($content),
                'imports' => $this->extractImports($content),
                'mediaQueries' => $this->extractMediaQueries($content)
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to parse CSS file: {$filePath}", [
                'error' => $e->getMessage()
            ]);
            
            // Return empty analysis on failure
            return new CssFileAnalysis($filePath);
        }
    }
    
    public function findUnusedClasses(CssFileAnalysis $analysis, array $htmlFiles = []): array
    {
        $unusedClasses = [];
        
        foreach ($analysis->classes as $class) {
            $className = $class['name'] ?? '';
            
            if (!$this->isClassUsedInHtml($className, $htmlFiles)) {
                $unusedClasses[] = $class;
            }
        }
        
        return $unusedClasses;
    }
    
    public function findUnusedIds(CssFileAnalysis $analysis, array $htmlFiles = []): array
    {
        $unusedIds = [];
        
        foreach ($analysis->ids as $id) {
            $idName = $id['name'] ?? '';
            
            if (!$this->isIdUsedInHtml($idName, $htmlFiles)) {
                $unusedIds[] = $id;
            }
        }
        
        return $unusedIds;
    }
    
    public function findDuplicateRules(array $analyses): array
    {
        $duplicates = [];
        $ruleSignatures = [];
        
        foreach ($analyses as $analysis) {
            if (!$analysis instanceof CssFileAnalysis) {
                continue;
            }
            
            foreach ($analysis->rules as $rule) {
                $signature = $this->generateRuleSignature($rule);
                
                if (!isset($ruleSignatures[$signature])) {
                    $ruleSignatures[$signature] = [];
                }
                
                $ruleSignatures[$signature][] = [
                    'file' => $analysis->filePath,
                    'rule' => $rule
                ];
            }
        }
        
        // Find signatures with multiple occurrences
        foreach ($ruleSignatures as $signature => $occurrences) {
            if (count($occurrences) > 1) {
                $duplicates[] = [
                    'signature' => $signature,
                    'occurrences' => $occurrences
                ];
            }
        }
        
        return $duplicates;
    }
    
    private function extractClasses(string $content): array
    {
        $classes = [];
        
        // Remove comments
        $content = $this->removeComments($content);
        
        // Match class selectors: .class-name
        preg_match_all('/\.([a-zA-Z][\w-]*)/m', $content, $matches, PREG_OFFSET_CAPTURE);
        
        foreach ($matches[1] as $match) {
            $className = $match[0];
            $position = $match[1];
            
            // Skip if it's inside a string or URL
            if ($this->isInsideString($content, $position)) {
                continue;
            }
            
            $classes[] = [
                'name' => $className,
                'line' => $this->getLineNumber($content, $position),
                'position' => $position
            ];
        }
        
        return array_unique($classes, SORT_REGULAR);
    }
    
    private function extractIds(string $content): array
    {
        $ids = [];
        
        // Remove comments
        $content = $this->removeComments($content);
        
        // Match ID selectors: #id-name
        preg_match_all('/#([a-zA-Z][\w-]*)/m', $content, $matches, PREG_OFFSET_CAPTURE);
        
        foreach ($matches[1] as $match) {
            $idName = $match[0];
            $position = $match[1];
            
            // Skip if it's inside a string or URL
            if ($this->isInsideString($content, $position)) {
                continue;
            }
            
            $ids[] = [
                'name' => $idName,
                'line' => $this->getLineNumber($content, $position),
                'position' => $position
            ];
        }
        
        return array_unique($ids, SORT_REGULAR);
    }
    
    private function extractRules(string $content): array
    {
        $rules = [];
        
        // Remove comments
        $content = $this->removeComments($content);
        
        // Match CSS rules: selector { properties }
        preg_match_all('/([^{}]+)\s*\{([^{}]*)\}/m', $content, $matches, PREG_OFFSET_CAPTURE);
        
        foreach ($matches[0] as $index => $match) {
            $fullRule = $match[0];
            $position = $match[1];
            $selector = trim($matches[1][$index][0]);
            $properties = trim($matches[2][$index][0]);
            
            // Skip @-rules and media queries (handled separately)
            if (strpos($selector, '@') === 0) {
                continue;
            }
            
            $rules[] = [
                'selector' => $selector,
                'properties' => $properties,
                'full_rule' => $fullRule,
                'line' => $this->getLineNumber($content, $position),
                'position' => $position
            ];
        }
        
        return $rules;
    }
    
    private function extractImports(string $content): array
    {
        $imports = [];
        
        // Match @import statements with quotes
        preg_match_all('/@import\s+["\']([^"\']+)["\'];?/m', $content, $matches, PREG_OFFSET_CAPTURE);
        
        foreach ($matches[1] as $index => $match) {
            $importPath = $match[0];
            $position = $matches[0][$index][1];
            
            $imports[] = [
                'path' => $importPath,
                'line' => $this->getLineNumber($content, $position),
                'position' => $position
            ];
        }
        
        // Match @import statements with url()
        preg_match_all('/@import\s+url\s*\(\s*["\']?([^"\'()]+)["\']?\s*\);?/m', $content, $urlMatches, PREG_OFFSET_CAPTURE);
        
        foreach ($urlMatches[1] as $index => $match) {
            $importPath = $match[0];
            $position = $urlMatches[0][$index][1];
            
            $imports[] = [
                'path' => $importPath,
                'line' => $this->getLineNumber($content, $position),
                'position' => $position
            ];
        }
        
        return $imports;
    }
    
    private function extractMediaQueries(string $content): array
    {
        $mediaQueries = [];
        
        // Match @media queries
        preg_match_all('/@media\s+([^{]+)\s*\{([^{}]*(?:\{[^{}]*\}[^{}]*)*)\}/m', $content, $matches, PREG_OFFSET_CAPTURE);
        
        foreach ($matches[0] as $index => $match) {
            $fullQuery = $match[0];
            $position = $match[1];
            $condition = trim($matches[1][$index][0]);
            $rules = trim($matches[2][$index][0]);
            
            $mediaQueries[] = [
                'condition' => $condition,
                'rules' => $rules,
                'full_query' => $fullQuery,
                'line' => $this->getLineNumber($content, $position),
                'position' => $position
            ];
        }
        
        return $mediaQueries;
    }
    
    private function isClassUsedInHtml(string $className, array $htmlFiles): bool
    {
        foreach ($htmlFiles as $htmlFile) {
            if (!File::exists($htmlFile)) {
                continue;
            }
            
            $htmlContent = File::get($htmlFile);
            
            // Check for class usage in class attributes
            if (preg_match('/class\s*=\s*["\'][^"\']*\b' . preg_quote($className, '/') . '\b[^"\']*["\']/', $htmlContent)) {
                return true;
            }
            
            // Check for dynamic class usage in JavaScript
            if (preg_match('/["\']' . preg_quote($className, '/') . '["\']/', $htmlContent)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function isIdUsedInHtml(string $idName, array $htmlFiles): bool
    {
        foreach ($htmlFiles as $htmlFile) {
            if (!File::exists($htmlFile)) {
                continue;
            }
            
            $htmlContent = File::get($htmlFile);
            
            // Check for ID usage in id attributes
            if (preg_match('/id\s*=\s*["\']' . preg_quote($idName, '/') . '["\']/', $htmlContent)) {
                return true;
            }
            
            // Check for ID usage in href attributes (anchors)
            if (preg_match('/href\s*=\s*["\']#' . preg_quote($idName, '/') . '["\']/', $htmlContent)) {
                return true;
            }
            
            // Check for dynamic ID usage in JavaScript
            if (preg_match('/["\']' . preg_quote($idName, '/') . '["\']/', $htmlContent)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function generateRuleSignature(array $rule): string
    {
        $properties = $rule['properties'] ?? '';
        
        // Normalize properties by sorting and removing whitespace
        $propertyLines = array_filter(array_map('trim', explode(';', $properties)));
        sort($propertyLines);
        $normalizedProperties = implode(';', $propertyLines);
        
        // For duplicate detection, we only compare properties, not selectors
        // This will find rules with identical properties but different selectors
        return md5($normalizedProperties);
    }
    
    private function removeComments(string $content): string
    {
        // Remove /* */ comments
        return preg_replace('/\/\*.*?\*\//s', '', $content);
    }
    
    private function isInsideString(string $content, int $position): bool
    {
        // Simple check to see if position is inside a quoted string
        $beforePosition = substr($content, 0, $position);
        $singleQuotes = substr_count($beforePosition, "'") - substr_count($beforePosition, "\\'");
        $doubleQuotes = substr_count($beforePosition, '"') - substr_count($beforePosition, '\\"');
        
        return ($singleQuotes % 2 !== 0) || ($doubleQuotes % 2 !== 0);
    }
    
    private function getLineNumber(string $content, int $position): int
    {
        return substr_count(substr($content, 0, $position), "\n") + 1;
    }
}