<?php

namespace App\Services\Cleanup\Models;

class CleanupPlan
{
    public array $filesToDelete = [];      // File paths that can be safely removed
    public array $importsToRemove = [];    // Unused import statements
    public array $methodsToRemove = [];    // Unused methods and functions
    public array $variablesToRemove = [];  // Unused variables
    public array $duplicatesToRefactor = []; // Duplicate code to be refactored
    public array $componentsToCreate = [];   // New components to extract
    public float $estimatedSizeReduction = 0.0; // Estimated file size reduction
    
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
    
    public function getTotalOperations(): int
    {
        return count($this->filesToDelete) + count($this->importsToRemove) + 
               count($this->methodsToRemove) + count($this->variablesToRemove) +
               count($this->duplicatesToRefactor) + count($this->componentsToCreate);
    }
}