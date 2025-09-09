<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\Cleanup\Models\CleanupConfig;
use App\Services\Cleanup\Models\CleanupPlan;
use App\Services\Cleanup\Models\CleanupReport;

class CleanupWorkflowBasicTest extends TestCase
{
    /** @test */
    public function cleanup_config_can_be_created_with_default_values()
    {
        $config = new CleanupConfig();
        
        $this->assertTrue($config->dryRun);
        $this->assertTrue($config->createBackup);
        $this->assertTrue($config->runTests);
        $this->assertEquals(['php', 'js', 'css', 'blade.php'], $config->includeFileTypes);
        $this->assertTrue($config->removeUnusedImports);
        $this->assertTrue($config->removeUnusedMethods);
        $this->assertTrue($config->removeUnusedVariables);
        $this->assertTrue($config->refactorDuplicates);
        $this->assertTrue($config->createComponents);
        $this->assertEquals(50, $config->batchSize);
        $this->assertEquals(1048576, $config->maxFileSize);
    }

    /** @test */
    public function cleanup_config_can_be_created_with_custom_values()
    {
        $config = new CleanupConfig([
            'dryRun' => false,
            'includeFileTypes' => ['php'],
            'removeUnusedImports' => false,
            'batchSize' => 25,
            'maxFileSize' => 2097152
        ]);
        
        $this->assertFalse($config->dryRun);
        $this->assertEquals(['php'], $config->includeFileTypes);
        $this->assertFalse($config->removeUnusedImports);
        $this->assertEquals(25, $config->batchSize);
        $this->assertEquals(2097152, $config->maxFileSize);
    }

    /** @test */
    public function cleanup_config_can_check_file_type_inclusion()
    {
        $config = new CleanupConfig(['includeFileTypes' => ['php', 'js']]);
        
        $this->assertTrue($config->isFileTypeIncluded('php'));
        $this->assertTrue($config->isFileTypeIncluded('js'));
        $this->assertFalse($config->isFileTypeIncluded('css'));
        $this->assertFalse($config->isFileTypeIncluded('blade.php'));
    }

    /** @test */
    public function cleanup_config_can_check_path_exclusion()
    {
        $config = new CleanupConfig(['excludePaths' => ['vendor', 'node_modules']]);
        
        $this->assertTrue($config->isPathExcluded('vendor/laravel/framework/src/File.php'));
        $this->assertTrue($config->isPathExcluded('node_modules/lodash/index.js'));
        $this->assertFalse($config->isPathExcluded('app/Models/User.php'));
        $this->assertFalse($config->isPathExcluded('resources/views/welcome.blade.php'));
    }

    /** @test */
    public function cleanup_plan_can_calculate_total_operations()
    {
        $plan = new CleanupPlan([
            'filesToDelete' => ['file1.php', 'file2.php'],
            'importsToRemove' => ['import1', 'import2', 'import3'],
            'methodsToRemove' => ['method1'],
            'variablesToRemove' => ['var1', 'var2'],
            'duplicatesToRefactor' => ['duplicate1'],
            'componentsToCreate' => ['component1', 'component2']
        ]);

        $this->assertEquals(11, $plan->getTotalOperations());
    }

    /** @test */
    public function cleanup_plan_handles_empty_arrays()
    {
        $plan = new CleanupPlan();

        $this->assertEquals(0, $plan->getTotalOperations());
        $this->assertEmpty($plan->filesToDelete);
        $this->assertEmpty($plan->importsToRemove);
        $this->assertEmpty($plan->methodsToRemove);
        $this->assertEmpty($plan->variablesToRemove);
        $this->assertEmpty($plan->duplicatesToRefactor);
        $this->assertEmpty($plan->componentsToCreate);
        $this->assertEquals(0.0, $plan->estimatedSizeReduction);
    }

    /** @test */
    public function cleanup_report_can_be_instantiated()
    {
        $report = new CleanupReport();
        
        $this->assertEquals(0, $report->filesRemoved);
        $this->assertEquals(0, $report->linesRemoved);
        $this->assertEquals(0, $report->importsRemoved);
        $this->assertEquals(0, $report->methodsRemoved);
        $this->assertEquals(0, $report->duplicatesRefactored);
        $this->assertEquals(0, $report->componentsCreated);
        $this->assertEquals(0.0, $report->sizeReductionMB);
        $this->assertIsArray($report->performanceImprovements);
        $this->assertIsArray($report->maintenanceRecommendations);
        $this->assertIsArray($report->riskAssessments);
    }

    /** @test */
    public function cleanup_commands_are_registered()
    {
        // Test that the commands are properly registered
        $this->assertTrue(class_exists(\App\Console\Commands\CleanupInteractiveCommand::class));
        $this->assertTrue(class_exists(\App\Console\Commands\CleanupSelectiveCommand::class));
    }

    /** @test */
    public function interactive_cleanup_command_has_correct_signature()
    {
        $command = new \App\Console\Commands\CleanupInteractiveCommand(
            $this->app->make(\App\Services\Cleanup\CleanupOrchestrator::class)
        );
        
        $this->assertEquals('cleanup:interactive', $command->getName());
        $this->assertStringContainsString('Interactive step-by-step cleanup process', $command->getDescription());
    }

    /** @test */
    public function selective_cleanup_command_has_correct_signature()
    {
        $command = new \App\Console\Commands\CleanupSelectiveCommand(
            $this->app->make(\App\Services\Cleanup\CleanupOrchestrator::class)
        );
        
        $this->assertEquals('cleanup:selective', $command->getName());
        $this->assertStringContainsString('Selective cleanup for specific file types', $command->getDescription());
    }

    /** @test */
    public function cleanup_config_ignores_unknown_properties()
    {
        $config = new CleanupConfig([
            'dryRun' => false,
            'unknownProperty' => 'should be ignored',
            'anotherUnknown' => 123
        ]);
        
        $this->assertFalse($config->dryRun);
        $this->assertFalse(property_exists($config, 'unknownProperty'));
        $this->assertFalse(property_exists($config, 'anotherUnknown'));
    }

    /** @test */
    public function cleanup_plan_ignores_unknown_properties()
    {
        $plan = new CleanupPlan([
            'filesToDelete' => ['file1.php'],
            'unknownProperty' => 'should be ignored'
        ]);
        
        $this->assertEquals(['file1.php'], $plan->filesToDelete);
        $this->assertFalse(property_exists($plan, 'unknownProperty'));
    }
}