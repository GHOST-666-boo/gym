<?php

namespace App\Services\Cleanup\Models;

class MethodExtractionSuggestion
{
    public string $sourceFile;
    public string $methodName;
    public string $methodCode;
    public array $duplicateLocations = [];
    public string $suggestedClassName;
    public string $suggestedMethodName;
    public string $suggestedFilePath;
    public array $parameters = [];
    public string $returnType = 'mixed';
    public string $visibility = 'public';
    public bool $isStatic = false;
    
    public function __construct(
        string $sourceFile,
        string $methodName,
        string $methodCode,
        array $duplicateLocations = []
    ) {
        $this->sourceFile = $sourceFile;
        $this->methodName = $methodName;
        $this->methodCode = $methodCode;
        $this->duplicateLocations = $duplicateLocations;
        
        // Generate default suggestions
        $this->suggestedClassName = $this->generateClassName();
        $this->suggestedMethodName = $this->generateMethodName();
        $this->suggestedFilePath = $this->generateFilePath();
    }
    
    private function generateClassName(): string
    {
        $baseName = pathinfo($this->sourceFile, PATHINFO_FILENAME);
        return $baseName . 'Helper';
    }
    
    private function generateMethodName(): string
    {
        // Clean up method name for extraction
        return 'extracted' . ucfirst($this->methodName);
    }
    
    private function generateFilePath(): string
    {
        $directory = dirname($this->sourceFile);
        return $directory . '/Helpers/' . $this->suggestedClassName . '.php';
    }
    
    public function getDuplicateCount(): int
    {
        return count($this->duplicateLocations);
    }
    
    public function getEstimatedSavings(): int
    {
        $methodLines = substr_count($this->methodCode, "\n") + 1;
        return $methodLines * $this->getDuplicateCount();
    }
}