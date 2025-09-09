<?php

namespace App\Services\Cleanup\Contracts;

use App\Services\Cleanup\Models\PhpFileAnalysis;

interface PhpAnalyzerInterface
{
    public function parseFile(string $filePath): PhpFileAnalysis;
    
    public function findUnusedImports(PhpFileAnalysis $analysis): array;
    
    public function findUnusedMethods(PhpFileAnalysis $analysis): array;
    
    public function findDuplicateMethods(array $analyses): array;
}