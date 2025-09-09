<?php

namespace App\Services\Cleanup;

use App\Services\Cleanup\Contracts\BackupManagerInterface;
use App\Services\Cleanup\Contracts\SafetyValidationServiceInterface;
use App\Services\Cleanup\Contracts\TestValidatorInterface;
use Illuminate\Support\Facades\Log;

class SafetyValidationService implements SafetyValidationServiceInterface
{
    private array $safetyReport = [];
    private ?string $lastBackupId = null;
    private array $checkpoints = [];

    public function __construct(
        private BackupManagerInterface $backupManager,
        private TestValidatorInterface $testValidator
    ) {}

    public function validateBeforeCleanup(array $cleanupPlan): array
    {
        Log::info('Starting pre-cleanup safety validation');
        
        $validation = [
            'timestamp' => now()->toISOString(),
            'phase' => 'pre_cleanup',
            'backup_created' => false,
            'dynamic_usage_check' => [],
            'test_results' => [],
            'safety_score' => 0,
            'recommendations' => [],
            'safe_to_proceed' => false
        ];

        try {
            // Create backup before validation
            $this->lastBackupId = $this->backupManager->createBackup('Pre-cleanup safety backup');
            $validation['backup_created'] = true;
            $validation['backup_id'] = $this->lastBackupId;
            
            // Check for dynamic usage patterns in code to be removed
            $codeElements = $this->extractCodeElements($cleanupPlan);
            $validation['dynamic_usage_check'] = $this->testValidator->checkDynamicUsage($codeElements);
            
            // Run baseline tests
            $validation['test_results'] = $this->testValidator->runValidationTests();
            
            // Calculate safety score
            $validation['safety_score'] = $this->calculateSafetyScore($validation);
            
            // Generate recommendations
            $validation['recommendations'] = $this->generateRecommendations($validation);
            
            // Determine if safe to proceed
            $validation['safe_to_proceed'] = $this->isSafeToCleanup($cleanupPlan);
            
            $this->safetyReport['pre_cleanup'] = $validation;
            
            Log::info('Pre-cleanup safety validation completed', [
                'safe_to_proceed' => $validation['safe_to_proceed'],
                'safety_score' => $validation['safety_score']
            ]);
            
        } catch (\Exception $e) {
            Log::error('Pre-cleanup safety validation failed: ' . $e->getMessage());
            $validation['error'] = $e->getMessage();
            $validation['safe_to_proceed'] = false;
        }

        return $validation;
    }

    public function validateAfterCleanup(array $executedOperations): array
    {
        Log::info('Starting post-cleanup safety validation');
        
        $validation = [
            'timestamp' => now()->toISOString(),
            'phase' => 'post_cleanup',
            'executed_operations' => count($executedOperations),
            'functionality_verified' => false,
            'test_results' => [],
            'runtime_errors' => [],
            'rollback_required' => false,
            'validation_passed' => false
        ];

        try {
            // Verify functionality after cleanup
            $validation['functionality_verified'] = $this->testValidator->verifyFunctionality($executedOperations);
            
            // Run validation tests
            $validation['test_results'] = $this->testValidator->runValidationTests();
            
            // Check for runtime errors
            $validation['runtime_errors'] = $this->testValidator->checkRuntimeErrors();
            
            // Determine if rollback is required
            $validation['rollback_required'] = $this->shouldRollback($validation);
            
            // Overall validation status
            $validation['validation_passed'] = $validation['functionality_verified'] && 
                                             empty($validation['runtime_errors']) && 
                                             !$validation['rollback_required'];
            
            $this->safetyReport['post_cleanup'] = $validation;
            
            if ($validation['rollback_required']) {
                Log::warning('Post-cleanup validation failed - rollback recommended');
            } else {
                Log::info('Post-cleanup safety validation passed');
            }
            
        } catch (\Exception $e) {
            Log::error('Post-cleanup safety validation failed: ' . $e->getMessage());
            $validation['error'] = $e->getMessage();
            $validation['rollback_required'] = true;
            $validation['validation_passed'] = false;
        }

        return $validation;
    }

