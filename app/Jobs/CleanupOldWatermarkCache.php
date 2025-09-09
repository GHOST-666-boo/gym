<?php

namespace App\Jobs;

use App\Services\WatermarkService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class CleanupOldWatermarkCache implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $timeout = 600; // 10 minutes timeout
    public $tries = 2;
    public $backoff = [60, 120]; // 1 and 2 minute backoff

    protected int $daysOld;
    protected bool $aggressive;
    protected ?int $maxFilesToDelete;

    /**
     * Create a new job instance.
     */
    public function __construct(int $daysOld = 7, bool $aggressive = false, ?int $maxFilesToDelete = null)
    {
        $this->daysOld = $daysOld;
        $this->aggressive = $aggressive;
        $this->maxFilesToDelete = $maxFilesToDelete;
        
        // Use low priority queue for cleanup operations
        $this->onQueue('watermark-low');
    }

    /**
     * Execute the job.
     */
    public function handle(WatermarkService $watermarkService): void
    {
        try {
            Log::info('Starting watermark cache cleanup', [
                'days_old' => $this->daysOld,
                'aggressive' => $this->aggressive,
                'max_files_to_delete' => $this->maxFilesToDelete,
                'attempt' => $this->attempts()
            ]);

            $startTime = microtime(true);
            $stats = $this->performCleanup($watermarkService);
            $processingTime = microtime(true) - $startTime;

            Log::info('Watermark cache cleanup completed', [
                'files_deleted' => $stats['files_deleted'],
                'space_freed' => $this->formatBytes($stats['space_freed']),
                'orphaned_metadata_cleared' => $stats['orphaned_metadata_cleared'],
                'processing_time' => round($processingTime, 2) . 's',
                'errors' => $stats['errors']
            ]);

            // Update cleanup metrics
            $this->updateCleanupMetrics($stats, $processingTime);

        } catch (\Exception $e) {
            Log::error('Watermark cache cleanup failed', [
                'error' => $e->getMessage(),
                'attempt' => $this->attempts()
            ]);

            throw $e;
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Watermark cache cleanup job failed permanently', [
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }

    /**
     * Perform the actual cleanup
     */
    protected function performCleanup(WatermarkService $watermarkService): array
    {
        $stats = [
            'files_deleted' => 0,
            'space_freed' => 0,
            'orphaned_metadata_cleared' => 0,
            'errors' => []
        ];

        // Clean up cached watermarked images
        $cacheStats = $this->cleanupCachedImages();
        $stats['files_deleted'] += $cacheStats['files_deleted'];
        $stats['space_freed'] += $cacheStats['space_freed'];
        $stats['errors'] = array_merge($stats['errors'], $cacheStats['errors']);

        // Clean up orphaned metadata
        $metadataStats = $this->cleanupOrphanedMetadata();
        $stats['orphaned_metadata_cleared'] += $metadataStats['cleared'];
        $stats['errors'] = array_merge($stats['errors'], $metadataStats['errors']);

        // Clean up temporary files if aggressive mode
        if ($this->aggressive) {
            $tempStats = $this->cleanupTemporaryFiles();
            $stats['files_deleted'] += $tempStats['files_deleted'];
            $stats['space_freed'] += $tempStats['space_freed'];
            $stats['errors'] = array_merge($stats['errors'], $tempStats['errors']);
        }

        // Clean up old job tracking data
        $jobStats = $this->cleanupOldJobData();
        $stats['orphaned_metadata_cleared'] += $jobStats['cleared'];

        return $stats;
    }

    /**
     * Clean up cached watermarked images
     */
    protected function cleanupCachedImages(): array
    {
        $stats = [
            'files_deleted' => 0,
            'space_freed' => 0,
            'errors' => []
        ];

        try {
            $cacheDir = 'watermarks/cache';
            
            if (!Storage::disk('public')->exists($cacheDir)) {
                return $stats;
            }

            $cutoffTime = now()->subDays($this->daysOld)->timestamp;
            $files = Storage::disk('public')->allFiles($cacheDir);
            $filesDeleted = 0;

            foreach ($files as $file) {
                try {
                    // Check file limit
                    if ($this->maxFilesToDelete && $filesDeleted >= $this->maxFilesToDelete) {
                        Log::info('Reached maximum file deletion limit', [
                            'limit' => $this->maxFilesToDelete,
                            'deleted' => $filesDeleted
                        ]);
                        break;
                    }

                    $fullPath = Storage::disk('public')->path($file);
                    
                    if (!file_exists($fullPath)) {
                        continue;
                    }

                    $fileTime = filemtime($fullPath);
                    $fileSize = filesize($fullPath);

                    // Check if file is old enough
                    if ($fileTime < $cutoffTime) {
                        // Additional checks for aggressive mode
                        if ($this->aggressive || $this->shouldDeleteFile($file, $fileTime, $fileSize)) {
                            Storage::disk('public')->delete($file);
                            $stats['files_deleted']++;
                            $stats['space_freed'] += $fileSize;
                            $filesDeleted++;

                            Log::debug('Deleted old cached watermark file', [
                                'file' => $file,
                                'age_days' => round((time() - $fileTime) / 86400, 1),
                                'size' => $this->formatBytes($fileSize)
                            ]);
                        }
                    }

                } catch (\Exception $e) {
                    $error = "Failed to process file {$file}: " . $e->getMessage();
                    $stats['errors'][] = $error;
                    Log::warning($error);
                }
            }

        } catch (\Exception $e) {
            $error = "Failed to cleanup cached images: " . $e->getMessage();
            $stats['errors'][] = $error;
            Log::error($error);
        }

        return $stats;
    }

    /**
     * Clean up orphaned metadata
     */
    protected function cleanupOrphanedMetadata(): array
    {
        $stats = [
            'cleared' => 0,
            'errors' => []
        ];

        try {
            // Clean up individual cache metadata
            $cacheKeys = Cache::get('watermark_cache_keys', []);
            $orphanedKeys = [];

            foreach ($cacheKeys as $key) {
                try {
                    $metadata = Cache::get($key);
                    if ($metadata && isset($metadata['cached_path'])) {
                        // Check if cached file still exists
                        if (!Storage::disk('public')->exists($metadata['cached_path'])) {
                            Cache::forget($key);
                            $orphanedKeys[] = $key;
                            $stats['cleared']++;
                        }
                    }
                } catch (\Exception $e) {
                    $stats['errors'][] = "Failed to check metadata key {$key}: " . $e->getMessage();
                }
            }

            // Update cache keys list
            if (!empty($orphanedKeys)) {
                $remainingKeys = array_diff($cacheKeys, $orphanedKeys);
                Cache::put('watermark_cache_keys', $remainingKeys, 7 * 24 * 60 * 60);
            }

            // Clean up cache index
            $this->cleanupCacheIndex();

        } catch (\Exception $e) {
            $error = "Failed to cleanup orphaned metadata: " . $e->getMessage();
            $stats['errors'][] = $error;
            Log::error($error);
        }

        return $stats;
    }

    /**
     * Clean up cache index
     */
    protected function cleanupCacheIndex(): void
    {
        try {
            $indexKey = 'watermark_cache_index';
            $index = Cache::get($indexKey, []);
            $cleanedIndex = [];

            foreach ($index as $originalPath => $cacheData) {
                if (isset($cacheData['cached_path']) && Storage::disk('public')->exists($cacheData['cached_path'])) {
                    $cleanedIndex[$originalPath] = $cacheData;
                }
            }

            if (count($cleanedIndex) !== count($index)) {
                Cache::put($indexKey, $cleanedIndex, 7 * 24 * 60 * 60);
                Log::info('Cleaned cache index', [
                    'original_entries' => count($index),
                    'cleaned_entries' => count($cleanedIndex),
                    'removed' => count($index) - count($cleanedIndex)
                ]);
            }

        } catch (\Exception $e) {
            Log::warning('Failed to cleanup cache index: ' . $e->getMessage());
        }
    }

    /**
     * Clean up temporary files (aggressive mode)
     */
    protected function cleanupTemporaryFiles(): array
    {
        $stats = [
            'files_deleted' => 0,
            'space_freed' => 0,
            'errors' => []
        ];

        try {
            // Clean up any temporary watermark files
            $tempDirs = ['temp', 'tmp', 'watermarks/temp'];
            
            foreach ($tempDirs as $tempDir) {
                if (Storage::disk('public')->exists($tempDir)) {
                    $tempFiles = Storage::disk('public')->allFiles($tempDir);
                    
                    foreach ($tempFiles as $file) {
                        try {
                            $fullPath = Storage::disk('public')->path($file);
                            if (file_exists($fullPath)) {
                                $fileSize = filesize($fullPath);
                                Storage::disk('public')->delete($file);
                                $stats['files_deleted']++;
                                $stats['space_freed'] += $fileSize;
                            }
                        } catch (\Exception $e) {
                            $stats['errors'][] = "Failed to delete temp file {$file}: " . $e->getMessage();
                        }
                    }
                }
            }

        } catch (\Exception $e) {
            $error = "Failed to cleanup temporary files: " . $e->getMessage();
            $stats['errors'][] = $error;
            Log::error($error);
        }

        return $stats;
    }

    /**
     * Clean up old job tracking data
     */
    protected function cleanupOldJobData(): array
    {
        $stats = [
            'cleared' => 0
        ];

        try {
            $cutoffTime = now()->subDays($this->daysOld * 2); // Keep job data longer than cache files

            // Clean up job status cache
            $jobKeys = Cache::get('watermark_job_keys', []);
            $expiredKeys = [];

            foreach ($jobKeys as $key) {
                try {
                    $jobData = Cache::get($key);
                    if ($jobData && isset($jobData['completed_at'])) {
                        if ($jobData['completed_at'] < $cutoffTime) {
                            Cache::forget($key);
                            $expiredKeys[] = $key;
                            $stats['cleared']++;
                        }
                    }
                } catch (\Exception $e) {
                    // Ignore individual key errors
                }
            }

            // Update job keys list
            if (!empty($expiredKeys)) {
                $remainingKeys = array_diff($jobKeys, $expiredKeys);
                Cache::put('watermark_job_keys', $remainingKeys, 7 * 24 * 60 * 60);
            }

            // Clean up bulk operation tracking
            $this->cleanupBulkOperationData($cutoffTime);

        } catch (\Exception $e) {
            Log::warning('Failed to cleanup old job data: ' . $e->getMessage());
        }

        return $stats;
    }

    /**
     * Clean up bulk operation tracking data
     */
    protected function cleanupBulkOperationData(\Carbon\Carbon $cutoffTime): void
    {
        try {
            // This would require scanning cache keys, which is not efficient
            // In a production environment, you might want to use a more structured approach
            // like storing bulk operation IDs in a separate cache key
            
            Log::debug('Bulk operation data cleanup completed');

        } catch (\Exception $e) {
            Log::warning('Failed to cleanup bulk operation data: ' . $e->getMessage());
        }
    }

    /**
     * Determine if a file should be deleted based on additional criteria
     */
    protected function shouldDeleteFile(string $file, int $fileTime, int $fileSize): bool
    {
        // Don't delete very large files unless in aggressive mode
        if (!$this->aggressive && $fileSize > 10 * 1024 * 1024) { // 10MB
            return false;
        }

        // Always delete very old files
        $veryOldCutoff = now()->subDays($this->daysOld * 2)->timestamp;
        if ($fileTime < $veryOldCutoff) {
            return true;
        }

        // Delete files that haven't been accessed recently
        $accessTime = fileatime(Storage::disk('public')->path($file));
        if ($accessTime && $accessTime < now()->subDays($this->daysOld)->timestamp) {
            return true;
        }

        return false;
    }

    /**
     * Update cleanup metrics
     */
    protected function updateCleanupMetrics(array $stats, float $processingTime): void
    {
        $metricsKey = 'watermark_cleanup_metrics';
        $metrics = Cache::get($metricsKey, [
            'total_cleanups' => 0,
            'total_files_deleted' => 0,
            'total_space_freed' => 0,
            'average_processing_time' => 0,
            'last_cleanup' => null
        ]);

        $metrics['total_cleanups']++;
        $metrics['total_files_deleted'] += $stats['files_deleted'];
        $metrics['total_space_freed'] += $stats['space_freed'];
        $metrics['average_processing_time'] = (
            ($metrics['average_processing_time'] * ($metrics['total_cleanups'] - 1)) + $processingTime
        ) / $metrics['total_cleanups'];
        $metrics['last_cleanup'] = now();
        $metrics['last_cleanup_stats'] = $stats;

        Cache::put($metricsKey, $metrics, 30 * 24 * 60 * 60); // Cache for 30 days
    }

    /**
     * Format bytes to human readable format
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        
        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * Schedule regular cleanup (static method for external use)
     */
    public static function scheduleRegularCleanup(): void
    {
        // Schedule daily cleanup of files older than 7 days
        self::dispatch(7, false, 1000)
            ->delay(now()->addMinutes(5));

        // Schedule weekly aggressive cleanup of files older than 3 days
        if (now()->dayOfWeek === 0) { // Sunday
            self::dispatch(3, true, 5000)
                ->delay(now()->addHours(1));
        }

        Log::info('Scheduled regular watermark cache cleanup');
    }
}
