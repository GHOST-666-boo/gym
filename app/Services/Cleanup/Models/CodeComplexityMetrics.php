<?php

namespace App\Services\Cleanup\Models;

class CodeComplexityMetrics
{
    public int $cyclomaticComplexity = 0;
    public int $cognitiveComplexity = 0;
    public int $nestingDepth = 0;
    public int $linesOfCode = 0;
    public int $numberOfMethods = 0;
    public int $numberOfClasses = 0;
    public array $complexityByMethod = [];
    public array $complexityByClass = [];
    public array $codeSmells = [];

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Calculate average complexity per method
     */
    public function getAverageComplexityPerMethod(): float
    {
        if ($this->numberOfMethods == 0) {
            return 0.0;
        }
        return $this->cyclomaticComplexity / $this->numberOfMethods;
    }

    /**
     * Calculate average lines per method
     */
    public function getAverageLinesPerMethod(): float
    {
        if ($this->numberOfMethods == 0) {
            return 0.0;
        }
        return $this->linesOfCode / $this->numberOfMethods;
    }

    /**
     * Get complexity rating
     */
    public function getComplexityRating(): string
    {
        $avgComplexity = $this->getAverageComplexityPerMethod();
        
        if ($avgComplexity <= 5) {
            return 'Low';
        } elseif ($avgComplexity <= 10) {
            return 'Moderate';
        } elseif ($avgComplexity <= 20) {
            return 'High';
        } else {
            return 'Very High';
        }
    }

    /**
     * Get maintainability index (simplified calculation)
     */
    public function getMaintainabilityIndex(): float
    {
        if ($this->linesOfCode == 0) {
            return 100.0;
        }

        // Simplified maintainability index calculation
        $halsteadVolume = $this->linesOfCode * 4.2; // Approximation
        $cyclomaticComplexity = max($this->cyclomaticComplexity, 1);
        
        $maintainabilityIndex = 171 - 5.2 * log($halsteadVolume) - 0.23 * $cyclomaticComplexity - 16.2 * log($this->linesOfCode);
        
        return max(0, min(100, $maintainabilityIndex));
    }

    /**
     * Get technical debt ratio
     */
    public function getTechnicalDebtRatio(): float
    {
        $totalIssues = count($this->codeSmells);
        if ($this->linesOfCode == 0) {
            return 0.0;
        }
        
        return ($totalIssues / $this->linesOfCode) * 100;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'cyclomatic_complexity' => $this->cyclomaticComplexity,
            'cognitive_complexity' => $this->cognitiveComplexity,
            'nesting_depth' => $this->nestingDepth,
            'lines_of_code' => $this->linesOfCode,
            'number_of_methods' => $this->numberOfMethods,
            'number_of_classes' => $this->numberOfClasses,
            'complexity_by_method' => $this->complexityByMethod,
            'complexity_by_class' => $this->complexityByClass,
            'code_smells' => $this->codeSmells,
            'average_complexity_per_method' => $this->getAverageComplexityPerMethod(),
            'average_lines_per_method' => $this->getAverageLinesPerMethod(),
            'complexity_rating' => $this->getComplexityRating(),
            'maintainability_index' => $this->getMaintainabilityIndex(),
            'technical_debt_ratio' => $this->getTechnicalDebtRatio(),
        ];
    }
}