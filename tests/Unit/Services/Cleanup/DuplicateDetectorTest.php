<?php

namespace Tests\Unit\Services\Cleanup;

use App\Models\Cleanup\PhpFileAnalysis;
use App\Services\Cleanup\DuplicateDetector;
use App\Services\Cleanup\Models\DuplicateMethodMatch;
use PHPUnit\Framework\TestCase;

class DuplicateDetectorTest extends TestCase
{
    private DuplicateDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new DuplicateDetector();
    }

    public function test_finds_exact_duplicate_methods()
    {
        $analysis1 = new PhpFileAnalysis(
            filePath: '/test/file1.php',
            methods: [
                [
                    'name' => 'calculateTotal',
                    'class' => 'OrderService',
                    'visibility' => 'public',
                    'static' => false,
                    'line' => 10,
                    'endLine' => 20,
                    'parameters' => [
                        ['name' => 'items', 'type' => 'array']
                    ],
                    'returnType' => 'float'
                ]
            ]
        );

        $analysis2 = new PhpFileAnalysis(
            filePath: '/test/file2.php',
            methods: [
                [
                    'name' => 'calculateTotal',
                    'class' => 'InvoiceService',
                    'visibility' => 'public',
                    'static' => false,
                    'line' => 15,
                    'endLine' => 25,
                    'parameters' => [
                        ['name' => 'items', 'type' => 'array']
                    ],
                    'returnType' => 'float'
                ]
            ]
        );

        // Create test files with identical method content
        $this->createTestFile('/test/file1.php', $this->getTestMethodCode('calculateTotal'));
        $this->createTestFile('/test/file2.php', $this->getTestMethodCode('calculateTotal'));

        $duplicates = $this->detector->findDuplicateMethods([$analysis1, $analysis2]);

        $this->assertCount(1, $duplicates);
        $this->assertInstanceOf(DuplicateMethodMatch::class, $duplicates[0]);
        $this->assertGreaterThan(0.8, $duplicates[0]->similarity);
    }

    public function test_finds_near_duplicate_methods_with_minor_differences()
    {
        $analysis1 = new PhpFileAnalysis(
            filePath: '/test/file1.php',
            methods: [
                [
                    'name' => 'processData',
                    'class' => 'DataProcessor',
                    'visibility' => 'private',
                    'static' => false,
                    'line' => 5,
                    'endLine' => 15,
                    'parameters' => [
                        ['name' => 'data', 'type' => 'array']
                    ]
                ]
            ]
        );

        $analysis2 = new PhpFileAnalysis(
            filePath: '/test/file2.php',
            methods: [
                [
                    'name' => 'handleData',
                    'class' => 'DataHandler',
                    'visibility' => 'private',
                    'static' => false,
                    'line' => 8,
                    'endLine' => 18,
                    'parameters' => [
                        ['name' => 'input', 'type' => 'array']
                    ]
                ]
            ]
        );

        // Create test files with similar but not identical method content
        $this->createTestFile('/test/file1.php', $this->getSimilarMethodCode1());
        $this->createTestFile('/test/file2.php', $this->getSimilarMethodCode2());

        $duplicates = $this->detector->findDuplicateMethods([$analysis1, $analysis2]);

        $this->assertCount(1, $duplicates);
        $duplicate = $duplicates[0];
        $this->assertGreaterThan(0.7, $duplicate->similarity);
        // The similarity might be high due to similar structure, which is expected
        $this->assertTrue($duplicate->similarity >= 0.85 && $duplicate->similarity < 1.0);
        $this->assertContains($duplicate->suggestion->type, ['near_duplicate', 'exact_duplicate']);
    }

    public function test_ignores_very_short_methods()
    {
        $analysis = new PhpFileAnalysis(
            filePath: '/test/file1.php',
            methods: [
                [
                    'name' => 'shortMethod',
                    'class' => 'TestClass',
                    'visibility' => 'public',
                    'static' => false,
                    'line' => 5,
                    'endLine' => 7, // Only 3 lines
                    'parameters' => []
                ]
            ]
        );

        $this->createTestFile('/test/file1.php', $this->getShortMethodCode());

        $duplicates = $this->detector->findDuplicateMethods([$analysis]);

        $this->assertEmpty($duplicates);
    }

    public function test_skips_methods_with_different_signatures()
    {
        $analysis1 = new PhpFileAnalysis(
            filePath: '/test/file1.php',
            methods: [
                [
                    'name' => 'process',
                    'class' => 'Service1',
                    'visibility' => 'public',
                    'static' => false,
                    'line' => 10,
                    'endLine' => 20,
                    'parameters' => [
                        ['name' => 'data', 'type' => 'string']
                    ],
                    'returnType' => 'string'
                ]
            ]
        );

        $analysis2 = new PhpFileAnalysis(
            filePath: '/test/file2.php',
            methods: [
                [
                    'name' => 'process',
                    'class' => 'Service2',
                    'visibility' => 'public',
                    'static' => false,
                    'line' => 15,
                    'endLine' => 25,
                    'parameters' => [
                        ['name' => 'data', 'type' => 'array'],
                        ['name' => 'options', 'type' => 'array']
                    ],
                    'returnType' => 'array'
                ]
            ]
        );

        $this->createTestFile('/test/file1.php', $this->getTestMethodCode('process'));
        $this->createTestFile('/test/file2.php', $this->getTestMethodCode('process'));

        $duplicates = $this->detector->findDuplicateMethods([$analysis1, $analysis2]);

        // Should find no duplicates due to very different signatures
        $this->assertEmpty($duplicates);
    }

    public function test_generates_appropriate_refactoring_suggestions()
    {
        $analysis1 = new PhpFileAnalysis(
            filePath: '/test/file1.php',
            methods: [
                [
                    'name' => 'validateInput',
                    'class' => 'Validator1',
                    'visibility' => 'public',
                    'static' => false,
                    'line' => 10,
                    'endLine' => 25,
                    'parameters' => [
                        ['name' => 'input', 'type' => 'string']
                    ],
                    'returnType' => 'bool'
                ]
            ]
        );

        $analysis2 = new PhpFileAnalysis(
            filePath: '/test/file2.php',
            methods: [
                [
                    'name' => 'validateInput',
                    'class' => 'Validator2',
                    'visibility' => 'public',
                    'static' => false,
                    'line' => 15,
                    'endLine' => 30,
                    'parameters' => [
                        ['name' => 'input', 'type' => 'string']
                    ],
                    'returnType' => 'bool'
                ]
            ]
        );

        $this->createTestFile('/test/file1.php', $this->getValidationMethodCode());
        $this->createTestFile('/test/file2.php', $this->getValidationMethodCode());

        $duplicates = $this->detector->findDuplicateMethods([$analysis1, $analysis2]);

        $this->assertCount(1, $duplicates);
        $duplicate = $duplicates[0];
        
        $this->assertNotEmpty($duplicate->suggestion->description);
        $this->assertContains($duplicate->suggestion->effort, ['low', 'medium', 'high']);
        $this->assertArrayHasKey('lines_saved', $duplicate->suggestion->benefits);
        $this->assertGreaterThan(0, $duplicate->suggestion->getEstimatedLinesSaved());
    }

    private function createTestFile(string $path, string $content): void
    {
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        file_put_contents($path, $content);
    }

    private function getTestMethodCode(string $methodName): string
    {
        return "<?php
class TestClass {
    public function {$methodName}(\$items) {
        \$total = 0;
        foreach (\$items as \$item) {
            \$total += \$item['price'] * \$item['quantity'];
        }
        
        if (\$total > 100) {
            \$total *= 0.9; // 10% discount
        }
        
        return \$total;
    }
}";
    }

    private function getSimilarMethodCode1(): string
    {
        return "<?php
class DataProcessor {
    private function processData(\$data) {
        \$result = [];
        foreach (\$data as \$item) {
            \$processed = \$this->transform(\$item);
            if (\$processed !== null) {
                \$result[] = \$processed;
            }
        }
        \$this->logProcessing(\$result);
        return \$result;
    }
}";
    }

    private function getSimilarMethodCode2(): string
    {
        return "<?php
class DataHandler {
    private function handleData(\$input) {
        \$output = [];
        foreach (\$input as \$element) {
            \$transformed = \$this->convert(\$element);
            if (\$transformed) {
                \$output[] = \$transformed;
            }
        }
        \$this->recordHandling(\$output);
        return \$output;
    }
}";
    }

    private function getShortMethodCode(): string
    {
        return "<?php
class TestClass {
    public function shortMethod() {
        return true;
    }
}";
    }

    private function getValidationMethodCode(): string
    {
        return "<?php
class Validator {
    public function validateInput(\$input) {
        if (empty(\$input)) {
            return false;
        }
        
        if (strlen(\$input) < 3) {
            return false;
        }
        
        if (!preg_match('/^[a-zA-Z0-9]+$/', \$input)) {
            return false;
        }
        
        return true;
    }
}";
    }

    protected function tearDown(): void
    {
        // Clean up test files
        $testFiles = [
            '/test/file1.php',
            '/test/file2.php'
        ];
        
        foreach ($testFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        
        if (is_dir('/test')) {
            rmdir('/test');
        }
        
        parent::tearDown();
    }
}