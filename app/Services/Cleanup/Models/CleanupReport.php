<?php

namespace App\Services\Cleanup\Models;

class CleanupReport
{
    public int $filesRemoved = 0;
    public int $linesRemoved = 0;
    public int $importsRemoved = 0;
    public int $methodsRemoved = 0;
    public int $duplicatesRefactored = 0;
    public int $componentsCreated = 0;
    public float $sizeReductionMB = 0.0;
    public array $performanceImprovements = [];
    public array $maintenanceRecommendations = [];
    public array $riskAssessments = [];
    public array $executionSummary = [];
    public array $codeQualityImprovements = [];
    public array $futureOptimizationOpportunities = [];
    public \DateTime $generatedAt;
    
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        
        if (!isset($this->generatedAt)) {
            $this->generatedAt = new \DateTime();
        }
    }
    
    public function getTotalItemsProcessed(): int
    {
        return $this->filesRemoved + $this->importsRemoved + 
               $this->methodsRemoved + $this->duplicatesRefactored + 
               $this->componentsCreated;
    }

    /**
     * Get overall cleanup success rate
     */
    public function getSuccessRate(): float
    {
        if (isset($this->executionSummary['success_rate'])) {
            return $this->executionSummary['success_rate'];
        }
        return 100.0; // Default if no execution data
    }

    /**
     * Get high priority recommendations
     */
    public function getHighPriorityRecommendations(): array
    {
        return array_filter($this->maintenanceRecommendations, function ($recommendation) {
            return isset($recommendation['priority']) && $recommendation['priority'] === 'high';
        });
    }

    /**
     * Get critical risks
     */
    public function getCriticalRisks(): array
    {
        return array_filter($this->riskAssessments, function ($risk) {
            return isset($risk['severity']) && in_array($risk['severity'], ['critical', 'high']);
        });
    }

    /**
     * Get total estimated time savings
     */
    public function getEstimatedTimeSavings(): array
    {
        $totalHours = 0;
        foreach ($this->maintenanceRecommendations as $recommendation) {
            if (isset($recommendation['estimated_hours'])) {
                $hours = $recommendation['estimated_hours'];
                $totalHours += is_array($hours) ? ($hours['max'] ?? 0) : 0;
            }
        }

        return [
            'total_hours' => $totalHours,
            'weekly_savings' => $totalHours * 0.1, // Estimate 10% weekly benefit
            'monthly_savings' => $totalHours * 0.4, // Estimate 40% monthly benefit
        ];
    }

    /**
     * Get cleanup impact summary
     */
    public function getImpactSummary(): array
    {
        return [
            'files_processed' => $this->getTotalItemsProcessed(),
            'size_reduction_mb' => $this->sizeReductionMB,
            'success_rate' => $this->getSuccessRate(),
            'high_priority_items' => count($this->getHighPriorityRecommendations()),
            'critical_risks' => count($this->getCriticalRisks()),
            'estimated_time_savings' => $this->getEstimatedTimeSavings(),
        ];
    }

    /**
     * Convert to array for serialization
     */
    public function toArray(): array
    {
        return [
            'files_removed' => $this->filesRemoved,
            'lines_removed' => $this->linesRemoved,
            'imports_removed' => $this->importsRemoved,
            'methods_removed' => $this->methodsRemoved,
            'duplicates_refactored' => $this->duplicatesRefactored,
            'components_created' => $this->componentsCreated,
            'size_reduction_mb' => $this->sizeReductionMB,
            'performance_improvements' => $this->performanceImprovements,
            'maintenance_recommendations' => $this->maintenanceRecommendations,
            'risk_assessments' => $this->riskAssessments,
            'execution_summary' => $this->executionSummary,
            'code_quality_improvements' => $this->codeQualityImprovements,
            'future_optimization_opportunities' => $this->futureOptimizationOpportunities,
            'generated_at' => $this->generatedAt->format('Y-m-d H:i:s'),
            'total_items_processed' => $this->getTotalItemsProcessed(),
            'success_rate' => $this->getSuccessRate(),
            'high_priority_recommendations' => $this->getHighPriorityRecommendations(),
            'critical_risks' => $this->getCriticalRisks(),
            'impact_summary' => $this->getImpactSummary(),
        ];
    }

    /**
     * Export to JSON
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }

    /**
     * Save report to file
     */
    public function saveToFile(string $filePath): bool
    {
        return file_put_contents($filePath, $this->toJson()) !== false;
    }
}