<?php

namespace Tests\Unit\Services\Cleanup;

use Tests\TestCase;
use App\Services\Cleanup\MetricsCollector;
use App\Services\Cleanup\Models\PerformanceMetrics;
use App\Services\Cleanup\Models\CleanupMetrics;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MetricsCollectorTest extends TestCase
{
    use RefreshDatabase;

    private MetricsCollector $metricsCollector;
    private string $testProjectPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->metricsCollector = new MetricsCollector();
        $this->testProjectPath = base_path('tests/fixtures/sample_project');
        
        // Create test directory structure
        $this->createTestProjectStructure();
    }

    protected function tearDown(): void
    {
        // Clean up test files
        if (is_dir($this->testProjectPath)) {
            $this->removeDirectory($this->testProjectPath);
        }
        parent::tearDown();
    }

    public function test_logs_operation_with_details()
    {
        $operation = 'test_operation';
        $details = ['file' => 'test.php', 'action' => 'analyze'];

        $this->metricsCollector->logOperation($operation, $details);
        $metrics = $this->metricsCollector->getCleanupMetrics();

        $this->assertEquals(1, $metrics->operationsPerformed);
        $this->assertCount(1, $metrics->operationLog);
        $this->assertEquals($operation, $metrics->operationLog[0]['operation']);
        $this->assertEquals($details, $metrics->operationLog[0]['details']);
    }

    public function test_records_memory_usage_at_checkpoints()
    {
        $checkpoint1 = 'start';
        $checkpoint2 = 'middle';

        $this->metricsCollector->recordMemoryUsage($checkpoint1);
        $this->metricsCollector->recordMemoryUsage($checkpoint2);

        $metrics = $this->metricsCollector->getCleanupMetrics();

        $this->assertArrayHasKey($checkpoint1, $metrics->memoryUsage);
        $this->assertArrayHasKey($checkpoint2, $metrics->memoryUsage);
        $this->assertArrayHasKey('current', $metrics->memoryUsage[$checkpoint1]);
        $this->assertArrayHasKey('peak', $metrics->memoryUsage[$checkpoint1]);
    }

    public function test_records_before_metrics()
    {
        $beforeMetrics = $this->metricsCollector->recordBeforeMetrics($this->testProjectPath);

        $this->assertInstanceOf(PerformanceMetrics::class, $beforeMetrics);
        $this->assertGreaterThan(0, $beforeMetrics->totalFiles);
        $this->assertGreaterThan(0, $beforeMetrics->totalFileSize);
        $this->assertGreaterThan(0, $beforeMetrics->totalLines);
    }

    public function test_records_after_metrics()
    {
        // Record before metrics first
        $this->metricsCollector->recordBeforeMetrics($this->testProjectPath);
        
        $afterMetrics = $this->metricsCollector->recordAfterMetrics($this->testProjectPath);

        $this->assertInstanceOf(PerformanceMetrics::class, $afterMetrics);
        $this->assertGreaterThan(0, $afterMetrics->totalFiles);
    }

    public function test_calculates_improvements()
    {
        // Record before metrics
        $this->metricsCollector->recordBeforeMetrics($this->testProjectPath);
        
        // Simulate some cleanup by removing a file
        $testFile = $this->testProjectPath . '/test_removal.php';
        if (file_exists($testFile)) {
            unlink($testFile);
        }
        
        // Record after metrics
        $this->metricsCollector->recordAfterMetrics($this->testProjectPath);
        
        $improvements = $this->metricsCollector->calculateImprovements();

        $this->assertArrayHasKey('file_size_reduction', $improvements);
        $this->assertArrayHasKey('file_count_reduction', $improvements);
        $this->assertArrayHasKey('line_count_reduction', $improvements);
        $this->assertArrayHasKey('complexity_reduction', $improvements);
        
        // Check structure of improvements
        $this->assertArrayHasKey('bytes', $improvements['file_size_reduction']);
        $this->assertArrayHasKey('percentage', $improvements['file_size_reduction']);
        $this->assertArrayHasKey('mb', $improvements['file_size_reduction']);
    }

    public function test_throws_exception_when_calculating_improvements_without_metrics()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Both before and after metrics must be recorded');

        $this->metricsCollector->calculateImprovements();
    }

    public function test_gets_comprehensive_cleanup_metrics()
    {
        $this->metricsCollector->logOperation('test_op_1');
        $this->metricsCollector->logOperation('test_op_2');
        $this->metricsCollector->recordMemoryUsage('test_checkpoint');

        $metrics = $this->metricsCollector->getCleanupMetrics();

        $this->assertInstanceOf(CleanupMetrics::class, $metrics);
        $this->assertEquals(2, $metrics->operationsPerformed);
        $this->assertGreaterThan(0, $metrics->executionTime);
        $this->assertArrayHasKey('test_checkpoint', $metrics->memoryUsage);
    }

    public function test_performance_metrics_calculations()
    {
        $metrics = new PerformanceMetrics([
            'totalFileSize' => 1024000,
            'totalFiles' => 10,
            'totalLines' => 500,
            'cyclomaticComplexity' => 50,
            'codeSmells' => 5,
        ]);

        $this->assertEquals('1000 KB', $metrics->getFormattedFileSize());
        $this->assertEquals(0.1, $metrics->getComplexityPerLine());
        $this->assertEquals(0.5, $metrics->getCodeSmellsPerFile());
    }

    public function test_cleanup_metrics_calculations()
    {
        $metricsData = [
            'execution_time' => 120.5,
            'operations_performed' => 10,
            'performance_improvements' => [
                'file_size_reduction' => ['mb' => 5.2, 'percentage' => 15.0],
                'file_count_reduction' => ['percentage' => 10.0],
                'line_count_reduction' => ['percentage' => 20.0],
            ],
        ];

        $metrics = new CleanupMetrics($metricsData);

        $this->assertEquals('2.01 minutes', $metrics->getFormattedExecutionTime());
        $this->assertEquals(5.2, $metrics->getTotalSizeReductionMB());
        $this->assertEquals(15.0, $metrics->getTotalPercentageImprovement());
        $this->assertGreaterThan(0, $metrics->getOperationsPerSecond());
    }

    private function createTestProjectStructure(): void
    {
        if (!is_dir($this->testProjectPath)) {
            mkdir($this->testProjectPath, 0755, true);
        }

        // Create test PHP files
        file_put_contents($this->testProjectPath . '/test1.php', "<?php\nclass TestClass {\n    public function testMethod() {\n        return 'test';\n    }\n}\n");
        file_put_contents($this->testProjectPath . '/test2.php', "<?php\nfunction testFunction() {\n    if (true) {\n        return 'test';\n    }\n}\n");
        file_put_contents($this->testProjectPath . '/test_removal.php', "<?php\n// This file will be removed\necho 'test';\n");

        // Create test JS file
        file_put_contents($this->testProjectPath . '/test.js', "function testJs() {\n    console.log('test');\n}\n");

        // Create test CSS file
        file_put_contents($this->testProjectPath . '/test.css', ".test-class {\n    color: red;\n}\n");

        // Create test Blade file
        file_put_contents($this->testProjectPath . '/test.blade.php', "<div>{{ \$test }}</div>\n");
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}