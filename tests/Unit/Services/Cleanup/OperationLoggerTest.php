<?php

namespace Tests\Unit\Services\Cleanup;

use Tests\TestCase;
use App\Services\Cleanup\OperationLogger;
use App\Services\Cleanup\Models\OperationLog;
use Illuminate\Support\Facades\Log;

class OperationLoggerTest extends TestCase
{
    private OperationLogger $logger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = new OperationLogger();
        Log::spy();
    }

    public function test_starts_operation_and_returns_id()
    {
        $operationType = 'test_operation';
        $context = ['file' => 'test.php'];

        $operationId = $this->logger->startOperation($operationType, $context);

        $this->assertIsString($operationId);
        $this->assertStringContainsString($operationType, $operationId);
        
        Log::shouldHaveReceived('info')
            ->once()
            ->with("Cleanup operation started: {$operationType}", [
                'operation_id' => $operationId,
                'context' => $context,
            ]);
    }

    public function test_completes_operation_successfully()
    {
        $operationId = $this->logger->startOperation('test_operation');
        $results = ['files_processed' => 5];

        // Add small delay to ensure execution time is measurable
        usleep(1000);

        $this->logger->completeOperation($operationId, $results);

        $operations = $this->logger->getOperations();
        $operation = $operations[0];

        $this->assertEquals('completed', $operation['status']);
        $this->assertEquals($results, $operation['results']);
        $this->assertArrayHasKey('execution_time', $operation);
        $this->assertGreaterThan(0, $operation['execution_time']);
        
        Log::shouldHaveReceived('info')
            ->with("Cleanup operation completed: test_operation", \Mockery::type('array'));
    }

    public function test_fails_operation_with_error()
    {
        $operationId = $this->logger->startOperation('test_operation');
        $error = 'Test error message';
        $exception = new \Exception('Test exception');

        $this->logger->failOperation($operationId, $error, $exception);

        $operations = $this->logger->getOperations();
        $operation = $operations[0];

        $this->assertEquals('failed', $operation['status']);
        $this->assertEquals($error, $operation['error']);
        $this->assertArrayHasKey('exception', $operation);
        $this->assertEquals('Test exception', $operation['exception']['message']);
        
        Log::shouldHaveReceived('error')
            ->with("Cleanup operation failed: test_operation", \Mockery::type('array'));
    }

    public function test_throws_exception_for_unknown_operation_id()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Operation unknown_id not found');

        $this->logger->completeOperation('unknown_id');
    }

    public function test_logs_file_operation()
    {
        $operationType = 'delete';
        $filePath = '/path/to/file.php';
        $details = ['reason' => 'unused'];

        $this->logger->logFileOperation($operationType, $filePath, $details);

        $operations = $this->logger->getOperations();
        $this->assertCount(1, $operations);
        
        $operation = $operations[0];
        $this->assertEquals('file_delete', $operation['type']);
        $this->assertEquals('completed', $operation['status']);
        $this->assertEquals($filePath, $operation['context']['file_path']);
    }

    public function test_logs_analysis_results()
    {
        $analysisType = 'php_unused_imports';
        $results = ['unused_imports' => ['App\UnusedClass']];

        $this->logger->logAnalysisResults($analysisType, $results);

        $operations = $this->logger->getOperations();
        $operation = $operations[0];

        $this->assertEquals('analysis_php_unused_imports', $operation['type']);
        $this->assertEquals($results, $operation['results']);
    }

    public function test_gets_operations_by_type()
    {
        $this->logger->startOperation('type_a');
        $this->logger->startOperation('type_b');
        $this->logger->startOperation('type_a');

        $typeAOperations = $this->logger->getOperationsByType('type_a');
        $typeBOperations = $this->logger->getOperationsByType('type_b');

        $this->assertCount(2, $typeAOperations);
        $this->assertCount(1, $typeBOperations);
    }

    public function test_gets_failed_operations()
    {
        $op1 = $this->logger->startOperation('operation_1');
        $op2 = $this->logger->startOperation('operation_2');
        $op3 = $this->logger->startOperation('operation_3');

        $this->logger->completeOperation($op1);
        $this->logger->failOperation($op2, 'Error 1');
        $this->logger->failOperation($op3, 'Error 2');

        $failedOperations = $this->logger->getFailedOperations();

        $this->assertCount(2, $failedOperations);
        foreach ($failedOperations as $operation) {
            $this->assertEquals('failed', $operation['status']);
        }
    }

    public function test_gets_operation_statistics()
    {
        $op1 = $this->logger->startOperation('operation_1');
        $op2 = $this->logger->startOperation('operation_2');
        $op3 = $this->logger->startOperation('operation_3');

        $this->logger->completeOperation($op1);
        $this->logger->failOperation($op2, 'Error');
        // op3 remains in progress

        $stats = $this->logger->getOperationStatistics();

        $this->assertEquals(3, $stats['total_operations']);
        $this->assertEquals(1, $stats['completed_operations']);
        $this->assertEquals(1, $stats['failed_operations']);
        $this->assertEquals(1, $stats['in_progress_operations']);
        $this->assertEquals(33.33, round($stats['success_rate'], 2));
    }

    public function test_exports_operation_log()
    {
        $this->logger->startOperation('test_operation');
        $this->logger->logCleanupStats(['files_cleaned' => 10]);

        $operationLog = $this->logger->exportLog();

        $this->assertInstanceOf(OperationLog::class, $operationLog);
        $this->assertNotEmpty($operationLog->sessionId);
        $this->assertCount(2, $operationLog->operations);
        $this->assertArrayHasKey('total_operations', $operationLog->statistics);
    }

    public function test_clears_log()
    {
        $this->logger->startOperation('test_operation');
        $this->logger->logCleanupStats(['test' => 'data']);

        $this->assertCount(2, $this->logger->getOperations());

        $this->logger->clearLog();

        $this->assertCount(0, $this->logger->getOperations());
        $this->assertCount(0, $this->logger->getFailedOperations());
    }

    public function test_operation_log_model_functionality()
    {
        $operations = [
            [
                'type' => 'analysis',
                'status' => 'completed',
                'started_at' => now()->subMinutes(5),
                'execution_time' => 2.5,
            ],
            [
                'type' => 'cleanup',
                'status' => 'completed',
                'started_at' => now()->subMinutes(3),
                'execution_time' => 1.8,
            ],
        ];

        $operationLog = new OperationLog([
            'session_id' => 'test_session',
            'operations' => $operations,
            'statistics' => ['total_operations' => 2],
            'errors' => [],
        ]);

        $operationsByType = $operationLog->getOperationsByType();
        $this->assertArrayHasKey('analysis', $operationsByType);
        $this->assertArrayHasKey('cleanup', $operationsByType);

        $timeline = $operationLog->getTimeline();
        $this->assertCount(2, $timeline);

        $performanceMetrics = $operationLog->getPerformanceMetrics();
        $this->assertEquals(4.3, $performanceMetrics['total_time']);
        $this->assertEquals(2.15, $performanceMetrics['average_time']);
    }
}