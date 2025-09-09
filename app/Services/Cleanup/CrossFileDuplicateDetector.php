<?php

namespace App\Services\Cleanup;

use App\Services\Cleanup\Contracts\JavaScriptAnalyzerInterface;
use App\Services\Cleanup\Contracts\CssAnalyzerInterface;
use App\Services\Cleanup\Contracts\BladeAnalyzerInterface;
use App\Services\Cleanup\Models\CrossFileDuplicateReport;
use App\Services\Cleanup\Models\ComponentExtractionSuggestion;

class CrossFileDuplicateDetector
{
    public function __construct(
        private JavaScriptAnalyzerInterface $jsAnalyzer,
        private CssAnalyzerInterface $cssAnalyzer,
        private BladeAnalyzerInterface $bladeAnalyzer
    ) {}

    /**
     * Find duplicates across all file types
     */
    public function findAllDuplicates(array $jsAnalyses, array $cssAnalyses, array $bladeAnalyses): CrossFileDuplicateReport
    {
        $jsDuplicates = $this->findJavaScriptDuplicates($jsAnalyses);
        $cssDuplicates = $this->findCssDuplicates($cssAnalyses);
        $bladeDuplicates = $this->findBladeDuplicates($bladeAnalyses);
        $componentSuggestions = $this->generateComponentSuggestions($bladeAnalyses);

        return new CrossFileDuplicateReport(
            jsDuplicates: $jsDuplicates,
            cssDuplicates: $cssDuplicates,
            bladeDuplicates: $bladeDuplicates,
            componentSuggestions: $componentSuggestions,
            summary: $this->generateSummary($jsDuplicates, $cssDuplicates, $bladeDuplicates, $componentSuggestions)
        );
    }

    /**
     * Find duplicate JavaScript functions across files
     */
    public function findJavaScriptDuplicates(array $analyses): array
    {
        $duplicates = $this->jsAnalyzer->findDuplicateFunctions($analyses);
        
        return array_map(function ($duplicate) {
            return $this->enhanceJsDuplicate($duplicate);
        }, $duplicates);
    }

    /**
     * Find duplicate CSS rules across stylesheets
     */
    public function findCssDuplicates(array $analyses): array
    {
        $duplicates = $this->cssAnalyzer->findDuplicateRules($analyses);
        
        return array_map(function ($duplicate) {
            return $this->enhanceCssDuplicate($duplicate);
        }, $duplicates);
    }

    /**
     * Find duplicate Blade template structures
     */
    public function findBladeDuplicates(array $analyses): array
    {
        $duplicates = $this->bladeAnalyzer->findDuplicateStructures($analyses);
        
        return array_map(function ($duplicate) {
            return $this->enhanceBladeDuplicate($duplicate);
        }, $duplicates);
    }

    /**
     * Generate component extraction suggestions
     */
    public function generateComponentSuggestions(array $bladeAnalyses): array
    {
        $candidates = $this->bladeAnalyzer->extractComponentCandidates($bladeAnalyses);
        
        return array_map(function ($candidate) {
            return new ComponentExtractionSuggestion(
                suggestedName: $candidate['suggested_name'],
                occurrences: $candidate['occurrences'],
                potentialSavings: $candidate['potential_savings'],
                structure: $candidate['structure'],
                priority: $this->calculateComponentPriority($candidate),
                refactoringSteps: $this->generateComponentRefactoringSteps($candidate)
            );
        }, $candidates);
    }

    /**
     * Enhance JavaScript duplicate with additional analysis
     */
    private function enhanceJsDuplicate(array $duplicate): array
    {
        $occurrences = $duplicate['occurrences'];
        $signature = $duplicate['signature'];
        
        // Calculate complexity and refactoring effort
        $complexity = $this->calculateJsFunctionComplexity($occurrences[0]['function']);
        $effort = $this->estimateJsRefactoringEffort($occurrences, $complexity);
        $benefits = $this->calculateJsRefactoringBenefits($occurrences);
        
        return [
            'type' => 'javascript_function',
            'signature' => $signature,
            'occurrences' => $occurrences,
            'complexity' => $complexity,
            'effort' => $effort,
            'benefits' => $benefits,
            'suggestion' => $this->generateJsRefactoringSuggestion($occurrences, $signature),
            'priority' => $this->calculateJsPriority($occurrences, $complexity, $benefits)
        ];
    }

