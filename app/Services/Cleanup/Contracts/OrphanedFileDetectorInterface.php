<?php

namespace App\Services\Cleanup\Contracts;

use App\Services\Cleanup\Models\AssetFileAnalysis;

interface OrphanedFileDetectorInterface
{
    /**
     * Scan the entire codebase to track file references
     */
    public function scanCodebaseReferences(): array;
    
    /**
     * Detect asset usage across the application
     */
    public function detectAssetUsage(array $assetPaths): array;
    
    /**
     * Find orphaned files that are not referenced anywhere
     */
    public function findOrphanedFiles(): array;
    
    /**
     * Validate that a file can be safely deleted
     */
    public function validateSafeDeletion(string $filePath): bool;
    
    /**
     * Get detailed analysis of a specific asset file
     */
    public function analyzeAssetFile(string $filePath): AssetFileAnalysis;
}