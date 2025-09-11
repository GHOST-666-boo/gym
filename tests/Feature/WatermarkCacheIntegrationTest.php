<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\WatermarkService;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;

class WatermarkCacheIntegrationTest extends TestCase
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
    public function complete_watermark_caching_workflow_works_correctly()
    {
        // Step 1: Create an original image
        $originalPath = 'products/test-product.jpg';
        $originalContent = $this->createFakeImageContent();
        Storage::disk('public')->put($originalPath, $originalContent);

        // Step 2: Apply watermark (should create cached version)
        $watermarkedPath = $this->watermarkService->applyWatermark($originalPath);
        
        // Verify cached image was created
        $this->assertNotEquals($originalPath, $watermarkedPath);
        $this->assertStringStartsWith('watermarks/cache/', $watermarkedPath);
        $this->assertTrue(Storage::disk('public')->exists($watermarkedPath));

        // Step 3: Apply watermark again (should use cached version)
        $watermarkedPath2 = $this->watermarkService->applyWatermark($originalPath);
        $this->assertEquals($watermarkedPath, $watermarkedPath2);

        // Step 4: Change watermark settings (should invalidate cache)
        $this->settingsService->set('watermark_text', 'New Watermark Text', 'string', 'watermark');
        
        // Verify cache was cleared
        $this->assertFalse(Storage::disk('public')->exists($watermarkedPath));

        // Step 5: Apply watermark with new settings (should create new cached version)
        $watermarkedPath3 = $this->watermarkService->applyWatermark($originalPath);
        $this->assertNotEquals($watermarkedPath, $watermarkedPath3);
        $this->assertTrue(Storage::disk('public')->exists($watermarkedPath3));

        // Step 6: Get cache statistics
        $stats = $this->watermarkService->getCacheStats();
        $this->assertEquals(1, $stats['total_cached_files']);
        $this->assertGreaterThan(0, $stats['total_cache_size']);

        // Step 7: Clear all cache using service
        $this->watermarkService->clearWatermarkCache();
        $this->assertFalse(Storage::disk('public')->exists($watermarkedPath3));

        // Step 8: Verify cache stats after clearing
        $statsAfter = $this->watermarkService->getCacheStats();
        $this->assertEquals(0, $statsAfter['total_cached_files']);
    }

    /** @test */
    public function console_commands_work_correctly()
    {
        // Create some cached files
        Storage::disk('public')->put('watermarks/cache/image1.jpg', 'content1');
        Storage::disk('public')->put('watermarks/cache/image2.jpg', 'content2');

        // Test cache clear command
        Artisan::call('watermark:clear', ['--force' => true]);
        
        $this->assertFalse(Storage::disk('public')->exists('watermarks/cache/image1.jpg'));
        $this->assertFalse(Storage::disk('public')->exists('watermarks/cache/image2.jpg'));

        // Create files for cleanup test
        Storage::disk('public')->put('watermarks/cache/recent.jpg', 'recent');
        Storage::disk('public')->put('watermarks/cache/old.jpg', 'old');

        // Make one file old
        $oldFilePath = Storage::disk('public')->path('watermarks/cache/old.jpg');
        $oldTimestamp = time() - (8 * 24 * 60 * 60); // 8 days ago
        touch($oldFilePath, $oldTimestamp);

        // Test cleanup command
        Artisan::call('watermark:cleanup', ['--days' => 7, '--force' => true]);

        $this->assertTrue(Storage::disk('public')->exists('watermarks/cache/recent.jpg'));
        $this->assertFalse(Storage::disk('public')->exists('watermarks/cache/old.jpg'));
    }

    /** @test */
    public function cache_invalidation_works_with_different_watermark_settings()
    {
        $originalPath = 'products/test-image.jpg';
        Storage::disk('public')->put($originalPath, $this->createFakeImageContent());

        // Test with different watermark settings
        $settings = [
            ['watermark_text', 'Watermark 1', 'string', 'watermark'],
            ['watermark_opacity', 75, 'integer', 'watermark'],
            ['watermark_position', 'top-left', 'string', 'watermark'],
            ['watermark_size', 32, 'integer', 'watermark'],
            ['watermark_text_color', '#FF0000', 'string', 'watermark'],
        ];

        $cachedPaths = [];

        foreach ($settings as [$key, $value, $type, $group]) {
            // Set the setting
            $this->settingsService->set($key, $value, $type, $group);
            
            // Apply watermark
            $cachedPath = $this->watermarkService->applyWatermark($originalPath);
            
            // Verify new cached file was created
            $this->assertTrue(Storage::disk('public')->exists($cachedPath));
            $this->assertNotContains($cachedPath, $cachedPaths);
            
            $cachedPaths[] = $cachedPath;
        }

        // Verify all different cached versions were created
        $this->assertCount(5, array_unique($cachedPaths));
    }

    /** @test */
    public function cache_respects_original_image_modifications()
    {
        $originalPath = 'products/test-image.jpg';
        Storage::disk('public')->put($originalPath, $this->createFakeImageContent());

        // Apply watermark (creates cached version)
        $cachedPath1 = $this->watermarkService->applyWatermark($originalPath);
        $this->assertTrue(Storage::disk('public')->exists($cachedPath1));

        // Store the cached file content to compare later
        $cachedContent1 = Storage::disk('public')->get($cachedPath1);

        // Modify original image with a newer timestamp
        sleep(2); // Ensure different timestamp
        $newContent = $this->createFakeImageContent(255, 0, 0); // Red image instead of white
        Storage::disk('public')->put($originalPath, $newContent);
        
        // Manually update the file timestamp to ensure it's newer
        $originalFullPath = Storage::disk('public')->path($originalPath);
        touch($originalFullPath, time());

        // Apply watermark again (should regenerate cached version due to newer original)
        $cachedPath2 = $this->watermarkService->applyWatermark($originalPath);
        
        // The cached path should be the same (same settings), but content should be different
        $this->assertEquals($cachedPath1, $cachedPath2);
        $this->assertTrue(Storage::disk('public')->exists($cachedPath2));
        
        // The cached file content should be different (regenerated from new original)
        $cachedContent2 = Storage::disk('public')->get($cachedPath2);
        $this->assertNotEquals($cachedContent1, $cachedContent2);
    }

    /** @test */
    public function disabled_watermarking_skips_caching()
    {
        $originalPath = 'products/test-image.jpg';
        Storage::disk('public')->put($originalPath, $this->createFakeImageContent());

        // Disable watermarking
        $this->settingsService->set('watermark_enabled', false, 'boolean', 'watermark');

        // Apply watermark (should return original path)
        $result = $this->watermarkService->applyWatermark($originalPath);
        $this->assertEquals($originalPath, $result);

        // Verify no cached files were created
        $stats = $this->watermarkService->getCacheStats();
        $this->assertEquals(0, $stats['total_cached_files']);
    }

    /**
     * Create fake image content that can be processed by GD
     */
    protected function createFakeImageContent(int $r = 255, int $g = 255, int $b = 255): string
    {
        // Create a simple 10x10 pixel PNG image
        $image = imagecreate(10, 10);
        imagecolorallocate($image, $r, $g, $b);
        
        ob_start();
        imagepng($image);
        $content = ob_get_contents();
        ob_end_clean();
        
        imagedestroy($image);
        
        return $content;
    }
}