    /**
     * Enhance CSS duplicate with additional analysis
     */
    private function enhanceCssDuplicate(array $duplicate): array
    {
        $occurrences = $duplicate['occurrences'];
        $signature = $duplicate['signature'];
        
        // Calculate specificity and refactoring potential
        $specificity = $this->calculateCssSpecificity($occurrences[0]['rule']);
        $effort = $this->estimateCssRefactoringEffort($occurrences);
        $benefits = $this->calculateCssRefactoringBenefits($occurrences);
        
        return [
            'type' => 'css_rule',
            'signature' => $signature,
            'occurrences' => $occurrences,
            'specificity' => $specificity,
            'effort' => $effort,
            'benefits' => $benefits,
            'suggestion' => $this->generateCssRefactoringSuggestion($occurrences),
            'priority' => $this->calculateCssPriority($occurrences, $benefits)
        ];
    }

    /**
     * Enhance Blade duplicate with additional analysis
     */
    private function enhanceBladeDuplicate(array $duplicate): array
    {
        $occurrences = $duplicate['occurrences'];
        $similarityScore = $duplicate['similarity_score'];
        $complexityScore = $duplicate['complexity_score'];
        
        $effort = $this->estimateBladeRefactoringEffort($duplicate);
        $benefits = $this->calculateBladeRefactoringBenefits($occurrences);
        
        return [
            'type' => 'blade_structure',
            'hash' => $duplicate['hash'],
            'occurrences' => $occurrences,
            'similarity_score' => $similarityScore,
            'complexity_score' => $complexityScore,
            'effort' => $effort,
            'benefits' => $benefits,
            'suggestion' => $this->generateBladeRefactoringSuggestion($duplicate),
            'priority' => $duplicate['refactoring_priority'] ?? 0
        ];
    }

    /**
     * Calculate JavaScript function complexity
     */
    private function calculateJsFunctionComplexity(array $function): int
    {
        $body = $function['body'] ?? '';
        
        // Count complexity indicators
        $complexity = 1; // Base complexity
        
        // Control structures add complexity
        $complexity += substr_count($body, 'if ');
        $complexity += substr_count($body, 'else');
        $complexity += substr_count($body, 'for ');
        $complexity += substr_count($body, 'while ');
        $complexity += substr_count($body, 'switch ');
        $complexity += substr_count($body, 'case ');
        $complexity += substr_count($body, 'catch ');
        $complexity += substr_count($body, '&&');
        $complexity += substr_count($body, '||');
        $complexity += substr_count($body, '?');
        
        return $complexity;
    }

    /**
     * Estimate JavaScript refactoring effort
     */
    private function estimateJsRefactoringEffort(array $occurrences, int $complexity): string
    {
        $occurrenceCount = count($occurrences);
        
        if ($complexity <= 3 && $occurrenceCount <= 3) {
            return 'low';
        } elseif ($complexity <= 7 && $occurrenceCount <= 5) {
            return 'medium';
        } else {
            return 'high';
        }
    }

    /**
     * Calculate JavaScript refactoring benefits
     */
    private function calculateJsRefactoringBenefits(array $occurrences): array
    {
        $totalLines = 0;
        $files = [];
        
        foreach ($occurrences as $occurrence) {
            $function = $occurrence['function'];
            $body = $function['body'] ?? '';
            $totalLines += substr_count($body, "\n") + 1;
            $files[] = $occurrence['file'];
        }
        
        $duplicateCount = count($occurrences) - 1; // Subtract original
        $linesSaved = $totalLines * $duplicateCount / count($occurrences);
        
        return [
            'lines_saved' => (int) $linesSaved,
            'files_affected' => count(array_unique($files)),
            'maintainability' => 'improved',
            'bundle_size_reduction' => $linesSaved * 50 // Rough estimate in bytes
        ];
    }

    /**
     * Generate JavaScript refactoring suggestion
     */
    private function generateJsRefactoringSuggestion(array $occurrences, string $signature): string
    {
        $fileCount = count(array_unique(array_column($occurrences, 'file')));
        $functionName = explode('(', $signature)[0];
        
        if ($fileCount > 1) {
            return "Extract function '{$functionName}' into a shared utility module. Found in {$fileCount} files with identical implementation.";
        } else {
            return "Consolidate duplicate '{$functionName}' functions within the same file into a single implementation.";
        }
    }

    /**
     * Calculate JavaScript priority score
     */
    private function calculateJsPriority(array $occurrences, int $complexity, array $benefits): int
    {
        $occurrenceScore = count($occurrences) * 10;
        $complexityScore = $complexity * 5;
        $benefitScore = $benefits['lines_saved'] * 2;
        
        return $occurrenceScore + $complexityScore + $benefitScore;
    }

    /**
     * Calculate CSS specificity score
     */
    private function calculateCssSpecificity(array $rule): int
    {
        $selector = $rule['selector'] ?? '';
        
        // Simple specificity calculation
        $specificity = 0;
        $specificity += substr_count($selector, '#') * 100; // IDs
        $specificity += substr_count($selector, '.') * 10;  // Classes
        $specificity += substr_count($selector, ' ') * 1;   // Elements
        
        return $specificity;
    }

