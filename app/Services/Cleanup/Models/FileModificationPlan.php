<?php

namespace App\Services\Cleanup\Models;

class FileModificationPlan
{
    public string $filePath;
    public array $importsToRemove = [];
    public array $variablesToRemove = [];
    public array $methodsToRemove = [];
    public array $referenceUpdates = [];
    public bool $createBackup = true;
    public bool $validateAfterModification = true;
    
    public function __construct(string $filePath, array $data = [])
    {
        $this->filePath = $filePath;
        
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
    
    public function hasModifications(): bool
    {
        return !empty($this->importsToRemove) || 
               !empty($this->variablesToRemove) || 
               !empty($this->methodsToRemove) ||
               !empty($this->referenceUpdates);
    }
    
    public function getTotalModifications(): int
    {
        return count($this->importsToRemove) + 
               count($this->variablesToRemove) + 
               count($this->methodsToRemove) +
               count($this->referenceUpdates);
    }
}