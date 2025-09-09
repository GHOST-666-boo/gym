<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use App\Services\Cleanup\CleanupOrchestrator;
use App\Services\Cleanup\Models\CleanupConfig;
use App\Services\Cleanup\Models\CodebaseAnalysis;
use App\Services\Cleanup\Models\CleanupPlan;
use App\Services\Cleanup\Models\CleanupReport;
use Mockery;

class CleanupInteractiveWorkflowTest extends TestCase
{

    private $mockOrchestrator;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockOrchestrator = Mockery::mock(CleanupOrchestrator::class);
        $this->app->instance(CleanupOrchestrator::class, $this->mockOrchestrator);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_run_interactive_cleanup_in_preview_mode()
    {
        // Mock the orchestrator methods
        $mockAnalysis = $this->createMockAnalysis();
        $mockPlan = $this->createMockCleanupPlan();
        $mockReport = $this->createMockCleanupReport();

        $this->mockOrchestrator
            ->shouldReceive('analyzeCodebase')
            ->once()
            ->andReturn($mockAnalysis);

        $this->mockOrchestrator
            ->shouldReceive('generateCleanupPlan')
            ->once()
            ->with($mockAnalysis)
            ->andReturn($mockPlan);

        $this->mockOrchestrator
            ->shouldReceive('executeCleanup')
            ->once()
            ->andReturn($mockReport);

        // Run the command with preview mode - should skip the first question since --preview is set
        $this->artisan('cleanup:interactive', ['--preview' => true])
            ->expectsChoice('Which file types should be included? (comma-separated)', ['php','js','css','blade.php'], ['php', 'js', 'css', 'blade.php'])
            ->expectsQuestion('Do you want to specify paths to analyze?', false)
            ->expectsQuestion('Do you want to exclude any paths?', false)
            ->expectsQuestion('Remove unused imports?', true)
            ->expectsQuestion('Remove unused methods?', true)
            ->expectsQuestion('Remove unused variables?', true)
            ->expectsQuestion('Refactor duplicate code?', true)
            ->expectsQuestion('Create components from duplicates?', true)
            ->expectsQuestion('Configure advanced settings?', false)
            ->expectsQuestion('Proceed with this configuration?', true)
            ->expectsQuestion('Proceed with preview analysis?', true)
            ->assertExitCode(0);
    }

    /** @test */
    public function it_can_run_interactive_cleanup_with_step_by_step_confirmation()
    {
        $mockAnalysis = $this->createMockAnalysis();
        $mockPlan = $this->createMockCleanupPlanWithItems();
        $mockReport = $this->createMockCleanupReport();

        $this->mockOrchestrator
            ->shouldReceive('analyzeCodebase')
            ->once()
            ->andReturn($mockAnalysis);

        $this->mockOrchestrator
            ->shouldReceive('generateCleanupPlan')
            ->once()
            ->andReturn($mockPlan);

        $this->mockOrchestrator
            ->shouldReceive('executeCleanup')
            ->once()
            ->andReturn($mockReport);

        $this->artisan('cleanup:interactive')
            ->expectsQuestion('Run in preview mode (no changes will be made)?', false)
            ->expectsQuestion('Create backup before cleanup?', true)
            ->expectsQuestion('Run tests after cleanup to validate changes?', true)
            ->expectsQuestion('Which file types should be included? (comma-separated)', 'php')
            ->expectsQuestion('Do you want to specify paths to analyze?', false)
            ->expectsQuestion('Do you want to exclude any paths?', false)
            ->expectsQuestion('Remove unused imports?', true)
            ->expectsQuestion('Remove unused methods?', false)
            ->expectsQuestion('Remove unused variables?', false)
            ->expectsQuestion('Refactor duplicate code?', false)
            ->expectsQuestion('Create components from duplicates?', false)
            ->expectsQuestion('Configure advanced settings?', false)
            ->expectsQuestion('Proceed with this configuration?', true)
            ->expectsQuestion('Approve import removals for app/Models/TestModel.php?', true)
            ->expectsQuestion('Do you want to see a detailed preview first?', true)
            ->expectsQuestion('Are you absolutely sure you want to proceed with these changes?', true)
            ->assertExitCode(0);
    }