    /**
     * Estimate CSS refactoring effort
     */
    private function estimateCssRefactoringEffort(array $occurrences): string
    {
        $occurrenceCount = count($occurrences);
        $hasComplexSelectors = false;
        
        foreach ($occurrences as $occurrence) {
            $selector = $occurrence['rule']['selector'] ?? '';
            if (strpos($selector, ':') !== false || strpos($selector, '>') !== false) {
                $hasComplexSelectors = true;
                break;
            }
        }
        
        if ($occurrenceCount <= 3 && !$hasComplexSelectors) {
            return 'low';
        } elseif ($occurrenceCount <= 5) {
            return 'medium';
        } else {
            return 'high';
        }
    }

    /**
     * Calculate CSS refactoring benefits
     */
    private function calculateCssRefactoringBenefits(array $occurrences): array
    {
        $totalRules = count($occurrences);
        $files = array_unique(array_column($occurrences, 'file'));
        
        // Estimate CSS size reduction
        $avgRuleSize = 100; // Average bytes per CSS rule
        $sizeReduction = ($totalRules - 1) * $avgRuleSize;
        
        return [
            'rules_consolidated' => $totalRules - 1,
            'files_affected' => count($files),
            'css_size_reduction' => $sizeReduction,
            'maintainability' => 'improved'
        ];
    }

    /**
     * Generate CSS refactoring suggestion
     */
    private function generateCssRefactoringSuggestion(array $occurrences): string
    {
        $ruleCount = count($occurrences);
        $files = array_unique(array_column($occurrences, 'file'));
        $fileCount = count($files);
        
        if ($fileCount > 1) {
            return "Consolidate {$ruleCount} duplicate CSS rules across {$fileCount} files. Consider creating a shared CSS class or moving to a common stylesheet.";
        } else {
            return "Merge {$ruleCount} duplicate CSS rules within the same file. Consider combining selectors or creating a more specific class.";
        }
    }

    /**
     * Calculate CSS priority score
     */
    private function calculateCssPriority(array $occurrences, array $benefits): int
    {
        $occurrenceScore = count($occurrences) * 8;
        $sizeScore = ($benefits['css_size_reduction'] ?? 0) / 10;
        $fileScore = ($benefits['files_affected'] ?? 1) * 5;
        
        return (int) ($occurrenceScore + $sizeScore + $fileScore);
    }

    /**
     * Estimate Blade refactoring effort
     */
    private function estimateBladeRefactoringEffort(array $duplicate): string
    {
        $occurrenceCount = count($duplicate['occurrences']);
        $complexityScore = $duplicate['complexity_score'] ?? 0;
        $similarityScore = $duplicate['similarity_score'] ?? 0;
        
        if ($similarityScore > 0.9 && $complexityScore < 20 && $occurrenceCount <= 3) {
            return 'low';
        } elseif ($similarityScore > 0.7 && $complexityScore < 50 && $occurrenceCount <= 5) {
            return 'medium';
        } else {
            return 'high';
        }
    }

    /**
     * Calculate Blade refactoring benefits
     */
    private function calculateBladeRefactoringBenefits(array $occurrences): array
    {
        $totalSize = 0;
        $files = [];
        
        foreach ($occurrences as $occurrence) {
            $structure = $occurrence['structure'];
            $totalSize += $structure['size'] ?? strlen($structure['content'] ?? '');
            $files[] = $occurrence['file'];
        }
        
        $duplicateCount = count($occurrences) - 1;
        $sizeReduction = $totalSize * $duplicateCount / count($occurrences);
        
        return [
            'template_size_reduction' => (int) $sizeReduction,
            'files_affected' => count(array_unique($files)),
            'maintainability' => 'improved',
            'reusability' => 'increased'
        ];
    }

    /**
     * Generate Blade refactoring suggestion
     */
    private function generateBladeRefactoringSuggestion(array $duplicate): string
    {
        $occurrences = $duplicate['occurrences'];
        $fileCount = count(array_unique(array_column($occurrences, 'file')));
        $similarityScore = $duplicate['similarity_score'] ?? 0;
        
        if ($similarityScore > 0.9) {
            return "Extract nearly identical HTML structures from {$fileCount} templates into a reusable Blade component.";
        } elseif ($similarityScore > 0.7) {
            return "Consider creating a parameterized Blade component for similar HTML structures found in {$fileCount} templates.";
        } else {
            return "Analyze similar HTML patterns in {$fileCount} templates for potential component extraction opportunities.";
        }
    }

