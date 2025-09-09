<?php

namespace App\Services\Cleanup\Models;

class ComponentExtractionSuggestion
{
    public function __construct(
        public readonly string $suggestedName,
        public readonly array $occurrences,
        public readonly int $potentialSavings,
        public readonly array $structure,
        public readonly int $priority,
        public readonly array $refactoringSteps
    ) {}

    public function toArray(): array
    {
        return [
            'suggested_name' => $this->suggestedName,
            'occurrences' => $this->occurrences,
            'potential_savings' => $this->potentialSavings,
            'structure' => $this->structure,
            'priority' => $this->priority,
            'refactoring_steps' => $this->refactoringSteps,
        ];
    }

    public function getAffectedFiles(): array
    {
        return array_unique(array_column($this->occurrences, 'file'));
    }

    public function getFileCount(): int
    {
        return count($this->getAffectedFiles());
    }

    public function isHighPriority(): bool
    {
        return $this->priority > 50;
    }

    public function isMediumPriority(): bool
    {
        return $this->priority >= 20 && $this->priority <= 50;
    }

    public function isLowPriority(): bool
    {
        return $this->priority < 20;
    }

    public function getEstimatedEffort(): string
    {
        if ($this->potentialSavings <= 2 && $this->getFileCount() <= 3) {
            return 'low';
        } elseif ($this->potentialSavings <= 5 && $this->getFileCount() <= 5) {
            return 'medium';
        } else {
            return 'high';
        }
    }

    public function generateComponentPath(): string
    {
        return "resources/views/components/{$this->suggestedName}.blade.php";
    }

    public function generateComponentUsage(): string
    {
        return "<x-{$this->suggestedName}>";
    }

    public function getStructurePreview(): string
    {
        $content = $this->structure['content'] ?? '';
        
        // Truncate long content for preview
        if (strlen($content) > 200) {
            $content = substr($content, 0, 200) . '...';
        }
        
        return $content;
    }

    public function calculateComplexity(): int
    {
        $content = $this->structure['content'] ?? '';
        
        $complexity = 0;
        $complexity += substr_count($content, '<') * 2; // HTML elements
        $complexity += substr_count($content, 'class=') * 1; // CSS classes
        $complexity += substr_count($content, '{{') * 3; // Blade variables
        $complexity += substr_count($content, '@') * 4; // Blade directives
        
        return $complexity;
    }

    public function getComplexityLevel(): string
    {
        $complexity = $this->calculateComplexity();
        
        if ($complexity <= 10) {
            return 'simple';
        } elseif ($complexity <= 30) {
            return 'moderate';
        } else {
            return 'complex';
        }
    }

    public function generateDescription(): string
    {
        $fileCount = $this->getFileCount();
        $savings = $this->potentialSavings;
        $complexity = $this->getComplexityLevel();
        
        return "Extract {$complexity} HTML structure found in {$fileCount} templates into reusable component '{$this->suggestedName}'. Potential savings: {$savings} duplicate instances.";
    }
}