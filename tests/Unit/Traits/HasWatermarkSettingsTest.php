<?php

namespace Tests\Unit\Traits;

use Tests\TestCase;
use App\Traits\HasWatermarkSettings;
use App\Services\WatermarkSettingsService;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class HasWatermarkSettingsTest extends TestCase
{
    use RefreshDatabase;

    private $testClass;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test class that uses the trait
        $this->testClass = new class {
            use HasWatermarkSettings;
            
            // Make protected methods public for testing
            public function testIsWatermarkEnabled(): bool
            {
                return $this->isWatermarkEnabled();
            }
            
            public function testIsImageProtectionEnabled(): bool
            {
                return $this->isImageProtectionEnabled();
            }
            
            public function testGetWatermarkConfig(): array
            {
                return $this->getWatermarkConfig();
            }
            
            public function testGetProtectionConfig(): array
            {
                return $this->getProtectionConfig();
            }
            
            public function testHasAnyProtectionEnabled(): bool
            {
                return $this->hasAnyProtectionEnabled();
            }
            
            public function testWatermarkSettings(): WatermarkSettingsService
            {
                return $this->watermarkSettings();
            }
        };
        
        // Clear cache and settings
        Cache::flush();
        SiteSetting::query()->delete();
    }

    public function test_it_can_access_watermark_settings_service()
    {
        $service = $this->testClass->testWatermarkSettings();
        
        $this->assertInstanceOf(WatermarkSettingsService::class, $service);
    }

    public function test_it_can_check_if_watermark_is_enabled()
    {
        // Test when disabled
        SiteSetting::create([
            'key' => 'watermark_enabled',
            'value' => '0',
            'type' => 'boolean',
            'group' => 'image_protection'
        ]);

        $this->assertFalse($this->testClass->testIsWatermarkEnabled());

        // Test when enabled
        SiteSetting::where('key', 'watermark_enabled')->update(['value' => '1']);
        Cache::flush(); // Clear cache to get fresh data
        
        $this->assertTrue($this->testClass->testIsWatermarkEnabled());
    }

    public function test_it_can_check_if_image_protection_is_enabled()
    {
        // Test when disabled
        SiteSetting::create([
            'key' => 'image_protection_enabled',
            'value' => '0',
            'type' => 'boolean',
            'group' => 'image_protection'
        ]);

        $this->assertFalse($this->testClass->testIsImageProtectionEnabled());

        // Test when enabled
        SiteSetting::where('key', 'image_protection_enabled')->update(['value' => '1']);
        Cache::flush(); // Clear cache to get fresh data
        
        $this->assertTrue($this->testClass->testIsImageProtectionEnabled());
    }

    public function test_it_can_get_watermark_config()
    {
        // Create test settings
        SiteSetting::create(['key' => 'watermark_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'image_protection']);
        SiteSetting::create(['key' => 'watermark_text', 'value' => 'Test Watermark', 'type' => 'string', 'group' => 'watermark']);
        SiteSetting::create(['key' => 'watermark_position', 'value' => 'center', 'type' => 'string', 'group' => 'watermark']);
        SiteSetting::create(['key' => 'watermark_opacity', 'value' => '75', 'type' => 'integer', 'group' => 'watermark']);

        $config = $this->testClass->testGetWatermarkConfig();

        $this->assertIsArray($config);
        $this->assertTrue($config['enabled']);
        $this->assertEquals('Test Watermark', $config['text']);
        $this->assertEquals('center', $config['position']);
        $this->assertEquals(75, $config['opacity']);
        $this->assertArrayHasKey('logo_path', $config);
        $this->assertArrayHasKey('size', $config);
        $this->assertArrayHasKey('text_color', $config);
    }

    public function test_it_can_get_protection_config()
    {
        // Create test settings
        SiteSetting::create(['key' => 'image_protection_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'image_protection']);
        SiteSetting::create(['key' => 'right_click_protection', 'value' => '1', 'type' => 'boolean', 'group' => 'image_protection']);
        SiteSetting::create(['key' => 'drag_drop_protection', 'value' => '0', 'type' => 'boolean', 'group' => 'image_protection']);
        SiteSetting::create(['key' => 'keyboard_protection', 'value' => '1', 'type' => 'boolean', 'group' => 'image_protection']);

        $config = $this->testClass->testGetProtectionConfig();

        $this->assertIsArray($config);
        $this->assertTrue($config['enabled']);
        $this->assertTrue($config['right_click']);
        $this->assertFalse($config['drag_drop']);
        $this->assertTrue($config['keyboard']);
    }

    public function test_it_can_check_if_any_protection_is_enabled()
    {
        // Test when protection is disabled
        SiteSetting::create(['key' => 'image_protection_enabled', 'value' => '0', 'type' => 'boolean', 'group' => 'image_protection']);
        
        $this->assertFalse($this->testClass->testHasAnyProtectionEnabled());

        // Test when protection is enabled but no methods are enabled
        SiteSetting::where('key', 'image_protection_enabled')->update(['value' => '1']);
        SiteSetting::create(['key' => 'right_click_protection', 'value' => '0', 'type' => 'boolean', 'group' => 'image_protection']);
        SiteSetting::create(['key' => 'drag_drop_protection', 'value' => '0', 'type' => 'boolean', 'group' => 'image_protection']);
        SiteSetting::create(['key' => 'keyboard_protection', 'value' => '0', 'type' => 'boolean', 'group' => 'image_protection']);
        
        Cache::flush(); // Clear cache to get fresh data
        $this->assertFalse($this->testClass->testHasAnyProtectionEnabled());

        // Test when at least one protection method is enabled
        SiteSetting::where('key', 'right_click_protection')->update(['value' => '1']);
        Cache::flush(); // Clear cache to get fresh data
        
        $this->assertTrue($this->testClass->testHasAnyProtectionEnabled());
    }

    public function test_trait_methods_return_consistent_results()
    {
        // Set up consistent test data
        SiteSetting::create(['key' => 'watermark_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'image_protection']);
        SiteSetting::create(['key' => 'image_protection_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'image_protection']);
        SiteSetting::create(['key' => 'right_click_protection', 'value' => '1', 'type' => 'boolean', 'group' => 'image_protection']);

        // Multiple calls should return consistent results
        $this->assertTrue($this->testClass->testIsWatermarkEnabled());
        $this->assertTrue($this->testClass->testIsWatermarkEnabled());
        
        $this->assertTrue($this->testClass->testIsImageProtectionEnabled());
        $this->assertTrue($this->testClass->testIsImageProtectionEnabled());
        
        $this->assertTrue($this->testClass->testHasAnyProtectionEnabled());
        $this->assertTrue($this->testClass->testHasAnyProtectionEnabled());
    }
}