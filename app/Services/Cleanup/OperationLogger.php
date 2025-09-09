<?php

namespace App\Services\Cleanup;

use App\Services\Cleanup\Models\OperationLog;
use Illuminate\Support\Facades\Log;

class OperationLogger
{
    private array $operations = [];
    private array $timings = [];
    private array $errors = [];
    private string $sessionId;

    public function __construct()
    {
        $this->sessionId = uniqid('cleanup_', true);
    }
    
    /**
     * Start logging operations
     */
    public function startLogging(): void
    {
        Log::info('Starting cleanup operation logging', [
            'session_id' => $this->sessionId
        ]);
    }
    
    /**
     * Stop logging operations
     */
    public function stopLogging(): void
    {
        Log::info('Stopping cleanup operation logging', [
            'session_id' => $this->sessionId,
            'total_operations' => count($this->operations)
        ]);
    }
    
    /**
     * Get the operation log
     */
    public function getLog(): OperationLog
    {
        return $this->exportLog();
    }

    /**
     * Start timing an operation
     */
    public function startOperation(string $operationType, array $context = []): string
    {
        $operationId = uniqid($operationType . '_', true);
        
        $this->timings[$operationId] = [
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(true),
        ];

        $this->operations[$operationId] = [
            'id' => $operationId,
            'type' => $operationType,
            'status' => 'started',
            'context' => $context,
            'session_id' => $this->sessionId,
            'started_at' => now(),
        ];

        Log::info("Cleanup operation started: {$operationType}", [
            'operation_id' => $operationId,
            'context' => $context,
        ]);

        return $operationId;
    }

    /**
     * Complete an operation successfully
     */
    public function completeOperation(string $operationId, array $results = []): void
    {
        if (!isset($this->operations[$operationId])) {
            throw new \InvalidArgumentException("Operation {$operationId} not found");
        }

        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);

        $this->operations[$operationId]['status'] = 'completed';
        $this->operations[$operationId]['results'] = $results;
        $this->operations[$operationId]['completed_at'] = now();
        
        if (isset($this->timings[$operationId])) {
            $this->operations[$operationId]['execution_time'] = $endTime - $this->timings[$operationId]['start_time'];
            $this->operations[$operationId]['memory_used'] = $endMemory - $this->timings[$operationId]['start_memory'];
        }

        Log::info("Cleanup operation completed: {$this->operations[$operationId]['type']}", [
            'operation_id' => $operationId,
            'execution_time' => $this->operations[$operationId]['execution_time'] ?? 0,
            'results' => $results,
        ]);
    }

    /**
     * Mark an operation as failed
     */
    public function failOperation(string $operationId, string $error, \Throwable $exception = null): void
    {
        if (!isset($this->operations[$operationId])) {
            throw new \InvalidArgumentException("Operation {$operationId} not found");
        }

        $endTime = microtime(true);

        $this->operations[$operationId]['status'] = 'failed';
        $this->operations[$operationId]['error'] = $error;
        $this->operations[$operationId]['failed_at'] = now();
        
        if (isset($this->timings[$operationId])) {
            $this->operations[$operationId]['execution_time'] = $endTime - $this->timings[$operationId]['start_time'];
        }

        if ($exception) {
            $this->operations[$operationId]['exception'] = [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ];
        }

        $this->errors[] = [
            'operation_id' => $operationId,
            'error' => $error,
            'exception' => $exception?->getMessage(),
            'timestamp' => now(),
        ];

        Log::error("Cleanup operation failed: {$this->operations[$operationId]['type']}", [
            'operation_id' => $operationId,
            'error' => $error,
            'exception' => $exception?->getMessage(),
        ]);
    }

    /**
     * Log a file operation (creation, modification, deletion)
     */
    public function logFileOperation(string $operationType, string $filePath, array $details = []): void
    {
        $operationId = $this->startOperation('file_' . $operationType, [
            'file_path' => $filePath,
            'details' => $details,
        ]);

        $this->completeOperation($operationId, [
            'file_path' => $filePath,
            'operation' => $operationType,
            'details' => $details,
        ]);
    }

    /**
     * Log code analysis results
     */
    public function logAnalysisResults(string $analysisType, array $results): void
    {
        $operationId = $this->startOperation('analysis_' . $analysisType, [
            'analysis_type' => $analysisType,
        ]);

        $this->completeOperation($operationId, $results);
    }

    /**
     * Log cleanup statistics
     */
    public function logCleanupStats(array $stats): void
    {
        $operationId = $this->startOperation('cleanup_stats', $stats);
        $this->completeOperation($operationId, $stats);
    }

    /**
     * Get all operations for the current session
     */
    public function getOperations(): array
    {
        return array_values($this->operations);
    }

    /**
     * Get operations by type
     */
    public function getOperationsByType(string $type): array
    {
        return array_filter($this->operations, function ($operation) use ($type) {
            return $operation['type'] === $type;
        });
    }

    /**
     * Get failed operations
     */
    public function getFailedOperations(): array
    {
        return array_filter($this->operations, function ($operation) {
            return $operation['status'] === 'failed';
        });
    }

    /**
     * Get operation statistics
     */
    public function getOperationStatistics(): array
    {
        $total = count($this->operations);
        $completed = count(array_filter($this->operations, fn($op) => $op['status'] === 'completed'));
        $failed = count(array_filter($this->operations, fn($op) => $op['status'] === 'failed'));
        $inProgress = count(array_filter($this->operations, fn($op) => $op['status'] === 'started'));

        $totalExecutionTime = array_sum(array_column($this->operations, 'execution_time'));
        $averageExecutionTime = $total > 0 ? $totalExecutionTime / $total : 0;

        return [
            'total_operations' => $total,
            'completed_operations' => $completed,
            'failed_operations' => $failed,
            'in_progress_operations' => $inProgress,
            'success_rate' => $total > 0 ? ($completed / $total) * 100 : 0,
            'total_execution_time' => $totalExecutionTime,
            'average_execution_time' => $averageExecutionTime,
            'session_id' => $this->sessionId,
        ];
    }

    /**
     * Export operation log
     */
    public function exportLog(): OperationLog
    {
        return new OperationLog([
            'session_id' => $this->sessionId,
            'operations' => $this->getOperations(),
            'statistics' => $this->getOperationStatistics(),
            'errors' => $this->errors,
            'created_at' => now(),
        ]);
    }

    /**
     * Clear all logged operations
     */
    public function clearLog(): void
    {
        $this->operations = [];
        $this->timings = [];
        $this->errors = [];
        $this->sessionId = uniqid('cleanup_', true);
    }
}