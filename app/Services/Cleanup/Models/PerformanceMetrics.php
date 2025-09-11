<?php

namespace App\Services\Cleanup\Models;

class PerformanceMetrics
{
    public int $totalFileSize = 0;
    public int $totalFiles = 0;
    public int $totalLines = 0;
    public int $cyclomaticComplexity = 0;
    public int $codeSmells = 0;
    public array $fileTypeBreakdown = [];
    public array $complexityByFile = [];
    public float $averageFileSize = 0.0;
    public float $averageLinesPerFile = 0.0;

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        
        $this->calculateAverages();
    }

    /**
     * Calculate average metrics
     */
    private function calculateAverages(): void
    {
        if ($this->totalFiles > 0) {
            $this->averageFileSize = $this->totalFileSize / $this->totalFiles;
            $this->averageLinesPerFile = $this->totalLines / $this->totalFiles;
        }
    }

    /**
     * Get formatted file size
     */
    public function getFormattedFileSize(): string
    {
        return $this->formatBytes($this->totalFileSize);
    }

    /**
     * Get complexity per line ratio
     */
    public function getComplexityPerLine(): float
    {
        if ($this->totalLines == 0) {
            return 0.0;
        }
        return $this->cyclomaticComplexity / $this->totalLines;
    }

    /**
     * Get code smells per file ratio
     */
    public function getCodeSmellsPerFile(): float
    {
        if ($this->totalFiles == 0) {
            return 0.0;
        }
        return $this->codeSmells / $this->totalFiles;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'total_file_size' => $this->totalFileSize,
            'total_files' => $this->totalFiles,
            'total_lines' => $this->totalLines,
            'cyclomatic_complexity' => $this->cyclomaticComplexity,
            'code_smells' => $this->codeSmells,
            'file_type_breakdown' => $this->fileTypeBreakdown,
            'complexity_by_file' => $this->complexityByFile,
            'average_file_size' => $this->averageFileSize,
            'average_lines_per_file' => $this->averageLinesPerFile,
            'formatted_file_size' => $this->getFormattedFileSize(),
            'complexity_per_line' => $this->getComplexityPerLine(),
            'code_smells_per_file' => $this->getCodeSmellsPerFile(),
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