    public function createSafetyCheckpoint(string $operation, array $metadata = []): string
    {
        Log::info("Creating safety checkpoint for operation: {$operation}");
        
        try {
            $checkpointId = $this->backupManager->createCheckpoint($operation, $metadata);
            
            $this->checkpoints[] = [
                'id' => $checkpointId,
                'operation' => $operation,
                'metadata' => $metadata,
                'timestamp' => now()->toISOString()
            ];
            
            Log::info("Safety checkpoint created: {$checkpointId}");
            
            return $checkpointId;
        } catch (\Exception $e) {
            Log::error("Failed to create safety checkpoint: " . $e->getMessage());
            throw $e;
        }
    }

    public function rollbackToSafetyCheckpoint(string $checkpointId): bool
    {
        Log::info("Rolling back to safety checkpoint: {$checkpointId}");
        
        try {
            $result = $this->backupManager->rollbackToCheckpoint($checkpointId);
            
            if ($result) {
                // Remove checkpoints created after this one
                $this->cleanupCheckpointsAfter($checkpointId);
                Log::info("Successfully rolled back to checkpoint: {$checkpointId}");
            } else {
                Log::error("Failed to rollback to checkpoint: {$checkpointId}");
            }
            
            return $result;
        } catch (\Exception $e) {
            Log::error("Error during checkpoint rollback: " . $e->getMessage());
            return false;
        }
    }

    public function getSafetyReport(): array
    {
        return [
            'report_generated' => now()->toISOString(),
            'last_backup_id' => $this->lastBackupId,
            'checkpoints_created' => count($this->checkpoints),
            'validations' => $this->safetyReport,
            'summary' => $this->generateSafetyReportSummary()
        ];
    }

