<?php

namespace App\Services\Cleanup\Models;

class RefactoringPlan
{
    public array $componentExtractions = [];
    public array $methodExtractions = [];
    public array $methodConsolidations = [];
    public array $referenceUpdates = [];
    public bool $createBackups = true;
    public bool $validateAfterRefactoring = true;
    
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
    
    public function hasRefactorings(): bool
    {
        return !empty($this->componentExtractions) || 
               !empty($this->methodExtractions) || 
               !empty($this->methodConsolidations) ||
               !empty($this->referenceUpdates);
    }
    
    public function getTotalRefactorings(): int
    {
        return count($this->componentExtractions) + 
               count($this->methodExtractions) + 
               count($this->methodConsolidations) +
               count($this->referenceUpdates);
    }
    
    public function addComponentExtraction(ComponentExtractionSuggestion $suggestion): void
    {
        $this->componentExtractions[] = $suggestion;
    }
    
    public function addMethodExtraction(MethodExtractionSuggestion $suggestion): void
    {
        $this->methodExtractions[] = $suggestion;
    }
    
    public function addReferenceUpdate(ReferenceUpdate $update): void
    {
        $this->referenceUpdates[] = $update;
    }
}