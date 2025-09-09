<?php

namespace App\Services\Cleanup;

use App\Models\Cleanup\PhpFileAnalysis;
use App\Services\Cleanup\Models\DuplicateMethodMatch;
use App\Services\Cleanup\Models\RefactoringSuggestion;

class DuplicateDetector
{
    private const SIMILARITY_THRESHOLD = 0.8; // 80% similarity threshold
    private const MIN_METHOD_LENGTH = 5; // Minimum lines to consider for duplication

    /**
     * Find duplicate methods across PHP files
     */
    public function findDuplicateMethods(array $analyses): array
    {
        $duplicates = [];
        $methods = $this->extractAllMethods($analyses);
        
        for ($i = 0; $i < count($methods); $i++) {
            for ($j = $i + 1; $j < count($methods); $j++) {
                $method1 = $methods[$i];
                $method2 = $methods[$j];
                
                // Skip if same file and same method
                if ($method1['filePath'] === $method2['filePath'] && 
                    $method1['name'] === $method2['name']) {
                    continue;
                }
                
                $similarity = $this->calculateMethodSimilarity($method1, $method2);
                
                if ($similarity >= self::SIMILARITY_THRESHOLD) {
                    $duplicates[] = new DuplicateMethodMatch(
                        method1: $method1,
                        method2: $method2,
                        similarity: $similarity,
                        suggestion: $this->generateRefactoringSuggestion($method1, $method2, $similarity)
                    );
                }
            }
        }
        
        return $this->groupDuplicates($duplicates);
    }

    /**
     * Extract all methods from analyses with their code content
     */
    private function extractAllMethods(array $analyses): array
    {
        $methods = [];
        
        foreach ($analyses as $analysis) {
            if ($analysis->hasErrors()) {
                continue;
            }
            
            $fileContent = file_get_contents($analysis->filePath);
            $lines = explode("\n", $fileContent);
            
            foreach ($analysis->methods as $method) {
                // Skip very short methods
                if (($method['endLine'] ?? $method['line']) - $method['line'] < self::MIN_METHOD_LENGTH) {
                    continue;
                }
                
                $methodCode = $this->extractMethodCode($lines, $method);
                $normalizedCode = $this->normalizeCode($methodCode);
                
                $methods[] = [
                    'filePath' => $analysis->filePath,
                    'class' => $method['class'] ?? null,
                    'name' => $method['name'],
                    'visibility' => $method['visibility'] ?? 'public',
                    'static' => $method['static'] ?? false,
                    'line' => $method['line'],
                    'endLine' => $method['endLine'] ?? $method['line'],
                    'code' => $methodCode,
                    'normalizedCode' => $normalizedCode,
                    'signature' => $this->generateMethodSignature($method),
                    'parameters' => $method['parameters'] ?? [],
                    'returnType' => $method['returnType'] ?? null,
                ];
            }
        }
        
        return $methods;
    }

    /**
     * Extract method code from file lines
     */
    private function extractMethodCode(array $lines, array $method): string
    {
        $startLine = $method['line'] - 1; // Convert to 0-based index
        $endLine = ($method['endLine'] ?? $method['line']) - 1;
        
        if ($startLine < 0 || $endLine >= count($lines) || $startLine > $endLine) {
            return '';
        }
        
        $methodLines = array_slice($lines, $startLine, $endLine - $startLine + 1);
        return implode("\n", $methodLines);
    }

    /**
     * Normalize code for comparison by removing whitespace, comments, and variable names
     */
    private function normalizeCode(string $code): string
    {
        // Remove comments
        $code = preg_replace('/\/\*.*?\*\//s', '', $code);
        $code = preg_replace('/\/\/.*$/m', '', $code);
        $code = preg_replace('/#.*$/m', '', $code);
        
        // Remove extra whitespace
        $code = preg_replace('/\s+/', ' ', $code);
        
        // Normalize variable names to generic placeholders
        $code = preg_replace('/\$[a-zA-Z_][a-zA-Z0-9_]*/', '$var', $code);
        
        // Normalize string literals
        $code = preg_replace('/"[^"]*"/', '"string"', $code);
        $code = preg_replace("/'[^']*'/", "'string'", $code);
        
        // Normalize numbers
        $code = preg_replace('/\b\d+(\.\d+)?\b/', 'number', $code);
        
        return trim($code);
    }

