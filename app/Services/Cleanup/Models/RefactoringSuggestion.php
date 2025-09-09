<?php

namespace App\Services\Cleanup\Models;

class RefactoringSuggestion
{
    public function __construct(
        public readonly string $type,
        public readonly string $description,
        public readonly array $methods,
        public readonly float $similarity,
        public readonly string $effort,
        public readonly array $benefits
    ) {}

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'description' => $this->description,
            'methods' => $this->methods,
            'similarity' => $this->similarity,
            'effort' => $this->effort,
            'benefits' => $this->benefits,
        ];
    }

    public function isLowEffort(): bool
    {
        return $this->effort === 'low';
    }

    public function isMediumEffort(): bool
    {
        return $this->effort === 'medium';
    }

    public function isHighEffort(): bool
    {
        return $this->effort === 'high';
    }

    public function getEstimatedLinesSaved(): int
    {
        return $this->benefits['lines_saved'] ?? 0;
    }

    public function getPriority(): int
    {
        // Higher similarity and lower effort = higher priority
        $similarityScore = $this->similarity * 100;
        $effortScore = match ($this->effort) {
            'low' => 30,
            'medium' => 20,
            'high' => 10,
            default => 0
        };
        
        return (int) ($similarityScore + $effortScore);
    }

    public function generateRefactoredMethodName(): string
    {
        // Extract common parts from method names to suggest a new name
        $method1Name = $this->methods[0]['name'];
        $method2Name = $this->methods[1]['name'];
        
        // Find common prefix
        $commonPrefix = '';
        $minLength = min(strlen($method1Name), strlen($method2Name));
        
        for ($i = 0; $i < $minLength; $i++) {
            if ($method1Name[$i] === $method2Name[$i]) {
                $commonPrefix .= $method1Name[$i];
            } else {
                break;
            }
        }
        
        // If we have a meaningful common prefix, use it
        if (strlen($commonPrefix) >= 3) {
            return $commonPrefix . 'Common';
        }
        
        // Otherwise, suggest a generic name based on the type
        return match ($this->type) {
            'exact_duplicate' => 'extractedMethod',
            'near_duplicate' => 'sharedLogic',
            'similar_logic' => 'commonHelper',
            default => 'refactoredMethod'
        };
    }

    public function generateRefactoringSteps(): array
    {
        return match ($this->type) {
            'exact_duplicate' => [
                '1. Create a new method with the common logic',
                '2. Replace both duplicate methods with calls to the new method',
                '3. Remove the duplicate implementations',
                '4. Update any tests to reflect the changes'
            ],
            'near_duplicate' => [
                '1. Identify the differences between the methods',
                '2. Create a parameterized method that handles both cases',
                '3. Replace both methods with calls to the new parameterized method',
                '4. Update tests and ensure all edge cases are covered'
            ],
            'similar_logic' => [
                '1. Extract the common logic into helper methods',
                '2. Refactor both methods to use the shared helpers',
                '3. Consider creating a trait or base class for shared functionality',
                '4. Update documentation and tests'
            ],
            default => [
                '1. Analyze the similarities and differences',
                '2. Design a refactoring strategy',
                '3. Implement the refactoring incrementally',
                '4. Test thoroughly after each step'
            ]
        };
    }
}