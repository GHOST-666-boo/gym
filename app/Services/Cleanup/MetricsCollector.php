<?php

namespace App\Services\Cleanup;

use App\Services\Cleanup\Models\CleanupMetrics;
use App\Services\Cleanup\Models\PerformanceMetrics;
use App\Services\Cleanup\Models\CodeComplexityMetrics;

class MetricsCollector
{
    private array $operationLog = [];
    private array $performanceData = [];
    private float $startTime;
    private array $memoryUsage = [];
    private bool $isCollecting = false;

    public function __construct()
    {
        $this->startTime = microtime(true);
        $this->recordMemoryUsage('initialization');
    }
    
    /**
     * Start metrics collection
     */
    public function startCollection(): void
    {
        $this->isCollecting = true;
        $this->startTime = microtime(true);
        $this->recordMemoryUsage('collection_start');
    }
    
    /**
     * Stop metrics collection
     */
    public function stopCollection(): void
    {
        $this->isCollecting = false;
        $this->recordMemoryUsage('collection_end');
    }
    
    /**
     * Get collected metrics
     */
    public function getMetrics(): CleanupMetrics
    {
        return $this->getCleanupMetrics();
    }

    /**
     * Log a cleanup operation with detailed metrics
     */
    public function logOperation(string $operation, array $details = []): void
    {
        $this->operationLog[] = [
            'operation' => $operation,
            'timestamp' => microtime(true),
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'details' => $details,
        ];
    }

    /**
     * Record performance metrics before cleanup
     */
    public function recordBeforeMetrics(string $projectPath): PerformanceMetrics
    {
        $metrics = new PerformanceMetrics();
        
        // Calculate total file sizes
        $metrics->totalFileSize = $this->calculateDirectorySize($projectPath);
        $metrics->totalFiles = $this->countFiles($projectPath);
        $metrics->totalLines = $this->countTotalLines($projectPath);
        
        // Calculate complexity metrics
        $metrics->cyclomaticComplexity = $this->calculateCyclomaticComplexity($projectPath);
        $metrics->codeSmells = $this->detectCodeSmells($projectPath);
        
        $this->performanceData['before'] = $metrics;
        $this->logOperation('before_metrics_recorded', $metrics->toArray());
        
        return $metrics;
    }

    /**
     * Record performance metrics after cleanup
     */
    public function recordAfterMetrics(string $projectPath): PerformanceMetrics
    {
        $metrics = new PerformanceMetrics();
        
        $metrics->totalFileSize = $this->calculateDirectorySize($projectPath);
        $metrics->totalFiles = $this->countFiles($projectPath);
        $metrics->totalLines = $this->countTotalLines($projectPath);
        $metrics->cyclomaticComplexity = $this->calculateCyclomaticComplexity($projectPath);
        $metrics->codeSmells = $this->detectCodeSmells($projectPath);
        
        $this->performanceData['after'] = $metrics;
        $this->logOperation('after_metrics_recorded', $metrics->toArray());
        
        return $metrics;
    }

    /**
     * Calculate performance improvements between before and after metrics
     */
    public function calculateImprovements(): array
    {
        if (!isset($this->performanceData['before']) || !isset($this->performanceData['after'])) {
            throw new \InvalidArgumentException('Both before and after metrics must be recorded');
        }

        $before = $this->performanceData['before'];
        $after = $this->performanceData['after'];

        $improvements = [
            'file_size_reduction' => [
                'bytes' => $before->totalFileSize - $after->totalFileSize,
                'percentage' => $this->calculatePercentageReduction($before->totalFileSize, $after->totalFileSize),
                'mb' => ($before->totalFileSize - $after->totalFileSize) / (1024 * 1024),
            ],
            'file_count_reduction' => [
                'count' => $before->totalFiles - $after->totalFiles,
                'percentage' => $this->calculatePercentageReduction($before->totalFiles, $after->totalFiles),
            ],
            'line_count_reduction' => [
                'lines' => $before->totalLines - $after->totalLines,
                'percentage' => $this->calculatePercentageReduction($before->totalLines, $after->totalLines),
            ],
            'complexity_reduction' => [
                'cyclomatic' => $before->cyclomaticComplexity - $after->cyclomaticComplexity,
                'percentage' => $this->calculatePercentageReduction($before->cyclomaticComplexity, $after->cyclomaticComplexity),
            ],
            'code_smells_reduction' => [
                'count' => $before->codeSmells - $after->codeSmells,
                'percentage' => $this->calculatePercentageReduction($before->codeSmells, $after->codeSmells),
            ],
        ];

        $this->logOperation('improvements_calculated', $improvements);
        
        return $improvements;
    }

