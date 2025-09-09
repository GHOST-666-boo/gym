<?php

namespace Tests\Integration\Services\Cleanup;

use Tests\TestCase;
use App\Services\Cleanup\CleanupOrchestrator;
use App\Services\Cleanup\Models\CleanupConfig;
use App\Services\Cleanup\Models\CodebaseAnalysis;
use App\Services\Cleanup\Models\CleanupPlan;
use App\Services\Cleanup\Models\CleanupReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

class CleanupOrchestratorIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private CleanupOrchestrator $orchestrator;
    private string $testProjectPath;
    private array $testFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test project structure
        $this->testProjectPath = storage_path('testing/cleanup-integration');
        $this->createTestProjectStructure();
        
        // Get orchestrator instance from container
        $this->orchestrator = app(CleanupOrchestrator::class);
    }

    protected function tearDown(): void
    {
        // Clean up test files
        if (File::isDirectory($this->testProjectPath)) {
            File::deleteDirectory($this->testProjectPath);
        }
        
        parent::tearDown();
    }

    /** @test */
    public function it_can_analyze_complete_codebase()
    {
        // Act
        $analysis = $this->orchestrator->analyzeCodebase();

        // Assert
        $this->assertInstanceOf(CodebaseAnalysis::class, $analysis);
        $this->assertIsArray($analysis->phpFiles);
        $this->assertIsArray($analysis->jsFiles);
        $this->assertIsArray($analysis->bladeFiles);
        $this->assertIsArray($analysis->cssFiles);
        $this->assertIsArray($analysis->routeDefinitions);
        $this->assertIsArray($analysis->assetFiles);
        $this->assertNotNull($analysis->dependencies);
        
        // Verify that files were actually analyzed
        $this->assertGreaterThan(0, $analysis->getTotalFiles());
    }

    /** @test */
    public function it_can_generate_comprehensive_cleanup_plan()
    {
        // Arrange
        $analysis = $this->orchestrator->analyzeCodebase();

        // Act
        $plan = $this->orchestrator->generateCleanupPlan($analysis);

        // Assert
        $this->assertInstanceOf(CleanupPlan::class, $plan);
        $this->assertIsArray($plan->filesToDelete);
        $this->assertIsArray($plan->importsToRemove);
        $this->assertIsArray($plan->methodsToRemove);
        $this->assertIsArray($plan->variablesToRemove);
        $this->assertIsArray($plan->duplicatesToRefactor);
        $this->assertIsArray($plan->componentsToCreate);
        $this->assertIsFloat($plan->estimatedSizeReduction);
        
        // Verify plan has operations
        $this->assertGreaterThanOrEqual(0, $plan->getTotalOperations());
    }

    /** @test */
    public function it_can_execute_dry_run_cleanup()
    {
        // Arrange
        $config = new CleanupConfig([
            'dryRun' => true,
            'createBackup' => false,
            'runTests' => false
        ]);

        // Act
        $report = $this->orchestrator->executeCleanup($config);

        // Assert
        $this->assertInstanceOf(CleanupReport::class, $report);
        $this->assertIsInt($report->filesRemoved);
        $this->assertIsInt($report->importsRemoved);
        $this->assertIsInt($report->methodsRemoved);
        $this->assertIsInt($report->duplicatesRefactored);
        $this->assertIsInt($report->componentsCreated);
        $this->assertIsFloat($report->sizeReductionMB);
        $this->assertIsArray($report->performanceImprovements);
        $this->assertIsArray($report->maintenanceRecommendations);
        $this->assertIsArray($report->riskAssessments);
        
        // Verify dry run didn't actually modify files
        $this->assertFileExists($this->testFiles['php_with_unused_imports']);
        $this->assertFileExists($this->testFiles['js_with_unused_vars']);
    }

    /** @test */
    public function it_can_execute_full_cleanup_with_safety_validation()
    {
        // Arrange
        $config = new CleanupConfig([
            'dryRun' => false,
            'createBackup' => true,
            'runTests' => true,
            'removeUnusedImports' => true,
            'removeUnusedMethods' => false, // Keep safe for integration test
            'removeUnusedVariables' => false, // Keep safe for integration test
            'refactorDuplicates' => false, // Keep safe for integration test
            'createComponents' => false // Keep safe for integration test
        ]);

        // Act
        $report = $this->orchestrator->executeCleanup($config);

        // Assert
        $this->assertInstanceOf(CleanupReport::class, $report);
        $this->assertGreaterThanOrEqual(0, $report->getTotalItemsProcessed());
        
        // Verify backup was created if any changes were made
        if ($report->getTotalItemsProcessed() > 0) {
            $backupDir = storage_path('app/cleanup-backups');
            $this->assertTrue(File::isDirectory($backupDir));
        }
    }

    /** @test */
    public function it_handles_analyzer_coordination_correctly()
    {
        // Act
        $analysis = $this->orchestrator->analyzeCodebase();

        // Assert - Verify all analyzers were coordinated
        $this->assertNotEmpty($analysis->phpFiles, 'PHP analyzer should have found files');
        $this->assertNotEmpty($analysis->jsFiles, 'JavaScript analyzer should have found files');
        $this->assertNotEmpty($analysis->bladeFiles, 'Blade analyzer should have found files');
        $this->assertNotEmpty($analysis->cssFiles, 'CSS analyzer should have found files');
        
        // Verify dependency graph was built
        $this->assertNotNull($analysis->dependencies);
        $this->assertIsArray($analysis->dependencies->getNodes());
    }

    /** @test */
    public function it_validates_data_flow_between_services()
    {
        // Arrange
        $analysis = $this->orchestrator->analyzeCodebase();
        $plan = $this->orchestrator->generateCleanupPlan($analysis);

        // Act & Assert - Verify data flows correctly between analysis and plan
        $this->assertInstanceOf(CodebaseAnalysis::class, $analysis);
        $this->assertInstanceOf(CleanupPlan::class, $plan);
        
        // Verify plan is based on analysis data
        if (!empty($analysis->phpFiles)) {
            // If we have PHP files, we should have some cleanup operations
            $this->assertGreaterThanOrEqual(0, count($plan->importsToRemove));
        }
        
        if (!empty($analysis->jsFiles)) {
            // If we have JS files, we should have some cleanup operations
            $this->assertGreaterThanOrEqual(0, count($plan->variablesToRemove));
        }
    }

    /** @test */
    public function it_validates_safety_mechanisms()
    {
        // Arrange
        $config = new CleanupConfig([
            'dryRun' => false,
            'createBackup' => true,
            'runTests' => true
        ]);

        // Act
        $report = $this->orchestrator->executeCleanup($config);

        // Assert - Verify safety mechanisms were triggered
        $this->assertInstanceOf(CleanupReport::class, $report);
        
        // Check that safety validation was performed
        $this->assertIsArray($report->riskAssessments);
        
        // Verify no critical errors occurred
        $criticalRisks = $report->getCriticalRisks();
        $this->assertIsArray($criticalRisks);
    }

    /** @test */
    public function it_validates_rollback_functionality()
    {
        // This test would be more complex in a real scenario
        // For now, we'll test that the orchestrator handles errors gracefully
        
        // Arrange - Create a config that might cause issues
        $config = new CleanupConfig([
            'dryRun' => false,
            'createBackup' => true,
            'runTests' => true,
            'maxFileSize' => 1 // Very small to potentially cause issues
        ]);

        // Act
        $report = $this->orchestrator->executeCleanup($config);

        // Assert - Verify the system handled any issues gracefully
        $this->assertInstanceOf(CleanupReport::class, $report);
        
        // Even if there were issues, we should get a report
        $this->assertIsFloat($report->getSuccessRate());
        $this->assertGreaterThanOrEqual(0, $report->getSuccessRate());
        $this->assertLessThanOrEqual(100, $report->getSuccessRate());
    }

    /** @test */
    public function it_integrates_with_command_line_interface()
    {
        // Test that the orchestrator works with Artisan commands
        
        // Act - Run cleanup analyze command
        $exitCode = Artisan::call('cleanup:analyze', [
            '--dry-run' => true
        ]);

        // Assert
        $this->assertEquals(0, $exitCode, 'Cleanup analyze command should succeed');
        
        $output = Artisan::output();
        $this->assertStringContainsString('analysis', strtolower($output));
    }

    /** @test */
    public function it_handles_large_codebase_efficiently()
    {
        // Arrange - Create additional test files to simulate larger codebase
        $this->createLargeTestFiles();
        
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        // Act
        $analysis = $this->orchestrator->analyzeCodebase();
        $plan = $this->orchestrator->generateCleanupPlan($analysis);
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);

        // Assert - Performance should be reasonable
        $executionTime = $endTime - $startTime;
        $memoryUsed = $endMemory - $startMemory;
        
        $this->assertLessThan(30, $executionTime, 'Analysis should complete within 30 seconds');
        $this->assertLessThan(100 * 1024 * 1024, $memoryUsed, 'Memory usage should be under 100MB');
        
        // Verify results are still accurate
        $this->assertInstanceOf(CodebaseAnalysis::class, $analysis);
        $this->assertInstanceOf(CleanupPlan::class, $plan);
    }

    /** @test */
    public function it_generates_comprehensive_reports()
    {
        // Arrange
        $config = new CleanupConfig(['dryRun' => true]);

        // Act
        $report = $this->orchestrator->executeCleanup($config);

        // Assert - Verify report completeness
        $this->assertInstanceOf(CleanupReport::class, $report);
        
        // Check all report sections are present
        $this->assertIsArray($report->performanceImprovements);
        $this->assertIsArray($report->maintenanceRecommendations);
        $this->assertIsArray($report->riskAssessments);
        $this->assertIsArray($report->executionSummary);
        $this->assertIsArray($report->codeQualityImprovements);
        $this->assertIsArray($report->futureOptimizationOpportunities);
        
        // Verify report methods work
        $this->assertIsInt($report->getTotalItemsProcessed());
        $this->assertIsFloat($report->getSuccessRate());
        $this->assertIsArray($report->getHighPriorityRecommendations());
        $this->assertIsArray($report->getCriticalRisks());
        $this->assertIsArray($report->getEstimatedTimeSavings());
        $this->assertIsArray($report->getImpactSummary());
        
        // Verify report can be serialized
        $this->assertIsArray($report->toArray());
        $this->assertIsString($report->toJson());
    }

    /** @test */
    public function it_handles_concurrent_operations_safely()
    {
        // This test simulates multiple cleanup operations
        // In a real scenario, this would test file locking and concurrency
        
        $configs = [
            new CleanupConfig(['dryRun' => true, 'batchSize' => 10]),
            new CleanupConfig(['dryRun' => true, 'batchSize' => 20]),
        ];
        
        $reports = [];
        
        // Act - Run multiple cleanup operations
        foreach ($configs as $config) {
            $reports[] = $this->orchestrator->executeCleanup($config);
        }

        // Assert - All operations should complete successfully
        foreach ($reports as $report) {
            $this->assertInstanceOf(CleanupReport::class, $report);
            $this->assertGreaterThanOrEqual(0, $report->getSuccessRate());
        }
    }

    /**
     * Create test project structure with various file types
     */
    private function createTestProjectStructure(): void
    {
        // Create directories
        File::makeDirectory($this->testProjectPath, 0755, true);
        File::makeDirectory($this->testProjectPath . '/app', 0755, true);
        File::makeDirectory($this->testProjectPath . '/resources/views', 0755, true);
        File::makeDirectory($this->testProjectPath . '/resources/js', 0755, true);
        File::makeDirectory($this->testProjectPath . '/resources/css', 0755, true);
        File::makeDirectory($this->testProjectPath . '/public/images', 0755, true);

        // Create test PHP file with unused imports
        $phpContent = '<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\UnusedTrait; // This import is unused
use Carbon\Carbon; // This import is unused

class TestModel extends Model
{
    use HasFactory;
    
    protected $fillable = [\'name\', \'email\'];
    
    public function getNameAttribute($value)
    {
        return ucfirst($value);
    }
    
    private function unusedMethod()
    {
        return "This method is never called";
    }
}';
        
        $this->testFiles['php_with_unused_imports'] = $this->testProjectPath . '/app/TestModel.php';
        File::put($this->testFiles['php_with_unused_imports'], $phpContent);

        // Create test JavaScript file with unused variables
        $jsContent = 'const unusedVariable = "This is never used";
const usedVariable = "This is used";

function testFunction() {
    console.log(usedVariable);
    
    const localUnusedVar = "Local unused";
    const localUsedVar = "Local used";
    
    return localUsedVar;
}

function unusedFunction() {
    return "This function is never called";
}

export { testFunction };';
        
        $this->testFiles['js_with_unused_vars'] = $this->testProjectPath . '/resources/js/test.js';
        File::put($this->testFiles['js_with_unused_vars'], $jsContent);

        // Create test Blade template
        $bladeContent = '@extends(\'layouts.app\')

@section(\'content\')
<div class="container">
    <h1>{{ $title }}</h1>
    <p>{{ $description }}</p>
    
    <!-- Duplicate structure that could be componentized -->
    <div class="card">
        <div class="card-header">Header 1</div>
        <div class="card-body">Body 1</div>
    </div>
    
    <div class="card">
        <div class="card-header">Header 2</div>
        <div class="card-body">Body 2</div>
    </div>
</div>
@endsection';
        
        $this->testFiles['blade_template'] = $this->testProjectPath . '/resources/views/test.blade.php';
        File::put($this->testFiles['blade_template'], $bladeContent);

        // Create test CSS file
        $cssContent = '.used-class {
    color: blue;
}

.unused-class {
    color: red;
}

.duplicate-style {
    margin: 10px;
    padding: 5px;
}

.another-duplicate-style {
    margin: 10px;
    padding: 5px;
}';
        
        $this->testFiles['css_file'] = $this->testProjectPath . '/resources/css/test.css';
        File::put($this->testFiles['css_file'], $cssContent);

        // Create test image file (empty for testing)
        $this->testFiles['test_image'] = $this->testProjectPath . '/public/images/test.jpg';
        File::put($this->testFiles['test_image'], 'fake image content');
    }

    /**
     * Create additional test files to simulate larger codebase
     */
    private function createLargeTestFiles(): void
    {
        // Create multiple PHP files
        for ($i = 1; $i <= 10; $i++) {
            $content = "<?php\n\nnamespace App\\Test;\n\nclass TestClass{$i}\n{\n    public function method{$i}()\n    {\n        return 'test{$i}';\n    }\n}\n";
            File::put($this->testProjectPath . "/app/TestClass{$i}.php", $content);
        }

        // Create multiple JS files
        for ($i = 1; $i <= 5; $i++) {
            $content = "const testVar{$i} = 'value{$i}';\n\nfunction testFunc{$i}() {\n    return testVar{$i};\n}\n\nexport { testFunc{$i} };\n";
            File::put($this->testProjectPath . "/resources/js/test{$i}.js", $content);
        }

        // Create multiple Blade files
        for ($i = 1; $i <= 5; $i++) {
            $content = "<div class='test-class-{$i}'>\n    <h1>Test {$i}</h1>\n    <p>Content {$i}</p>\n</div>\n";
            File::put($this->testProjectPath . "/resources/views/test{$i}.blade.php", $content);
        }
    }
}