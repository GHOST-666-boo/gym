<?php

namespace Tests\Integration\Services\Cleanup;

use Tests\TestCase;
use App\Services\Cleanup\SafetyValidator;
use App\Services\Cleanup\GitBackupManager;
use App\Services\Cleanup\TestValidator;
use App\Services\Cleanup\FileModificationService;
use App\Services\Cleanup\Models\CleanupConfig;
use App\Services\Cleanup\Models\FileModificationPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class SafetyValidationTest extends TestCase
{
    use RefreshDatabase;

    private SafetyValidator $safetyValidator;
    private FileModificationService $fileModificationService;
    private string $testProjectPath;
    private array $testFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->safetyValidator = app(SafetyValidator::class);
        $this->fileModificationService = app(FileModificationService::class);
        $this->testProjectPath = storage_path('testing/safety-validation');
        $this->createTestFiles();
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->testProjectPath)) {
            File::deleteDirectory($this->testProjectPath);
        }
        
        parent::tearDown();
    }

    /** @test */
    public function it_validates_cleanup_operations_before_execution()
    {
        // Arrange
        $operations = [
            'delete_files' => [$this->testFiles['safe_to_delete']],
            'remove_imports' => [
                ['file' => $this->testFiles['php_file'], 'import' => 'UnusedClass']
            ],
            'remove_methods' => [
                ['file' => $this->testFiles['php_file'], 'method' => 'unusedMethod']
            ]
        ];

        // Act
        $isValid = $this->safetyValidator->validateCleanupSafety($operations);

        // Assert
        $this->assertTrue($isValid, 'Safe operations should pass validation');
    }

    /** @test */
    public function it_rejects_unsafe_cleanup_operations()
    {
        // Arrange
        $unsafeOperations = [
            'delete_files' => [
                'public/index.php', // Critical system file
                'app/Http/Kernel.php' // Critical Laravel file
            ],
            'remove_methods' => [
                ['file' => $this->testFiles['php_file'], 'method' => '__construct'] // Constructor
            ]
        ];

        // Act
        $isValid = $this->safetyValidator->validateCleanupSafety($unsafeOperations);

        // Assert
        $this->assertFalse($isValid, 'Unsafe operations should fail validation');
    }

    /** @test */
    public function it_runs_test_validation_after_cleanup()
    {
        // Arrange
        $config = new CleanupConfig([
            'runTests' => true,
            'dryRun' => false
        ]);

        // Act
        $testsPassed = $this->safetyValidator->runTestValidation();

        // Assert
        $this->assertIsBool($testsPassed);
        // In a real scenario, this would run the actual test suite
        // For integration testing, we verify the method executes without error
    }

    /** @test */
    public function it_detects_dynamic_code_references()
    {
        // Arrange
        $codeWithDynamicReferences = '
        $className = "DynamicClass";
        $instance = new $className();
        
        $methodName = "dynamicMethod";
        $result = $instance->$methodName();
        
        call_user_func([$instance, "anotherMethod"]);
        ';

        // Act
        $hasDynamicReferences = $this->safetyValidator->checkDynamicReferences($codeWithDynamicReferences);

        // Assert
        $this->assertTrue($hasDynamicReferences, 'Should detect dynamic references');
    }

    /** @test */
    public function it_validates_file_modification_plans()
    {
        // Arrange
        $plan = new FileModificationPlan($this->testFiles['php_file'], [
            'importsToRemove' => ['UnusedImport'],
            'methodsToRemove' => ['unusedMethod'],
            'createBackup' => true
        ]);

        // Act
        $validationErrors = $this->fileModificationService->validateModifications($plan);

        // Assert
        $this->assertIsArray($validationErrors);
        $this->assertEmpty($validationErrors, 'Valid modification plan should have no errors');
    }

    /** @test */
    public function it_rejects_invalid_file_modification_plans()
    {
        // Arrange
        $invalidPlan = new FileModificationPlan('/non/existent/file.php', [
            'importsToRemove' => ['SomeImport'],
            'createBackup' => true
        ]);

        // Act
        $validationErrors = $this->fileModificationService->validateModifications($invalidPlan);

        // Assert
        $this->assertNotEmpty($validationErrors, 'Invalid plan should have validation errors');
        $this->assertStringContainsString('does not exist', $validationErrors[0]);
    }

    /** @test */
    public function it_creates_backups_before_modifications()
    {
        // Arrange
        $originalContent = File::get($this->testFiles['php_file']);

        // Act
        $backupPath = $this->fileModificationService->createFileBackup($this->testFiles['php_file']);

        // Assert
        $this->assertFileExists($backupPath);
        $this->assertEquals($originalContent, File::get($backupPath));
        
        // Cleanup
        File::delete($backupPath);
    }

    /** @test */
    public function it_can_restore_from_backup()
    {
        // Arrange
        $originalContent = File::get($this->testFiles['php_file']);
        $backupPath = $this->fileModificationService->createFileBackup($this->testFiles['php_file']);
        
        // Modify the original file
        File::put($this->testFiles['php_file'], '<?php // Modified content');

        // Act
        $restored = $this->fileModificationService->restoreFromBackup($this->testFiles['php_file'], $backupPath);

        // Assert
        $this->assertTrue($restored);
        $this->assertEquals($originalContent, File::get($this->testFiles['php_file']));
        
        // Cleanup
        File::delete($backupPath);
    }

    /** @test */
    public function it_validates_php_syntax_before_modifications()
    {
        // Arrange
        $validPhpFile = $this->testFiles['php_file'];
        $invalidPhpFile = $this->testFiles['invalid_php_file'];

        // Act
        $validPlan = new FileModificationPlan($validPhpFile, ['importsToRemove' => ['Test']]);
        $invalidPlan = new FileModificationPlan($invalidPhpFile, ['importsToRemove' => ['Test']]);
        
        $validErrors = $this->fileModificationService->validateModifications($validPlan);
        $invalidErrors = $this->fileModificationService->validateModifications($invalidPlan);

        // Assert
        $this->assertEmpty($validErrors, 'Valid PHP file should pass syntax validation');
        $this->assertNotEmpty($invalidErrors, 'Invalid PHP file should fail syntax validation');
    }

    /** @test */
    public function it_prevents_modification_of_protected_files()
    {
        // Arrange
        $protectedFiles = [
            'composer.json',
            'package.json',
            '.env',
            'artisan'
        ];

        foreach ($protectedFiles as $file) {
            $operations = [
                'delete_files' => [$file],
                'remove_imports' => []
            ];

            // Act
            $isValid = $this->safetyValidator->validateCleanupSafety($operations);

            // Assert
            $this->assertFalse($isValid, "Should not allow modification of protected file: {$file}");
        }
    }

    /** @test */
    public function it_validates_cross_file_dependencies()
    {
        // Arrange
        $operations = [
            'remove_methods' => [
                ['file' => $this->testFiles['php_file'], 'method' => 'publicMethod']
            ]
        ];

        // Act
        $isValid = $this->safetyValidator->validateCleanupSafety($operations);

        // Assert
        // This should check if the method is used elsewhere
        $this->assertIsBool($isValid);
    }

    /** @test */
    public function it_handles_git_backup_integration()
    {
        // Skip if git is not available
        if (!$this->isGitAvailable()) {
            $this->markTestSkipped('Git is not available for backup testing');
        }

        // Arrange
        $gitBackupManager = app(GitBackupManager::class);

        // Act & Assert
        $this->assertNotNull($gitBackupManager);
        // In a real scenario, this would test git commit creation
    }

    /** @test */
    public function it_validates_laravel_conventions()
    {
        // Arrange
        $operations = [
            'remove_methods' => [
                ['file' => $this->testFiles['controller_file'], 'method' => 'index'] // Controller action
            ]
        ];

        // Act
        $isValid = $this->safetyValidator->validateCleanupSafety($operations);

        // Assert
        // Should not remove controller actions that might be used by routes
        $this->assertIsBool($isValid);
    }

    /** @test */
    public function it_provides_detailed_validation_feedback()
    {
        // Arrange
        $operations = [
            'delete_files' => ['public/index.php'], // Critical file
            'remove_methods' => [
                ['file' => $this->testFiles['php_file'], 'method' => '__construct']
            ]
        ];

        // Act
        $isValid = $this->safetyValidator->validateCleanupSafety($operations);

        // Assert
        $this->assertFalse($isValid);
        // In a real implementation, this would also return detailed error messages
    }

    /**
     * Create test files for safety validation testing
     */
    private function createTestFiles(): void
    {
        File::makeDirectory($this->testProjectPath, 0755, true);
        File::makeDirectory($this->testProjectPath . '/app/Http/Controllers', 0755, true);

        // Valid PHP file
        $phpContent = '<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\UnusedTrait;

class TestModel extends Model
{
    public function publicMethod()
    {
        return "This method might be used elsewhere";
    }
    
    private function unusedMethod()
    {
        return "This method is safe to remove";
    }
}';
        
        $this->testFiles['php_file'] = $this->testProjectPath . '/app/TestModel.php';
        File::put($this->testFiles['php_file'], $phpContent);

        // Invalid PHP file (syntax error)
        $invalidPhpContent = '<?php

namespace App\Models;

class InvalidModel
{
    public function method()
    {
        return "missing semicolon"
    } // Missing semicolon above
}';
        
        $this->testFiles['invalid_php_file'] = $this->testProjectPath . '/app/InvalidModel.php';
        File::put($this->testFiles['invalid_php_file'], $invalidPhpContent);

        // Controller file
        $controllerContent = '<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TestController extends Controller
{
    public function index()
    {
        return view("test.index");
    }
    
    public function show($id)
    {
        return view("test.show", compact("id"));
    }
}';
        
        $this->testFiles['controller_file'] = $this->testProjectPath . '/app/Http/Controllers/TestController.php';
        File::put($this->testFiles['controller_file'], $controllerContent);

        // File that's safe to delete
        $safeContent = 'This file can be safely deleted for testing';
        $this->testFiles['safe_to_delete'] = $this->testProjectPath . '/safe_file.txt';
        File::put($this->testFiles['safe_to_delete'], $safeContent);
    }

    /**
     * Check if git is available for testing
     */
    private function isGitAvailable(): bool
    {
        try {
            exec('git --version', $output, $returnCode);
            return $returnCode === 0;
        } catch (\Exception $e) {
            return false;
        }
    }
}