    /**
     * Calculate similarity between two methods
     */
    private function calculateMethodSimilarity(array $method1, array $method2): float
    {
        // Check signature similarity first
        $signatureSimilarity = $this->calculateSignatureSimilarity($method1, $method2);
        
        // If signatures are very different, methods are likely not duplicates
        if ($signatureSimilarity < 0.3) {
            return 0.0;
        }
        
        // Calculate code similarity using Levenshtein distance
        $codeSimilarity = $this->calculateCodeSimilarity(
            $method1['normalizedCode'], 
            $method2['normalizedCode']
        );
        
        // Weighted average: signature 30%, code 70%
        return ($signatureSimilarity * 0.3) + ($codeSimilarity * 0.7);
    }

    /**
     * Calculate signature similarity between two methods
     */
    private function calculateSignatureSimilarity(array $method1, array $method2): float
    {
        $similarities = [];
        
        // Method name similarity
        $similarities[] = $this->calculateStringSimilarity($method1['name'], $method2['name']);
        
        // Parameter count similarity - be more strict
        $paramCount1 = count($method1['parameters']);
        $paramCount2 = count($method2['parameters']);
        
        // If parameter counts are very different, return low similarity
        if (abs($paramCount1 - $paramCount2) > 1) {
            return 0.0;
        }
        
        $maxParams = max($paramCount1, $paramCount2, 1);
        $similarities[] = 1 - (abs($paramCount1 - $paramCount2) / $maxParams);
        
        // Return type similarity - be strict about this
        $returnType1 = $method1['returnType'] ?? 'void';
        $returnType2 = $method2['returnType'] ?? 'void';
        $returnTypeSimilarity = $returnType1 === $returnType2 ? 1.0 : 0.0;
        
        // If return types are different, heavily penalize
        if ($returnTypeSimilarity === 0.0) {
            return 0.0;
        }
        
        $similarities[] = $returnTypeSimilarity;
        
        // Parameter type similarity
        $paramTypeSimilarity = $this->calculateParameterTypeSimilarity($method1['parameters'], $method2['parameters']);
        $similarities[] = $paramTypeSimilarity;
        
        return array_sum($similarities) / count($similarities);
    }

    /**
     * Calculate parameter type similarity
     */
    private function calculateParameterTypeSimilarity(array $params1, array $params2): float
    {
        $maxCount = max(count($params1), count($params2));
        
        if ($maxCount === 0) {
            return 1.0;
        }
        
        $matches = 0;
        $minCount = min(count($params1), count($params2));
        
        for ($i = 0; $i < $minCount; $i++) {
            $type1 = $params1[$i]['type'] ?? 'mixed';
            $type2 = $params2[$i]['type'] ?? 'mixed';
            
            if ($type1 === $type2) {
                $matches++;
            }
        }
        
        // Penalize for different parameter counts
        $penalty = abs(count($params1) - count($params2)) / $maxCount;
        
        return ($matches / $maxCount) - $penalty;
    }

    /**
     * Calculate code similarity using normalized Levenshtein distance
     */
    private function calculateCodeSimilarity(string $code1, string $code2): float
    {
        if (empty($code1) && empty($code2)) {
            return 1.0;
        }
        
        if (empty($code1) || empty($code2)) {
            return 0.0;
        }
        
        $maxLength = max(strlen($code1), strlen($code2));
        $distance = levenshtein($code1, $code2);
        
        return 1 - ($distance / $maxLength);
    }

    /**
     * Calculate string similarity using similar_text
     */
    private function calculateStringSimilarity(string $str1, string $str2): float
    {
        if (empty($str1) && empty($str2)) {
            return 1.0;
        }
        
        if (empty($str1) || empty($str2)) {
            return 0.0;
        }
        
        similar_text($str1, $str2, $percent);
        return $percent / 100;
    }

    /**
     * Generate method signature for comparison
     */
    private function generateMethodSignature(array $method): string
    {
        $signature = ($method['visibility'] ?? 'public') . ' ';
        
        if ($method['static'] ?? false) {
            $signature .= 'static ';
        }
        
        $signature .= 'function ' . $method['name'] . '(';
        
        $params = [];
        foreach ($method['parameters'] ?? [] as $param) {
            $paramStr = '';
            if (isset($param['type'])) {
                $paramStr .= $param['type'] . ' ';
            }
            $paramStr .= '$' . $param['name'];
            if (isset($param['default'])) {
                $paramStr .= ' = ' . $param['default'];
            }
            $params[] = $paramStr;
        }
        
        $signature .= implode(', ', $params) . ')';
        
        if (isset($method['returnType'])) {
            $signature .= ': ' . $method['returnType'];
        }
        
        return $signature;
    }