    /** @test */
    public function it_can_cancel_cleanup_during_step_by_step_review()
    {
        $mockAnalysis = $this->createMockAnalysis();
        $mockPlan = $this->createMockCleanupPlanWithItems();

        $this->mockOrchestrator
            ->shouldReceive('analyzeCodebase')
            ->once()
            ->andReturn($mockAnalysis);

        $this->mockOrchestrator
            ->shouldReceive('generateCleanupPlan')
            ->once()
            ->andReturn($mockPlan);

        // Should not call executeCleanup since we're cancelling
        $this->mockOrchestrator
            ->shouldNotReceive('executeCleanup');

        $this->artisan('cleanup:interactive')
            ->expectsQuestion('Run in preview mode (no changes will be made)?', false)
            ->expectsQuestion('Create backup before cleanup?', true)
            ->expectsQuestion('Run tests after cleanup to validate changes?', true)
            ->expectsQuestion('Which file types should be included? (comma-separated)', 'php')
            ->expectsQuestion('Do you want to specify paths to analyze?', false)
            ->expectsQuestion('Do you want to exclude any paths?', false)
            ->expectsQuestion('Remove unused imports?', true)
            ->expectsQuestion('Remove unused methods?', false)
            ->expectsQuestion('Remove unused variables?', false)
            ->expectsQuestion('Refactor duplicate code?', false)
            ->expectsQuestion('Create components from duplicates?', false)
            ->expectsQuestion('Configure advanced settings?', false)
            ->expectsQuestion('Proceed with this configuration?', true)
            ->expectsQuestion('Approve import removals for app/Models/TestModel.php?', false)
            ->assertExitCode(0)
            ->expectsOutput('Cleanup cancelled by user.');
    }

    /** @test */
    public function it_can_load_configuration_from_file()
    {
        // Create a temporary config file
        $configPath = storage_path('test-cleanup-config.json');
        $config = [
            'dryRun' => true,
            'includeFileTypes' => ['php'],
            'removeUnusedImports' => true,
            'removeUnusedMethods' => false,
        ];
        file_put_contents($configPath, json_encode($config));

        $mockAnalysis = $this->createMockAnalysis();
        $mockPlan = $this->createMockCleanupPlan();
        $mockReport = $this->createMockCleanupReport();

        $this->mockOrchestrator
            ->shouldReceive('analyzeCodebase')
            ->once()
            ->andReturn($mockAnalysis);

        $this->mockOrchestrator
            ->shouldReceive('generateCleanupPlan')
            ->once()
            ->andReturn($mockPlan);

        $this->mockOrchestrator
            ->shouldReceive('executeCleanup')
            ->once()
            ->andReturn($mockReport);

        try {
            $this->artisan('cleanup:interactive', ['--config' => $configPath])
                ->expectsQuestion('Which file types should be included? (comma-separated)', 'php')
                ->expectsQuestion('Do you want to specify paths to analyze?', false)
                ->expectsQuestion('Do you want to exclude any paths?', false)
                ->expectsQuestion('Remove unused imports?', true)
                ->expectsQuestion('Remove unused methods?', false)
                ->expectsQuestion('Remove unused variables?', true)
                ->expectsQuestion('Refactor duplicate code?', true)
                ->expectsQuestion('Create components from duplicates?', true)
                ->expectsQuestion('Configure advanced settings?', false)
                ->expectsQuestion('Proceed with this configuration?', true)
                ->expectsQuestion('Proceed with preview analysis?', true)
                ->assertExitCode(0);
        } finally {
            // Clean up the test config file
            if (file_exists($configPath)) {
                unlink($configPath);
            }
        }
    }

    /** @test */
    public function it_handles_invalid_configuration_file()
    {
        $invalidConfigPath = storage_path('invalid-config.json');
        file_put_contents($invalidConfigPath, 'invalid json content');

        try {
            $this->artisan('cleanup:interactive', ['--config' => $invalidConfigPath])
                ->assertExitCode(1)
                ->expectsOutput('Interactive cleanup failed: Invalid JSON in configuration file: Syntax error');
        } finally {
            if (file_exists($invalidConfigPath)) {
                unlink($invalidConfigPath);
            }
        }
    }

