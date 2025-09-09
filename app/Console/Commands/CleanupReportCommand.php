<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Cleanup\ReportGenerator;
use App\Services\Cleanup\Models\CleanupReport;
use App\Services\Cleanup\Models\CleanupPlan;
use App\Services\Cleanup\Models\CleanupMetrics;
use App\Services\Cleanup\Models\OperationLog;

class CleanupReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cleanup:report 
                            {--input= : Input file with cleanup results (JSON)}
                            {--output= : Output file for the report}
                            {--format=detailed : Report format (detailed,summary,json,html)}
                            {--include=* : Sections to include (summary,performance,risks,recommendations)}
                            {--exclude=* : Sections to exclude}';

    /**
     * The console command description.
     */
    protected $description = 'Generate comprehensive cleanup reports';

    private ReportGenerator $reportGenerator;

    public function __construct(ReportGenerator $reportGenerator)
    {
        parent::__construct();
        $this->reportGenerator = $reportGenerator;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Generating cleanup report...');
        
        try {
            // Load or create report data
            $reportData = $this->loadReportData();
            
            // Generate report
            $report = $this->generateReport($reportData);
            
            // Display or save report
            $this->outputReport($report);
            
            $this->info('Report generated successfully!');
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Report generation failed: ' . $e->getMessage());
            if ($this->output->isVerbose()) {
                $this->error($e->getTraceAsString());
            }
            return Command::FAILURE;
        }
    }

    /**
     * Load report data from input or create sample data
     */
    private function loadReportData(): array
    {
        if ($this->option('input')) {
            $inputFile = $this->option('input');
            
            if (!file_exists($inputFile)) {
                throw new \Exception("Input file not found: {$inputFile}");
            }
            
            $data = json_decode(file_get_contents($inputFile), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception("Invalid JSON in input file: " . json_last_error_msg());
            }
            
            return $data;
        }
        
        // Create sample report data for demonstration
        return $this->createSampleReportData();
    }

    /**
     * Generate report from data
     */
    private function generateReport(array $data): CleanupReport
    {
        // Create models from data
        $plan = $this->createCleanupPlan($data['plan'] ?? []);
        $executionResults = $data['execution_results'] ?? [];
        $metrics = $this->createCleanupMetrics($data['metrics'] ?? []);
        $operationLog = $this->createOperationLog($data['operation_log'] ?? []);
        
        // Generate comprehensive report
        return $this->reportGenerator->generateReport(
            $plan,
            $executionResults,
            $metrics,
            $operationLog
        );
    }

    /**
     * Output report in requested format
     */
    private function outputReport(CleanupReport $report): void
    {
        $format = $this->option('format');
        $outputFile = $this->option('output');
        
        $content = match ($format) {
            'summary' => $this->formatSummaryReport($report),
            'json' => $this->formatJsonReport($report),
            'html' => $this->formatHtmlReport($report),
            default => $this->formatDetailedReport($report),
        };
        
        if ($outputFile) {
            file_put_contents($outputFile, $content);
            $this->info("Report saved to: {$outputFile}");
        } else {
            $this->line($content);
        }
    }

    /**
     * Format detailed report
     */
    private function formatDetailedReport(CleanupReport $report): string
    {
        $output = [];
        
        $output[] = "# Cleanup Report";
        $output[] = "Generated: " . now()->format('Y-m-d H:i:s');
        $output[] = "";
        
        // Executive Summary
        if ($this->shouldIncludeSection('summary')) {
            $output[] = "## Executive Summary";
            $output[] = "";
            $output[] = "This report summarizes the code cleanup operations performed on the codebase.";
            $output[] = "The cleanup process identified and addressed various code quality issues.";
            $output[] = "";
            
            $output[] = "### Key Metrics";
            $output[] = "- Files Removed: {$report->filesRemoved}";
            $output[] = "- Lines of Code Removed: {$report->linesRemoved}";
            $output[] = "- Unused Imports Removed: {$report->importsRemoved}";
            $output[] = "- Unused Methods Removed: {$report->methodsRemoved}";
            $output[] = "- Duplicate Code Refactored: {$report->duplicatesRefactored}";
            $output[] = "- Components Created: {$report->componentsCreated}";
            
            if ($report->sizeReductionMB > 0) {
                $output[] = "- Total Size Reduction: {$report->sizeReductionMB} MB";
            }
            $output[] = "";
        }
        
        // Performance Improvements
        if ($this->shouldIncludeSection('performance') && !empty($report->performanceImprovements)) {
            $output[] = "## Performance Improvements";
            $output[] = "";
            
            foreach ($report->performanceImprovements as $category => $improvement) {
                $output[] = "### " . ucfirst(str_replace('_', ' ', $category));
                $output[] = "- Description: {$improvement['description']}";
                $output[] = "- Impact Level: {$improvement['impact_level']}";
                
                if (is_array($improvement['value'])) {
                    foreach ($improvement['value'] as $key => $value) {
                        $output[] = "- " . ucfirst(str_replace('_', ' ', $key)) . ": {$value}";
                    }
                }
                $output[] = "";
            }
        }
        
        // Code Quality Improvements
        if (!empty($report->codeQualityImprovements)) {
            $output[] = "## Code Quality Improvements";
            $output[] = "";
            
            $quality = $report->codeQualityImprovements;
            
            if (isset($quality['maintainability_score'])) {
                $output[] = "- Maintainability Score: {$quality['maintainability_score']}/100";
            }
            
            if (isset($quality['technical_debt_reduction'])) {
                $debt = $quality['technical_debt_reduction'];
                $output[] = "- Estimated Hours Saved: {$debt['estimated_hours_saved']}";
                $output[] = "- Maintenance Cost Reduction: $" . number_format($debt['maintenance_cost_reduction']);
                $output[] = "- Development Velocity Improvement: {$debt['development_velocity_improvement']}%";
            }
            $output[] = "";
        }
        
        // Risk Assessments
        if ($this->shouldIncludeSection('risks') && !empty($report->riskAssessments)) {
            $output[] = "## Risk Assessments";
            $output[] = "";
            
            foreach ($report->riskAssessments as $risk) {
                $level = $risk['level'] ?? 'Unknown';
                $description = $risk['description'] ?? 'No description';
                $mitigation = $risk['mitigation'] ?? 'No mitigation provided';
                
                $output[] = "### {$level} Risk";
                $output[] = "- **Description**: {$description}";
                $output[] = "- **Mitigation**: {$mitigation}";
                $output[] = "";
            }
        }
        
        // Maintenance Recommendations
        if ($this->shouldIncludeSection('recommendations') && !empty($report->maintenanceRecommendations)) {
            $output[] = "## Maintenance Recommendations";
            $output[] = "";
            
            foreach ($report->maintenanceRecommendations as $index => $recommendation) {
                $output[] = ($index + 1) . ". {$recommendation}";
            }
            $output[] = "";
        }
        
        // Future Optimization Opportunities
        if (!empty($report->futureOptimizationOpportunities)) {
            $output[] = "## Future Optimization Opportunities";
            $output[] = "";
            
            foreach ($report->futureOptimizationOpportunities as $opportunity) {
                $output[] = "### {$opportunity['type']}";
                $output[] = "- **Description**: {$opportunity['description']}";
                $output[] = "- **Potential Impact**: {$opportunity['potential_impact']}";
                $output[] = "- **Estimated Effort**: {$opportunity['estimated_effort']}";
                $output[] = "";
            }
        }
        
        // Execution Summary
        if (!empty($report->executionSummary)) {
            $output[] = "## Execution Summary";
            $output[] = "";
            
            $summary = $report->executionSummary;
            $output[] = "- Total Operations: {$summary['total_operations']}";
            $output[] = "- Execution Time: {$summary['execution_time']}";
            $output[] = "- Memory Usage: {$summary['memory_usage']}";
            $output[] = "- Success Rate: {$summary['success_rate']}%";
            
            if (isset($summary['operations_per_second'])) {
                $output[] = "- Operations per Second: {$summary['operations_per_second']}";
            }
            $output[] = "";
        }
        
        return implode("\n", $output);
    }

    /**
     * Format summary report
     */
    private function formatSummaryReport(CleanupReport $report): string
    {
        $output = [];
        
        $output[] = "Cleanup Summary Report";
        $output[] = str_repeat("=", 50);
        $output[] = "";
        $output[] = "Files Removed: {$report->filesRemoved}";
        $output[] = "Lines Removed: {$report->linesRemoved}";
        $output[] = "Imports Removed: {$report->importsRemoved}";
        $output[] = "Methods Removed: {$report->methodsRemoved}";
        $output[] = "Duplicates Refactored: {$report->duplicatesRefactored}";
        $output[] = "Components Created: {$report->componentsCreated}";
        
        if ($report->sizeReductionMB > 0) {
            $output[] = "Size Reduction: {$report->sizeReductionMB} MB";
        }
        
        if (!empty($report->riskAssessments)) {
            $highRisks = array_filter($report->riskAssessments, fn($r) => ($r['level'] ?? '') === 'High');
            if (!empty($highRisks)) {
                $output[] = "";
                $output[] = "⚠️  High Risk Items: " . count($highRisks);
            }
        }
        
        return implode("\n", $output);
    }

    /**
     * Format JSON report
     */
    private function formatJsonReport(CleanupReport $report): string
    {
        return json_encode($report, JSON_PRETTY_PRINT);
    }

    /**
     * Format HTML report
     */
    private function formatHtmlReport(CleanupReport $report): string
    {
        $html = [];
        
        $html[] = "<!DOCTYPE html>";
        $html[] = "<html><head><title>Cleanup Report</title>";
        $html[] = "<style>";
        $html[] = "body { font-family: Arial, sans-serif; margin: 40px; }";
        $html[] = "h1, h2, h3 { color: #333; }";
        $html[] = ".metric { background: #f5f5f5; padding: 10px; margin: 5px 0; border-radius: 5px; }";
        $html[] = ".risk-high { color: #d32f2f; }";
        $html[] = ".risk-medium { color: #f57c00; }";
        $html[] = ".risk-low { color: #388e3c; }";
        $html[] = "</style></head><body>";
        
        $html[] = "<h1>Code Cleanup Report</h1>";
        $html[] = "<p>Generated: " . now()->format('Y-m-d H:i:s') . "</p>";
        
        $html[] = "<h2>Summary</h2>";
        $html[] = "<div class='metric'>Files Removed: <strong>{$report->filesRemoved}</strong></div>";
        $html[] = "<div class='metric'>Lines Removed: <strong>{$report->linesRemoved}</strong></div>";
        $html[] = "<div class='metric'>Imports Removed: <strong>{$report->importsRemoved}</strong></div>";
        $html[] = "<div class='metric'>Methods Removed: <strong>{$report->methodsRemoved}</strong></div>";
        $html[] = "<div class='metric'>Duplicates Refactored: <strong>{$report->duplicatesRefactored}</strong></div>";
        $html[] = "<div class='metric'>Components Created: <strong>{$report->componentsCreated}</strong></div>";
        
        if ($report->sizeReductionMB > 0) {
            $html[] = "<div class='metric'>Size Reduction: <strong>{$report->sizeReductionMB} MB</strong></div>";
        }
        
        if (!empty($report->riskAssessments)) {
            $html[] = "<h2>Risk Assessments</h2>";
            foreach ($report->riskAssessments as $risk) {
                $level = strtolower($risk['level'] ?? 'unknown');
                $description = htmlspecialchars($risk['description'] ?? 'No description');
                $html[] = "<div class='risk-{$level}'><strong>" . ucfirst($level) . " Risk:</strong> {$description}</div>";
            }
        }
        
        $html[] = "</body></html>";
        
        return implode("\n", $html);
    }

    /**
     * Check if section should be included
     */
    private function shouldIncludeSection(string $section): bool
    {
        $include = $this->option('include');
        $exclude = $this->option('exclude');
        
        if (!empty($exclude) && in_array($section, $exclude)) {
            return false;
        }
        
        if (!empty($include)) {
            return in_array($section, $include);
        }
        
        return true;
    }

    /**
     * Create sample report data for demonstration
     */
    private function createSampleReportData(): array
    {
        return [
            'plan' => [
                'files_to_delete' => ['unused1.php', 'unused2.js'],
                'imports_to_remove' => ['unused_import1', 'unused_import2'],
                'methods_to_remove' => ['unusedMethod1', 'unusedMethod2'],
                'duplicates_to_refactor' => ['duplicate1', 'duplicate2'],
                'estimated_size_reduction' => 2.5,
            ],
            'execution_results' => [
                'files_removed' => 2,
                'lines_removed' => 450,
                'imports_removed' => 15,
                'methods_removed' => 8,
                'duplicates_refactored' => 5,
                'components_created' => 3,
            ],
            'metrics' => [
                'execution_time' => 120,
                'memory_usage' => [['peak' => 67108864, 'current' => 33554432]], // 64MB peak, 32MB current
                'performance_improvements' => [
                    'file_size_reduction' => ['percentage' => 12.5, 'bytes' => 2621440],
                    'complexity_reduction' => ['percentage' => 8.3, 'cyclomatic' => 15],
                ],
            ],
            'operation_log' => [
                'operations' => [
                    ['type' => 'remove_import', 'file' => 'Controller.php', 'success' => true],
                    ['type' => 'remove_method', 'file' => 'Service.php', 'success' => true],
                ],
            ],
        ];
    }

    /**
     * Create CleanupPlan from data
     */
    private function createCleanupPlan(array $data): CleanupPlan
    {
        $plan = new CleanupPlan();
        $plan->filesToDelete = $data['files_to_delete'] ?? [];
        $plan->importsToRemove = $data['imports_to_remove'] ?? [];
        $plan->methodsToRemove = $data['methods_to_remove'] ?? [];
        $plan->duplicatesToRefactor = $data['duplicates_to_refactor'] ?? [];
        $plan->estimatedSizeReduction = $data['estimated_size_reduction'] ?? 0.0;
        
        return $plan;
    }

    /**
     * Create CleanupMetrics from data
     */
    private function createCleanupMetrics(array $data): CleanupMetrics
    {
        return new CleanupMetrics($data);
    }

    /**
     * Create OperationLog from data
     */
    private function createOperationLog(array $data): OperationLog
    {
        $log = new OperationLog();
        $log->operations = $data['operations'] ?? [];
        
        return $log;
    }
}