    /**
     * Generate refactoring suggestion for duplicate methods
     */
    private function generateRefactoringSuggestion(array $method1, array $method2, float $similarity): RefactoringSuggestion
    {
        $type = $this->determineSuggestionType($method1, $method2, $similarity);
        $description = $this->generateSuggestionDescription($method1, $method2, $type);
        
        return new RefactoringSuggestion(
            type: $type,
            description: $description,
            methods: [$method1, $method2],
            similarity: $similarity,
            effort: $this->estimateRefactoringEffort($type, $similarity),
            benefits: $this->calculateRefactoringBenefits($method1, $method2)
        );
    }

    /**
     * Determine the type of refactoring suggestion
     */
    private function determineSuggestionType(array $method1, array $method2, float $similarity): string
    {
        if ($similarity >= 0.95) {
            return 'exact_duplicate';
        } elseif ($similarity >= 0.85) {
            return 'near_duplicate';
        } else {
            return 'similar_logic';
        }
    }

    /**
     * Generate human-readable suggestion description
     */
    private function generateSuggestionDescription(array $method1, array $method2, string $type): string
    {
        $class1 = $method1['class'] ? $method1['class'] . '::' : '';
        $class2 = $method2['class'] ? $method2['class'] . '::' : '';
        
        switch ($type) {
            case 'exact_duplicate':
                return "Methods {$class1}{$method1['name']} and {$class2}{$method2['name']} are nearly identical. Consider extracting common logic into a shared method or trait.";
            
            case 'near_duplicate':
                return "Methods {$class1}{$method1['name']} and {$class2}{$method2['name']} have very similar logic. Consider refactoring to use a common base method with parameters for differences.";
            
            case 'similar_logic':
                return "Methods {$class1}{$method1['name']} and {$class2}{$method2['name']} share similar logic patterns. Consider extracting common functionality into helper methods.";
            
            default:
                return "Methods {$class1}{$method1['name']} and {$class2}{$method2['name']} may benefit from refactoring to reduce duplication.";
        }
    }

    /**
     * Estimate refactoring effort
     */
    private function estimateRefactoringEffort(string $type, float $similarity): string
    {
        switch ($type) {
            case 'exact_duplicate':
                return 'low';
            case 'near_duplicate':
                return $similarity >= 0.9 ? 'low' : 'medium';
            case 'similar_logic':
                return 'medium';
            default:
                return 'high';
        }
    }

    /**
     * Calculate refactoring benefits
     */
    private function calculateRefactoringBenefits(array $method1, array $method2): array
    {
        $method1Lines = $method1['endLine'] - $method1['line'] + 1;
        $method2Lines = $method2['endLine'] - $method2['line'] + 1;
        
        return [
            'lines_saved' => min($method1Lines, $method2Lines),
            'maintainability' => 'improved',
            'consistency' => 'improved',
            'test_coverage' => 'consolidated'
        ];
    }

    /**
     * Group similar duplicates together
     */
    private function groupDuplicates(array $duplicates): array
    {
        $groups = [];
        $processed = [];
        
        foreach ($duplicates as $duplicate) {
            $key = $this->generateDuplicateKey($duplicate);
            
            if (!in_array($key, $processed)) {
                $groups[] = $duplicate;
                $processed[] = $key;
            }
        }
        
        return $groups;
    }

    /**
     * Generate unique key for duplicate to avoid showing same pair multiple times
     */
    private function generateDuplicateKey(DuplicateMethodMatch $duplicate): string
    {
        $method1 = $duplicate->method1;
        $method2 = $duplicate->method2;
        
        $key1 = $method1['filePath'] . ':' . $method1['line'];
        $key2 = $method2['filePath'] . ':' . $method2['line'];
        
        // Sort to ensure consistent key regardless of order
        return $key1 < $key2 ? $key1 . '|' . $key2 : $key2 . '|' . $key1;
    }
}