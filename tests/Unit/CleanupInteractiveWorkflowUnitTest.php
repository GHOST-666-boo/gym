<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Console\Commands\CleanupInteractiveCommand;
use App\Console\Commands\CleanupSelectiveCommand;
use App\Services\Cleanup\CleanupOrchestrator;
use App\Services\Cleanup\Models\CleanupConfig;
use App\Services\Cleanup\Models\CleanupPlan;
use Mockery;
use ReflectionClass;
use ReflectionMethod;

class CleanupInteractiveWorkflowUnitTest extends TestCase
{
    private $mockOrchestrator;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockOrchestrator = Mockery::mock(CleanupOrchestrator::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function cleanup_config_can_be_instantiated_with_array()
    {
        $config = new CleanupConfig([
            'dryRun' => false,
            'includeFileTypes' => ['php', 'js'],
            'removeUnusedImports' => true,
            'batchSize' => 25
        ]);

        $this->assertFalse($config->dryRun);
        $this->assertEquals(['php', 'js'], $config->includeFileTypes);
        $this->assertTrue($config->removeUnusedImports);
        $this->assertEquals(25, $config->batchSize);
    }

    /** @test */
    public function cleanup_config_can_check_file_type_inclusion()
    {
        $config = new CleanupConfig([
            'includeFileTypes' => ['php', 'js']
        ]);

        $this->assertTrue($config->isFileTypeIncluded('php'));
        $this->assertTrue($config->isFileTypeIncluded('js'));
        $this->assertFalse($config->isFileTypeIncluded('css'));
        $this->assertFalse($config->isFileTypeIncluded('blade.php'));
    }

    /** @test */
    public function cleanup_config_can_check_path_exclusion()
    {
        $config = new CleanupConfig([
            'excludePaths' => ['vendor', 'node_modules', 'tests']
        ]);

        $this->assertTrue($config->isPathExcluded('vendor/laravel/framework/src/File.php'));
        $this->assertTrue($config->isPathExcluded('node_modules/lodash/index.js'));
        $this->assertTrue($config->isPathExcluded('tests/Feature/ExampleTest.php'));
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
    }

    /** @test */
    public function interactive_command_can_group_imports_by_file()
    {
        $command = new CleanupInteractiveCommand($this->mockOrchestrator);
        $reflection = new ReflectionClass($command);
        $method = $reflection->getMethod('groupImportsByFile');
        $method->setAccessible(true);

        $imports = [
            ['file' => 'app/Models/User.php', 'import' => 'Illuminate\Support\Str'],
            ['file' => 'app/Models/User.php', 'import' => 'Carbon\Carbon'],
            ['file' => 'app/Models/Product.php', 'import' => 'Illuminate\Support\Arr'],
            'Illuminate\Support\Collection' // String format
        ];

        $grouped = $method->invoke($command, $imports);

        $this->assertArrayHasKey('app/Models/User.php', $grouped);
        $this->assertArrayHasKey('app/Models/Product.php', $grouped);
        $this->assertArrayHasKey('Unknown file', $grouped);
        
        $this->assertCount(2, $grouped['app/Models/User.php']);
        $this->assertCount(1, $grouped['app/Models/Product.php']);
        $this->assertCount(1, $grouped['Unknown file']);
        
        $this->assertContains('Illuminate\Support\Str', $grouped['app/Models/User.php']);
        $this->assertContains('Carbon\Carbon', $grouped['app/Models/User.php']);
        $this->assertContains('Illuminate\Support\Arr', $grouped['app/Models/Product.php']);
        $this->assertContains('Illuminate\Support\Collection', $grouped['Unknown file']);
    }

    /** @test */
    public function interactive_command_can_group_methods_by_class()
    {
        $command = new CleanupInteractiveCommand($this->mockOrchestrator);
        $reflection = new ReflectionClass($command);
        $method = $reflection->getMethod('groupMethodsByClass');
        $method->setAccessible(true);

        $methods = [
            ['class' => 'App\Models\User', 'method' => 'unusedMethod1'],
            ['class' => 'App\Models\User', 'method' => 'unusedMethod2'],
            ['class' => 'App\Models\Product', 'method' => 'unusedMethod3'],
            'orphanedMethod' // String format
        ];

        $grouped = $method->invoke($command, $methods);

        $this->assertArrayHasKey('App\Models\User', $grouped);
        $this->assertArrayHasKey('App\Models\Product', $grouped);
        $this->assertArrayHasKey('Unknown class', $grouped);
        
        $this->assertCount(2, $grouped['App\Models\User']);
        $this->assertCount(1, $grouped['App\Models\Product']);
        $this->assertCount(1, $grouped['Unknown class']);
        
        $this->assertContains('unusedMethod1', $grouped['App\Models\User']);
        $this->assertContains('unusedMethod2', $grouped['App\Models\User']);
        $this->assertContains('unusedMethod3', $grouped['App\Models\Product']);
        $this->assertContains('orphanedMethod', $grouped['Unknown class']);
    }

    /** @test */
    public function interactive_command_can_group_variables_by_file()
    {
        $command = new CleanupInteractiveCommand($this->mockOrchestrator);
        $reflection = new ReflectionClass($command);
        $method = $reflection->getMethod('groupVariablesByFile');
        $method->setAccessible(true);

        $variables = [
            ['file' => 'app/Controllers/UserController.php', 'variable' => '$unusedVar1'],
            ['file' => 'app/Controllers/UserController.php', 'variable' => '$unusedVar2'],
            ['file' => 'app/Controllers/ProductController.php', 'variable' => '$unusedVar3'],
            '$orphanedVar' // String format
        ];

        $grouped = $method->invoke($command, $variables);

        $this->assertArrayHasKey('app/Controllers/UserController.php', $grouped);
        $this->assertArrayHasKey('app/Controllers/ProductController.php', $grouped);
        $this->assertArrayHasKey('Unknown file', $grouped);
        
        $this->assertCount(2, $grouped['app/Controllers/UserController.php']);
        $this->assertCount(1, $grouped['app/Controllers/ProductController.php']);
        $this->assertCount(1, $grouped['Unknown file']);
        
        $this->assertContains('$unusedVar1', $grouped['app/Controllers/UserController.php']);
        $this->assertContains('$unusedVar2', $grouped['app/Controllers/UserController.php']);
        $this->assertContains('$unusedVar3', $grouped['app/Controllers/ProductController.php']);
        $this->assertContains('$orphanedVar', $grouped['Unknown file']);
    }

    /** @test */
    public function selective_command_can_filter_files_by_directory()
    {
        $command = new CleanupSelectiveCommand($this->mockOrchestrator);
        $reflection = new ReflectionClass($command);
        $method = $reflection->getMethod('filterFilesByDirectory');
        $method->setAccessible(true);

        $files = [
            (object) ['filePath' => 'app/Models/User.php'],
            (object) ['filePath' => 'app/Controllers/UserController.php'],
            (object) ['filePath' => 'resources/views/user.blade.php'],
            (object) ['filePath' => 'app/Services/UserService.php'],
        ];

        // Test filtering by specific directories
        $directories = ['app/Models/', 'app/Services/'];
        $filtered = $method->invoke($command, $files, $directories);

        $this->assertCount(2, $filtered);
        $this->assertEquals('app/Models/User.php', $filtered[0]->filePath);
        $this->assertEquals('app/Services/UserService.php', $filtered[3]->filePath);

        // Test with empty directories (should return all files)
        $filtered = $method->invoke($command, $files, []);
        $this->assertCount(4, $filtered);

        // Test with non-matching directories
        $filtered = $method->invoke($command, $files, ['tests/']);
        $this->assertCount(0, $filtered);
    }

    /** @test */
    public function selective_command_can_count_unused_imports()
    {
        $command = new CleanupSelectiveCommand($this->mockOrchestrator);
        $reflection = new ReflectionClass($command);
        $method = $reflection->getMethod('countUnusedImports');
        $method->setAccessible(true);

        $analysis = new \App\Services\Cleanup\Models\CodebaseAnalysis([
            'phpFiles' => [
                (object) ['unusedImports' => ['import1', 'import2']],
                (object) ['unusedImports' => ['import3']],
            ],
            'jsFiles' => [
                (object) ['unusedImports' => ['jsImport1']],
                (object) ['unusedImports' => ['jsImport2', 'jsImport3']],
            ]
        ]);

        $count = $method->invoke($command, $analysis);
        $this->assertEquals(6, $count); // 2 + 1 + 1 + 2 = 6
    }

    /** @test */
    public function selective_command_can_count_unused_methods()
    {
        $command = new CleanupSelectiveCommand($this->mockOrchestrator);
        $reflection = new ReflectionClass($command);
        $method = $reflection->getMethod('countUnusedMethods');
        $method->setAccessible(true);

        $analysis = new \App\Services\Cleanup\Models\CodebaseAnalysis([
            'phpFiles' => [
                (object) ['unusedMethods' => ['method1', 'method2', 'method3']],
                (object) ['unusedMethods' => ['method4']],
                (object) ['unusedMethods' => []],
            ]
        ]);

        $count = $method->invoke($command, $analysis);
        $this->assertEquals(4, $count); // 3 + 1 + 0 = 4
    }

    /** @test */
    public function selective_command_can_count_unused_variables()
    {
        $command = new CleanupSelectiveCommand($this->mockOrchestrator);
        $reflection = new ReflectionClass($command);
        $method = $reflection->getMethod('countUnusedVariables');
        $method->setAccessible(true);

        $analysis = new \App\Services\Cleanup\Models\CodebaseAnalysis([
            'phpFiles' => [
                (object) ['unusedVariables' => ['$var1', '$var2']],
            ],
            'jsFiles' => [
                (object) ['unusedVariables' => ['jsVar1', 'jsVar2', 'jsVar3']],
            ]
        ]);

        $count = $method->invoke($command, $analysis);
        $this->assertEquals(5, $count); // 2 + 3 = 5
    }

    /** @test */
    public function selective_command_can_count_duplicates()
    {
        $command = new CleanupSelectiveCommand($this->mockOrchestrator);
        $reflection = new ReflectionClass($command);
        $method = $reflection->getMethod('countDuplicates');
        $method->setAccessible(true);

        $analysis = new \App\Services\Cleanup\Models\CodebaseAnalysis([
            'phpFiles' => [
                (object) ['duplicateMethods' => ['dup1', 'dup2']],
            ],
            'bladeFiles' => [
                (object) ['duplicateStructures' => ['struct1']],
                (object) ['duplicateStructures' => ['struct2', 'struct3']],
            ]
        ]);

        $count = $method->invoke($command, $analysis);
        $this->assertEquals(5, $count); // 2 + 1 + 2 = 5
    }

    /** @test */
    public function selective_command_can_count_component_opportunities()
    {
        $command = new CleanupSelectiveCommand($this->mockOrchestrator);
        $reflection = new ReflectionClass($command);
        $method = $reflection->getMethod('countComponentOpportunities');
        $method->setAccessible(true);

        $analysis = new \App\Services\Cleanup\Models\CodebaseAnalysis([
            'bladeFiles' => [
                (object) ['componentOpportunities' => ['comp1', 'comp2']],
                (object) ['componentOpportunities' => []],
                (object) ['componentOpportunities' => ['comp3']],
            ]
        ]);

        $count = $method->invoke($command, $analysis);
        $this->assertEquals(3, $count); // 2 + 0 + 1 = 3
    }

    /** @test */
    public function selective_command_handles_missing_properties_gracefully()
    {
        $command = new CleanupSelectiveCommand($this->mockOrchestrator);
        $reflection = new ReflectionClass($command);
        $method = $reflection->getMethod('countUnusedImports');
        $method->setAccessible(true);

        // Test with missing unusedImports property
        $analysis = new \App\Services\Cleanup\Models\CodebaseAnalysis([
            'phpFiles' => [
                (object) [], // No unusedImports property
            ],
            'jsFiles' => [
                (object) ['unusedImports' => ['jsImport1']],
            ]
        ]);

        $count = $method->invoke($command, $analysis);
        $this->assertEquals(1, $count); // Only counts the JS import
    }
}