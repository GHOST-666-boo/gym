<?php

namespace App\Services\Cleanup;

use App\Services\Cleanup\Models\PhpFileAnalysis;

interface PhpAnalyzerInterface
{
    /**
     * Parse a PHP file and return analysis results
     */
    public function parseFile(string $filePath): PhpFileAnalysis;

    /**
     * Find unused import statements in the analyzed file
     */
    public function findUnusedImports(PhpFileAnalysis $analysis): array;

    /**
     * Find unused methods in the analyzed file
     */
    public function findUnusedMethods(PhpFileAnalysis $analysis): array;

    /**
     * Find duplicate methods across multiple file analyses
     */
    public function findDuplicateMethods(array $analyses): array;
}