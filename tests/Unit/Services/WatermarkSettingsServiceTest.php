<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\WatermarkSettingsService;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class WatermarkSettingsServiceTest extends TestCase
{
    use RefreshDatabase;

    private WatermarkSettingsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WatermarkSettingsService();
        
        // Clear cache before each test
        Cache::flush();
        
        // Clear existing settings to avoid conflicts
        SiteSetting::query()->delete();
    }

    /** @test */
    public function it_can_get_all_watermark_settings()
    {
        // Create test settings
        SiteSetting::create([
            'key' => 'watermark_enabled',
            'value' => '1',
            'type' => 'boolean',
            'group' => 'image_protection'
        ]);

        SiteSetting::create([
            'key' => 'watermark_text',
            'value' => 'Test Watermark',
            'type' => 'string',
            'group' => 'watermark'
        ]);

        $settings = $this->service->getAllSettings();

        $this->assertIsArray($settings);
        $this->assertTrue($settings['watermark_enabled']);
        $this->assertEquals('Test Watermark', $settings['watermark_text']);
    }

    /** @test */
    public function it_caches_all_settings()
    {
        SiteSetting::create([
            'key' => 'watermark_enabled',
            'value' => '1',
            'type' => 'boolean',
            'group' => 'image_protection'
        ]);

        // First call should cache the result
        $settings1 = $this->service->getAllSettings();
        
        // Second call should use cache
        $settings2 = $this->service->getAllSettings();
        
        $this->assertEquals($settings1, $settings2);
        $this->assertTrue(Cache::has(WatermarkSettingsService::CACHE_KEY));
    }

    /** @test */
    public function it_can_get_specific_setting()
    {
        SiteSetting::create([
            'key' => 'watermark_opacity',
            'value' => '75',
            'type' => 'integer',
            'group' => 'watermark'
        ]);

        $opacity = $this->service->getSetting('watermark_opacity');
        $this->assertEquals(75, $opacity);
    }

    /** @test */
    public function it_returns_default_for_missing_setting()
    {
        $result = $this->service->getSetting('non_existent_key', 'default_value');
        $this->assertEquals('default_value', $result);
    }

    /** @test */
    public function it_checks_if_watermark_is_enabled()
    {
        SiteSetting::create([
            'key' => 'watermark_enabled',
            'value' => '1',
            'type' => 'boolean',
            'group' => 'image_protection'
        ]);

        $this->assertTrue($this->service->isWatermarkEnabled());
    }

    /** @test */
    public function it_checks_if_image_protection_is_enabled()
    {
        SiteSetting::create([
            'key' => 'image_protection_enabled',
            'value' => '0',
            'type' => 'boolean',
            'group' => 'image_protection'
        ]);

        $this->assertFalse($this->service->isImageProtectionEnabled());
    }

    /** @test */
    public function it_gets_watermark_text_with_fallback()
    {
        // Test with custom watermark text
        SiteSetting::create([
            'key' => 'watermark_text',
            'value' => 'Custom Watermark',
            'type' => 'string',
            'group' => 'watermark'
        ]);

        $this->assertEquals('Custom Watermark', $this->service->getWatermarkText());

        // Test fallback to site name
        SiteSetting::where('key', 'watermark_text')->delete();
        SiteSetting::create([
            'key' => 'site_name',
            'value' => 'Test Site',
            'type' => 'string',
            'group' => 'general'
        ]);

        // Clear cache to get fresh data
        $this->service->clearCache();
        
        $this->assertEquals('Test Site', $this->service->getWatermarkText());
    }

    /** @test */
    public function it_gets_watermark_logo_path()
    {
        SiteSetting::create([
            'key' => 'watermark_logo_path',
            'value' => '/path/to/logo.png',
            'type' => 'file',
            'group' => 'watermark'
        ]);

        $this->assertEquals('/path/to/logo.png', $this->service->getWatermarkLogoPath());
    }

    /** @test */
    public function it_returns_null_for_empty_logo_path()
    {
        SiteSetting::create([
            'key' => 'watermark_logo_path',
            'value' => '',
            'type' => 'file',
            'group' => 'watermark'
        ]);

        $this->assertNull($this->service->getWatermarkLogoPath());
    }

    /** @test */
    public function it_gets_watermark_position_with_default()
    {
        $this->assertEquals('bottom-right', $this->service->getWatermarkPosition());

        SiteSetting::create([
            'key' => 'watermark_position',
            'value' => 'top-left',
            'type' => 'string',
            'group' => 'watermark'
        ]);

        // Clear cache to get fresh data
        $this->service->clearCache();
        
        $this->assertEquals('top-left', $this->service->getWatermarkPosition());
    }

    /** @test */
    public function it_gets_watermark_opacity_with_default()
    {
        $this->assertEquals(50, $this->service->getWatermarkOpacity());

        SiteSetting::create([
            'key' => 'watermark_opacity',
            'value' => '75',
            'type' => 'integer',
            'group' => 'watermark'
        ]);

        // Clear cache to get fresh data
        $this->service->clearCache();
        
        $this->assertEquals(75, $this->service->getWatermarkOpacity());
    }

    /** @test */
    public function it_gets_watermark_size_with_default()
    {
        $this->assertEquals('medium', $this->service->getWatermarkSize());

        SiteSetting::create([
            'key' => 'watermark_size',
            'value' => 'large',
            'type' => 'string',
            'group' => 'watermark'
        ]);

        // Clear cache to get fresh data
        $this->service->clearCache();
        
        $this->assertEquals('large', $this->service->getWatermarkSize());
    }

    /** @test */
    public function it_gets_watermark_text_color_with_default()
    {
        $this->assertEquals('#ffffff', $this->service->getWatermarkTextColor());

        SiteSetting::create([
            'key' => 'watermark_text_color',
            'value' => '#000000',
            'type' => 'string',
            'group' => 'watermark'
        ]);

        // Clear cache to get fresh data
        $this->service->clearCache();
        
        $this->assertEquals('#000000', $this->service->getWatermarkTextColor());
    }

    /** @test */
    public function it_validates_watermark_settings_successfully()
    {
        $validSettings = [
            'watermark_text' => 'Test Watermark',
            'watermark_position' => 'center',
            'watermark_opacity' => 60,
            'watermark_size' => 'large',
            'watermark_text_color' => '#ff0000',
            'watermark_enabled' => true,
        ];

        $validated = $this->service->validateSettings($validSettings);
        
        $this->assertEquals($validSettings, $validated);
    }

    /** @test */
    public function it_validates_watermark_text_length()
    {
        $this->expectException(ValidationException::class);

        $settings = [
            'watermark_text' => str_repeat('a', 101), // Too long
            'watermark_position' => 'center',
            'watermark_opacity' => 50,
            'watermark_size' => 'medium',
            'watermark_text_color' => '#ffffff',
        ];

        $this->service->validateSettings($settings);
    }

    /** @test */
    public function it_validates_watermark_position()
    {
        $this->expectException(ValidationException::class);

        $settings = [
            'watermark_position' => 'invalid-position',
            'watermark_opacity' => 50,
            'watermark_size' => 'medium',
            'watermark_text_color' => '#ffffff',
        ];

        $this->service->validateSettings($settings);
    }

    /** @test */
    public function it_validates_watermark_opacity_range()
    {
        $this->expectException(ValidationException::class);

        $settings = [
            'watermark_position' => 'center',
            'watermark_opacity' => 5, // Too low
            'watermark_size' => 'medium',
            'watermark_text_color' => '#ffffff',
        ];

        $this->service->validateSettings($settings);
    }

    /** @test */
    public function it_validates_watermark_size()
    {
        $this->expectException(ValidationException::class);

        $settings = [
            'watermark_position' => 'center',
            'watermark_opacity' => 50,
            'watermark_size' => 'invalid-size',
            'watermark_text_color' => '#ffffff',
        ];

        $this->service->validateSettings($settings);
    }

    /** @test */
    public function it_validates_watermark_text_color_format()
    {
        $this->expectException(ValidationException::class);

        $settings = [
            'watermark_position' => 'center',
            'watermark_opacity' => 50,
            'watermark_size' => 'medium',
            'watermark_text_color' => 'invalid-color',
        ];

        $this->service->validateSettings($settings);
    }

    /** @test */
    public function it_updates_settings_with_validation()
    {
        $settings = [
            'watermark_text' => 'Updated Watermark',
            'watermark_position' => 'top-center',
            'watermark_opacity' => 70,
            'watermark_size' => 'small',
            'watermark_text_color' => '#00ff00',
            'watermark_enabled' => true,
        ];

        $result = $this->service->updateSettings($settings);
        
        $this->assertTrue($result);
        
        // Verify settings were saved
        $this->assertEquals('Updated Watermark', SiteSetting::get('watermark_text'));
        $this->assertEquals('top-center', SiteSetting::get('watermark_position'));
        $this->assertEquals(70, SiteSetting::get('watermark_opacity'));
        $this->assertEquals('small', SiteSetting::get('watermark_size'));
        $this->assertEquals('#00ff00', SiteSetting::get('watermark_text_color'));
        $this->assertTrue(SiteSetting::get('watermark_enabled'));
    }

    /** @test */
    public function it_clears_cache_when_updating_settings()
    {
        // Set up initial cache
        $this->service->getAllSettings();
        $this->assertTrue(Cache::has(WatermarkSettingsService::CACHE_KEY));

        $settings = [
            'watermark_text' => 'New Text',
            'watermark_position' => 'center',
            'watermark_opacity' => 50,
            'watermark_size' => 'medium',
            'watermark_text_color' => '#ffffff',
        ];

        $this->service->updateSettings($settings);
        
        // Cache should be cleared
        $this->assertFalse(Cache::has(WatermarkSettingsService::CACHE_KEY));
    }

    /** @test */
    public function it_gets_watermark_config()
    {
        SiteSetting::create(['key' => 'watermark_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'image_protection']);
        SiteSetting::create(['key' => 'watermark_text', 'value' => 'Test', 'type' => 'string', 'group' => 'watermark']);
        SiteSetting::create(['key' => 'watermark_position', 'value' => 'center', 'type' => 'string', 'group' => 'watermark']);
        SiteSetting::create(['key' => 'watermark_opacity', 'value' => '60', 'type' => 'integer', 'group' => 'watermark']);

        $config = $this->service->getWatermarkConfig();

        $this->assertIsArray($config);
        $this->assertTrue($config['enabled']);
        $this->assertEquals('Test', $config['text']);
        $this->assertEquals('center', $config['position']);
        $this->assertEquals(60, $config['opacity']);
    }

    /** @test */
    public function it_gets_protection_config()
    {
        SiteSetting::create(['key' => 'image_protection_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'image_protection']);
        SiteSetting::create(['key' => 'right_click_protection', 'value' => '1', 'type' => 'boolean', 'group' => 'image_protection']);
        SiteSetting::create(['key' => 'drag_drop_protection', 'value' => '0', 'type' => 'boolean', 'group' => 'image_protection']);

        $config = $this->service->getProtectionConfig();

        $this->assertIsArray($config);
        $this->assertTrue($config['enabled']);
        $this->assertTrue($config['right_click']);
        $this->assertFalse($config['drag_drop']);
    }

    /** @test */
    public function it_checks_if_any_protection_is_enabled()
    {
        // No protection enabled
        SiteSetting::create(['key' => 'image_protection_enabled', 'value' => '0', 'type' => 'boolean', 'group' => 'image_protection']);
        $this->assertFalse($this->service->hasAnyProtectionEnabled());

        // Protection enabled but no methods
        SiteSetting::where('key', 'image_protection_enabled')->update(['value' => '1']);
        SiteSetting::create(['key' => 'right_click_protection', 'value' => '0', 'type' => 'boolean', 'group' => 'image_protection']);
        SiteSetting::create(['key' => 'drag_drop_protection', 'value' => '0', 'type' => 'boolean', 'group' => 'image_protection']);
        SiteSetting::create(['key' => 'keyboard_protection', 'value' => '0', 'type' => 'boolean', 'group' => 'image_protection']);
        
        // Clear cache to get fresh data
        $this->service->clearCache();
        $this->assertFalse($this->service->hasAnyProtectionEnabled());

        // Enable one protection method
        SiteSetting::where('key', 'right_click_protection')->update(['value' => '1']);
        $this->service->clearCache();
        $this->assertTrue($this->service->hasAnyProtectionEnabled());
    }

    /** @test */
    public function it_validates_protection_settings_when_protection_disabled()
    {
        $settings = [
            'image_protection_enabled' => false,
            'right_click_protection' => false,
            'drag_drop_protection' => false,
            'keyboard_protection' => false,
        ];

        $result = $this->service->validateProtectionSettings($settings);
        $this->assertTrue($result);
    }

    /** @test */
    public function it_validates_protection_settings_when_protection_enabled_with_methods()
    {
        $settings = [
            'image_protection_enabled' => true,
            'right_click_protection' => true,
            'drag_drop_protection' => false,
            'keyboard_protection' => false,
        ];

        $result = $this->service->validateProtectionSettings($settings);
        $this->assertTrue($result);
    }

    /** @test */
    public function it_fails_validation_when_protection_enabled_without_methods()
    {
        $this->expectException(ValidationException::class);

        $settings = [
            'image_protection_enabled' => true,
            'right_click_protection' => false,
            'drag_drop_protection' => false,
            'keyboard_protection' => false,
        ];

        $this->service->validateProtectionSettings($settings);
    }

    /** @test */
    public function it_clears_all_related_caches()
    {
        // Set up caches
        Cache::put(WatermarkSettingsService::CACHE_KEY, ['test' => 'data']);
        Cache::put('site_settings_group_watermark', ['test' => 'data']);
        Cache::put('site_settings_group_image_protection', ['test' => 'data']);

        $this->service->clearCache();

        $this->assertFalse(Cache::has(WatermarkSettingsService::CACHE_KEY));
        $this->assertFalse(Cache::has('site_settings_group_watermark'));
        $this->assertFalse(Cache::has('site_settings_group_image_protection'));
    }
}