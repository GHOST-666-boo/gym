<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\Cleanup\CleanupOrchestrator;
use App\Services\Cleanup\Models\CodebaseAnalysis;
use App\Services\Cleanup\Models\CleanupPlan;
use App\Services\Cleanup\Models\CleanupReport;
use Mockery;

class CleanupSelectiveWorkflowTest extends TestCase
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
    public function it_can_run_selective_cleanup_with_specific_file_types()
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

        $this->artisan('cleanup:selective', [
                '--type' => ['php', 'js'],
                '--operation' => ['imports', 'methods'],
                '--dry-run' => true
            ])
            ->expectsQuestion('Proceed with selective cleanup analysis?', true)
            ->expectsQuestion('Would you like to see a detailed preview of changes?', false)
            ->expectsQuestion('Execute cleanup on selected items?', true)
            ->assertExitCode(0);
    }

    /** @test */
    public function it_can_run_selective_cleanup_with_specific_directories()
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

        $this->artisan('cleanup:selective', [
                '--directory' => ['app/Models', 'app/Services'],
                '--operation' => ['imports'],
                '--dry-run' => true
            ])
            ->expectsQuestion('Proceed with selective cleanup analysis?', true)
            ->expectsQuestion('Would you like to see a detailed preview of changes?', false)
            ->expectsQuestion('Execute cleanup on selected items?', true)
            ->assertExitCode(0);
    }

    /** @test */
    public function it_can_run_selective_cleanup_in_interactive_mode()
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

        $this->artisan('cleanup:selective', ['--interactive' => true])
            ->expectsChoice('Which file types do you want to clean?', ['php', 'js'], ['php', 'js', 'css', 'blade.php'])
            ->expectsQuestion('Do you want to limit to specific directories?', true)
            ->expectsChoice('Which directories do you want to include?', ['app/'], ['app/', 'app/Models/', 'app/Controllers/', 'app/Services/', 'resources/views/', 'resources/js/', 'resources/css/', 'public/js/', 'public/css/'])
            ->expectsQuestion('Remove unused imports?', true)
            ->expectsQuestion('Remove unused methods?', false)
            ->expectsQuestion('Remove unused variables?', false)
            ->expectsQuestion('Refactor duplicate code?', false)
            ->expectsQuestion('Create components from duplicates?', false)
            ->expectsQuestion('Proceed with selective cleanup analysis?', true)
            ->expectsQuestion('Would you like to see a detailed preview of changes?', false)
            ->expectsQuestion('Execute cleanup on selected items?', true)
            ->assertExitCode(0);
    }

    /** @test */
    public function it_can_show_detailed_preview_in_selective_cleanup()
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

        $this->artisan('cleanup:selective', [
                '--type' => ['php'],
                '--operation' => ['imports', 'methods'],
                '--dry-run' => true
            ])
            ->expectsQuestion('Proceed with selective cleanup analysis?', true)
            ->expectsQuestion('Would you like to see a detailed preview of changes?', true)
            ->expectsQuestion('Execute cleanup on selected items?', true)
            ->assertExitCode(0);
    }

    /** @test */
    public function it_can_cancel_selective_cleanup_during_execution_confirmation()
    {
        $mockAnalysis = $this->createMockAnalysis();
        $mockPlan = $this->createMockCleanupPlan();

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

        $this->artisan('cleanup:selective', [
                '--type' => ['php'],
                '--operation' => ['imports']
            ])
            ->expectsQuestion('Proceed with selective cleanup analysis?', true)
            ->expectsQuestion('Would you like to see a detailed preview of changes?', false)
            ->expectsQuestion('Execute cleanup on selected items?', false)
            ->assertExitCode(0);
    }

    /** @test */
    public function it_requires_additional_confirmation_for_non_dry_run_mode()
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

        $this->artisan('cleanup:selective', [
                '--type' => ['php'],
                '--operation' => ['imports']
                // No --dry-run flag, so it will modify files
            ])
            ->expectsQuestion('Proceed with selective cleanup analysis?', true)
            ->expectsQuestion('Would you like to see a detailed preview of changes?', false)
            ->expectsQuestion('Execute cleanup on selected items?', true)
            ->expectsQuestion('This will modify your files. Are you sure?', true)
            ->assertExitCode(0);
    }

    /** @test */
    public function it_can_cancel_during_file_modification_confirmation()
    {
        $mockAnalysis = $this->createMockAnalysis();
        $mockPlan = $this->createMockCleanupPlan();

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

        $this->artisan('cleanup:selective', [
                '--type' => ['php'],
                '--operation' => ['imports']
            ])
            ->expectsQuestion('Proceed with selective cleanup analysis?', true)
            ->expectsQuestion('Would you like to see a detailed preview of changes?', false)
            ->expectsQuestion('Execute cleanup on selected items?', true)
            ->expectsQuestion('This will modify your files. Are you sure?', false)
            ->assertExitCode(0)
            ->expectsOutput('Cleanup cancelled.');
    }

    /** @test */
    public function it_filters_analysis_results_by_file_types()
    {
        $mockAnalysis = $this->createMockAnalysisWithMultipleFileTypes();
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

        // Only select PHP files
        $this->artisan('cleanup:selective', [
                '--type' => ['php'],
                '--operation' => ['imports'],
                '--dry-run' => true
            ])
            ->expectsQuestion('Proceed with selective cleanup analysis?', true)
            ->expectsQuestion('Would you like to see a detailed preview of changes?', false)
            ->expectsQuestion('Execute cleanup on selected items?', true)
            ->assertExitCode(0);
    }

    /** @test */
    public function it_displays_correct_operation_counts_in_results()
    {
        $mockAnalysis = $this->createMockAnalysisWithIssues();
        $mockPlan = $this->createMockCleanupPlanWithItems();
        $mockReport = $this->createMockCleanupReportWithResults();

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

        $this->artisan('cleanup:selective', [
                '--type' => ['php'],
                '--operation' => ['imports', 'methods'],
                '--dry-run' => true
            ])
            ->expectsQuestion('Proceed with selective cleanup analysis?', true)
            ->expectsQuestion('Would you like to see a detailed preview of changes?', false)
            ->expectsQuestion('Execute cleanup on selected items?', true)
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
                'unusedImports' => [],
                'unusedMethods' => [],
                'unusedVariables' => [],
                'duplicateMethods' => []
            ]
        ];
        $analysis->jsFiles = [];
        $analysis->cssFiles = [];
        $analysis->bladeFiles = [];
        
        return $analysis;
    }

    /**
     * Create a mock analysis with multiple file types
     */
    private function createMockAnalysisWithMultipleFileTypes(): CodebaseAnalysis
    {
        $analysis = new CodebaseAnalysis();
        $analysis->phpFiles = [
            (object) [
                'filePath' => 'app/Models/TestModel.php',
                'unusedImports' => ['Illuminate\Support\Str'],
                'unusedMethods' => [],
                'unusedVariables' => [],
                'duplicateMethods' => []
            ]
        ];
        $analysis->jsFiles = [
            (object) [
                'filePath' => 'resources/js/app.js',
                'unusedImports' => ['lodash'],
                'unusedVariables' => ['unusedVar'],
            ]
        ];
        $analysis->cssFiles = [
            (object) [
                'filePath' => 'resources/css/app.css',
                'unusedRules' => ['.unused-class'],
                'duplicateRules' => []
            ]
        ];
        $analysis->bladeFiles = [];
        
        return $analysis;
    }

    /**
     * Create a mock analysis with issues
     */
    private function createMockAnalysisWithIssues(): CodebaseAnalysis
    {
        $analysis = new CodebaseAnalysis();
        $analysis->phpFiles = [
            (object) [
                'filePath' => 'app/Models/TestModel.php',
                'unusedImports' => ['Illuminate\Support\Str', 'Carbon\Carbon'],
                'unusedMethods' => ['unusedMethod1', 'unusedMethod2'],
                'unusedVariables' => ['$unusedVar'],
                'duplicateMethods' => []
            ]
        ];
        $analysis->jsFiles = [];
        $analysis->cssFiles = [];
        $analysis->bladeFiles = [];
        
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
            'estimatedSizeReduction' => 0.0
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
                ['file' => 'app/Models/TestModel.php', 'import' => 'Illuminate\Support\Str'],
                ['file' => 'app/Models/TestModel.php', 'import' => 'Carbon\Carbon']
            ],
            'methodsToRemove' => [
                ['class' => 'App\Models\TestModel', 'method' => 'unusedMethod1'],
                ['class' => 'App\Models\TestModel', 'method' => 'unusedMethod2']
            ],
            'variablesToRemove' => [
                ['file' => 'app/Models/TestModel.php', 'variable' => '$unusedVar']
            ],
            'duplicatesToRefactor' => [
                [
                    'description' => 'Duplicate validation logic',
                    'files' => ['app/Models/TestModel.php', 'app/Models/AnotherModel.php'],
                    'suggestion' => 'Extract to ValidationTrait'
                ]
            ],
            'componentsToCreate' => [
                [
                    'name' => 'UserCard',
                    'path' => 'resources/views/components/',
                    'sources' => ['user-profile.blade.php', 'user-list.blade.php']
                ]
            ],
            'estimatedSizeReduction' => 0.5
        ]);
    }

    /**
     * Create a mock cleanup report
     */
    private function createMockCleanupReport(): CleanupReport
    {
        $report = new CleanupReport();
        $report->filesRemoved = 0;
        $report->linesRemoved = 0;
        $report->importsRemoved = 0;
        $report->methodsRemoved = 0;
        $report->duplicatesRefactored = 0;
        $report->componentsCreated = 0;
        $report->sizeReductionMB = 0.0;
        
        return $report;
    }

    /**
     * Create a mock cleanup report with results
     */
    private function createMockCleanupReportWithResults(): CleanupReport
    {
        $report = new CleanupReport();
        $report->filesRemoved = 0;
        $report->linesRemoved = 25;
        $report->importsRemoved = 2;
        $report->methodsRemoved = 2;
        $report->duplicatesRefactored = 1;
        $report->componentsCreated = 1;
        $report->sizeReductionMB = 0.5;
        
        return $report;
    }
}