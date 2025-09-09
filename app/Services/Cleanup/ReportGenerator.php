<?php

namespace App\Services\Cleanup;

use App\Services\Cleanup\Models\CleanupReport;
use App\Services\Cleanup\Models\CleanupPlan;
use App\Services\Cleanup\Models\CleanupMetrics;
use App\Services\Cleanup\Models\OperationLog;
use App\Services\Cleanup\Models\MaintenanceRecommendation;
use App\Services\Cleanup\Models\RiskAssessment;

class ReportGenerator
{
    private MaintenanceRecommendationEngine $recommendationEngine;
    private RiskAssessmentEngine $riskAssessmentEngine;

    public function __construct(
        MaintenanceRecommendationEngine $recommendationEngine = null,
        RiskAssessmentEngine $riskAssessmentEngine = null
    ) {
        $this->recommendationEngine = $recommendationEngine ?? new MaintenanceRecommendationEngine();
        $this->riskAssessmentEngine = $riskAssessmentEngine ?? new RiskAssessmentEngine();
    }

    /**
     * Generate comprehensive cleanup report
     */
    public function generateReport(
        CleanupPlan $plan, 
        array $executionResults, 
        CleanupMetrics $metrics = null,
        OperationLog $operationLog = null
    ): CleanupReport {
        $report = new CleanupReport();

        // Basic cleanup statistics
        $report->filesRemoved = $executionResults['files_removed'] ?? 0;
        $report->linesRemoved = $executionResults['lines_removed'] ?? 0;
        $report->importsRemoved = $executionResults['imports_removed'] ?? 0;
        $report->methodsRemoved = $executionResults['methods_removed'] ?? 0;
        $report->duplicatesRefactored = $executionResults['duplicates_refactored'] ?? 0;
        $report->componentsCreated = $executionResults['components_created'] ?? 0;

        // Performance improvements
        if ($metrics && !empty($metrics->performanceImprovements)) {
            $report->performanceImprovements = $this->formatPerformanceImprovements($metrics->performanceImprovements);
            $report->sizeReductionMB = $metrics->getTotalSizeReductionMB();
        }

        // Maintenance recommendations
        $report->maintenanceRecommendations = $this->generateMaintenanceRecommendations($plan, $executionResults);

        // Risk assessments
        $report->riskAssessments = $this->generateRiskAssessments($plan, $executionResults, $operationLog);

        // Additional report sections
        $report->executionSummary = $this->generateExecutionSummary($executionResults, $metrics);
        $report->codeQualityImprovements = $this->calculateCodeQualityImprovements($metrics);
        $report->futureOptimizationOpportunities = $this->identifyFutureOptimizations($plan, $executionResults);

        return $report;
    }

    /**
     * Calculate performance improvements between before and after metrics
     */
    public function calculatePerformanceImprovements(array $beforeMetrics, array $afterMetrics): array
    {
        $improvements = [];

        // File size improvements
        if (isset($beforeMetrics['total_file_size']) && isset($afterMetrics['total_file_size'])) {
            $sizeDiff = $beforeMetrics['total_file_size'] - $afterMetrics['total_file_size'];
            $improvements['file_size'] = [
                'bytes_reduced' => $sizeDiff,
                'mb_reduced' => $sizeDiff / (1024 * 1024),
                'percentage_reduction' => $this->calculatePercentageReduction(
                    $beforeMetrics['total_file_size'], 
                    $afterMetrics['total_file_size']
                ),
            ];
        }

        // File count improvements
        if (isset($beforeMetrics['total_files']) && isset($afterMetrics['total_files'])) {
            $filesDiff = $beforeMetrics['total_files'] - $afterMetrics['total_files'];
            $improvements['file_count'] = [
                'files_removed' => $filesDiff,
                'percentage_reduction' => $this->calculatePercentageReduction(
                    $beforeMetrics['total_files'], 
                    $afterMetrics['total_files']
                ),
            ];
        }

        // Code complexity improvements
        if (isset($beforeMetrics['cyclomatic_complexity']) && isset($afterMetrics['cyclomatic_complexity'])) {
            $complexityDiff = $beforeMetrics['cyclomatic_complexity'] - $afterMetrics['cyclomatic_complexity'];
            $improvements['complexity'] = [
                'complexity_reduced' => $complexityDiff,
                'percentage_reduction' => $this->calculatePercentageReduction(
                    $beforeMetrics['cyclomatic_complexity'], 
                    $afterMetrics['cyclomatic_complexity']
                ),
            ];
        }

        // Lines of code improvements
        if (isset($beforeMetrics['total_lines']) && isset($afterMetrics['total_lines'])) {
            $linesDiff = $beforeMetrics['total_lines'] - $afterMetrics['total_lines'];
            $improvements['lines_of_code'] = [
                'lines_removed' => $linesDiff,
                'percentage_reduction' => $this->calculatePercentageReduction(
                    $beforeMetrics['total_lines'], 
                    $afterMetrics['total_lines']
                ),
            ];
        }

        return $improvements;
    }

