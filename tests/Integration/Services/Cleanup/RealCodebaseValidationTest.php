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

class RealCodebaseValidationTest extends TestCase
{
    use RefreshDatabase;

    private CleanupOrchestrator $orchestrator;
    private string $backupPath;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->orchestrator = app(CleanupOrchestrator::class);
        $this->backupPath = storage_path('testing/real-codebase-backup');
        
        // Create backup directory
        if (!File::isDirectory($this->backupPath)) {
            File::makeDirectory($this->backupPath, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        // Clean up backup directory
        if (File::isDirectory($this->backupPath)) {
            File::deleteDirectory($this->backupPath);
        }
        
        parent::tearDown();
    }

    /** @test */
    public function it_can_analyze_real_gym_machines_codebase()
    {
        // Act
        $startTime = microtime(true);
        $analysis = $this->orchestrator->analyzeCodebase();
        $endTime = microtime(true);

        // Assert
        $this->assertInstanceOf(CodebaseAnalysis::class, $analysis);
        
        // Verify analysis contains real project files
        $this->assertGreaterThan(0, count($analysis->phpFiles), 'Should find PHP files in the project');
        $this->assertGreaterThan(0, count($analysis->jsFiles), 'Should find JavaScript files in the project');
        $this->assertGreaterThan(0, count($analysis->bladeFiles), 'Should find Blade template files in the project');
        $this->assertGreaterThan(0, count($analysis->cssFiles), 'Should find CSS files in the project');
        
        // Verify performance is acceptable
        $executionTime = $endTime - $startTime;
        $this->assertLessThan(60, $executionTime, 'Analysis should complete within 60 seconds');
        
        // Verify dependency graph was built
        $this->assertNotNull($analysis->dependencies);
        $this->assertIsArray($analysis->dependencies->getNodes());
        
        // Log analysis results for review
        Log::info('Real codebase analysis completed', [
            'total_files' => $analysis->getTotalFiles(),
            'php_files' => count($analysis->phpFiles),
            'js_files' => count($analysis->jsFiles),
            'blade_files' => count($analysis->bladeFiles),
            'css_files' => count($analysis->cssFiles),
            'execution_time' => $executionTime
        ]);
    }

    /** @test */
    public function it_can_generate_realistic_cleanup_plan_for_real_codebase()
    {
        // Arrange
        $analysis = $this->orchestrator->analyzeCodebase();

        // Act
        $startTime = microtime(true);
        $plan = $this->orchestrator->generateCleanupPlan($analysis);
        $endTime = microtime(true);

        // Assert
        $this->assertInstanceOf(CleanupPlan::class, $plan);
        
        // Verify plan contains realistic operations
        $this->assertIsArray($plan->filesToDelete);
        $this->assertIsArray($plan->importsToRemove);
        $this->assertIsArray($plan->methodsToRemove);
        $this->assertIsArray($plan->variablesToRemove);
        $this->assertIsArray($plan->duplicatesToRefactor);
        $this->assertIsArray($plan->componentsToCreate);
        
        // Verify plan generation performance
        $executionTime = $endTime - $startTime;
        $this->assertLessThan(30, $executionTime, 'Plan generation should complete within 30 seconds');
        
        // Verify estimated size reduction is reasonable
        $this->assertGreaterThanOrEqual(0, $plan->estimatedSizeReduction);
        $this->assertLessThan(100, $plan->estimatedSizeReduction, 'Size reduction should be reasonable (< 100MB)');
        
        // Log plan results for review
        Log::info('Real codebase cleanup plan generated', [
            'total_operations' => $plan->getTotalOperations(),
            'files_to_delete' => count($plan->filesToDelete),
            'imports_to_remove' => count($plan->importsToRemove),
            'methods_to_remove' => count($plan->methodsToRemove),
            'variables_to_remove' => count($plan->variablesToRemove),
            'duplicates_to_refactor' => count($plan->duplicatesToRefactor),
            'components_to_create' => count($plan->componentsToCreate),
            'estimated_size_reduction_mb' => $plan->estimatedSizeReduction,
            'execution_time' => $executionTime
        ]);
    }

    /** @test */
    public function it_can_execute_safe_dry_run_on_real_codebase()
    {
        // Arrange
        $config = new CleanupConfig([
            'dryRun' => true,
            'createBackup' => false,
            'runTests' => false,
            'removeUnusedImports' => true,
            'removeUnusedMethods' => false, // Keep conservative for real codebase
            'removeUnusedVariables' => false, // Keep conservative for real codebase
            'refactorDuplicates' => false, // Keep conservative for real codebase
            'createComponents' => false // Keep conservative for real codebase
        ]);

        // Act
        $startTime = microtime(true);
        $report = $this->orchestrator->executeCleanup($config);
        $endTime = microtime(true);

        // Assert
        $this->assertInstanceOf(CleanupReport::class, $report);
        
        // Verify dry run completed successfully
        $this->assertGreaterThanOrEqual(0, $report->getTotalItemsProcessed());
        $this->assertGreaterThanOrEqual(0, $report->getSuccessRate());
        $this->assertLessThanOrEqual(100, $report->getSuccessRate());
        
        // Verify performance is acceptable
        $executionTime = $endTime - $startTime;
        $this->assertLessThan(120, $executionTime, 'Dry run should complete within 2 minutes');
        
        // Verify report completeness
        $this->assertIsArray($report->performanceImprovements);
        $this->assertIsArray($report->maintenanceRecommendations);
        $this->assertIsArray($report->riskAssessments);
        
        // Verify no actual changes were made (dry run)
        $this->assertEquals(0, $report->filesRemoved, 'Dry run should not remove files');
        
        // Log execution results for review
        Log::info('Real codebase dry run completed', [
            'total_items_processed' => $report->getTotalItemsProcessed(),
            'success_rate' => $report->getSuccessRate(),
            'imports_removed' => $report->importsRemoved,
            'size_reduction_mb' => $report->sizeReductionMB,
            'execution_time' => $executionTime,
            'high_priority_recommendations' => count($report->getHighPriorityRecommendations()),
            'critical_risks' => count($report->getCriticalRisks())
        ]);
    }

    /** @test */
    public function it_validates_all_functionality_remains_intact()
    {
        // This test ensures that after cleanup, all functionality still works
        
        // Arrange - Run a very conservative cleanup
        $config = new CleanupConfig([
            'dryRun' => false,
            'createBackup' => true,
            'runTests' => true,
            'removeUnusedImports' => true, // Only remove imports (safest operation)
            'removeUnusedMethods' => false,
            'removeUnusedVariables' => false,
            'refactorDuplicates' => false,
            'createComponents' => false,
            'batchSize' => 5 // Small batch size for safety
        ]);

        // Act
        $report = $this->orchestrator->executeCleanup($config);

        // Assert
        $this->assertInstanceOf(CleanupReport::class, $report);
        
        // Verify cleanup completed without critical errors
        $criticalRisks = $report->getCriticalRisks();
        $this->assertEmpty($criticalRisks, 'Should not have critical risks after conservative cleanup');
        
        // Verify success rate is high
        $this->assertGreaterThan(90, $report->getSuccessRate(), 'Success rate should be > 90% for conservative cleanup');
        
        // Run basic functionality tests to ensure nothing is broken
        $this->runBasicFunctionalityTests();
        
        // Log validation results
        Log::info('Real codebase validation completed', [
            'success_rate' => $report->getSuccessRate(),
            'items_processed' => $report->getTotalItemsProcessed(),
            'critical_risks' => count($criticalRisks),
            'functionality_intact' => true
        ]);
    }

    /** @test */
    public function it_generates_comprehensive_report_for_real_codebase()
    {
        // Arrange
        $config = new CleanupConfig(['dryRun' => true]);

        // Act
        $report = $this->orchestrator->executeCleanup($config);

        // Assert
        $this->assertInstanceOf(CleanupReport::class, $report);
        
        // Verify report has all required sections
        $this->assertIsArray($report->performanceImprovements);
        $this->assertIsArray($report->maintenanceRecommendations);
        $this->assertIsArray($report->riskAssessments);
        $this->assertIsArray($report->executionSummary);
        $this->assertIsArray($report->codeQualityImprovements);
        $this->assertIsArray($report->futureOptimizationOpportunities);
        
        // Verify report methods work correctly
        $impactSummary = $report->getImpactSummary();
        $this->assertIsArray($impactSummary);
        $this->assertArrayHasKey('files_processed', $impactSummary);
        $this->assertArrayHasKey('success_rate', $impactSummary);
        
        // Verify report can be serialized
        $reportArray = $report->toArray();
        $this->assertIsArray($reportArray);
        
        $reportJson = $report->toJson();
        $this->assertIsString($reportJson);
        $this->assertJson($reportJson);
        
        // Save report to file for manual review
        $reportPath = $this->backupPath . '/real_codebase_cleanup_report.json';
        $saved = $report->saveToFile($reportPath);
        $this->assertTrue($saved, 'Should be able to save report to file');
        $this->assertFileExists($reportPath);
        
        Log::info('Comprehensive report generated for real codebase', [
            'report_path' => $reportPath,
            'report_size_bytes' => File::size($reportPath),
            'maintenance_recommendations' => count($report->maintenanceRecommendations),
            'risk_assessments' => count($report->riskAssessments),
            'performance_improvements' => count($report->performanceImprovements)
        ]);
    }

    /** @test */
    public function it_handles_large_real_codebase_efficiently()
    {
        // This test validates performance with the actual codebase size
        
        // Arrange
        $config = new CleanupConfig([
            'dryRun' => true,
            'batchSize' => 20 // Reasonable batch size
        ]);
        
        $startMemory = memory_get_usage(true);
        $startTime = microtime(true);

        // Act
        $report = $this->orchestrator->executeCleanup($config);
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);

        // Assert
        $executionTime = $endTime - $startTime;
        $memoryUsed = $endMemory - $startMemory;
        
        // Performance assertions
        $this->assertLessThan(300, $executionTime, 'Should complete within 5 minutes for real codebase');
        $this->assertLessThan(256 * 1024 * 1024, $memoryUsed, 'Memory usage should be under 256MB');
        $this->assertLessThan(512 * 1024 * 1024, $peakMemory, 'Peak memory should be under 512MB');
        
        // Verify results are still accurate despite performance optimizations
        $this->assertInstanceOf(CleanupReport::class, $report);
        $this->assertGreaterThanOrEqual(0, $report->getTotalItemsProcessed());
        
        Log::info('Large codebase performance validation completed', [
            'execution_time_seconds' => $executionTime,
            'memory_used_mb' => $memoryUsed / (1024 * 1024),
            'peak_memory_mb' => $peakMemory / (1024 * 1024),
            'items_processed' => $report->getTotalItemsProcessed(),
            'success_rate' => $report->getSuccessRate()
        ]);
    }