    /**
     * Record memory usage at specific points
     */
    public function recordMemoryUsage(string $checkpoint): void
    {
        $this->memoryUsage[$checkpoint] = [
            'current' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'timestamp' => microtime(true),
        ];
    }

    /**
     * Get comprehensive cleanup metrics
     */
    public function getCleanupMetrics(): CleanupMetrics
    {
        $executionTime = microtime(true) - $this->startTime;
        
        // Only calculate improvements if both before and after metrics are available
        $performanceImprovements = [];
        if (isset($this->performanceData['before']) && isset($this->performanceData['after'])) {
            $performanceImprovements = $this->calculateImprovements();
        }
        
        return new CleanupMetrics([
            'execution_time' => $executionTime,
            'operations_performed' => count($this->operationLog),
            'memory_usage' => $this->memoryUsage,
            'operation_log' => $this->operationLog,
            'performance_improvements' => $performanceImprovements,
        ]);
    }

    /**
     * Calculate directory size recursively
     */
    private function calculateDirectorySize(string $path): int
    {
        $size = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $this->shouldIncludeFile($file->getPathname())) {
                $size += $file->getSize();
            }
        }

        return $size;
    }

    /**
     * Count total files in directory
     */
    private function countFiles(string $path): int
    {
        $count = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $this->shouldIncludeFile($file->getPathname())) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Count total lines of code
     */
    private function countTotalLines(string $path): int
    {
        $totalLines = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $this->shouldIncludeFile($file->getPathname())) {
                $content = file_get_contents($file->getPathname());
                if ($content !== false) {
                    $totalLines += substr_count($content, "\n") + 1;
                }
            }
        }

        return $totalLines;
    }

    /**
     * Calculate basic cyclomatic complexity
     */
    private function calculateCyclomaticComplexity(string $path): int
    {
        $complexity = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $this->isPhpFile($file->getPathname())) {
                $content = file_get_contents($file->getPathname());
                if ($content !== false) {
                    // Count decision points (if, while, for, foreach, case, catch, &&, ||)
                    $complexity += preg_match_all('/\b(if|while|for|foreach|case|catch)\b|\|\||&&/', $content);
                }
            }
        }

        return $complexity;
    }

    /**
     * Detect basic code smells
     */
    private function detectCodeSmells(string $path): int
    {
        $smells = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $this->shouldIncludeFile($file->getPathname())) {
                $content = file_get_contents($file->getPathname());
                if ($content !== false) {
                    // Count potential code smells
                    $smells += preg_match_all('/TODO|FIXME|XXX|HACK/', $content);
                    $smells += preg_match_all('/function\s+\w+\s*\([^)]*\)\s*{[^}]{500,}}/s', $content); // Long methods
                }
            }
        }

        return $smells;
    }

    /**
     * Calculate percentage reduction
     */
    private function calculatePercentageReduction(float $before, float $after): float
    {
        if ($before == 0) {
            return 0.0;
        }
        
        return (($before - $after) / $before) * 100;
    }

    /**
     * Check if file should be included in metrics
     */
    private function shouldIncludeFile(string $filePath): bool
    {
        $excludePatterns = [
            '/vendor/',
            '/node_modules/',
            '/storage/',
            '/.git/',
            '/bootstrap/cache/',
        ];

        foreach ($excludePatterns as $pattern) {
            if (strpos($filePath, $pattern) !== false) {
                return false;
            }
        }

        return $this->isPhpFile($filePath) || 
               $this->isJavaScriptFile($filePath) || 
               $this->isCssFile($filePath) || 
               $this->isBladeFile($filePath);
    }

    private function isPhpFile(string $filePath): bool
    {
        return pathinfo($filePath, PATHINFO_EXTENSION) === 'php';
    }

    private function isJavaScriptFile(string $filePath): bool
    {
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        return in_array($ext, ['js', 'ts', 'jsx', 'tsx']);
    }

    private function isCssFile(string $filePath): bool
    {
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        return in_array($ext, ['css', 'scss', 'sass', 'less']);
    }

    private function isBladeFile(string $filePath): bool
    {
        return str_ends_with($filePath, '.blade.php');
    }
}