    /**
     * Generate maintenance recommendations based on cleanup results
     */
    public function generateMaintenanceRecommendations(CleanupPlan $plan, array $executionResults = []): array
    {
        return $this->recommendationEngine->generateRecommendations($plan, $executionResults);
    }

    /**
     * Generate risk assessments for cleanup operations
     */
    public function generateRiskAssessments(CleanupPlan $plan, array $executionResults, OperationLog $operationLog = null): array
    {
        return $this->riskAssessmentEngine->assessRisks($plan, $executionResults, $operationLog);
    }

    /**
     * Generate execution summary
     */
    private function generateExecutionSummary(array $executionResults, CleanupMetrics $metrics = null): array
    {
        $summary = [
            'total_operations' => array_sum($executionResults),
            'execution_time' => $metrics ? $metrics->getFormattedExecutionTime() : 'N/A',
            'memory_usage' => $metrics ? $metrics->getFormattedMemoryUsage() : 'N/A',
            'success_rate' => $this->calculateSuccessRate($executionResults),
        ];

        if ($metrics) {
            $summary['operations_per_second'] = $metrics->getOperationsPerSecond();
            $summary['peak_memory_usage'] = $metrics->getPeakMemoryUsage();
        }

        return $summary;
    }

    /**
     * Calculate code quality improvements
     */
    private function calculateCodeQualityImprovements(CleanupMetrics $metrics = null): array
    {
        if (!$metrics || empty($metrics->performanceImprovements)) {
            return [];
        }

        $improvements = $metrics->performanceImprovements;
        
        return [
            'maintainability_score' => $this->calculateMaintainabilityScore($improvements),
            'technical_debt_reduction' => $this->calculateTechnicalDebtReduction($improvements),
            'code_smell_reduction' => $improvements['code_smells_reduction'] ?? [],
            'complexity_improvement' => $improvements['complexity_reduction'] ?? [],
        ];
    }

    /**
     * Identify future optimization opportunities
     */
    private function identifyFutureOptimizations(CleanupPlan $plan, array $executionResults): array
    {
        $opportunities = [];

        // Check for remaining duplicates
        if (isset($plan->duplicatesToRefactor) && count($plan->duplicatesToRefactor) > ($executionResults['duplicates_refactored'] ?? 0)) {
            $opportunities[] = [
                'type' => 'duplicate_code',
                'description' => 'Additional duplicate code patterns detected that could be refactored',
                'potential_impact' => 'Medium',
                'estimated_effort' => 'Low to Medium',
            ];
        }

        // Check for component extraction opportunities
        if (isset($plan->componentsToCreate) && count($plan->componentsToCreate) > ($executionResults['components_created'] ?? 0)) {
            $opportunities[] = [
                'type' => 'component_extraction',
                'description' => 'Additional opportunities for component extraction in templates',
                'potential_impact' => 'Medium',
                'estimated_effort' => 'Medium',
            ];
        }

        // Performance optimization opportunities
        $opportunities[] = [
            'type' => 'performance_optimization',
            'description' => 'Consider implementing lazy loading and caching strategies',
            'potential_impact' => 'High',
            'estimated_effort' => 'Medium to High',
        ];

        return $opportunities;
    }