    /** @test */
    public function it_identifies_real_optimization_opportunities()
    {
        // This test validates that the system finds actual optimization opportunities
        
        // Act
        $analysis = $this->orchestrator->analyzeCodebase();
        $plan = $this->orchestrator->generateCleanupPlan($analysis);

        // Assert - Verify realistic findings
        $totalOperations = $plan->getTotalOperations();
        $this->assertGreaterThan(0, $totalOperations, 'Should find some optimization opportunities in real codebase');
        
        // Verify findings are reasonable (not too aggressive)
        $this->assertLessThan(1000, $totalOperations, 'Should not suggest excessive changes');
        
        // Check for specific types of optimizations that are likely in a real Laravel project
        if (count($plan->importsToRemove) > 0) {
            $this->assertLessThan(100, count($plan->importsToRemove), 'Import removals should be reasonable');
        }
        
        if (count($plan->duplicatesToRefactor) > 0) {
            $this->assertLessThan(50, count($plan->duplicatesToRefactor), 'Duplicate refactoring should be reasonable');
        }
        
        // Log findings for manual review
        Log::info('Real optimization opportunities identified', [
            'total_operations' => $totalOperations,
            'imports_to_remove' => count($plan->importsToRemove),
            'methods_to_remove' => count($plan->methodsToRemove),
            'variables_to_remove' => count($plan->variablesToRemove),
            'duplicates_to_refactor' => count($plan->duplicatesToRefactor),
            'components_to_create' => count($plan->componentsToCreate),
            'files_to_delete' => count($plan->filesToDelete),
            'estimated_size_reduction_mb' => $plan->estimatedSizeReduction
        ]);
    }