    public function isSafeToCleanup(array $cleanupPlan): bool
    {
        try {
            // Extract code elements from cleanup plan
            $codeElements = $this->extractCodeElements($cleanupPlan);
            
            // Check for high-risk dynamic usage
            $dynamicUsages = $this->testValidator->checkDynamicUsage($codeElements);
            $highRiskUsages = array_filter($dynamicUsages, fn($usage) => $usage['risk_level'] === 'high');
            
            if (!empty($highRiskUsages)) {
                Log::warning('High-risk dynamic usage detected - cleanup not safe', [
                    'high_risk_count' => count($highRiskUsages)
                ]);
                return false;
            }
            
            // Validate critical paths are working
            if (!$this->testValidator->validateCriticalPaths()) {
                Log::warning('Critical paths validation failed - cleanup not safe');
                return false;
            }
            
            // Check if backup was created successfully
            if (!$this->lastBackupId || !$this->backupManager->canRollback($this->lastBackupId)) {
                Log::warning('Backup not available - cleanup not safe');
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Safety check failed: ' . $e->getMessage());
            return false;
        }
    }

    public function emergencyRollback(): bool
    {
        Log::warning('Performing emergency rollback');
        
        try {
            if ($this->lastBackupId) {
                $result = $this->backupManager->rollback($this->lastBackupId);
                
                if ($result) {
                    Log::info('Emergency rollback successful');
                    
                    // Validate that rollback worked
                    $postRollbackValidation = $this->testValidator->validateCriticalPaths();
                    
                    if (!$postRollbackValidation) {
                        Log::error('Emergency rollback completed but critical paths still failing');
                        return false;
                    }
                    
                    return true;
                } else {
                    Log::error('Emergency rollback failed');
                    return false;
                }
            } else {
                Log::error('No backup available for emergency rollback');
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Emergency rollback error: ' . $e->getMessage());
            return false;
        }
    }

    private function extractCodeElements(array $cleanupPlan): array
    {
        $elements = [];
        
        foreach ($cleanupPlan as $operation) {
            if (isset($operation['type']) && isset($operation['elements'])) {
                foreach ($operation['elements'] as $element) {
                    $elements[] = [
                        'file' => $element['file'] ?? null,
                        'name' => $element['name'] ?? null,
                        'type' => $operation['type']
                    ];
                }
            }
        }
        
        return $elements;
    }

    private function calculateSafetyScore(array $validation): int
    {
        $score = 100;
        
        // Deduct points for dynamic usage
        $dynamicUsages = $validation['dynamic_usage_check'] ?? [];
        foreach ($dynamicUsages as $usage) {
            switch ($usage['risk_level']) {
                case 'high':
                    $score -= 30;
                    break;
                case 'medium':
                    $score -= 15;
                    break;
                case 'low':
                    $score -= 5;
                    break;
            }
        }
        
        // Deduct points for test failures
        $testResults = $validation['test_results'] ?? [];
        if (isset($testResults['unit_tests']) && !$testResults['unit_tests']['passed']) {
            $score -= 20;
        }
        if (isset($testResults['feature_tests']) && !$testResults['feature_tests']['passed']) {
            $score -= 20;
        }
        if (!($testResults['critical_paths'] ?? true)) {
            $score -= 25;
        }
        
        return max(0, $score);
    }

    private function generateRecommendations(array $validation): array
    {
        $recommendations = [];
        
        $dynamicUsages = $validation['dynamic_usage_check'] ?? [];
        $highRiskUsages = array_filter($dynamicUsages, fn($usage) => $usage['risk_level'] === 'high');
        
        if (!empty($highRiskUsages)) {
            $recommendations[] = [
                'type' => 'warning',
                'message' => 'High-risk dynamic code usage detected. Manual review required before cleanup.',
                'details' => $highRiskUsages
            ];
        }
        
        if ($validation['safety_score'] < 70) {
            $recommendations[] = [
                'type' => 'caution',
                'message' => 'Low safety score. Consider running cleanup in smaller batches.',
                'score' => $validation['safety_score']
            ];
        }
        
        $testResults = $validation['test_results'] ?? [];
        if (!($testResults['critical_paths'] ?? true)) {
            $recommendations[] = [
                'type' => 'error',
                'message' => 'Critical paths validation failed. Fix issues before proceeding with cleanup.'
            ];
        }
        
        return $recommendations;
    }

    private function shouldRollback(array $validation): bool
    {
        // Rollback if functionality verification failed
        if (!$validation['functionality_verified']) {
            return true;
        }
        
        // Rollback if there are runtime errors
        if (!empty($validation['runtime_errors'])) {
            return true;
        }
        
        // Rollback if critical tests failed
        $testResults = $validation['test_results'] ?? [];
        if (!($testResults['critical_paths'] ?? true)) {
            return true;
        }
        
        return false;
    }

    private function cleanupCheckpointsAfter(string $checkpointId): void
    {
        $checkpointIndex = null;
        
        foreach ($this->checkpoints as $index => $checkpoint) {
            if ($checkpoint['id'] === $checkpointId) {
                $checkpointIndex = $index;
                break;
            }
        }
        
        if ($checkpointIndex !== null) {
            $this->checkpoints = array_slice($this->checkpoints, 0, $checkpointIndex + 1);
        }
    }

    private function generateSafetyReportSummary(): array
    {
        $summary = [
            'total_validations' => count($this->safetyReport),
            'validations_passed' => 0,
            'validations_failed' => 0,
            'rollbacks_performed' => 0,
            'overall_status' => 'unknown'
        ];
        
        foreach ($this->safetyReport as $validation) {
            if ($validation['phase'] === 'post_cleanup') {
                if ($validation['validation_passed'] ?? false) {
                    $summary['validations_passed']++;
                } else {
                    $summary['validations_failed']++;
                }
                
                if ($validation['rollback_required'] ?? false) {
                    $summary['rollbacks_performed']++;
                }
            }
        }
        
        if ($summary['validations_failed'] > 0) {
            $summary['overall_status'] = 'failed';
        } elseif ($summary['validations_passed'] > 0) {
            $summary['overall_status'] = 'passed';
        }
        
        return $summary;
    }
}