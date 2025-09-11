<?php

namespace App\Contracts\Cleanup;

use App\Models\Cleanup\PhpFileAnalysis;

interface PhpAnalyzerInterface
{
    /**
     * Parse a PHP file and return analysis results
     */
    public function parseFile(string $filePath): PhpFileAnalysis;

    /**
     * Find unused imports in the given analysis
     */
    public function findUnusedImports(PhpFileAnalysis $analysis): array;

    /**
     * Find unused methods in the given analysis
     */
    public function findUnusedMethods(PhpFileAnalysis $analysis): array;

    /**
     * Find duplicate methods across multiple analyses
     */
    public function findDuplicateMethods(array $analyses): array;

    /**
     * Find unused variables in the given analysis
     */
    public function findUnusedVariables(PhpFileAnalysis $analysis): array;

    /**
     * Remove unused imports from a PHP file
     */
    public function removeUnusedImports(string $filePath, array $unusedImports): bool;
}