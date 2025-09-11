<?php

namespace App\Services\Cleanup\Models;

class CrossFileDuplicateReport
{
    public function __construct(
        public readonly array $jsDuplicates,
        public readonly array $cssDuplicates,
        public readonly array $bladeDuplicates,
        public readonly array $componentSuggestions,
        public readonly array $summary
    ) {}

    public function toArray(): array
    {
        return [
            'js_duplicates' => $this->jsDuplicates,
            'css_duplicates' => $this->cssDuplicates,
            'blade_duplicates' => $this->bladeDuplicates,
            'component_suggestions' => array_map(fn($suggestion) => $suggestion->toArray(), $this->componentSuggestions),
            'summary' => $this->summary,
        ];
    }

    public function getTotalDuplicatesFound(): int
    {
        return $this->summary['total_duplicates_found'] ?? 0;
    }

    public function getHighPriorityItems(): array
    {
        $recommendations = $this->summary['priority_recommendations'] ?? [];
        return array_filter($recommendations, fn($item) => $item['priority'] > 50);
    }

    public function getLowEffortItems(): array
    {
        $recommendations = $this->summary['priority_recommendations'] ?? [];
        return array_filter($recommendations, fn($item) => $item['effort'] === 'low');
    }

    public function getEstimatedTotalSavings(): array
    {
        return $this->summary['estimated_savings'] ?? [];
    }

    public function hasSignificantDuplicates(): bool
    {
        return $this->getTotalDuplicatesFound() > 5 || 
               count($this->componentSuggestions) > 2;
    }

    public function generateExecutiveSummary(): string
    {
        $total = $this->getTotalDuplicatesFound();
        $components = count($this->componentSuggestions);
        $jsSavings = $this->summary['estimated_savings']['javascript_lines'] ?? 0;
        $cssSavings = $this->summary['estimated_savings']['css_bytes'] ?? 0;
        
        $summary = "Found {$total} duplicate code patterns across the codebase";
        
        if ($components > 0) {
            $summary .= " and identified {$components} opportunities for component extraction";
        }
        
        $summary .= ". Potential savings: {$jsSavings} lines of JavaScript";
        
        if ($cssSavings > 0) {
            $summary .= ", " . round($cssSavings / 1024, 1) . "KB of CSS";
        }
        
        $summary .= ".";
        
        return $summary;
    }
}