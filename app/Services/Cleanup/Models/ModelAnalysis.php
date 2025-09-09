<?php

namespace App\Services\Cleanup\Models;

class ModelAnalysis
{
    public string $className;
    public string $filePath;
    public string $tableName;
    public array $relationships = [];
    public array $usageLocations = [];
    public bool $isUsed = false;
    public int $usageCount = 0;
    
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
    
    public function addUsageLocation(string $file, int $line, string $context = ''): void
    {
        $this->usageLocations[] = [
            'file' => $file,
            'line' => $line,
            'context' => $context
        ];
        $this->usageCount++;
        $this->isUsed = true;
    }
    
    public function addRelationship(string $type, string $relatedModel): void
    {
        $this->relationships[] = [
            'type' => $type,
            'model' => $relatedModel
        ];
    }
    
    public function getShortClassName(): string
    {
        $parts = explode('\\', $this->className);
        return end($parts);
    }
}