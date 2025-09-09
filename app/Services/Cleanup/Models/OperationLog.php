<?php

namespace App\Services\Cleanup\Models;

class OperationLog
{
    public string $sessionId;
    public array $operations;
    public array $statistics;
    public array $errors;
    public \DateTime $createdAt;

    public function __construct(array $data = [])
    {
        $this->sessionId = $data['session_id'] ?? '';
        $this->operations = $data['operations'] ?? [];
        $this->statistics = $data['statistics'] ?? [];
        $this->errors = $data['errors'] ?? [];
        $this->createdAt = $data['created_at'] ?? new \DateTime();
    }

    /**
     * Get operations grouped by type
     */
    public function getOperationsByType(): array
    {
        $grouped = [];
        foreach ($this->operations as $operation) {
            $type = $operation['type'];
            if (!isset($grouped[$type])) {
                $grouped[$type] = [];
            }
            $grouped[$type][] = $operation;
        }
        return $grouped;
    }

    /**
     * Get timeline of operations
     */
    public function getTimeline(): array
    {
        $timeline = [];
        foreach ($this->operations as $operation) {
            $timeline[] = [
                'timestamp' => $operation['started_at'],
                'type' => $operation['type'],
                'status' => $operation['status'],
                'duration' => $operation['execution_time'] ?? 0,
            ];
        }
        
        // Sort by timestamp
        usort($timeline, function ($a, $b) {
            return $a['timestamp'] <=> $b['timestamp'];
        });
        
        return $timeline;
    }

    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics(): array
    {
        $executionTimes = array_column($this->operations, 'execution_time');
        $executionTimes = array_filter($executionTimes, fn($time) => $time !== null);
        
        if (empty($executionTimes)) {
            return [
                'total_time' => 0,
                'average_time' => 0,
                'min_time' => 0,
                'max_time' => 0,
            ];
        }

        return [
            'total_time' => array_sum($executionTimes),
            'average_time' => array_sum($executionTimes) / count($executionTimes),
            'min_time' => min($executionTimes),
            'max_time' => max($executionTimes),
        ];
    }

    /**
     * Convert to array for serialization
     */
    public function toArray(): array
    {
        return [
            'session_id' => $this->sessionId,
            'operations' => $this->operations,
            'statistics' => $this->statistics,
            'errors' => $this->errors,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'operations_by_type' => $this->getOperationsByType(),
            'timeline' => $this->getTimeline(),
            'performance_metrics' => $this->getPerformanceMetrics(),
        ];
    }

    /**
     * Export to JSON
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }

    /**
     * Save to file
     */
    public function saveToFile(string $filePath): bool
    {
        return file_put_contents($filePath, $this->toJson()) !== false;
    }
}