    /** @test */
    public function it_handles_missing_configuration_file()
    {
        $missingConfigPath = storage_path('missing-config.json');

        $this->artisan('cleanup:interactive', ['--config' => $missingConfigPath])
            ->assertExitCode(1)
            ->expectsOutput("Interactive cleanup failed: Configuration file not found: {$missingConfigPath}");
    }

    /** @test */
    public function it_can_configure_advanced_settings()
    {
        $mockAnalysis = $this->createMockAnalysis();
        $mockPlan = $this->createMockCleanupPlan();
        $mockReport = $this->createMockCleanupReport();

        $this->mockOrchestrator
            ->shouldReceive('analyzeCodebase')
            ->once()
            ->andReturn($mockAnalysis);

        $this->mockOrchestrator
            ->shouldReceive('generateCleanupPlan')
            ->once()
            ->andReturn($mockPlan);

        $this->mockOrchestrator
            ->shouldReceive('executeCleanup')
            ->once()
            ->andReturn($mockReport);

        $this->artisan('cleanup:interactive', ['--preview' => true])
            ->expectsQuestion('Run in preview mode (no changes will be made)?', true)
            ->expectsQuestion('Which file types should be included? (comma-separated)', 'php,js')
            ->expectsQuestion('Do you want to specify paths to analyze?', true)
            ->expectsQuestion('Enter paths to analyze (comma-separated)', 'app/Models,app/Services')
            ->expectsQuestion('Do you want to exclude any paths?', true)
            ->expectsQuestion('Enter paths to exclude (comma-separated)', 'vendor,node_modules,tests')
            ->expectsQuestion('Remove unused imports?', true)
            ->expectsQuestion('Remove unused methods?', true)
            ->expectsQuestion('Remove unused variables?', true)
            ->expectsQuestion('Refactor duplicate code?', true)
            ->expectsQuestion('Create components from duplicates?', true)
            ->expectsQuestion('Configure advanced settings?', true)
            ->expectsQuestion('Batch size for processing files', 25)
            ->expectsQuestion('Maximum file size to process (MB)', 2)
            ->expectsQuestion('Proceed with this configuration?', true)
            ->expectsQuestion('Proceed with preview analysis?', true)
            ->assertExitCode(0);
    }

    /**
     * Create a mock codebase analysis
     */
    private function createMockAnalysis(): CodebaseAnalysis
    {
        $analysis = new CodebaseAnalysis();
        $analysis->phpFiles = [
            (object) [
                'filePath' => 'app/Models/TestModel.php',
                'unusedImports' => ['Illuminate\Support\Str'],
                'unusedMethods' => [],
                'duplicateMethods' => []
            ]
        ];
        $analysis->jsFiles = [];
        $analysis->cssFiles = [];
        $analysis->bladeFiles = [];
        $analysis->orphanedFiles = [];
        
        return $analysis;
    }

    /**
     * Create a mock cleanup plan
     */
    private function createMockCleanupPlan(): CleanupPlan
    {
        return new CleanupPlan([
            'filesToDelete' => [],
            'importsToRemove' => [],
            'methodsToRemove' => [],
            'variablesToRemove' => [],
            'duplicatesToRefactor' => [],
            'componentsToCreate' => [],
            'estimatedSizeReduction' => 0.5
        ]);
    }

    /**
     * Create a mock cleanup plan with items
     */
    private function createMockCleanupPlanWithItems(): CleanupPlan
    {
        return new CleanupPlan([
            'filesToDelete' => [],
            'importsToRemove' => [
                ['file' => 'app/Models/TestModel.php', 'import' => 'Illuminate\Support\Str']
            ],
            'methodsToRemove' => [],
            'variablesToRemove' => [],
            'duplicatesToRefactor' => [],
            'componentsToCreate' => [],
            'estimatedSizeReduction' => 0.1
        ]);
    }

    /**
     * Create a mock cleanup report
     */
    private function createMockCleanupReport(): CleanupReport
    {
        $report = new CleanupReport();
        $report->filesRemoved = 0;
        $report->linesRemoved = 15;
        $report->importsRemoved = 3;
        $report->methodsRemoved = 0;
        $report->duplicatesRefactored = 0;
        $report->componentsCreated = 0;
        $report->sizeReductionMB = 0.1;
        $report->performanceImprovements = [];
        $report->maintenanceRecommendations = [];
        $report->riskAssessments = [];
        
        return $report;
    }
}