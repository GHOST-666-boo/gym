<?php

namespace Tests\Unit\Services\Cleanup;

use Tests\TestCase;
use App\Services\Cleanup\ReportGenerator;
use App\Services\Cleanup\MaintenanceRecommendationEngine;
use App\Services\Cleanup\RiskAssessmentEngine;
use App\Services\Cleanup\Models\CleanupPlan;
use App\Services\Cleanup\Models\CleanupReport;
use App\Services\Cleanup\Models\CleanupMetrics;
use App\Services\Cleanup\Models\OperationLog;

class ReportGeneratorTest extends TestCase
{
    private ReportGenerator $reportGenerator;
    private MaintenanceRecommendationEngine $recommendationEngine;
    private RiskAssessmentEngine $riskAssessmentEngine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->recommendationEngine = $this->createMock(MaintenanceRecommendationEngine::class);
        $this->riskAssessmentEngine = $this->createMock(RiskAssessmentEngine::class);
        $this->reportGenerator = new ReportGenerator($this->recommendationEngine, $this->riskAssessmentEngine);
    }

    public function test_generates_comprehensive_cleanup_report()
    {
        $plan = new CleanupPlan();
        $executionResults = [
            'files_removed' => 15,
            'lines_removed' => 500,
            'imports_removed' => 25,
            'methods_removed' => 8,
            'duplicates_refactored' => 3,
            'components_created' => 2,
        ];

        $metrics = new CleanupMetrics([
            'performance_improvements' => [
                'file_size_reduction' => ['mb' => 2.5, 'percentage' => 10.0],
                'complexity_reduction' => ['percentage' => 15.0],
            ],
        ]);

        $this->recommendationEngine
            ->expects($this->once())
            ->method('generateRecommendations')
            ->willReturn([]);

        $this->riskAssessmentEngine
            ->expects($this->once())
            ->method('assessRisks')
            ->willReturn([]);

        $report = $this->reportGenerator->generateReport($plan, $executionResults, $metrics);

        $this->assertInstanceOf(CleanupReport::class, $report);
        $this->assertEquals(15, $report->filesRemoved);
        $this->assertEquals(500, $report->linesRemoved);
        $this->assertEquals(25, $report->importsRemoved);
        $this->assertEquals(8, $report->methodsRemoved);
        $this->assertEquals(3, $report->duplicatesRefactored);
        $this->assertEquals(2, $report->componentsCreated);
        $this->assertEquals(2.5, $report->sizeReductionMB);
    }

    public function test_calculates_performance_improvements()
    {
        $beforeMetrics = [
            'total_file_size' => 1000000,
            'total_files' => 100,
            'cyclomatic_complexity' => 200,
            'total_lines' => 5000,
        ];

        $afterMetrics = [
            'total_file_size' => 800000,
            'total_files' => 85,
            'cyclomatic_complexity' => 170,
            'total_lines' => 4200,
        ];

        $improvements = $this->reportGenerator->calculatePerformanceImprovements($beforeMetrics, $afterMetrics);

        $this->assertArrayHasKey('file_size', $improvements);
        $this->assertArrayHasKey('file_count', $improvements);
        $this->assertArrayHasKey('complexity', $improvements);
        $this->assertArrayHasKey('lines_of_code', $improvements);

        // Check file size improvements
        $this->assertEquals(200000, $improvements['file_size']['bytes_reduced']);
        $this->assertEquals(20.0, $improvements['file_size']['percentage_reduction']);

        // Check file count improvements
        $this->assertEquals(15, $improvements['file_count']['files_removed']);
        $this->assertEquals(15.0, $improvements['file_count']['percentage_reduction']);

        // Check complexity improvements
        $this->assertEquals(30, $improvements['complexity']['complexity_reduced']);
        $this->assertEquals(15.0, $improvements['complexity']['percentage_reduction']);
    }

    public function test_generates_maintenance_recommendations()
    {
        $plan = new CleanupPlan();
        $executionResults = ['files_removed' => 5];

        $expectedRecommendations = [
            [
                'type' => 'code_organization',
                'priority' => 'medium',
                'title' => 'Test Recommendation',
            ],
        ];

        $this->recommendationEngine
            ->expects($this->once())
            ->method('generateRecommendations')
            ->with($plan, $executionResults)
            ->willReturn($expectedRecommendations);

        $recommendations = $this->reportGenerator->generateMaintenanceRecommendations($plan, $executionResults);

        $this->assertEquals($expectedRecommendations, $recommendations);
    }

    public function test_generates_risk_assessments()
    {
        $plan = new CleanupPlan();
        $executionResults = ['files_removed' => 20];
        $operationLog = new OperationLog();

        $expectedRisks = [
            [
                'type' => 'file_deletion',
                'severity' => 'high',
                'title' => 'Test Risk',
            ],
        ];

        $this->riskAssessmentEngine
            ->expects($this->once())
            ->method('assessRisks')
            ->with($plan, $executionResults, $operationLog)
            ->willReturn($expectedRisks);

        $risks = $this->reportGenerator->generateRiskAssessments($plan, $executionResults, $operationLog);

        $this->assertEquals($expectedRisks, $risks);
    }

    public function test_cleanup_report_model_functionality()
    {
        $reportData = [
            'filesRemoved' => 10,
            'linesRemoved' => 200,
            'importsRemoved' => 15,
            'methodsRemoved' => 5,
            'duplicatesRefactored' => 2,
            'componentsCreated' => 1,
            'sizeReductionMB' => 1.5,
            'maintenanceRecommendations' => [
                ['priority' => 'high', 'title' => 'High Priority Item'],
                ['priority' => 'medium', 'title' => 'Medium Priority Item'],
            ],
            'riskAssessments' => [
                ['severity' => 'critical', 'title' => 'Critical Risk'],
                ['severity' => 'low', 'title' => 'Low Risk'],
            ],
            'executionSummary' => ['success_rate' => 95.0],
        ];

        $report = new CleanupReport($reportData);

        $this->assertEquals(33, $report->getTotalItemsProcessed());
        $this->assertEquals(95.0, $report->getSuccessRate());
        $this->assertCount(1, $report->getHighPriorityRecommendations());
        $this->assertCount(1, $report->getCriticalRisks());

        $impactSummary = $report->getImpactSummary();
        $this->assertEquals(33, $impactSummary['files_processed']);
        $this->assertEquals(1.5, $impactSummary['size_reduction_mb']);
        $this->assertEquals(95.0, $impactSummary['success_rate']);
    }

    public function test_report_serialization()
    {
        $report = new CleanupReport([
            'filesRemoved' => 5,
            'linesRemoved' => 100,
            'sizeReductionMB' => 0.5,
        ]);

        $array = $report->toArray();
        $this->assertIsArray($array);
        $this->assertEquals(5, $array['files_removed']);
        $this->assertEquals(100, $array['lines_removed']);
        $this->assertEquals(0.5, $array['size_reduction_mb']);

        $json = $report->toJson();
        $this->assertIsString($json);
        $decoded = json_decode($json, true);
        $this->assertEquals(5, $decoded['files_removed']);
    }

    public function test_handles_empty_metrics_gracefully()
    {
        $plan = new CleanupPlan();
        $executionResults = ['files_removed' => 1];

        $this->recommendationEngine
            ->expects($this->once())
            ->method('generateRecommendations')
            ->willReturn([]);

        $this->riskAssessmentEngine
            ->expects($this->once())
            ->method('assessRisks')
            ->willReturn([]);

        $report = $this->reportGenerator->generateReport($plan, $executionResults);

        $this->assertInstanceOf(CleanupReport::class, $report);
        $this->assertEquals(1, $report->filesRemoved);
        $this->assertEquals(0.0, $report->sizeReductionMB);
        $this->assertEmpty($report->performanceImprovements);
    }

    public function test_calculates_percentage_reduction_correctly()
    {
        $beforeMetrics = ['total_files' => 100];
        $afterMetrics = ['total_files' => 80];

        $improvements = $this->reportGenerator->calculatePerformanceImprovements($beforeMetrics, $afterMetrics);

        $this->assertEquals(20.0, $improvements['file_count']['percentage_reduction']);
    }

    public function test_handles_zero_before_metrics()
    {
        $beforeMetrics = ['total_files' => 0];
        $afterMetrics = ['total_files' => 0];

        $improvements = $this->reportGenerator->calculatePerformanceImprovements($beforeMetrics, $afterMetrics);

        $this->assertEquals(0.0, $improvements['file_count']['percentage_reduction']);
    }
}