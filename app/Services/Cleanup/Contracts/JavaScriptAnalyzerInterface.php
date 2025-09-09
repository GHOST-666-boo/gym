<?php

namespace App\Services\Cleanup\Contracts;

use App\Services\Cleanup\Models\JsFileAnalysis;

interface JavaScriptAnalyzerInterface
{
    public function parseFile(string $filePath): JsFileAnalysis;
    
    public function findUnusedImports(JsFileAnalysis $analysis): array;
    
    public function findUnusedVariables(JsFileAnalysis $analysis): array;
    
    public function findDuplicateFunctions(array $analyses): array;
}