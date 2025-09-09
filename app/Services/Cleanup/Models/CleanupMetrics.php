<?php

namespace App\Services\Cleanup\Models;

class CleanupMetrics
{
    public float $executionTime;
    public int $operationsPerformed;
    public array $memoryUsage;
    public array $operationLog;
    public array $performanceImprovements;
    public array $fileTypeBreakdown;
    public array $operationTimings;

    public function __construct(array $data = [])
    {
        $this->executionTime = $data['execution_time'] ?? 0.0;
        $this->operationsPerformed = $data['operations_performed'] ?? 0;
        $this->memoryUsage = $data['memory_usage'] ?? [];
        $this->operationLog = $data['operation_log'] ?? [];
        $this->performanceImprovements = $data['performance_improvements'] ?? [];
        $this->fileTypeBreakdown = $data['file_type_breakdown'] ?? [];
        $this->operationTimings = $data['operation_timings'] ?? [];
    }

    /**
     * Get peak memory usage during cleanup
     */
    public function getPeakMemoryUsage(): int
    {
        $peak = 0;
        foreach ($this->memoryUsage as $checkpoint) {
            $peak = max($peak, $checkpoint['peak']);
        }
        return $peak;
    }

    /**
     * Get memory usage formatted in human readable format
     */
    public function getFormattedMemoryUsage(): string
    {
        $peak = $this->getPeakMemoryUsage();
        return $this->formatBytes($peak);
    }

    /**
     * Get execution time formatted
     */
    public function getFormattedExecutionTime(): string
    {
        if ($this->executionTime < 60) {
            return number_format($this->executionTime, 2) . ' seconds';
        } elseif ($this->executionTime < 3600) {
            return number_format($this->executionTime / 60, 2) . ' minutes';
        } else {
            return number_format($this->executionTime / 3600, 2) . ' hours';
        }
    }

    /**
     * Get operations per second
     */
    public function getOperationsPerSecond(): float
    {
        if ($this->executionTime == 0) {
            return 0.0;
        }
        return $this->operationsPerformed / $this->executionTime;
    }

    /**
     * Get total size reduction in MB
     */
    public function getTotalSizeReductionMB(): float
    {
        return $this->performanceImprovements['file_size_reduction']['mb'] ?? 0.0;
    }

    /**
     * Get total percentage improvement
     */
    public function getTotalPercentageImprovement(): float
    {
        $improvements = $this->performanceImprovements;
        $totalReduction = 0;
        $count = 0;

        foreach (['file_size_reduction', 'file_count_reduction', 'line_count_reduction'] as $metric) {
            if (isset($improvements[$metric]['percentage'])) {
                $totalReduction += $improvements[$metric]['percentage'];
                $count++;
            }
        }

        return $count > 0 ? $totalReduction / $count : 0.0;
    }

    /**
     * Convert to array for serialization
     */
    public function toArray(): array
    {
        return [
            'execution_time' => $this->executionTime,
            'operations_performed' => $this->operationsPerformed,
            'memory_usage' => $this->memoryUsage,
            'operation_log' => $this->operationLog,
            'performance_improvements' => $this->performanceImprovements,
            'file_type_breakdown' => $this->fileTypeBreakdown,
            'operation_timings' => $this->operationTimings,
            'formatted_execution_time' => $this->getFormattedExecutionTime(),
            'formatted_memory_usage' => $this->getFormattedMemoryUsage(),
            'operations_per_second' => $this->getOperationsPerSecond(),
            'total_size_reduction_mb' => $this->getTotalSizeReductionMB(),
            'total_percentage_improvement' => $this->getTotalPercentageImprovement(),
        ];
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}