    /**
     * Format performance improvements for display
     */
    private function formatPerformanceImprovements(array $improvements): array
    {
        $formatted = [];

        foreach ($improvements as $category => $data) {
            $formatted[$category] = [
                'description' => $this->getImprovementDescription($category),
                'value' => $data,
                'impact_level' => $this->assessImpactLevel($category, $data),
            ];
        }

        return $formatted;
    }

    /**
     * Calculate percentage reduction
     */
    private function calculatePercentageReduction(float $before, float $after): float
    {
        if ($before == 0) {
            return 0.0;
        }
        return (($before - $after) / $before) * 100;
    }

    /**
     * Calculate success rate from execution results
     */
    private function calculateSuccessRate(array $executionResults): float
    {
        $totalOperations = array_sum($executionResults);
        $failedOperations = $executionResults['failed_operations'] ?? 0;
        
        if ($totalOperations == 0) {
            return 100.0;
        }
        
        return (($totalOperations - $failedOperations) / $totalOperations) * 100;
    }

    /**
     * Calculate maintainability score
     */
    private function calculateMaintainabilityScore(array $improvements): float
    {
        $score = 70.0; // Base score
        
        // Improve score based on reductions
        if (isset($improvements['complexity_reduction']['percentage'])) {
            $score += min($improvements['complexity_reduction']['percentage'] * 0.3, 15);
        }
        
        if (isset($improvements['code_smells_reduction']['percentage'])) {
            $score += min($improvements['code_smells_reduction']['percentage'] * 0.2, 10);
        }
        
        if (isset($improvements['file_size_reduction']['percentage'])) {
            $score += min($improvements['file_size_reduction']['percentage'] * 0.1, 5);
        }
        
        return min($score, 100.0);
    }

    /**
     * Calculate technical debt reduction
     */
    private function calculateTechnicalDebtReduction(array $improvements): array
    {
        $reduction = [
            'estimated_hours_saved' => 0,
            'maintenance_cost_reduction' => 0,
            'development_velocity_improvement' => 0,
        ];

        // Estimate based on complexity reduction
        if (isset($improvements['complexity_reduction']['cyclomatic'])) {
            $reduction['estimated_hours_saved'] += $improvements['complexity_reduction']['cyclomatic'] * 0.5;
        }

        // Estimate based on duplicate code removal
        if (isset($improvements['file_count_reduction']['count'])) {
            $reduction['estimated_hours_saved'] += $improvements['file_count_reduction']['count'] * 0.25;
        }

        $reduction['maintenance_cost_reduction'] = $reduction['estimated_hours_saved'] * 50; // $50/hour estimate
        $reduction['development_velocity_improvement'] = min($reduction['estimated_hours_saved'] * 2, 20); // Max 20% improvement

        return $reduction;
    }

    /**
     * Get improvement description
     */
    private function getImprovementDescription(string $category): string
    {
        return match ($category) {
            'file_size_reduction' => 'Reduction in total file size',
            'file_count_reduction' => 'Number of files removed',
            'line_count_reduction' => 'Lines of code removed',
            'complexity_reduction' => 'Cyclomatic complexity reduction',
            'code_smells_reduction' => 'Code smells eliminated',
            default => ucfirst(str_replace('_', ' ', $category)),
        };
    }

    /**
     * Assess impact level
     */
    private function assessImpactLevel(string $category, array $data): string
    {
        $percentage = $data['percentage'] ?? 0;
        
        if ($percentage >= 20) {
            return 'High';
        } elseif ($percentage >= 10) {
            return 'Medium';
        } elseif ($percentage > 0) {
            return 'Low';
        } else {
            return 'None';
        }
    }
}