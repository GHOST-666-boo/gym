<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\WatermarkService;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\UploadedFile;

class WatermarkCacheTest extends TestCase
{
    use RefreshDatabase;

    protected WatermarkService $watermarkService;
    protected SettingsService $settingsService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->watermarkService = app(WatermarkService::class);
        $this->settingsService = app(SettingsService::class);
        
        // Set up test storage
        Storage::fake('public');
        
        // Enable watermarking for tests
        $this->settingsService->set('watermark_enabled', true, 'boolean', 'watermark');
        $this->settingsService->set('watermark_text', 'Test Watermark', 'string', 'watermark');
    }

    /** @test */
    public function it_generates_cached_watermarked_image_path_with_settings_hash()
    {
        $originalPath = 'products/test-image.jpg';
        $settings = [
            'text' => 'Test Watermark',
            'opacity' => 50,
            'position' => 'bottom-right',
            'size' => 24,
            'color' => '#FFFFFF',
            'logo_path' => '',
            'logo_size' => 'medium',
        ];

        $reflection = new \ReflectionClass($this->watermarkService);
        $method = $reflection->getMethod('generateCachedWatermarkedPath');
        $method->setAccessible(true);

        $cachedPath = $method->invoke($this->watermarkService, $originalPath, $settings);

        $this->assertStringStartsWith('watermarks/cache/', $cachedPath);
        $this->assertStringContainsString('test-image_', $cachedPath);
        $this->assertStringEndsWith('.jpg', $cachedPath);
    }

    /** @test */
    public function it_generates_consistent_settings_hash_for_same_settings()
    {
        $settings1 = [
            'text' => 'Test Watermark',
            'opacity' => 50,
            'position' => 'bottom-right',
            'size' => 24,
            'color' => '#FFFFFF',
            'logo_path' => '',
            'logo_size' => 'medium',
        ];

        $settings2 = [
            'text' => 'Test Watermark',
            'opacity' => 50,
            'position' => 'bottom-right',
            'size' => 24,
            'color' => '#FFFFFF',
            'logo_path' => '',
            'logo_size' => 'medium',
        ];

        $reflection = new \ReflectionClass($this->watermarkService);
        $method = $reflection->getMethod('generateSettingsHash');
        $method->setAccessible(true);

        $hash1 = $method->invoke($this->watermarkService, $settings1);
        $hash2 = $method->invoke($this->watermarkService, $settings2);

        $this->assertEquals($hash1, $hash2);
        $this->assertEquals(8, strlen($hash1)); // Hash should be 8 characters
    }

    /** @test */
    public function it_generates_different_settings_hash_for_different_settings()
    {
        $settings1 = [
            'text' => 'Test Watermark',
            'opacity' => 50,
            'position' => 'bottom-right',
        ];

        $settings2 = [
            'text' => 'Different Watermark',
            'opacity' => 50,
            'position' => 'bottom-right',
        ];

        $reflection = new \ReflectionClass($this->watermarkService);
        $method = $reflection->getMethod('generateSettingsHash');
        $method->setAccessible(true);

        $hash1 = $method->invoke($this->watermarkService, $settings1);
        $hash2 = $method->invoke($this->watermarkService, $settings2);

        $this->assertNotEquals($hash1, $hash2);
    }

    /** @test */
    public function it_returns_null_when_cached_image_does_not_exist()
    {
        $originalPath = 'products/test-image.jpg';
        
        $reflection = new \ReflectionClass($this->watermarkService);
        $method = $reflection->getMethod('getCachedWatermarkedImage');
        $method->setAccessible(true);

        $cachedPath = $method->invoke($this->watermarkService, $originalPath);

        $this->assertNull($cachedPath);
    }

    /** @test */
    public function it_returns_cached_image_when_it_exists_and_is_valid()
    {
        // Create a test original image
        $originalPath = 'products/test-image.jpg';
        $originalContent = 'fake image content';
        Storage::disk('public')->put($originalPath, $originalContent);

        // Create a cached image
        $settings = $this->watermarkService->getWatermarkSettings();
        $reflection = new \ReflectionClass($this->watermarkService);
        $generateCachedPathMethod = $reflection->getMethod('generateCachedWatermarkedPath');
        $generateCachedPathMethod->setAccessible(true);
        
        $cachedPath = $generateCachedPathMethod->invoke($this->watermarkService, $originalPath, $settings);
        Storage::disk('public')->put($cachedPath, 'fake cached image content');

        // Test getting cached image
        $getCachedMethod = $reflection->getMethod('getCachedWatermarkedImage');
        $getCachedMethod->setAccessible(true);
        
        $result = $getCachedMethod->invoke($this->watermarkService, $originalPath);

        $this->assertEquals($cachedPath, $result);
    }

    /** @test */
    public function it_invalidates_cache_when_original_image_is_newer()
    {
        // Create a test original image
        $originalPath = 'products/test-image.jpg';
        Storage::disk('public')->put($originalPath, 'fake image content');

        // Create a cached image (older)
        $settings = $this->watermarkService->getWatermarkSettings();
        $reflection = new \ReflectionClass($this->watermarkService);
        $generateCachedPathMethod = $reflection->getMethod('generateCachedWatermarkedPath');
        $generateCachedPathMethod->setAccessible(true);
        
        $cachedPath = $generateCachedPathMethod->invoke($this->watermarkService, $originalPath, $settings);
        Storage::disk('public')->put($cachedPath, 'fake cached image content');

        // Make original image newer by updating it
        sleep(1); // Ensure different timestamp
        Storage::disk('public')->put($originalPath, 'updated fake image content');

        // Test getting cached image - should return null due to invalidation
        $getCachedMethod = $reflection->getMethod('getCachedWatermarkedImage');
        $getCachedMethod->setAccessible(true);
        
        $result = $getCachedMethod->invoke($this->watermarkService, $originalPath);

        $this->assertNull($result);
        $this->assertFalse(Storage::disk('public')->exists($cachedPath));
    }

    /** @test */
    public function it_stores_cache_metadata_when_creating_watermarked_image()
    {
        $originalPath = 'products/test-image.jpg';
        $cachedPath = 'watermarks/cache/test-image_12345678.jpg';
        $settings = ['text' => 'Test'];

        $reflection = new \ReflectionClass($this->watermarkService);
        $method = $reflection->getMethod('storeCacheMetadata');
        $method->setAccessible(true);

        // Create the cached file first
        Storage::disk('public')->put($cachedPath, 'fake cached content');

        $method->invoke($this->watermarkService, $originalPath, $cachedPath, $settings);

        $cacheKey = 'watermark_cache_' . md5($originalPath);
        $metadata = Cache::get($cacheKey);

        $this->assertNotNull($metadata);
        $this->assertEquals($originalPath, $metadata['original_path']);
        $this->assertEquals($cachedPath, $metadata['cached_path']);
        $this->assertArrayHasKey('created_at', $metadata);
        $this->assertArrayHasKey('file_size', $metadata);
    }

    /** @test */
    public function it_clears_watermark_cache_directory()
    {
        // Create some cached files
        Storage::disk('public')->put('watermarks/cache/image1_hash1.jpg', 'content1');
        Storage::disk('public')->put('watermarks/cache/image2_hash2.jpg', 'content2');
        Storage::disk('public')->put('watermarks/cache/image3_hash3.png', 'content3');

        $this->assertTrue(Storage::disk('public')->exists('watermarks/cache/image1_hash1.jpg'));
        $this->assertTrue(Storage::disk('public')->exists('watermarks/cache/image2_hash2.jpg'));
        $this->assertTrue(Storage::disk('public')->exists('watermarks/cache/image3_hash3.png'));

        $this->watermarkService->clearWatermarkCache();

        $this->assertFalse(Storage::disk('public')->exists('watermarks/cache/image1_hash1.jpg'));
        $this->assertFalse(Storage::disk('public')->exists('watermarks/cache/image2_hash2.jpg'));
        $this->assertFalse(Storage::disk('public')->exists('watermarks/cache/image3_hash3.png'));
    }

    /** @test */
    public function it_cleans_up_old_cached_images()
    {
        // Create cache directory
        Storage::disk('public')->makeDirectory('watermarks/cache');

        // Create some cached files with different timestamps
        $recentFile = 'watermarks/cache/recent_image.jpg';
        $oldFile = 'watermarks/cache/old_image.jpg';

        Storage::disk('public')->put($recentFile, 'recent content');
        Storage::disk('public')->put($oldFile, 'old content');

        // Manually set old file timestamp (simulate old file)
        $oldFilePath = Storage::disk('public')->path($oldFile);
        $oldTimestamp = time() - (8 * 24 * 60 * 60); // 8 days ago
        touch($oldFilePath, $oldTimestamp);

        $deletedCount = $this->watermarkService->cleanupOldCachedImages(7);

        $this->assertEquals(1, $deletedCount);
        $this->assertTrue(Storage::disk('public')->exists($recentFile));
        $this->assertFalse(Storage::disk('public')->exists($oldFile));
    }

    /** @test */
    public function it_returns_cache_statistics()
    {
        // Create some cached files
        Storage::disk('public')->put('watermarks/cache/image1.jpg', 'content1');
        Storage::disk('public')->put('watermarks/cache/image2.jpg', 'longer content for image2');
        Storage::disk('public')->put('watermarks/cache/image3.png', 'content3');

        $stats = $this->watermarkService->getCacheStats();

        $this->assertTrue($stats['cache_directory_exists']);
        $this->assertEquals(3, $stats['total_cached_files']);
        $this->assertGreaterThan(0, $stats['total_cache_size']);
        $this->assertNotNull($stats['total_cache_size_human']);
        $this->assertNotNull($stats['oldest_cache_file']);
        $this->assertNotNull($stats['newest_cache_file']);
    }

    /** @test */
    public function it_returns_empty_cache_statistics_when_no_cache_exists()
    {
        $stats = $this->watermarkService->getCacheStats();

        $this->assertFalse($stats['cache_directory_exists']);
        $this->assertEquals(0, $stats['total_cached_files']);
        $this->assertEquals(0, $stats['total_cache_size']);
        $this->assertNull($stats['oldest_cache_file']);
        $this->assertNull($stats['newest_cache_file']);
    }

    /** @test */
    public function it_formats_bytes_correctly()
    {
        $reflection = new \ReflectionClass($this->watermarkService);
        $method = $reflection->getMethod('formatBytes');
        $method->setAccessible(true);

        $this->assertEquals('500 B', $method->invoke($this->watermarkService, 500));
        $this->assertEquals('1 KB', $method->invoke($this->watermarkService, 1024));
        $this->assertEquals('1.5 KB', $method->invoke($this->watermarkService, 1536));
        $this->assertEquals('1 MB', $method->invoke($this->watermarkService, 1024 * 1024));
        $this->assertEquals('1 GB', $method->invoke($this->watermarkService, 1024 * 1024 * 1024));
    }

    /** @test */
    public function it_skips_watermarking_when_disabled()
    {
        // Disable watermarking
        $this->settingsService->set('watermark_enabled', false, 'boolean', 'watermark');

        $originalPath = 'products/test-image.jpg';
        Storage::disk('public')->put($originalPath, 'fake image content');

        $result = $this->watermarkService->applyWatermark($originalPath);

        // Should return original path when watermarking is disabled
        $this->assertEquals($originalPath, $result);
    }

    /** @test */
    public function it_uses_cached_image_when_available()
    {
        // Create original image
        $originalPath = 'products/test-image.jpg';
        Storage::disk('public')->put($originalPath, $this->createFakeImageContent());

        // Create cached image manually
        $settings = $this->watermarkService->getWatermarkSettings();
        $reflection = new \ReflectionClass($this->watermarkService);
        $generateCachedPathMethod = $reflection->getMethod('generateCachedWatermarkedPath');
        $generateCachedPathMethod->setAccessible(true);
        
        $cachedPath = $generateCachedPathMethod->invoke($this->watermarkService, $originalPath, $settings);
        Storage::disk('public')->put($cachedPath, 'fake cached watermarked content');

        $result = $this->watermarkService->applyWatermark($originalPath);

        $this->assertEquals($cachedPath, $result);
    }

    /**
     * Create fake image content that can be processed by GD
     */
    protected function createFakeImageContent(): string
    {
        // Create a simple 1x1 pixel PNG image
        $image = imagecreate(1, 1);
        imagecolorallocate($image, 255, 255, 255);
        
        ob_start();
        imagepng($image);
        $content = ob_get_contents();
        ob_end_clean();
        
        imagedestroy($image);
        
        return $content;
    }
}