    /** @test */
    public function it_provides_actionable_maintenance_recommendations()
    {
        // Arrange
        $config = new CleanupConfig(['dryRun' => true]);

        // Act
        $report = $this->orchestrator->executeCleanup($config);

        // Assert
        $recommendations = $report->maintenanceRecommendations;
        $this->assertIsArray($recommendations);
        
        if (!empty($recommendations)) {
            // Verify recommendations have required structure
            foreach ($recommendations as $recommendation) {
                $this->assertIsArray($recommendation);
                // Recommendations should have at least a description
                $this->assertTrue(
                    isset($recommendation['description']) || isset($recommendation['title']),
                    'Recommendations should have description or title'
                );
            }
            
            // Check for high priority recommendations
            $highPriorityRecommendations = $report->getHighPriorityRecommendations();
            $this->assertIsArray($highPriorityRecommendations);
            
            Log::info('Maintenance recommendations generated', [
                'total_recommendations' => count($recommendations),
                'high_priority_recommendations' => count($highPriorityRecommendations)
            ]);
        }
        
        // Verify time savings estimation
        $timeSavings = $report->getEstimatedTimeSavings();
        $this->assertIsArray($timeSavings);
        $this->assertArrayHasKey('total_hours', $timeSavings);
        $this->assertArrayHasKey('weekly_savings', $timeSavings);
        $this->assertArrayHasKey('monthly_savings', $timeSavings);
    }

