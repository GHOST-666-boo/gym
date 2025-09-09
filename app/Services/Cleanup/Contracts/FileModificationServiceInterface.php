<?php

namespace App\Services\Cleanup\Contracts;

use App\Services\Cleanup\Models\FileModificationPlan;
use App\Services\Cleanup\Models\FileModificationResult;

interface FileModificationServiceInterface
{
    /**
     * Execute atomic file modifications based on a modification plan
     */
    public function executeModifications(FileModificationPlan $plan): FileModificationResult;
    
    /**
     * Remove unused imports from a PHP file
     */
    public function removeUnusedImports(string $filePath, array $unusedImports): bool;
    
    /**
     * Remove unused variables from a PHP file
     */
    public function removeUnusedVariables(string $filePath, array $unusedVariables): bool;
    
    /**
     * Remove unused methods from a PHP file
     */
    public function removeUnusedMethods(string $filePath, array $unusedMethods): bool;
    
    /**
     * Update method references after refactoring
     */
    public function updateMethodReferences(array $referenceUpdates): bool;
    
    /**
     * Create a backup of the file before modification
     */
    public function createFileBackup(string $filePath): string;
    
    /**
     * Restore a file from backup
     */
    public function restoreFromBackup(string $filePath, string $backupPath): bool;
    
    /**
     * Validate that file modifications are safe to execute
     */
    public function validateModifications(FileModificationPlan $plan): array;
}