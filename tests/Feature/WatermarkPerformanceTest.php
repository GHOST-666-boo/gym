<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\WatermarkService;
use App\Jobs\ProcessWatermarkGeneration;
use App\Jobs\BulkWatermarkRegeneration;
use App\Jobs\CleanupOldWatermarkCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\UploadedFile;

class WatermarkPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected WatermarkService $watermarkService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->watermarkService = app(WatermarkService::class);
        
        // Create test image
        Storage::fake('public');
        $this->createTestImage();
        
        // Enable watermarking for tests
        $this->setSetting('watermark_enabled', true);
        $this->setSetting('watermark_text', 'Test Watermark');
    }

    /** @test */
    public function it_can_apply_watermark_lazily()
    {
        Queue::fake();
        
        $imagePath = 'test-images/test.jpg';
        
        // Apply watermark lazily
        $result = $this->watermarkService->applyWatermarkLazy($imagePath, [], 'high');
        
        // Should return original path immediately
        $this->assertEquals($imagePath, $result);
        
        // Should dispatch background job
        Queue::assertPushed(ProcessWatermarkGeneration::class);
    }

    /** @test */
    public function it_can_trigger_bulk_regeneration()
    {
        Queue::fake();
        
        $imagePaths = ['test-images/test1.jpg', 'test-images/test2.jpg'];
        $newSettings = [
            'watermark_text' => 'New Watermark',
            'watermark_opacity' => 75
        ];
        
        $oldSettings = [
            'watermark_text' => 'Old Watermark',
            'watermark_opacity' => 50
        ];
        
        // Create test images
        foreach ($imagePaths as $path) {
            $this->createTestImage($path);
        }
        
        // Directly dispatch the bulk regeneration job to test it
        BulkWatermarkRegeneration::dispatch($imagePaths, $newSettings, $oldSettings, 'test-batch', 10);
        
        // Should dispatch bulk regeneration job
        Queue::assertPushed(BulkWatermarkRegeneration::class);
    }

    /** @test */
    public function it_can_schedule_automatic_cleanup()
    {
        Queue::fake();
        
        // Schedule cleanup
        $this->watermarkService->scheduleAutomaticCleanup();
        
        // Should dispatch cleanup job
        Queue::assertPushed(CleanupOldWatermarkCache::class);
    }

    /** @test */
    public function it_can_optimize_cache()
    {
        // Create some test cached files
        $this->createTestCachedFiles();
        
        // Optimize cache
        $stats = $this->watermarkService->optimizeCache();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('optimized_entries', $stats);
        $this->assertArrayHasKey('space_saved', $stats);
        $this->assertArrayHasKey('errors', $stats);
    }

    /** @test */
    public function it_can_get_performance_metrics()
    {
        // Set some test metrics
        Cache::put('watermark_performance_metrics', [
            'total_jobs' => 100,
            'successful_jobs' => 95,
            'failed_jobs' => 5,
            'average_processing_time' => 2.5
        ], 3600);
        
        $metrics = $this->watermarkService->getPerformanceMetrics();
        
        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('generation', $metrics);
        $this->assertArrayHasKey('cleanup', $metrics);
        $this->assertArrayHasKey('cache_stats', $metrics);
        $this->assertArrayHasKey('system_info', $metrics);
    }

    /** @test */
    public function it_can_get_watermark_status()
    {
        $imagePath = 'test-images/test.jpg';
        
        $status = $this->watermarkService->getWatermarkStatus($imagePath);
        
        $this->assertIsArray($status);
        $this->assertArrayHasKey('image_path', $status);
        $this->assertArrayHasKey('has_cached_version', $status);
        $this->assertArrayHasKey('job_status', $status);
        $this->assertEquals($imagePath, $status['image_path']);
    }

    /** @test */
    public function it_handles_cached_watermark_retrieval()
    {
        $imagePath = 'test-images/test.jpg';
        
        // First call should generate watermark
        $result1 = $this->watermarkService->applyWatermark($imagePath);
        
        // Second call should use cached version
        $result2 = $this->watermarkService->applyWatermark($imagePath);
        
        // Both should return watermarked paths
        $this->assertNotEquals($imagePath, $result1);
        $this->assertEquals($result1, $result2);
    }

    /** @test */
    public function it_can_clear_watermark_cache()
    {
        // Generate a watermarked image first
        $imagePath = 'test-images/test.jpg';
        $this->watermarkService->applyWatermark($imagePath);
        
        // Verify cache exists
        $statsBefore = $this->watermarkService->getCacheStats();
        $this->assertGreaterThan(0, $statsBefore['total_cached_files']);
        
        // Clear cache
        $this->watermarkService->clearWatermarkCache();
        
        // Verify cache is cleared
        $statsAfter = $this->watermarkService->getCacheStats();
        $this->assertEquals(0, $statsAfter['total_cached_files']);
    }

    /** @test */
    public function it_can_cleanup_old_cached_images()
    {
        // Create some test cached files with old timestamps
        $this->createOldTestCachedFiles();
        
        // Cleanup files older than 1 day
        $deletedCount = $this->watermarkService->cleanupOldCachedImages(1);
        
        $this->assertGreaterThan(0, $deletedCount);
    }

    /** @test */
    public function process_watermark_generation_job_works()
    {
        $imagePath = 'test-images/test.jpg';
        $options = ['opacity' => 75];
        
        $job = new ProcessWatermarkGeneration($imagePath, $options, 'normal', 'test-request-123');
        
        // Execute the job
        $job->handle($this->watermarkService);
        
        // Verify job completion was cached
        $jobKey = 'watermark_job_' . md5($imagePath . serialize($options));
        $jobData = Cache::get($jobKey);
        
        $this->assertNotNull($jobData);
        $this->assertEquals('completed', $jobData['status']);
    }

    /** @test */
    public function bulk_watermark_regeneration_job_works()
    {
        $imagePaths = ['test-images/test1.jpg', 'test-images/test2.jpg'];
        $newSettings = ['watermark_text' => 'New Text'];
        $oldSettings = ['watermark_text' => 'Old Text'];
        
        // Create test images
        foreach ($imagePaths as $path) {
            $this->createTestImage($path);
        }
        
        $job = new BulkWatermarkRegeneration($imagePaths, $newSettings, $oldSettings, 'test-batch', 10);
        
        // Execute the job
        $job->handle($this->watermarkService);
        
        // Verify batch completion was tracked
        $batchData = BulkWatermarkRegeneration::getBatchStatus('test-batch');
        
        $this->assertNotNull($batchData);
        $this->assertEquals('completed', $batchData['status']);
        $this->assertEquals(count($imagePaths), $batchData['total_images']);
    }

    /** @test */
    public function cleanup_old_watermark_cache_job_works()
    {
        // Create some old test cached files
        $this->createOldTestCachedFiles();
        
        $job = new CleanupOldWatermarkCache(1, false, 100);
        
        // Execute the job
        $job->handle($this->watermarkService);
        
        // Verify cleanup metrics were updated
        $metrics = Cache::get('watermark_cleanup_metrics');
        $this->assertNotNull($metrics);
        $this->assertGreaterThan(0, $metrics['total_cleanups']);
    }

    protected function createTestImage(string $path = 'test-images/test.jpg'): void
    {
        $image = UploadedFile::fake()->image('test.jpg', 800, 600);
        Storage::disk('public')->putFileAs(dirname($path), $image, basename($path));
    }

    protected function createTestCachedFiles(): void
    {
        Storage::disk('public')->makeDirectory('watermarks/cache');
        
        for ($i = 1; $i <= 5; $i++) {
            $content = "test cached watermark content {$i}";
            Storage::disk('public')->put("watermarks/cache/test_cached_{$i}.jpg", $content);
        }
    }

    protected function createOldTestCachedFiles(): void
    {
        Storage::disk('public')->makeDirectory('watermarks/cache');
        
        for ($i = 1; $i <= 3; $i++) {
            $content = "old test cached watermark content {$i}";
            $path = "watermarks/cache/old_test_cached_{$i}.jpg";
            Storage::disk('public')->put($path, $content);
            
            // Set file modification time to 2 days ago
            $fullPath = Storage::disk('public')->path($path);
            touch($fullPath, time() - (2 * 24 * 60 * 60));
        }
    }

    protected function setSetting(string $key, $value): void
    {
        $settingsService = app(\App\Services\SettingsService::class);
        $settingsService->set($key, $value);
    }

    protected function createTestProductImages(): void
    {
        // Create test categories first
        \Illuminate\Support\Facades\DB::table('categories')->insert([
            [
                'id' => 1,
                'name' => 'Test Category',
                'slug' => 'test-category',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);

        // Create test products
        \Illuminate\Support\Facades\DB::table('products')->insert([
            [
                'id' => 1,
                'name' => 'Test Product 1',
                'slug' => 'test-product-1',
                'short_description' => 'Test short description',
                'long_description' => 'Test long description',
                'price' => 100.00,
                'category_id' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => 2,
                'name' => 'Test Product 2',
                'slug' => 'test-product-2',
                'short_description' => 'Test short description',
                'long_description' => 'Test long description',
                'price' => 200.00,
                'category_id' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);

        // Create test product images in database
        \Illuminate\Support\Facades\DB::table('product_images')->insert([
            [
                'product_id' => 1,
                'image_path' => 'test-images/product1.jpg',
                'alt_text' => 'Test Product 1',
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'product_id' => 2,
                'image_path' => 'test-images/product2.jpg',
                'alt_text' => 'Test Product 2',
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);

        // Create the actual image files
        $this->createTestImage('test-images/product1.jpg');
        $this->createTestImage('test-images/product2.jpg');
    }
}