    /**
     * Calculate component extraction priority
     */
    private function calculateComponentPriority(array $candidate): int
    {
        $savings = $candidate['potential_savings'] ?? 0;
        $complexity = $this->estimateStructureComplexity($candidate['structure']);
        $occurrenceCount = count($candidate['occurrences'] ?? []);
        
        return ($savings * 10) + ($complexity * 2) + ($occurrenceCount * 5);
    }

    /**
     * Estimate structure complexity for component extraction
     */
    private function estimateStructureComplexity(array $structure): int
    {
        $content = $structure['content'] ?? '';
        
        $complexity = 0;
        $complexity += substr_count($content, '<') * 2; // HTML elements
        $complexity += substr_count($content, 'class=') * 1; // CSS classes
        $complexity += substr_count($content, '{{') * 3; // Blade variables
        $complexity += substr_count($content, '@') * 4; // Blade directives
        
        return $complexity;
    }

    /**
     * Generate component refactoring steps
     */
    private function generateComponentRefactoringSteps(array $candidate): array
    {
        $componentName = $candidate['suggested_name'];
        $occurrenceCount = count($candidate['occurrences'] ?? []);
        
        return [
            "1. Create new Blade component: resources/views/components/{$componentName}.blade.php",
            "2. Extract common HTML structure and identify variable parts",
            "3. Add component parameters for dynamic content",
            "4. Replace {$occurrenceCount} duplicate structures with <x-{$componentName}> tags",
            "5. Test component in all affected templates",
            "6. Update any related CSS or JavaScript if needed"
        ];
    }

    /**
     * Generate summary of all duplicates found
     */
    private function generateSummary(array $jsDuplicates, array $cssDuplicates, array $bladeDuplicates, array $componentSuggestions): array
    {
        $totalDuplicates = count($jsDuplicates) + count($cssDuplicates) + count($bladeDuplicates);
        $totalComponents = count($componentSuggestions);
        
        // Calculate potential savings
        $jsSavings = array_sum(array_column(array_column($jsDuplicates, 'benefits'), 'lines_saved'));
        $cssSavings = array_sum(array_column(array_column($cssDuplicates, 'benefits'), 'css_size_reduction'));
        $bladeSavings = array_sum(array_column(array_column($bladeDuplicates, 'benefits'), 'template_size_reduction'));
        $componentSavings = array_sum(array_column($componentSuggestions, 'potentialSavings'));
        
        return [
            'total_duplicates_found' => $totalDuplicates,
            'javascript_duplicates' => count($jsDuplicates),
            'css_duplicates' => count($cssDuplicates),
            'blade_duplicates' => count($bladeDuplicates),
            'component_suggestions' => $totalComponents,
            'estimated_savings' => [
                'javascript_lines' => $jsSavings,
                'css_bytes' => $cssSavings,
                'template_bytes' => $bladeSavings,
                'component_extractions' => $componentSavings
            ],
            'priority_recommendations' => $this->generatePriorityRecommendations($jsDuplicates, $cssDuplicates, $bladeDuplicates, $componentSuggestions)
        ];
    }

    /**
     * Generate priority recommendations for refactoring
     */
    private function generatePriorityRecommendations(array $jsDuplicates, array $cssDuplicates, array $bladeDuplicates, array $componentSuggestions): array
    {
        $allItems = [];
        
        // Add JS duplicates
        foreach ($jsDuplicates as $duplicate) {
            $allItems[] = [
                'type' => 'javascript',
                'priority' => $duplicate['priority'],
                'description' => $duplicate['suggestion'],
                'effort' => $duplicate['effort']
            ];
        }
        
        // Add CSS duplicates
        foreach ($cssDuplicates as $duplicate) {
            $allItems[] = [
                'type' => 'css',
                'priority' => $duplicate['priority'],
                'description' => $duplicate['suggestion'],
                'effort' => $duplicate['effort']
            ];
        }
        
        // Add Blade duplicates
        foreach ($bladeDuplicates as $duplicate) {
            $allItems[] = [
                'type' => 'blade',
                'priority' => $duplicate['priority'],
                'description' => $duplicate['suggestion'],
                'effort' => $duplicate['effort']
            ];
        }
        
        // Add component suggestions
        foreach ($componentSuggestions as $suggestion) {
            $allItems[] = [
                'type' => 'component',
                'priority' => $suggestion->priority,
                'description' => "Extract component: {$suggestion->suggestedName}",
                'effort' => 'medium' // Components typically require medium effort
            ];
        }
        
        // Sort by priority (highest first)
        usort($allItems, fn($a, $b) => $b['priority'] <=> $a['priority']);
        
        // Return top 10 recommendations
        return array_slice($allItems, 0, 10);
    }
}