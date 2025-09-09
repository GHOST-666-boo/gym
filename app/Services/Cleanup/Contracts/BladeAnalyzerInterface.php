<?php

namespace App\Services\Cleanup\Contracts;

use App\Services\Cleanup\Models\BladeTemplateAnalysis;

interface BladeAnalyzerInterface
{
    public function parseTemplate(string $filePath): BladeTemplateAnalysis;
    
    public function findUnusedComponents(): array;
    
    public function findDuplicateStructures(array $analyses): array;
    
    public function extractComponentCandidates(array $analyses): array;
}