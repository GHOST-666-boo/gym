<?php

namespace App\Services\Cleanup\Contracts;

interface SafetyValidationServiceInterface
{
    /**
     * Perform complete safety validation before cleanup
     */
    public function validateBeforeCleanup(array $cleanupPlan): array;

    /**
     * Perform safety validation after cleanup operations
     */
    public function validateAfterCleanup(array $executedOperations): array;

    /**
     * Create safety checkpoint during cleanup process
     */
    public function createSafetyCheckpoint(string $operation, array $metadata = []): string;

    /**
     * Rollback to safety checkpoint if validation fails
     */
    public function rollbackToSafetyCheckpoint(string $checkpointId): bool;

    /**
     * Get comprehensive safety report
     */
    public function getSafetyReport(): array;

    /**
     * Validate that cleanup is safe to proceed
     */
    public function isSafeToCleanup(array $cleanupPlan): bool;

    /**
     * Emergency rollback to last known good state
     */
    public function emergencyRollback(): bool;
}