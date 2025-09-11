<?php

namespace App\Services\Cleanup\Contracts;

use App\Services\Cleanup\Models\RefactoringPlan;
use App\Services\Cleanup\Models\RefactoringResult;
use App\Services\Cleanup\Models\ComponentExtractionSuggestion;
use App\Services\Cleanup\Models\MethodExtractionSuggestion;

interface CodeRefactoringServiceInterface
{
    /**
     * Execute automated refactoring based on a refactoring plan
     */
    public function executeRefactoring(RefactoringPlan $plan): RefactoringResult;
    
    /**
     * Extract duplicate code into reusable components
     */
    public function extractComponents(array $componentSuggestions): RefactoringResult;
    
    /**
     * Extract duplicate methods into shared utilities
     */
    public function extractMethods(array $methodSuggestions): RefactoringResult;
    
    /**
     * Consolidate duplicate methods across classes
     */
    public function consolidateMethods(array $duplicateMethods): RefactoringResult;
    
    /**
     * Update all references after refactoring operations
     */
    public function updateReferences(array $referenceUpdates): bool;
    
    /**
     * Generate component extraction suggestions from duplicate code
     */
    public function generateComponentSuggestions(array $duplicateBlocks): array;
    
    /**
     * Generate method extraction suggestions from duplicate methods
     */
    public function generateMethodSuggestions(array $duplicateMethods): array;
    
    /**
     * Validate that refactoring operations are safe to execute
     */
    public function validateRefactoring(RefactoringPlan $plan): array;
}