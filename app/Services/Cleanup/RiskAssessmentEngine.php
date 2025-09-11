<?php

namespace App\Services\Cleanup;

use App\Services\Cleanup\Models\CleanupPlan;
use App\Services\Cleanup\Models\OperationLog;
use App\Services\Cleanup\Models\RiskAssessment;

class RiskAssessmentEngine
{
    /**
     * Assess risks for cleanup operations
     */
    public function assessRisks(CleanupPlan $plan, array $executionResults, OperationLog $operationLog = null): array
    {
        $risks = [];

        // Assess file deletion risks
        $risks = array_merge($risks, $this->assessFileDeletionRisks($plan, $executionResults));

        // Assess code modification risks
        $risks = array_merge($risks, $this->assessCodeModificationRisks($plan, $executionResults));

        // Assess refactoring risks
        $risks = array_merge($risks, $this->assessRefactoringRisks($plan, $executionResults));

        // Assess operational risks
        $risks = array_merge($risks, $this->assessOperationalRisks($executionResults, $operationLog));

        // Assess testing and validation risks
        $risks = array_merge($risks, $this->assessTestingRisks($plan, $executionResults));

        return $risks;
    }

    /**
     * Assess risks related to file deletion
     */
    private function assessFileDeletionRisks(CleanupPlan $plan, array $executionResults): array
    {
        $risks = [];
        $filesRemoved = $executionResults['files_removed'] ?? 0;

        if ($filesRemoved > 20) {
            $risks[] = new RiskAssessment([
                'type' => 'file_deletion',
                'severity' => 'high',
                'title' => 'Large Number of Files Removed',
                'description' => "Removed {$filesRemoved} files, which may impact system functionality if any were dynamically referenced.",
                'potential_impact' => 'System functionality may be affected if removed files were used through dynamic loading or reflection.',
                'mitigation_strategies' => [
                    'Thoroughly test all application features',
                    'Monitor error logs for missing file references',
                    'Keep backup of removed files for 30 days',
                    'Implement gradual rollout if possible',
                ],
                'likelihood' => 'medium',
                'detection_difficulty' => 'high',
            ]);
        } elseif ($filesRemoved > 10) {
            $risks[] = new RiskAssessment([
                'type' => 'file_deletion',
                'severity' => 'medium',
                'title' => 'Moderate File Removal',
                'description' => "Removed {$filesRemoved} files, requiring careful validation.",
                'potential_impact' => 'Some features may be affected if files were used in unexpected ways.',
                'mitigation_strategies' => [
                    'Run comprehensive test suite',
                    'Check for dynamic file loading patterns',
                    'Monitor application logs',
                ],
                'likelihood' => 'low',
                'detection_difficulty' => 'medium',
            ]);
        }

        // Check for critical file types
        if (isset($plan->filesToDelete)) {
            $criticalFiles = $this->identifyCriticalFiles($plan->filesToDelete);
            if (!empty($criticalFiles)) {
                $risks[] = new RiskAssessment([
                    'type' => 'file_deletion',
                    'severity' => 'high',
                    'title' => 'Critical Files Identified for Removal',
                    'description' => 'Some files marked for removal may be critical system components.',
                    'potential_impact' => 'System may fail to function properly if critical files are removed.',
                    'mitigation_strategies' => [
                        'Manual review of all critical files before removal',
                        'Create full system backup',
                        'Test in staging environment first',
                        'Have rollback plan ready',
                    ],
                    'likelihood' => 'high',
                    'detection_difficulty' => 'low',
                    'affected_files' => $criticalFiles,
                ]);
            }
        }

        return $risks;
    }

    /**
     * Assess risks related to code modification
     */
    private function assessCodeModificationRisks(CleanupPlan $plan, array $executionResults): array
    {
        $risks = [];
        $methodsRemoved = $executionResults['methods_removed'] ?? 0;
        $importsRemoved = $executionResults['imports_removed'] ?? 0;

        if ($methodsRemoved > 10) {
            $risks[] = new RiskAssessment([
                'type' => 'code_modification',
                'severity' => 'medium',
                'title' => 'Significant Method Removal',
                'description' => "Removed {$methodsRemoved} methods, which may affect inheritance chains or dynamic calls.",
                'potential_impact' => 'Child classes or dynamic method calls may fail.',
                'mitigation_strategies' => [
                    'Check all inheritance hierarchies',
                    'Search for dynamic method calls (call_user_func, etc.)',
                    'Run static analysis tools',
                    'Test all class instantiations',
                ],
                'likelihood' => 'medium',
                'detection_difficulty' => 'high',
            ]);
        }

        if ($importsRemoved > 50) {
            $risks[] = new RiskAssessment([
                'type' => 'code_modification',
                'severity' => 'low',
                'title' => 'Large Number of Import Removals',
                'description' => "Removed {$importsRemoved} unused imports, which is generally safe but should be validated.",
                'potential_impact' => 'Minimal risk, but may affect IDE autocompletion or future development.',
                'mitigation_strategies' => [
                    'Verify no dynamic class loading depends on removed imports',
                    'Check for string-based class references',
                    'Update IDE configurations if needed',
                ],
                'likelihood' => 'low',
                'detection_difficulty' => 'low',
            ]);
        }

        return $risks;
    }