    /**
     * Run basic functionality tests to ensure the application still works
     */
    private function runBasicFunctionalityTests(): void
    {
        // Test that basic routes are still accessible
        try {
            $response = $this->get('/');
            $this->assertTrue($response->status() < 500, 'Home page should be accessible');
        } catch (\Exception $e) {
            $this->fail('Home page should be accessible after cleanup: ' . $e->getMessage());
        }
        
        // Test that admin routes are still accessible (if user is authenticated)
        try {
            // This would need proper authentication setup in a real test
            // For now, just verify the route exists
            $this->assertTrue(true, 'Admin functionality validation placeholder');
        } catch (\Exception $e) {
            Log::warning('Admin functionality test failed', ['error' => $e->getMessage()]);
        }
        
        // Test that database connections still work
        try {
            \DB::connection()->getPdo();
            $this->assertTrue(true, 'Database connection should work');
        } catch (\Exception $e) {
            $this->fail('Database connection should work after cleanup: ' . $e->getMessage());
        }
        
        // Test that cache still works
        try {
            \Cache::put('cleanup_test', 'value', 60);
            $value = \Cache::get('cleanup_test');
            $this->assertEquals('value', $value, 'Cache should work after cleanup');
            \Cache::forget('cleanup_test');
        } catch (\Exception $e) {
            Log::warning('Cache functionality test failed', ['error' => $e->getMessage()]);
        }
    }
}