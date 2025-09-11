<?php

namespace Tests\Unit\Services\Cleanup;

use App\Services\Cleanup\CodeRefactoringService;
use App\Services\Cleanup\FileModificationService;
use App\Services\Cleanup\Models\RefactoringPlan;
use App\Services\Cleanup\Models\ComponentExtractionSuggestion;
use App\Services\Cleanup\Models\MethodExtractionSuggestion;
use App\Services\Cleanup\Models\ReferenceUpdate;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CodeRefactoringServiceTest extends TestCase
{
    private CodeRefactoringService $service;
    private FileModificationService $fileModificationService;
    private string $testFilesDir;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->fileModificationService = new FileModificationService();
        $this->service = new CodeRefactoringService($this->fileModificationService);
        $this->testFilesDir = storage_path('testing/refactoring');
        
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
    
    public function test_can_generate_component_suggestions()
    {
        $duplicateBlocks = [
            [
                'pattern' => '<div class="card"><h3>{{ $title }}</h3><p>{{ $content }}</p></div>',
                'locations' => [
                    ['file' => 'view1.blade.php', 'line' => 10],
                    ['file' => 'view2.blade.php', 'line' => 15],
                ]
            ]
        ];
        
        $suggestions = $this->service->generateComponentSuggestions($duplicateBlocks);
        
        $this->assertCount(1, $suggestions);
        $this->assertInstanceOf(ComponentExtractionSuggestion::class, $suggestions[0]);
        $this->assertCount(2, $suggestions[0]->occurrences);
    }
    
    public function test_can_generate_method_suggestions()
    {
        $duplicateMethods = [
            [
                'methods' => [
                    [
                        'file' => 'Class1.php',
                        'name' => 'formatDate',
                        'code' => 'return date("Y-m-d", strtotime($date));'
                    ],
                    [
                        'file' => 'Class2.php',
                        'name' => 'formatDate',
                        'code' => 'return date("Y-m-d", strtotime($date));'
                    ]
                ]
            ]
        ];
        
        $suggestions = $this->service->generateMethodSuggestions($duplicateMethods);
        
        $this->assertCount(1, $suggestions);
        $this->assertInstanceOf(MethodExtractionSuggestion::class, $suggestions[0]);
        $this->assertEquals('Class1.php', $suggestions[0]->sourceFile);
        $this->assertEquals('formatDate', $suggestions[0]->methodName);
        $this->assertCount(1, $suggestions[0]->duplicateLocations);
    }
    
    public function test_can_extract_methods()
    {
        // Create test source file
        $sourceFile = $this->testFilesDir . '/TestClass.php';
        $sourceContent = '<?php
class TestClass
{
    public function formatDate($date)
    {
        return date("Y-m-d", strtotime($date));
    }
    
    public function otherMethod()
    {
        return "test";
    }
}';
        File::put($sourceFile, $sourceContent);
        
        // Create method extraction suggestion
        $suggestion = new MethodExtractionSuggestion(
            $sourceFile,
            'formatDate',
            'return date("Y-m-d", strtotime($date));',
            [] // No duplicate locations for this test
        );
        
        $result = $this->service->extractMethods([$suggestion]);
        
        $this->assertTrue($result->success);
        $this->assertGreaterThan(0, $result->getRefactoringCount());
        $this->assertContains($suggestion->suggestedFilePath, $result->filesCreated);
        $this->assertFileExists($suggestion->suggestedFilePath);
    }    
 
   public function test_can_extract_components()
    {
        // Create test source file
        $sourceFile = $this->testFilesDir . '/test-view.blade.php';
        $sourceContent = '<div>
    <div class="card">
        <h3>{{ $title }}</h3>
        <p>{{ $content }}</p>
    </div>
    <div class="card">
        <h3>Another Title</h3>
        <p>Another Content</p>
    </div>
</div>';
        File::put($sourceFile, $sourceContent);
        
        // Create component extraction suggestion
        $suggestion = new ComponentExtractionSuggestion(
            'card-component',
            [
                ['file' => $sourceFile, 'content' => '<div class="card"><h3>{{ $title }}</h3><p>{{ $content }}</p></div>']
            ],
            1,
            ['content' => '<div class="card"><h3>{{ $title }}</h3><p>{{ $content }}</p></div>'],
            50,
            []
        );
        
        $result = $this->service->extractComponents([$suggestion]);
        
        $this->assertTrue($result->success);
        $this->assertGreaterThan(0, $result->getRefactoringCount());
        $componentPath = $suggestion->generateComponentPath();
        $this->assertContains($componentPath, $result->filesCreated);
        $this->assertFileExists($componentPath);
    }
    
    public function test_can_consolidate_methods()
    {
        $duplicateMethods = [
            [
                'file' => 'Class1.php',
                'name' => 'formatDate',
                'code' => 'return date("Y-m-d", strtotime($date));',
                'class' => 'Class1',
                'signature' => 'formatDate'
            ],
            [
                'file' => 'Class2.php',
                'name' => 'formatDate',
                'code' => 'return date("Y-m-d", strtotime($date));',
                'class' => 'Class2',
                'signature' => 'formatDate'
            ]
        ];
        
        $result = $this->service->consolidateMethods($duplicateMethods);
        
        $this->assertTrue($result->success);
        $this->assertEquals(1, $result->duplicatesRemoved);
    }
    
    public function test_can_execute_complete_refactoring_plan()
    {
        // Create test files
        $sourceFile = $this->testFilesDir . '/TestClass.php';
        $sourceContent = '<?php
class TestClass
{
    public function duplicateMethod()
    {
        return "duplicate code";
    }
}';
        File::put($sourceFile, $sourceContent);
        
        // Create refactoring plan
        $plan = new RefactoringPlan([
            'createBackups' => true,
            'validateAfterRefactoring' => true
        ]);
        
        $methodSuggestion = new MethodExtractionSuggestion(
            $sourceFile,
            'duplicateMethod',
            'return "duplicate code";',
            [['file' => 'AnotherClass.php']]
        );
        
        $plan->addMethodExtraction($methodSuggestion);
        
        $result = $this->service->executeRefactoring($plan);
        
        $this->assertTrue($result->success);
        $this->assertGreaterThan(0, $result->getRefactoringCount());
        $this->assertGreaterThan(0, $result->getTotalFilesAffected());
    }
    
    public function test_validates_refactoring_plan()
    {
        $plan = new RefactoringPlan();
        
        // Add invalid method extraction (non-existent file)
        $invalidSuggestion = new MethodExtractionSuggestion(
            '/non/existent/file.php',
            'testMethod',
            'return "test";',
            []
        );
        
        $plan->addMethodExtraction($invalidSuggestion);
        
        $errors = $this->service->validateRefactoring($plan);
        
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('does not exist', $errors[0]);
    }
    
    public function test_handles_refactoring_errors_gracefully()
    {
        $plan = new RefactoringPlan();
        
        // Add method extraction with invalid suggestion
        $plan->methodExtractions[] = 'invalid_suggestion';
        
        $result = $this->service->executeRefactoring($plan);
        
        $this->assertFalse($result->success);
        $this->assertNotEmpty($result->errors);
    }
    
    public function test_can_update_references()
    {
        // Create test file
        $testFile = $this->testFilesDir . '/test_references.php';
        $content = '<?php
class TestClass
{
    public function test()
    {
        $this->oldMethod();
        return $this->oldMethod();
    }
}';
        File::put($testFile, $content);
        
        $referenceUpdates = [
            new ReferenceUpdate($testFile, 'oldMethod', 'newMethod')
        ];
        
        $result = $this->service->updateReferences($referenceUpdates);
        
        $this->assertTrue($result);
        
        $modifiedContent = File::get($testFile);
        $this->assertStringNotContainsString('oldMethod', $modifiedContent);
        $this->assertStringContainsString('newMethod', $modifiedContent);
    }
    
    public function test_method_extraction_suggestion_generates_correct_paths()
    {
        $suggestion = new MethodExtractionSuggestion(
            '/path/to/TestClass.php',
            'testMethod',
            'return "test";',
            []
        );
        
        $this->assertEquals('TestClassHelper', $suggestion->suggestedClassName);
        $this->assertEquals('extractedTestMethod', $suggestion->suggestedMethodName);
        $this->assertStringContainsString('Helpers', $suggestion->suggestedFilePath);
        $this->assertStringEndsWith('.php', $suggestion->suggestedFilePath);
    }
    
    public function test_calculates_estimated_savings_correctly()
    {
        $methodCode = "line1\nline2\nline3";
        $duplicateLocations = [
            ['file' => 'file1.php'],
            ['file' => 'file2.php'],
            ['file' => 'file3.php']
        ];
        
        $suggestion = new MethodExtractionSuggestion(
            'source.php',
            'testMethod',
            $methodCode,
            $duplicateLocations
        );
        
        $expectedSavings = 3 * 3; // 3 lines * 3 duplicates
        $this->assertEquals($expectedSavings, $suggestion->getEstimatedSavings());
    }
}