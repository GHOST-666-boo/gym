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
use Illuminate\Support\Facades\DB;

class BulkWatermarkRegeneration implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $timeout = 1800; // 30 minutes timeout
    public $tries = 2;
    public $backoff = [300, 600]; // 5 and 10 minute backoff

    protected array $imagePaths;
    protected array $newSettings;
    protected array $oldSettings;
    protected string $batchId;
    protected int $batchSize;

    /**
     * Create a new job instance.
     */
    public function __construct(array $imagePaths, array $newSettings, array $oldSettings = [], string $batchId = null, int $batchSize = 50)
    {
        $this->imagePaths = $imagePaths;
        $this->newSettings = $newSettings;
        $this->oldSettings = $oldSettings;
        $this->batchId = $batchId ?: uniqid('bulk_watermark_');
        $this->batchSize = $batchSize;
        
        // Use high priority queue for bulk operations
        $this->onQueue('watermark-high');
    }

    /**
     * Execute the job.
     */
    public function handle(WatermarkService $watermarkService): void
    {
        try {
            Log::info('Starting bulk watermark regeneration', [
                'batch_id' => $this->batchId,
                'total_images' => count($this->imagePaths),
                'batch_size' => $this->batchSize,
                'attempt' => $this->attempts()
            ]);

            // Initialize batch tracking
            $this->initializeBatchTracking();

            // Process images in smaller chunks to prevent memory issues
            $chunks = array_chunk($this->imagePaths, $this->batchSize);
            $totalProcessed = 0;
            $totalSuccessful = 0;
            $totalFailed = 0;
            $errors = [];

            foreach ($chunks as $chunkIndex => $chunk) {
                Log::info('Processing chunk', [
                    'batch_id' => $this->batchId,
                    'chunk_index' => $chunkIndex + 1,
                    'chunk_size' => count($chunk),
                    'total_chunks' => count($chunks)
                ]);

                $chunkResults = $this->processImageChunk($chunk, $watermarkService);
                
                $totalProcessed += $chunkResults['processed'];
                $totalSuccessful += $chunkResults['successful'];
                $totalFailed += $chunkResults['failed'];
                $errors = array_merge($errors, $chunkResults['errors']);

                // Update progress
                $this->updateBatchProgress($totalProcessed, $totalSuccessful, $totalFailed, $errors);

                // Clear memory between chunks
                if (function_exists('gc_collect_cycles')) {
                    gc_collect_cycles();
                }

                // Small delay to prevent overwhelming the system
                usleep(100000); // 0.1 second
            }

            // Complete batch tracking
            $this->completeBatchTracking($totalProcessed, $totalSuccessful, $totalFailed, $errors);

            Log::info('Bulk watermark regeneration completed', [
                'batch_id' => $this->batchId,
                'total_processed' => $totalProcessed,
                'successful' => $totalSuccessful,
                'failed' => $totalFailed,
                'processing_time' => microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true))
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk watermark regeneration failed', [
                'batch_id' => $this->batchId,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts()
            ]);

            $this->failBatchTracking($e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Bulk watermark regeneration job failed permanently', [
            'batch_id' => $this->batchId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        $this->failBatchTracking($exception->getMessage());
    }

    /**
     * Process a chunk of images
     */
    protected function processImageChunk(array $imagePaths, WatermarkService $watermarkService): array
    {
        $processed = 0;
        $successful = 0;
        $failed = 0;
        $errors = [];

        foreach ($imagePaths as $imagePath) {
            try {
                // Clear old cached version first
                $this->clearOldCachedVersion($imagePath, $watermarkService);

                // Generate new watermarked image
                $watermarkedPath = $watermarkService->applyWatermark($imagePath, $this->newSettings);

                if ($watermarkedPath !== $imagePath) {
                    $successful++;
                    Log::debug('Successfully regenerated watermark', [
                        'batch_id' => $this->batchId,
                        'original_path' => $imagePath,
                        'watermarked_path' => $watermarkedPath
                    ]);
                } else {
                    $failed++;
                    $error = "Watermark generation returned original path for: {$imagePath}";
                    $errors[] = $error;
                    Log::warning($error, ['batch_id' => $this->batchId]);
                }

                $processed++;

            } catch (\Exception $e) {
                $failed++;
                $error = "Failed to regenerate watermark for {$imagePath}: " . $e->getMessage();
                $errors[] = $error;
                
                Log::error('Individual image watermark regeneration failed', [
                    'batch_id' => $this->batchId,
                    'image_path' => $imagePath,
                    'error' => $e->getMessage()
                ]);

                $processed++;
            }
        }

        return [
            'processed' => $processed,
            'successful' => $successful,
            'failed' => $failed,
            'errors' => $errors
        ];
    }

    /**
     * Clear old cached version of watermarked image
     */
    protected function clearOldCachedVersion(string $imagePath, WatermarkService $watermarkService): void
    {
        try {
            // Generate old cached path using old settings
            if (!empty($this->oldSettings)) {
                $oldCachedPath = $watermarkService->generateCachedWatermarkedPath($imagePath, $this->oldSettings);
                if (Storage::disk('public')->exists($oldCachedPath)) {
                    Storage::disk('public')->delete($oldCachedPath);
                }
            }

            // Also clear any existing cached version with current settings
            $currentCachedPath = $watermarkService->generateCachedWatermarkedPath($imagePath, $this->newSettings);
            if (Storage::disk('public')->exists($currentCachedPath)) {
                Storage::disk('public')->delete($currentCachedPath);
            }

        } catch (\Exception $e) {
            Log::warning('Failed to clear old cached watermark', [
                'batch_id' => $this->batchId,
                'image_path' => $imagePath,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Initialize batch tracking
     */
    protected function initializeBatchTracking(): void
    {
        $batchData = [
            'batch_id' => $this->batchId,
            'status' => 'processing',
            'total_images' => count($this->imagePaths),
            'processed' => 0,
            'successful' => 0,
            'failed' => 0,
            'errors' => [],
            'started_at' => now(),
            'updated_at' => now(),
            'new_settings' => $this->newSettings,
            'old_settings' => $this->oldSettings
        ];

        Cache::put("bulk_watermark_batch_{$this->batchId}", $batchData, 24 * 60 * 60); // Cache for 24 hours
    }

    /**
     * Update batch progress
     */
    protected function updateBatchProgress(int $processed, int $successful, int $failed, array $errors): void
    {
        $batchKey = "bulk_watermark_batch_{$this->batchId}";
        $batchData = Cache::get($batchKey, []);

        $batchData['processed'] = $processed;
        $batchData['successful'] = $successful;
        $batchData['failed'] = $failed;
        $batchData['errors'] = array_slice($errors, -50); // Keep only last 50 errors
        $batchData['updated_at'] = now();
        $batchData['progress_percentage'] = round(($processed / count($this->imagePaths)) * 100, 2);

        Cache::put($batchKey, $batchData, 24 * 60 * 60);
    }

    /**
     * Complete batch tracking
     */
    protected function completeBatchTracking(int $processed, int $successful, int $failed, array $errors): void
    {
        $batchKey = "bulk_watermark_batch_{$this->batchId}";
        $batchData = Cache::get($batchKey, []);

        $batchData['status'] = 'completed';
        $batchData['processed'] = $processed;
        $batchData['successful'] = $successful;
        $batchData['failed'] = $failed;
        $batchData['errors'] = array_slice($errors, -100); // Keep last 100 errors for review
        $batchData['completed_at'] = now();
        $batchData['updated_at'] = now();
        $batchData['processing_time'] = microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true));
        $batchData['progress_percentage'] = 100;

        Cache::put($batchKey, $batchData, 7 * 24 * 60 * 60); // Cache for 7 days after completion
    }

    /**
     * Mark batch as failed
     */
    protected function failBatchTracking(string $error): void
    {
        $batchKey = "bulk_watermark_batch_{$this->batchId}";
        $batchData = Cache::get($batchKey, []);

        $batchData['status'] = 'failed';
        $batchData['failed_at'] = now();
        $batchData['updated_at'] = now();
        $batchData['failure_reason'] = $error;

        Cache::put($batchKey, $batchData, 7 * 24 * 60 * 60); // Cache for 7 days
    }

    /**
     * Get batch status (static method for external access)
     */
    public static function getBatchStatus(string $batchId): ?array
    {
        return Cache::get("bulk_watermark_batch_{$batchId}");
    }

    /**
     * Create bulk regeneration job from settings change
     */
    public static function createFromSettingsChange(array $newSettings, array $oldSettings = []): string
    {
        // Get all product images that need regeneration
        $imagePaths = self::getAllProductImagePaths();
        
        if (empty($imagePaths)) {
            Log::info('No product images found for bulk watermark regeneration');
            return 'no-images-found';
        }

        $batchId = uniqid('settings_change_');
        
        // Dispatch the job
        self::dispatch($imagePaths, $newSettings, $oldSettings, $batchId)
            ->delay(now()->addSeconds(5)); // Small delay to allow settings to propagate

        Log::info('Bulk watermark regeneration job created from settings change', [
            'batch_id' => $batchId,
            'total_images' => count($imagePaths),
            'new_settings' => $newSettings,
            'old_settings' => $oldSettings
        ]);

        return $batchId;
    }

    /**
     * Get all product image paths
     */
    protected static function getAllProductImagePaths(): array
    {
        try {
            // Get images from product_images table
            $productImages = DB::table('product_images')
                ->select('image_path')
                ->whereNotNull('image_path')
                ->where('image_path', '!=', '')
                ->pluck('image_path')
                ->toArray();

            // Get images from products table (legacy)
            $productMainImages = DB::table('products')
                ->select('image')
                ->whereNotNull('image')
                ->where('image', '!=', '')
                ->pluck('image')
                ->toArray();

            // Combine and deduplicate
            $allImages = array_unique(array_merge($productImages, $productMainImages));

            // Filter out non-existent files
            $existingImages = [];
            foreach ($allImages as $imagePath) {
                if (Storage::disk('public')->exists($imagePath)) {
                    $existingImages[] = $imagePath;
                }
            }

            return $existingImages;

        } catch (\Exception $e) {
            Log::error('Failed to get product image paths for bulk regeneration', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}
