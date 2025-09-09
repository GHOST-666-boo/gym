<?php

namespace App\Services\Cleanup\Contracts;

interface BackupManagerInterface
{
    /**
     * Create a backup before cleanup operations
     */
    public function createBackup(string $description = null): string;

    /**
     * Rollback to a specific backup
     */
    public function rollback(string $backupId): bool;

    /**
     * Get list of available backups
     */
    public function getBackups(): array;

    /**
     * Delete a specific backup
     */
    public function deleteBackup(string $backupId): bool;

    /**
     * Create a validation checkpoint during cleanup
     */
    public function createCheckpoint(string $operation, array $metadata = []): string;

    /**
     * Rollback to a specific checkpoint
     */
    public function rollbackToCheckpoint(string $checkpointId): bool;

    /**
     * Get list of checkpoints for current session
     */
    public function getCheckpoints(): array;

    /**
     * Validate that rollback is possible
     */
    public function canRollback(string $backupId): bool;

    /**
     * Clean up old backups and checkpoints
     */
    public function cleanup(int $keepDays = 7): int;
}