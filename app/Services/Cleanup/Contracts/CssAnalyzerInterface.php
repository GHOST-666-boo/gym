<?php

namespace App\Services\Cleanup\Contracts;

use App\Services\Cleanup\Models\CssFileAnalysis;

interface CssAnalyzerInterface
{
    public function parseFile(string $filePath): CssFileAnalysis;
    
    public function findUnusedClasses(CssFileAnalysis $analysis, array $htmlFiles = []): array;
    
    public function findUnusedIds(CssFileAnalysis $analysis, array $htmlFiles = []): array;
    
    public function findDuplicateRules(array $analyses): array;
}