    /**
     * Assess risks related to refactoring
     */
    private function assessRefactoringRisks(CleanupPlan $plan, array $executionResults): array
    {
        $risks = [];
        $duplicatesRefactored = $executionResults['duplicates_refactored'] ?? 0;
        $componentsCreated = $executionResults['components_created'] ?? 0;

        if ($duplicatesRefactored > 5) {
            $risks[] = new RiskAssessment([
                'type' => 'refactoring',
                'severity' => 'medium',
                'title' => 'Extensive Code Refactoring',
                'description' => "Refactored {$duplicatesRefactored} duplicate code sections, which may introduce subtle behavioral changes.",
                'potential_impact' => 'Refactored code may have slightly different behavior than original duplicates.',
                'mitigation_strategies' => [
                    'Compare behavior of refactored vs original code',
                    'Add unit tests for refactored components',
                    'Perform integration testing',
                    'Monitor for behavioral differences in production',
                ],
                'likelihood' => 'medium',
                'detection_difficulty' => 'high',
            ]);
        }

        if ($componentsCreated > 3) {
            $risks[] = new RiskAssessment([
                'type' => 'refactoring',
                'severity' => 'low',
                'title' => 'New Component Dependencies',
                'description' => "Created {$componentsCreated} new components, introducing new dependencies.",
                'potential_impact' => 'New components may have different performance characteristics or dependencies.',
                'mitigation_strategies' => [
                    'Test component loading and performance',
                    'Verify component isolation',
                    'Check for circular dependencies',
                    'Document new component interfaces',
                ],
                'likelihood' => 'low',
                'detection_difficulty' => 'medium',
            ]);
        }

        return $risks;
    }

    /**
     * Assess operational risks
     */
    private function assessOperationalRisks(array $executionResults, OperationLog $operationLog = null): array
    {
        $risks = [];

        // Check for failed operations
        $failedOperations = $executionResults['failed_operations'] ?? 0;
        if ($failedOperations > 0) {
            $severity = $failedOperations > 5 ? 'high' : 'medium';
            $risks[] = new RiskAssessment([
                'type' => 'operational',
                'severity' => $severity,
                'title' => 'Failed Cleanup Operations',
                'description' => "{$failedOperations} cleanup operations failed during execution.",
                'potential_impact' => 'Incomplete cleanup may leave system in inconsistent state.',
                'mitigation_strategies' => [
                    'Review failed operation logs',
                    'Manually complete failed operations if safe',
                    'Verify system consistency',
                    'Consider rollback if issues persist',
                ],
                'likelihood' => 'high',
                'detection_difficulty' => 'low',
            ]);
        }

        // Check execution time and resource usage
        if ($operationLog) {
            $performanceMetrics = $operationLog->getPerformanceMetrics();
            if ($performanceMetrics['max_time'] > 300) { // 5 minutes
                $risks[] = new RiskAssessment([
                    'type' => 'operational',
                    'severity' => 'medium',
                    'title' => 'Long Operation Execution Time',
                    'description' => 'Some cleanup operations took longer than expected to complete.',
                    'potential_impact' => 'May indicate performance issues or resource constraints.',
                    'mitigation_strategies' => [
                        'Monitor system resources during cleanup',
                        'Consider breaking large operations into smaller chunks',
                        'Optimize cleanup algorithms',
                        'Schedule cleanup during low-traffic periods',
                    ],
                    'likelihood' => 'medium',
                    'detection_difficulty' => 'low',
                ]);
            }
        }

        return $risks;
    }

    /**
     * Assess testing and validation risks
     */
    private function assessTestingRisks(CleanupPlan $plan, array $executionResults): array
    {
        $risks = [];

        // Check if comprehensive testing was performed
        $testsPassed = $executionResults['tests_passed'] ?? null;
        $testsTotal = $executionResults['tests_total'] ?? null;

        if ($testsPassed === null || $testsTotal === null) {
            $risks[] = new RiskAssessment([
                'type' => 'testing',
                'severity' => 'high',
                'title' => 'No Test Validation Performed',
                'description' => 'Cleanup was performed without running comprehensive tests.',
                'potential_impact' => 'Unknown impact on system functionality.',
                'mitigation_strategies' => [
                    'Run full test suite immediately',
                    'Perform manual testing of critical features',
                    'Monitor production for issues',
                    'Have rollback plan ready',
                ],
                'likelihood' => 'high',
                'detection_difficulty' => 'medium',
            ]);
        } elseif ($testsPassed < $testsTotal) {
            $failedTests = $testsTotal - $testsPassed;
            $risks[] = new RiskAssessment([
                'type' => 'testing',
                'severity' => 'high',
                'title' => 'Test Failures After Cleanup',
                'description' => "{$failedTests} tests failed after cleanup operations.",
                'potential_impact' => 'Cleanup may have broken existing functionality.',
                'mitigation_strategies' => [
                    'Investigate and fix failing tests',
                    'Consider partial rollback of changes',
                    'Review cleanup operations that may have caused failures',
                    'Do not deploy until all tests pass',
                ],
                'likelihood' => 'high',
                'detection_difficulty' => 'low',
            ]);
        }

        return $risks;
    }

    /**
     * Identify critical files that should not be removed
     */
    private function identifyCriticalFiles(array $filesToDelete): array
    {
        $criticalPatterns = [
            '/config/',
            '/bootstrap/',
            '/public/index.php',
            '/artisan',
            '/.env',
            '/composer.json',
            '/package.json',
        ];

        $criticalFiles = [];
        foreach ($filesToDelete as $file) {
            foreach ($criticalPatterns as $pattern) {
                if (strpos($file, $pattern) !== false) {
                    $criticalFiles[] = $file;
                    break;
                }
            }
        }

        return $criticalFiles;
    }
}