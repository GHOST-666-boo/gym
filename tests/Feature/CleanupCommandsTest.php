<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\Cleanup\CleanupOrchestrator;
use App\Services\Cleanup\ReportGenerator;
use App\Services\Cleanup\Models\CodebaseAnalysis;
use App\Services\Cleanup\Models\CleanupReport;
use App\Services\Cleanup\Models\CleanupConfig;

class CleanupCommandsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock the cleanup services to avoid actual file operations during tests
        $this->mockCleanupServices();
    }

    /** @test */
    public function cleanup_analyze_command_runs_successfully()
    {
        $this->artisan('cleanup:analyze')
            ->expectsOutput('Starting codebase analysis...')
            ->expectsOutput('Analysis completed successfully!')
            ->assertExitCode(0);
    }

    /** @test */
    public function cleanup_analyze_command_accepts_path_option()
    {
        $this->artisan('cleanup:analyze --path=app/Models')
            ->expectsOutput('Starting codebase analysis...')
            ->assertExitCode(0);
    }

    /** @test */
    public function cleanup_analyze_command_accepts_exclude_option()
    {
        $this->artisan('cleanup:analyze --exclude=vendor --exclude=node_modules')
            ->expectsOutput('Starting codebase analysis...')
            ->assertExitCode(0);
    }

    /** @test */
    public function cleanup_analyze_command_accepts_types_option()
    {
        $this->artisan('cleanup:analyze --types=php --types=js')
            ->expectsOutput('Starting codebase analysis...')
            ->assertExitCode(0);
    }

    /** @test */
    public function cleanup_analyze_command_supports_json_output()
    {
        $this->artisan('cleanup:analyze --output=json')
            ->expectsOutput('Starting codebase analysis...')
            ->assertExitCode(0);
    }

    /** @test */
    public function cleanup_analyze_command_can_save_results()
    {
        $outputFile = storage_path('app/test_analysis.json');
        
        // Ensure file doesn't exist before test
        if (file_exists($outputFile)) {
            unlink($outputFile);
        }
        
        $this->artisan("cleanup:analyze --save={$outputFile}")
            ->expectsOutput('Starting codebase analysis...')
            ->expectsOutput("Analysis results saved to: {$outputFile}")
            ->assertExitCode(0);
        
        // Clean up
        if (file_exists($outputFile)) {
            unlink($outputFile);
        }
    }

    /** @test */
    public function cleanup_execute_command_runs_in_dry_run_mode()
    {
        $this->artisan('cleanup:execute --dry-run --force')
            ->expectsOutput('Starting cleanup execution...')
            ->expectsOutput('Cleanup completed successfully!')
            ->assertExitCode(0);
    }

    /** @test */
    public function cleanup_execute_command_accepts_configuration_options()
    {
        $this->artisan('cleanup:execute --dry-run --no-backup --no-tests --force')
            ->expectsOutput('Starting cleanup execution...')
            ->assertExitCode(0);
    }

    /** @test */
    public function cleanup_execute_command_accepts_specific_operations()
    {
        $this->artisan('cleanup:execute --dry-run --imports --methods --force')
            ->expectsOutput('Starting cleanup execution...')
            ->assertExitCode(0);
    }

    /** @test */
    public function cleanup_execute_command_accepts_path_filtering()
    {
        $this->artisan('cleanup:execute --dry-run --path=app/Services --exclude=vendor --force')
            ->expectsOutput('Starting cleanup execution...')
            ->assertExitCode(0);
    }

    /** @test */
    public function cleanup_execute_command_accepts_batch_size()
    {
        $this->artisan('cleanup:execute --dry-run --batch-size=25 --force')
            ->expectsOutput('Starting cleanup execution...')
            ->assertExitCode(0);
    }

    /** @test */
    public function cleanup_execute_command_requires_confirmation_without_force()
    {
        $this->artisan('cleanup:execute --dry-run')
            ->expectsQuestion('Proceed with dry run analysis?', false)
            ->expectsOutput('Cleanup cancelled by user.')
            ->assertExitCode(0);
    }

    /** @test */
    public function cleanup_report_command_generates_default_report()
    {
        $this->artisan('cleanup:report')
            ->expectsOutput('Generating cleanup report...')
            ->expectsOutput('Report generated successfully!')
            ->assertExitCode(0);
    }

    /** @test */
    public function cleanup_report_command_supports_different_formats()
    {
        $this->artisan('cleanup:report --format=summary')
            ->expectsOutput('Generating cleanup report...')
            ->assertExitCode(0);

        $this->artisan('cleanup:report --format=json')
            ->expectsOutput('Generating cleanup report...')
            ->assertExitCode(0);

        $this->artisan('cleanup:report --format=html')
            ->expectsOutput('Generating cleanup report...')
            ->assertExitCode(0);
    }

    /** @test */
    public function cleanup_report_command_can_save_to_file()
    {
        $outputFile = storage_path('app/test_report.md');
        
        // Ensure file doesn't exist before test
        if (file_exists($outputFile)) {
            unlink($outputFile);
        }
        
        $this->artisan("cleanup:report --output={$outputFile}")
            ->expectsOutput('Generating cleanup report...')
            ->expectsOutput("Report saved to: {$outputFile}")
            ->assertExitCode(0);
        
        $this->assertFileExists($outputFile);
        
        // Clean up
        if (file_exists($outputFile)) {
            unlink($outputFile);
        }
    }

    /** @test */
    public function cleanup_report_command_supports_section_filtering()
    {
        $this->artisan('cleanup:report --include=summary --include=performance')
            ->expectsOutput('Generating cleanup report...')
            ->assertExitCode(0);

        $this->artisan('cleanup:report --exclude=risks')
            ->expectsOutput('Generating cleanup report...')
            ->assertExitCode(0);
    }

    /** @test */
    public function cleanup_report_command_handles_input_file()
    {
        // Create a test input file
        $inputFile = storage_path('app/test_input.json');
        $testData = [
            'plan' => [
                'files_to_delete' => ['test.php'],
                'imports_to_remove' => ['unused_import'],
                'methods_to_remove' => ['unusedMethod'],
                'duplicates_to_refactor' => ['duplicate'],
                'estimated_size_reduction' => 1.0,
            ],
            'execution_results' => ['files_removed' => 1],
            'metrics' => [
                'execution_time' => 60,
                'memory_usage' => [['peak' => 33554432, 'current' => 16777216]], // 32MB peak, 16MB current
            ],
            'operation_log' => ['operations' => []],
        ];
        
        file_put_contents($inputFile, json_encode($testData));
        
        $this->artisan("cleanup:report --input={$inputFile}")
            ->expectsOutput('Generating cleanup report...')
            ->expectsOutput('Report generated successfully!')
            ->assertExitCode(0);
        
        // Clean up
        if (file_exists($inputFile)) {
            unlink($inputFile);
        }
    }

    /** @test */
    public function cleanup_report_command_handles_invalid_input_file()
    {
        $this->artisan('cleanup:report --input=nonexistent.json')
            ->expectsOutput('Report generation failed: Input file not found: nonexistent.json')
            ->assertExitCode(1);
    }

    /** @test */
    public function cleanup_commands_handle_exceptions_gracefully()
    {
        // Mock the orchestrator to throw an exception
        $this->app->bind(CleanupOrchestrator::class, function () {
            $mock = $this->createMock(CleanupOrchestrator::class);
            $mock->method('analyzeCodebase')
                ->willThrowException(new \Exception('Test exception'));
            return $mock;
        });

        $this->artisan('cleanup:analyze')
            ->expectsOutput('Analysis failed: Test exception')
            ->assertExitCode(1);
    }

    /** @test */
    public function cleanup_commands_show_verbose_errors_when_requested()
    {
        // Mock the orchestrator to throw an exception
        $this->app->bind(CleanupOrchestrator::class, function () {
            $mock = $this->createMock(CleanupOrchestrator::class);
            $mock->method('analyzeCodebase')
                ->willThrowException(new \Exception('Test exception'));
            return $mock;
        });

        $this->artisan('cleanup:analyze -v')
            ->expectsOutput('Analysis failed: Test exception')
            ->assertExitCode(1);
    }

    /**
     * Mock cleanup services to avoid actual file operations during tests
     */
    private function mockCleanupServices(): void
    {
        // Mock CleanupOrchestrator
        $this->app->bind(CleanupOrchestrator::class, function () {
            $mock = $this->createMock(CleanupOrchestrator::class);
            
            // Mock analyzeCodebase method
            $analysis = new CodebaseAnalysis();
            $analysis->phpFiles = [];
            $analysis->jsFiles = [];
            $analysis->cssFiles = [];
            $analysis->bladeFiles = [];
            $analysis->assetFiles = [];
            $analysis->routeDefinitions = [];
            $analysis->orphanedFiles = [];
            
            $mock->method('analyzeCodebase')->willReturn($analysis);
            
            // Mock executeCleanup method
            $report = new CleanupReport();
            $report->filesRemoved = 0;
            $report->linesRemoved = 0;
            $report->importsRemoved = 0;
            $report->methodsRemoved = 0;
            $report->duplicatesRefactored = 0;
            $report->componentsCreated = 0;
            $report->sizeReductionMB = 0.0;
            $report->performanceImprovements = [];
            $report->riskAssessments = [];
            $report->maintenanceRecommendations = [];
            $report->executionSummary = [
                'total_operations' => 0,
                'execution_time' => '0s',
                'memory_usage' => '0MB',
                'success_rate' => 100.0,
            ];
            
            $mock->method('executeCleanup')->willReturn($report);
            
            return $mock;
        });

        // Mock ReportGenerator
        $this->app->bind(ReportGenerator::class, function () {
            $mock = $this->createMock(ReportGenerator::class);
            
            $report = new CleanupReport();
            $report->filesRemoved = 2;
            $report->linesRemoved = 450;
            $report->importsRemoved = 15;
            $report->methodsRemoved = 8;
            $report->duplicatesRefactored = 5;
            $report->componentsCreated = 3;
            $report->sizeReductionMB = 2.5;
            $report->performanceImprovements = [
                'file_size_reduction' => [
                    'description' => 'Reduction in total file size',
                    'value' => ['percentage' => 12.5],
                    'impact_level' => 'Medium',
                ],
            ];
            $report->riskAssessments = [];
            $report->maintenanceRecommendations = [
                'Consider implementing automated code quality checks',
                'Set up regular cleanup schedules',
            ];
            $report->executionSummary = [
                'total_operations' => 33,
                'execution_time' => '2m 15s',
                'memory_usage' => '64MB',
                'success_rate' => 100.0,
            ];
            
            $mock->method('generateReport')->willReturn($report);
            
            return $mock;
        });
    }
}