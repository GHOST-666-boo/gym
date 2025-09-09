<?php

namespace App\Jobs;

use App\Services\WatermarkService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ProcessWatermarkGeneration implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $timeout = 300; // 5 minutes timeout
    public $tries = 3;
    public $backoff = [30, 60, 120]; // Exponential backoff

    protected string $imagePath;
    protected array $options;
    protected string $priority;
    protected ?string $requestId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $imagePath, array $options = [], string $priority = 'normal', ?string $requestId = null)
    {
        $this->imagePath = $imagePath;
        $this->options = $options;
        $this->priority = $priority;
        $this->requestId = $requestId;
        
        // Set queue priority based on priority level
        $this->onQueue($this->getQueueName($priority));
    }

    /**
     * Execute the job.
     */
    public function handle(WatermarkService $watermarkService): void
    {
        try {
            Log::info('Starting background watermark generation', [
                'image_path' => $this->imagePath,
                'priority' => $this->priority,
                'request_id' => $this->requestId,
                'attempt' => $this->attempts()
            ]);

            // Check if watermarking is still enabled
            if (!$watermarkService->isWatermarkingEnabled()) {
                Log::info('Watermarking disabled, skipping job', [
                    'image_path' => $this->imagePath
                ]);
                return;
            }

            // Check if cached version already exists and is valid
            if ($this->isCachedVersionValid($watermarkService)) {
                Log::info('Valid cached version exists, skipping generation', [
                    'image_path' => $this->imagePath
                ]);
                $this->markJobCompleted();
                return;
            }

            // Generate watermarked image
            $watermarkedPath = $watermarkService->applyWatermark($this->imagePath, $this->options);

            if ($watermarkedPath !== $this->imagePath) {
                Log::info('Background watermark generation completed', [
                    'original_path' => $this->imagePath,
                    'watermarked_path' => $watermarkedPath,
                    'priority' => $this->priority,
                    'processing_time' => microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true))
                ]);

                // Update job status cache
                $this->markJobCompleted($watermarkedPath);

                // Update performance metrics
                $this->updatePerformanceMetrics(true);
            } else {
                Log::warning('Background watermark generation returned original path', [
                    'image_path' => $this->imagePath
                ]);
                $this->updatePerformanceMetrics(false);
            }

        } catch (\Exception $e) {
            Log::error('Background watermark generation failed', [
                'image_path' => $this->imagePath,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
                'max_tries' => $this->tries
            ]);

            $this->updatePerformanceMetrics(false, $e->getMessage());

            // If this is the last attempt, mark as failed
            if ($this->attempts() >= $this->tries) {
                $this->markJobFailed($e->getMessage());
            }

            throw $e;
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Background watermark generation job failed permanently', [
            'image_path' => $this->imagePath,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        $this->markJobFailed($exception->getMessage());
    }

    /**
     * Get queue name based on priority
     */
    protected function getQueueName(string $priority): string
    {
        return match ($priority) {
            'high' => 'watermark-high',
            'low' => 'watermark-low',
            default => 'watermark-normal'
        };
    }

    /**
     * Check if cached version is valid
     */
    protected function isCachedVersionValid(WatermarkService $watermarkService): bool
    {
        $settings = array_merge($watermarkService->getWatermarkSettings(), $this->options);
        $cachedPath = $watermarkService->generateCachedWatermarkedPath($this->imagePath, $settings);
        
        return $watermarkService->getCachedWatermarkedImage($this->imagePath, $this->options) !== null;
    }

    /**
     * Mark job as completed in cache
     */
    protected function markJobCompleted(?string $watermarkedPath = null): void
    {
        $jobKey = $this->getJobCacheKey();
        $jobData = [
            'status' => 'completed',
            'completed_at' => now(),
            'watermarked_path' => $watermarkedPath,
            'processing_time' => microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true)),
            'attempts' => $this->attempts()
        ];

        Cache::put($jobKey, $jobData, 3600); // Cache for 1 hour

        // Notify any waiting processes
        if ($this->requestId) {
            $requestKey = "watermark_request_{$this->requestId}";
            Cache::put($requestKey, $jobData, 300); // Cache for 5 minutes
        }
    }

    /**
     * Mark job as failed in cache
     */
    protected function markJobFailed(string $error): void
    {
        $jobKey = $this->getJobCacheKey();
        $jobData = [
            'status' => 'failed',
            'failed_at' => now(),
            'error' => $error,
            'attempts' => $this->attempts()
        ];

        Cache::put($jobKey, $jobData, 3600); // Cache for 1 hour

        // Notify any waiting processes
        if ($this->requestId) {
            $requestKey = "watermark_request_{$this->requestId}";
            Cache::put($requestKey, $jobData, 300); // Cache for 5 minutes
        }
    }

    /**
     * Get job cache key
     */
    protected function getJobCacheKey(): string
    {
        return 'watermark_job_' . md5($this->imagePath . serialize($this->options));
    }

    /**
     * Update performance metrics
     */
    protected function updatePerformanceMetrics(bool $success, ?string $error = null): void
    {
        $metricsKey = 'watermark_performance_metrics';
        $metrics = Cache::get($metricsKey, [
            'total_jobs' => 0,
            'successful_jobs' => 0,
            'failed_jobs' => 0,
            'average_processing_time' => 0,
            'last_updated' => now()
        ]);

        $metrics['total_jobs']++;
        
        if ($success) {
            $metrics['successful_jobs']++;
        } else {
            $metrics['failed_jobs']++;
        }

        $processingTime = microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true));
        $metrics['average_processing_time'] = (
            ($metrics['average_processing_time'] * ($metrics['total_jobs'] - 1)) + $processingTime
        ) / $metrics['total_jobs'];

        $metrics['last_updated'] = now();

        if ($error) {
            $metrics['last_error'] = $error;
            $metrics['last_error_at'] = now();
        }

        Cache::put($metricsKey, $metrics, 24 * 60 * 60); // Cache for 24 hours
    }
}
