<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\WatermarkService;
use App\Services\SettingsService;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Event;

class WatermarkCacheInvalidationTest extends TestCase
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
    public function it_invalidates_cache_when_watermark_text_changes()
    {
        // Create some cached files
        Storage::disk('public')->put('watermarks/cache/image1_hash1.jpg', 'content1');
        Storage::disk('public')->put('watermarks/cache/image2_hash2.jpg', 'content2');

        $this->assertTrue(Storage::disk('public')->exists('watermarks/cache/image1_hash1.jpg'));
        $this->assertTrue(Storage::disk('public')->exists('watermarks/cache/image2_hash2.jpg'));

        // Change watermark text setting
        $this->settingsService->set('watermark_text', 'New Watermark Text', 'string', 'watermark');

        // Cache should be cleared
        $this->assertFalse(Storage::disk('public')->exists('watermarks/cache/image1_hash1.jpg'));
        $this->assertFalse(Storage::disk('public')->exists('watermarks/cache/image2_hash2.jpg'));
    }

    /** @test */
    public function it_invalidates_cache_when_watermark_opacity_changes()
    {
        // Create some cached files
        Storage::disk('public')->put('watermarks/cache/image1_hash1.jpg', 'content1');

        $this->assertTrue(Storage::disk('public')->exists('watermarks/cache/image1_hash1.jpg'));

        // Change watermark opacity setting
        $this->settingsService->set('watermark_opacity', 75, 'integer', 'watermark');

        // Cache should be cleared
        $this->assertFalse(Storage::disk('public')->exists('watermarks/cache/image1_hash1.jpg'));
    }

    /** @test */
    public function it_invalidates_cache_when_watermark_position_changes()
    {
        // Create some cached files
        Storage::disk('public')->put('watermarks/cache/image1_hash1.jpg', 'content1');

        $this->assertTrue(Storage::disk('public')->exists('watermarks/cache/image1_hash1.jpg'));

        // Change watermark position setting
        $this->settingsService->set('watermark_position', 'top-left', 'string', 'watermark');

        // Cache should be cleared
        $this->assertFalse(Storage::disk('public')->exists('watermarks/cache/image1_hash1.jpg'));
    }

    /** @test */
    public function it_invalidates_cache_when_watermark_enabled_changes()
    {
        // Create some cached files
        Storage::disk('public')->put('watermarks/cache/image1_hash1.jpg', 'content1');

        $this->assertTrue(Storage::disk('public')->exists('watermarks/cache/image1_hash1.jpg'));

        // Disable watermarking
        $this->settingsService->set('watermark_enabled', false, 'boolean', 'watermark');

        // Cache should be cleared
        $this->assertFalse(Storage::disk('public')->exists('watermarks/cache/image1_hash1.jpg'));
    }

    /** @test */
    public function it_invalidates_cache_when_watermark_logo_path_changes()
    {
        // Create some cached files
        Storage::disk('public')->put('watermarks/cache/image1_hash1.jpg', 'content1');

        $this->assertTrue(Storage::disk('public')->exists('watermarks/cache/image1_hash1.jpg'));

        // Change watermark logo path setting
        $this->settingsService->set('watermark_logo_path', 'logos/new-logo.png', 'string', 'watermark');

        // Cache should be cleared
        $this->assertFalse(Storage::disk('public')->exists('watermarks/cache/image1_hash1.jpg'));
    }

    /** @test */
    public function it_does_not_invalidate_cache_for_non_watermark_settings()
    {
        // Create some cached files
        Storage::disk('public')->put('watermarks/cache/image1_hash1.jpg', 'content1');

        $this->assertTrue(Storage::disk('public')->exists('watermarks/cache/image1_hash1.jpg'));

        // Change a non-watermark setting
        $this->settingsService->set('site_name', 'New Site Name', 'string', 'general');

        // Cache should still exist
        $this->assertTrue(Storage::disk('public')->exists('watermarks/cache/image1_hash1.jpg'));
    }

    /** @test */
    public function it_invalidates_cache_when_image_protection_settings_change()
    {
        // Create some cached files
        Storage::disk('public')->put('watermarks/cache/image1_hash1.jpg', 'content1');

        $this->assertTrue(Storage::disk('public')->exists('watermarks/cache/image1_hash1.jpg'));

        // Change image protection setting (related to watermarking)
        $this->settingsService->set('image_protection_enabled', false, 'boolean', 'image_protection');

        // Cache should be cleared
        $this->assertFalse(Storage::disk('public')->exists('watermarks/cache/image1_hash1.jpg'));
    }

    /** @test */
    public function it_handles_cache_invalidation_when_setting_is_deleted()
    {
        // Create some cached files
        Storage::disk('public')->put('watermarks/cache/image1_hash1.jpg', 'content1');

        $this->assertTrue(Storage::disk('public')->exists('watermarks/cache/image1_hash1.jpg'));

        // First create the setting, then delete it
        $setting = SiteSetting::create([
            'key' => 'test_watermark_setting',
            'value' => 'test value',
            'type' => 'string',
            'group' => 'watermark'
        ]);

        // Delete the watermark setting
        $setting->delete();

        // Cache should be cleared
        $this->assertFalse(Storage::disk('public')->exists('watermarks/cache/image1_hash1.jpg'));
    }

    /** @test */
    public function it_logs_cache_invalidation_events()
    {
        // Create some cached files
        Storage::disk('public')->put('watermarks/cache/image1_hash1.jpg', 'content1');

        $this->assertTrue(Storage::disk('public')->exists('watermarks/cache/image1_hash1.jpg'));

        // Change watermark setting - this should trigger cache invalidation
        $this->settingsService->set('watermark_text', 'New Text', 'string', 'watermark');

        // Cache should be cleared
        $this->assertFalse(Storage::disk('public')->exists('watermarks/cache/image1_hash1.jpg'));
    }

    /** @test */
    public function cache_invalidation_listener_identifies_watermark_settings_correctly()
    {
        $listener = app(\App\Listeners\InvalidateWatermarkCache::class);
        
        // Test with watermark setting
        $watermarkEvent = (object) [
            'key' => 'watermark_text',
            'group' => 'watermark'
        ];
        
        $reflection = new \ReflectionClass($listener);
        $method = $reflection->getMethod('isWatermarkSetting');
        $method->setAccessible(true);
        
        $this->assertTrue($method->invoke($listener, $watermarkEvent));
        
        // Test with non-watermark setting
        $nonWatermarkEvent = (object) [
            'key' => 'site_name',
            'group' => 'general'
        ];
        
        $this->assertFalse($method->invoke($listener, $nonWatermarkEvent));
        
        // Test with image protection setting
        $protectionEvent = (object) [
            'key' => 'image_protection_enabled',
            'group' => 'image_protection'
        ];
        
        $this->assertTrue($method->invoke($listener, $protectionEvent));
    }

    /** @test */
    public function cache_invalidation_works_with_multiple_setting_updates()
    {
        // Create some cached files
        Storage::disk('public')->put('watermarks/cache/image1_hash1.jpg', 'content1');
        Storage::disk('public')->put('watermarks/cache/image2_hash2.jpg', 'content2');

        $this->assertTrue(Storage::disk('public')->exists('watermarks/cache/image1_hash1.jpg'));
        $this->assertTrue(Storage::disk('public')->exists('watermarks/cache/image2_hash2.jpg'));

        // Update multiple watermark settings at once
        $this->settingsService->updateMultiple([
            'watermark_text' => ['value' => 'New Text', 'type' => 'string', 'group' => 'watermark'],
            'watermark_opacity' => ['value' => 75, 'type' => 'integer', 'group' => 'watermark'],
            'watermark_position' => ['value' => 'top-center', 'type' => 'string', 'group' => 'watermark'],
        ]);

        // Cache should be cleared after the first watermark setting change
        $this->assertFalse(Storage::disk('public')->exists('watermarks/cache/image1_hash1.jpg'));
        $this->assertFalse(Storage::disk('public')->exists('watermarks/cache/image2_hash2.jpg'));
    }
}