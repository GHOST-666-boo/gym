<?php

namespace Tests\Unit\Services\Cleanup;

use App\Services\Cleanup\FileModificationService;
use App\Services\Cleanup\Models\FileModificationPlan;
use App\Services\Cleanup\Models\ReferenceUpdate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class FileModificationServiceTest extends TestCase
{
    
    private FileModificationService $service;
    private string $testFilesDir;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new FileModificationService();
        $this->testFilesDir = storage_path('testing/file-modification');
        
        // Create test directory
        if (!File::isDirectory($this->testFilesDir)) {
            File::makeDirectory($this->testFilesDir, 0755, true);
        }
    }
    
    protected function tearDown(): void
    {
        // Clean up test files
        if (File::isDirectory($this->testFilesDir)) {
            File::deleteDirectory($this->testFilesDir);
        }
        
        parent::tearDown();
    }
    
    public function test_can_remove_unused_imports()
    {
        // Create test PHP file with unused imports
        $testFile = $this->testFilesDir . '/test_imports.php';
        $content = '<?php

use App\Models\User;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

class TestClass
{
    public function test()
    {
        $user = new User();
        Log::info("test");
    }
}';
        
        File::put($testFile, $content);
        
        // Remove unused Product import
        $result = $this->service->removeUnusedImports($testFile, ['App\Models\Product']);
        
        $this->assertTrue($result);
        
        $modifiedContent = File::get($testFile);
        $this->assertStringNotContainsString('use App\Models\Product;', $modifiedContent);
        $this->assertStringContainsString('use App\Models\User;', $modifiedContent);
        $this->assertStringContainsString('use Illuminate\Support\Facades\Log;', $modifiedContent);
    }
    
    public function test_can_remove_unused_methods()
    {
        // Create test PHP file with unused methods
        $testFile = $this->testFilesDir . '/test_methods.php';
        $content = '<?php

class TestClass
{
    public function usedMethod()
    {
        return "used";
    }
    
    public function unusedMethod()
    {
        return "unused";
    }
    
    private function anotherUnusedMethod()
    {
        return "also unused";
    }
}';
        
        File::put($testFile, $content);
        
        // Remove unused methods
        $result = $this->service->removeUnusedMethods($testFile, ['unusedMethod', 'anotherUnusedMethod']);
        
        $this->assertTrue($result);
        
        $modifiedContent = File::get($testFile);
        $this->assertStringNotContainsString('function unusedMethod', $modifiedContent);
        $this->assertStringNotContainsString('function anotherUnusedMethod', $modifiedContent);
        $this->assertStringContainsString('function usedMethod', $modifiedContent);
    }
    
    public function test_can_update_method_references()
    {
        // Create test files with method references
        $testFile1 = $this->testFilesDir . '/test_references1.php';
        $testFile2 = $this->testFilesDir . '/test_references2.php';
        
        $content1 = '<?php
class TestClass
{
    public function callOldMethod()
    {
        $this->oldMethodName();
        return $this->oldMethodName();
    }
}';
        
        $content2 = '<?php
class AnotherClass
{
    public function test()
    {
        $obj = new TestClass();
        $obj->oldMethodName();
    }
}';
        
        File::put($testFile1, $content1);
        File::put($testFile2, $content2);
        
        // Update method references
        $referenceUpdates = [
            new ReferenceUpdate($testFile1, 'oldMethodName', 'newMethodName'),
            new ReferenceUpdate($testFile2, 'oldMethodName', 'newMethodName'),
        ];
        
        $result = $this->service->updateMethodReferences($referenceUpdates);
        
        $this->assertTrue($result);
        
        $modifiedContent1 = File::get($testFile1);
        $modifiedContent2 = File::get($testFile2);
        
        $this->assertStringNotContainsString('oldMethodName', $modifiedContent1);
        $this->assertStringNotContainsString('oldMethodName', $modifiedContent2);
        $this->assertStringContainsString('newMethodName', $modifiedContent1);
        $this->assertStringContainsString('newMethodName', $modifiedContent2);
    }    
   
 public function test_can_create_and_restore_backup()
    {
        // Create test file
        $testFile = $this->testFilesDir . '/test_backup.php';
        $originalContent = '<?php echo "original content";';
        File::put($testFile, $originalContent);
        
        // Create backup
        $backupPath = $this->service->createFileBackup($testFile);
        
        $this->assertFileExists($backupPath);
        $this->assertEquals($originalContent, File::get($backupPath));
        
        // Modify original file
        $modifiedContent = '<?php echo "modified content";';
        File::put($testFile, $modifiedContent);
        
        // Restore from backup
        $result = $this->service->restoreFromBackup($testFile, $backupPath);
        
        $this->assertTrue($result);
        $this->assertEquals($originalContent, File::get($testFile));
        
        // Clean up backup
        File::delete($backupPath);
    }
    
    public function test_execute_modifications_with_plan()
    {
        // Create test PHP file
        $testFile = $this->testFilesDir . '/test_plan.php';
        $content = '<?php

use App\Models\User;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

class TestClass
{
    public function usedMethod()
    {
        $user = new User();
        $unusedVar = "test";
        Log::info("test");
        return $user;
    }
    
    public function unusedMethod()
    {
        return "unused";
    }
}';
        
        File::put($testFile, $content);
        
        // Create modification plan
        $plan = new FileModificationPlan($testFile, [
            'importsToRemove' => ['App\Models\Product'],
            'methodsToRemove' => ['unusedMethod'],
            'variablesToRemove' => ['unusedVar'],
            'createBackup' => true
        ]);
        
        // Execute modifications
        $result = $this->service->executeModifications($plan);
        
        $this->assertTrue($result->success);
        $this->assertNotNull($result->backupPath);
        $this->assertGreaterThan(0, $result->getModificationCount());
        $this->assertGreaterThan(0, $result->bytesReduced);
        
        $modifiedContent = File::get($testFile);
        $this->assertStringNotContainsString('use App\Models\Product;', $modifiedContent);
        $this->assertStringNotContainsString('function unusedMethod', $modifiedContent);
        $this->assertStringContainsString('use App\Models\User;', $modifiedContent);
        $this->assertStringContainsString('function usedMethod', $modifiedContent);
        
        // Clean up backup
        if ($result->backupPath) {
            File::delete($result->backupPath);
        }
    }
    
    public function test_validation_catches_errors()
    {
        // Test with non-existent file
        $plan = new FileModificationPlan('/non/existent/file.php');
        $errors = $this->service->validateModifications($plan);
        
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('does not exist', $errors[0]);
    }
    
    public function test_validation_catches_php_syntax_errors()
    {
        // Create file with syntax error
        $testFile = $this->testFilesDir . '/syntax_error.php';
        $content = '<?php class Test { function test( { return "syntax error"; } }';
        File::put($testFile, $content);
        
        $plan = new FileModificationPlan($testFile, [
            'importsToRemove' => ['SomeClass']
        ]);
        
        $errors = $this->service->validateModifications($plan);
        
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('syntax error', $errors[0]);
    }
    
    public function test_handles_non_php_files_appropriately()
    {
        // Create non-PHP file
        $testFile = $this->testFilesDir . '/test.txt';
        $content = 'This is a text file';
        File::put($testFile, $content);
        
        $plan = new FileModificationPlan($testFile, [
            'importsToRemove' => ['SomeClass'] // This should cause validation error
        ]);
        
        $errors = $this->service->validateModifications($plan);
        
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Cannot perform PHP-specific operations', $errors[0]);
    }
    
    public function test_reference_update_model()
    {
        $update = new ReferenceUpdate(
            '/path/to/file.php',
            'oldMethod',
            'newMethod',
            42,
            'some context'
        );
        
        $this->assertEquals('/path/to/file.php', $update->filePath);
        $this->assertEquals('oldMethod', $update->oldReference);
        $this->assertEquals('newMethod', $update->newReference);
        $this->assertEquals(42, $update->lineNumber);
        $this->assertEquals('some context', $update->context);
        $this->assertEquals('oldMethod', $update->getSearchPattern());
        $this->assertEquals('newMethod', $update->getReplacement());
    }
}