<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ImageProtectionService;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;

class ImageProtectionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ImageProtectionService $imageProtectionService;
    protected $settingsServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock SettingsService
        $this->settingsServiceMock = Mockery::mock(SettingsService::class);
        $this->imageProtectionService = new ImageProtectionService($this->settingsServiceMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_is_protection_enabled_returns_false_when_disabled()
    {
        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('image_protection_enabled', false)
            ->andReturn(false);

        $result = $this->imageProtectionService->isProtectionEnabled();

        $this->assertFalse($result);
    }

    public function test_is_protection_enabled_returns_true_when_enabled()
    {
        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('image_protection_enabled', false)
            ->andReturn(true);

        $result = $this->imageProtectionService->isProtectionEnabled();

        $this->assertTrue($result);
    }

    public function test_get_protection_script_returns_empty_when_protection_disabled()
    {
        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('image_protection_enabled', false)
            ->andReturn(false);

        $result = $this->imageProtectionService->getProtectionScript();

        $this->assertEmpty($result);
    }

    public function test_get_protection_script_returns_script_when_protection_enabled()
    {
        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('image_protection_enabled', false)
            ->andReturn(true);

        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('right_click_protection', true)
            ->andReturn(true);

        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('drag_drop_protection', true)
            ->andReturn(true);

        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('keyboard_protection', true)
            ->andReturn(true);

        $result = $this->imageProtectionService->getProtectionScript();

        $this->assertNotEmpty($result);
        $this->assertStringContainsString('disableRightClick', $result);
        $this->assertStringContainsString('disableDragDrop', $result);
        $this->assertStringContainsString('disableKeyboardShortcuts', $result);
        $this->assertStringContainsString('addMobileTouchProtection', $result);
        $this->assertStringContainsString('initializeImageProtection', $result);
    }

    public function test_get_protection_settings_returns_correct_defaults()
    {
        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('right_click_protection', true)
            ->andReturn(true);

        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('drag_drop_protection', true)
            ->andReturn(true);

        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('keyboard_protection', true)
            ->andReturn(true);

        $result = $this->imageProtectionService->getProtectionSettings();

        $expected = [
            'right_click_protection' => true,
            'drag_drop_protection' => true,
            'keyboard_protection' => true,
        ];

        $this->assertEquals($expected, $result);
    }

    public function test_get_protection_settings_returns_custom_values()
    {
        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('right_click_protection', true)
            ->andReturn(false);

        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('drag_drop_protection', true)
            ->andReturn(true);

        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('keyboard_protection', true)
            ->andReturn(false);

        $result = $this->imageProtectionService->getProtectionSettings();

        $expected = [
            'right_click_protection' => false,
            'drag_drop_protection' => true,
            'keyboard_protection' => false,
        ];

        $this->assertEquals($expected, $result);
    }

    public function test_generate_protected_image_url_returns_original_when_protection_disabled()
    {
        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('image_protection_enabled', false)
            ->andReturn(false);

        $originalUrl = 'https://example.com/image.jpg';
        $result = $this->imageProtectionService->generateProtectedImageUrl($originalUrl);

        $this->assertEquals($originalUrl, $result);
    }

    public function test_generate_protected_image_url_returns_protected_url_when_protection_enabled()
    {
        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('image_protection_enabled', false)
            ->andReturn(true);

        $originalUrl = asset('storage/products/test-image.jpg');
        $result = $this->imageProtectionService->generateProtectedImageUrl($originalUrl);

        // Should return a protected URL with token
        $this->assertStringContainsString('/protected/image/', $result);
        $this->assertNotEquals($originalUrl, $result);
    }

    public function test_generate_protected_image_url_handles_invalid_urls()
    {
        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('image_protection_enabled', false)
            ->andReturn(true);

        $invalidUrl = 'https://external-site.com/image.jpg';
        $result = $this->imageProtectionService->generateProtectedImageUrl($invalidUrl);

        // Should return original URL if path cannot be extracted
        $this->assertEquals($invalidUrl, $result);
    }

    public function test_extract_image_path_from_storage_url()
    {
        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('image_protection_enabled', false)
            ->andReturn(true);

        $storageUrl = asset('storage/products/test-image.jpg');
        
        // Use reflection to test protected method
        $reflection = new \ReflectionClass($this->imageProtectionService);
        $method = $reflection->getMethod('extractImagePathFromUrl');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->imageProtectionService, $storageUrl);
        
        $this->assertEquals('products/test-image.jpg', $result);
    }

    public function test_generate_image_token_creates_valid_token()
    {
        $imagePath = 'products/test-image.jpg';
        
        // Use reflection to test protected method
        $reflection = new \ReflectionClass($this->imageProtectionService);
        $method = $reflection->getMethod('generateImageToken');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->imageProtectionService, $imagePath);
        
        $this->assertNotEmpty($result);
        $this->assertIsString($result);
        
        // Token should be base64 encoded
        $decoded = base64_decode($result);
        $this->assertNotFalse($decoded);
    }

    public function test_get_protection_styles_returns_empty_when_protection_disabled()
    {
        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('image_protection_enabled', false)
            ->andReturn(false);

        $result = $this->imageProtectionService->getProtectionStyles();

        $this->assertEmpty($result);
    }

    public function test_get_protection_styles_returns_css_when_protection_enabled()
    {
        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('image_protection_enabled', false)
            ->andReturn(true);

        $result = $this->imageProtectionService->getProtectionStyles();

        $this->assertNotEmpty($result);
        $this->assertStringContainsString('<style>', $result);
        $this->assertStringContainsString('.product-image img', $result);
        $this->assertStringContainsString('user-select: none', $result);
        $this->assertStringContainsString('user-drag: none', $result);
        $this->assertStringContainsString('-webkit-touch-callout: none', $result);
    }

    public function test_is_feature_enabled_returns_false_when_protection_disabled()
    {
        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('image_protection_enabled', false)
            ->andReturn(false);

        $result = $this->imageProtectionService->isFeatureEnabled('right_click_protection');

        $this->assertFalse($result);
    }

    public function test_is_feature_enabled_returns_correct_value_when_protection_enabled()
    {
        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('image_protection_enabled', false)
            ->andReturn(true);

        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('right_click_protection', true)
            ->andReturn(true);

        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('drag_drop_protection', true)
            ->andReturn(false);

        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('keyboard_protection', true)
            ->andReturn(true);

        $this->assertTrue($this->imageProtectionService->isFeatureEnabled('right_click_protection'));
        $this->assertFalse($this->imageProtectionService->isFeatureEnabled('drag_drop_protection'));
        $this->assertTrue($this->imageProtectionService->isFeatureEnabled('keyboard_protection'));
    }

    public function test_is_feature_enabled_returns_false_for_unknown_feature()
    {
        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('image_protection_enabled', false)
            ->andReturn(true);

        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('right_click_protection', true)
            ->andReturn(true);

        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('drag_drop_protection', true)
            ->andReturn(true);

        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('keyboard_protection', true)
            ->andReturn(true);

        $result = $this->imageProtectionService->isFeatureEnabled('unknown_feature');

        $this->assertFalse($result);
    }

    public function test_protection_script_includes_right_click_protection_when_enabled()
    {
        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('image_protection_enabled', false)
            ->andReturn(true);

        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('right_click_protection', true)
            ->andReturn(true);

        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('drag_drop_protection', true)
            ->andReturn(false);

        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('keyboard_protection', true)
            ->andReturn(false);

        $result = $this->imageProtectionService->getProtectionScript();

        $this->assertStringContainsString('disableRightClick', $result);
        $this->assertStringContainsString('addRightClickProtection', $result);
        $this->assertStringNotContainsString('disableDragDrop', $result);
        $this->assertStringNotContainsString('disableKeyboardShortcuts', $result);
    }

    public function test_protection_script_includes_drag_drop_protection_when_enabled()
    {
        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('image_protection_enabled', false)
            ->andReturn(true);

        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('right_click_protection', true)
            ->andReturn(false);

        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('drag_drop_protection', true)
            ->andReturn(true);

        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('keyboard_protection', true)
            ->andReturn(false);

        $result = $this->imageProtectionService->getProtectionScript();

        $this->assertStringContainsString('disableDragDrop', $result);
        $this->assertStringContainsString('addDragDropProtection', $result);
        $this->assertStringNotContainsString('disableRightClick', $result);
        $this->assertStringNotContainsString('disableKeyboardShortcuts', $result);
    }

    public function test_protection_script_includes_keyboard_protection_when_enabled()
    {
        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('image_protection_enabled', false)
            ->andReturn(true);

        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('right_click_protection', true)
            ->andReturn(false);

        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('drag_drop_protection', true)
            ->andReturn(false);

        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('keyboard_protection', true)
            ->andReturn(true);

        $result = $this->imageProtectionService->getProtectionScript();

        $this->assertStringContainsString('disableKeyboardShortcuts', $result);
        $this->assertStringContainsString('addKeyboardProtection', $result);
        $this->assertStringNotContainsString('disableRightClick', $result);
        $this->assertStringNotContainsString('disableDragDrop', $result);
    }

    public function test_protection_script_always_includes_mobile_touch_protection()
    {
        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('image_protection_enabled', false)
            ->andReturn(true);

        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('right_click_protection', true)
            ->andReturn(false);

        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('drag_drop_protection', true)
            ->andReturn(false);

        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('keyboard_protection', true)
            ->andReturn(false);

        $result = $this->imageProtectionService->getProtectionScript();

        // Mobile touch protection should always be included when protection is enabled
        $this->assertStringContainsString('addMobileTouchProtection', $result);
    }

    public function test_protection_script_includes_initialization_code()
    {
        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('image_protection_enabled', false)
            ->andReturn(true);

        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('right_click_protection', true)
            ->andReturn(true);

        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('drag_drop_protection', true)
            ->andReturn(true);

        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('keyboard_protection', true)
            ->andReturn(true);

        $result = $this->imageProtectionService->getProtectionScript();

        $this->assertStringContainsString('initializeImageProtection', $result);
        $this->assertStringContainsString('DOMContentLoaded', $result);
        $this->assertStringContainsString('MutationObserver', $result);
    }
}