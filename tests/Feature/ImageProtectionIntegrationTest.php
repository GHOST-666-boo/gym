<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Services\SettingsService;
use App\Services\WatermarkService;
use App\Services\ImageProtectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class ImageProtectionIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected SettingsService $settingsService;
    protected WatermarkService $watermarkService;
    protected ImageProtectionService $imageProtectionService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adminUser = User::factory()->create([
            'email' => 'admin@test.com',
            'is_admin' => true,
        ]);
        
        $this->settingsService = app(SettingsService::class);
        $this->watermarkService = app(WatermarkService::class);
        $this->imageProtectionService = app(ImageProtectionService::class);
        
        Storage::fake('public');
    }

    /** @test */
    public function test_complete_image_protection_workflow()
    {
        $this->actingAs($this->adminUser);

        // Step 1: Enable image protection through admin interface
        $response = $this->patch(route('admin.settings.update'), [
            'image_protection_enabled' => '1',
            'right_click_protection' => '1',
            'drag_drop_protection' => '1',
            'keyboard_protection' => '1',
        ]);

        $response->assertRedirect();
        $this->assertTrue($this->imageProtectionService->isProtectionEnabled());

        // Step 2: Enable watermarking
        $response = $this->patch(route('admin.settings.update'), [
            'watermark_enabled' => '1',
            'watermark_text' => 'Integration Test Watermark',
            'watermark_position' => 'bottom-right',
            'watermark_opacity' => '60',
        ]);

        $response->assertRedirect();

        // Step 3: Test protection script generation
        $protectionScript = $this->imageProtectionService->getProtectionScript();
        $this->assertNotEmpty($protectionScript);
        $this->assertStringContainsString('accessibility', $protectionScript);
        $this->assertStringContainsString('screen reader', $protectionScript);

        // Step 4: Test watermark application
        if (extension_loaded('gd')) {
            // Create test image
            $image = imagecreatetruecolor(400, 300);
            $color = imagecolorallocate($image, 100, 150, 200);
            imagefill($image, 0, 0, $color);
            
            $imagePath = 'products/integration_test.jpg';
            $fullPath = Storage::disk('public')->path($imagePath);
            $directory = dirname($fullPath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            
            imagejpeg($image, $fullPath, 90);
            imagedestroy($image);

            $watermarkedPath = $this->watermarkService->applyWatermark($imagePath);
            $this->assertNotEquals($imagePath, $watermarkedPath);
            $this->assertTrue(Storage::disk('public')->exists($watermarkedPath));
        }

        // Step 5: Test public page with protection
        $response = $this->get('/products');
        $response->assertStatus(200);
        
        $content = $response->getContent();
        $this->assertStringContainsString('product-image', $content);

        // Step 6: Test settings persistence
        $this->assertTrue((bool)$this->settingsService->get('image_protection_enabled'));
        $this->assertTrue((bool)$this->settingsService->get('watermark_enabled'));
        $this->assertEquals('Integration Test Watermark', $this->settingsService->get('watermark_text'));
    }

    /** @test */
    public function test_protection_effectiveness_measurement()
    {
        $this->actingAs($this->adminUser);

        // Enable all protection features
        $this->settingsService->updateMultiple([
            'image_protection_enabled' => ['value' => true, 'type' => 'boolean', 'group' => 'image_protection'],
            'right_click_protection' => ['value' => true, 'type' => 'boolean', 'group' => 'image_protection'],
            'drag_drop_protection' => ['value' => true, 'type' => 'boolean', 'group' => 'image_protection'],
            'keyboard_protection' => ['value' => true, 'type' => 'boolean', 'group' => 'image_protection'],
        ]);

        // Get protection effectiveness report
        $report = $this->imageProtectionService->getProtectionEffectivenessReport();

        $this->assertArrayHasKey('javascript_protection', $report);
        $this->assertArrayHasKey('css_fallback_protection', $report);
        $this->assertArrayHasKey('server_side_protection', $report);
        $this->assertArrayHasKey('overall_assessment', $report);

        // Verify protection levels
        $this->assertEquals('enabled', $report['javascript_protection']['right_click']);
        $this->assertEquals('enabled', $report['javascript_protection']['drag_drop']);
        $this->assertEquals('enabled', $report['javascript_protection']['keyboard_shortcuts']);
        $this->assertEquals('enabled', $report['javascript_protection']['mobile_touch']);

        // Verify overall assessment
        $this->assertContains($report['overall_assessment']['protection_level'], ['high', 'medium', 'low', 'minimal']);
        $this->assertIsArray($report['overall_assessment']['vulnerabilities']);
        $this->assertIsArray($report['overall_assessment']['recommendations']);
    }

    /** @test */
    public function test_accessibility_integration_with_protection()
    {
        $this->actingAs($this->adminUser);

        // Enable protection
        $this->settingsService->updateMultiple([
            'image_protection_enabled' => ['value' => true, 'type' => 'boolean', 'group' => 'image_protection'],
            'right_click_protection' => ['value' => true, 'type' => 'boolean', 'group' => 'image_protection'],
            'keyboard_protection' => ['value' => true, 'type' => 'boolean', 'group' => 'image_protection'],
        ]);

        // Get protection script
        $script = $this->imageProtectionService->getProtectionScript();

        // Verify accessibility features are included
        $this->assertStringContainsString('detectAssistiveTechnology', $script);
        $this->assertStringContainsString('accessibility.hasAccessibilityFeatures', $script);
        $this->assertStringContainsString('accessibility.isScreenReader', $script);

        // Verify screen reader detection
        $this->assertStringContainsString('nvda', $script);
        $this->assertStringContainsString('jaws', $script);
        $this->assertStringContainsString('voiceover', $script);
        $this->assertStringContainsString('talkback', $script);

        // Verify accessibility preferences detection
        $this->assertStringContainsString('prefers-reduced-motion', $script);
        $this->assertStringContainsString('prefers-contrast', $script);

        // Verify keyboard navigation preservation
        $this->assertStringContainsString('Tab', $script);
        $this->assertStringContainsString('Enter', $script);
        $this->assertStringContainsString('Space', $script);
        $this->assertStringContainsString('Escape', $script);
        $this->assertStringContainsString('ArrowUp', $script);
        $this->assertStringContainsString('ArrowDown', $script);
        $this->assertStringContainsString('ArrowLeft', $script);
        $this->assertStringContainsString('ArrowRight', $script);

        // Verify ARIA attributes preservation
        $this->assertStringContainsString('aria-label', $script);
        $this->assertStringContainsString('aria-describedby', $script);
        $this->assertStringContainsString('tabindex', $script);
    }

    /** @test */
    public function test_cross_browser_compatibility_integration()
    {
        $this->actingAs($this->adminUser);

        // Enable protection
        $this->settingsService->set('image_protection_enabled', true);

        // Get protection script and styles
        $script = $this->imageProtectionService->getProtectionScript();
        $styles = $this->imageProtectionService->getProtectionStyles();

        // Verify browser detection
        $this->assertStringContainsString('detectBrowser', $script);
        $this->assertStringContainsString('isChrome', $script);
        $this->assertStringContainsString('isFirefox', $script);
        $this->assertStringContainsString('isSafari', $script);
        $this->assertStringContainsString('isEdge', $script);
        $this->assertStringContainsString('isIE', $script);

        // Verify mobile detection
        $this->assertStringContainsString('isIOS', $script);
        $this->assertStringContainsString('isAndroid', $script);
        $this->assertStringContainsString('isMobile', $script);

        // Verify feature detection
        $this->assertStringContainsString('supportsTouch', $script);
        $this->assertStringContainsString('supportsGestures', $script);
        $this->assertStringContainsString('supportsPointer', $script);

        // Verify cross-browser CSS
        $this->assertStringContainsString('-webkit-user-select', $styles);
        $this->assertStringContainsString('-moz-user-select', $styles);
        $this->assertStringContainsString('-ms-user-select', $styles);
        $this->assertStringContainsString('user-select', $styles);

        // Verify browser-specific CSS hacks
        $this->assertStringContainsString('@media screen and (-webkit-min-device-pixel-ratio:0)', $styles);
        $this->assertStringContainsString('@-moz-document url-prefix()', $styles);
        $this->assertStringContainsString('@media screen and (min-width:0\\0)', $styles);
        $this->assertStringContainsString('@supports (-ms-ime-align:auto)', $styles);
    }

    /** @test */
    public function test_performance_impact_integration()
    {
        $this->actingAs($this->adminUser);

        // Enable all features
        $this->settingsService->updateMultiple([
            'image_protection_enabled' => ['value' => true, 'type' => 'boolean', 'group' => 'image_protection'],
            'watermark_enabled' => ['value' => true, 'type' => 'boolean', 'group' => 'watermark'],
            'right_click_protection' => ['value' => true, 'type' => 'boolean', 'group' => 'image_protection'],
            'drag_drop_protection' => ['value' => true, 'type' => 'boolean', 'group' => 'image_protection'],
            'keyboard_protection' => ['value' => true, 'type' => 'boolean', 'group' => 'image_protection'],
        ]);

        // Measure script generation time
        $startTime = microtime(true);
        $script = $this->imageProtectionService->getProtectionScript();
        $scriptGenerationTime = microtime(true) - $startTime;

        // Script generation should be fast (under 10ms)
        $this->assertLessThan(0.01, $scriptGenerationTime, 'Script generation took too long');

        // Measure styles generation time
        $startTime = microtime(true);
        $styles = $this->imageProtectionService->getProtectionStyles();
        $stylesGenerationTime = microtime(true) - $startTime;

        // Styles generation should be fast (under 5ms)
        $this->assertLessThan(0.005, $stylesGenerationTime, 'Styles generation took too long');

        // Verify script size is reasonable (under 50KB)
        $scriptSize = strlen($script);
        $this->assertLessThan(50000, $scriptSize, 'Protection script is too large');

        // Verify styles size is reasonable (under 10KB)
        $stylesSize = strlen($styles);
        $this->assertLessThan(10000, $stylesSize, 'Protection styles are too large');
    }

    /** @test */
    public function test_error_handling_integration()
    {
        $this->actingAs($this->adminUser);

        // Enable protection
        $this->settingsService->set('image_protection_enabled', true);

        // Test enhanced protection script with error handling
        $enhancedScript = $this->imageProtectionService->getEnhancedProtectionScript();
        
        $this->assertStringContainsString('error', $enhancedScript);
        $this->assertStringContainsString('try', $enhancedScript);
        $this->assertStringContainsString('catch', $enhancedScript);
        $this->assertStringContainsString('js-protection-failed', $enhancedScript);
        $this->assertStringContainsString('fallback', $enhancedScript);

        // Test no-script fallback
        $noScriptFallback = $this->imageProtectionService->getNoScriptFallbackStyles();
        
        $this->assertStringContainsString('<noscript>', $noScriptFallback);
        $this->assertStringContainsString('pointer-events: none', $noScriptFallback);
        $this->assertStringContainsString('user-select: none', $noScriptFallback);
    }

    /** @test */
    public function test_settings_validation_integration()
    {
        $this->actingAs($this->adminUser);

        // Test valid settings
        $validSettings = [
            'image_protection_enabled' => '1',
            'watermark_enabled' => '1',
            'watermark_text' => 'Valid Watermark Text',
            'watermark_position' => 'center',
            'watermark_opacity' => '50',
            'watermark_size' => '18',
            'watermark_text_color' => '#000000',
        ];

        $response = $this->patch(route('admin.settings.update'), $validSettings);
        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        // Test invalid settings
        $invalidSettings = [
            'watermark_opacity' => '150', // Invalid range
            'watermark_position' => 'invalid-position', // Invalid position
            'watermark_text_color' => 'not-a-color', // Invalid color
            'watermark_size' => '0', // Invalid size
        ];

        foreach ($invalidSettings as $key => $value) {
            $response = $this->patch(route('admin.settings.update'), [$key => $value]);
            $response->assertSessionHasErrors($key);
        }
    }

    /** @test */
    public function test_cache_integration()
    {
        $this->actingAs($this->adminUser);

        // Enable features
        $this->settingsService->updateMultiple([
            'image_protection_enabled' => ['value' => true, 'type' => 'boolean', 'group' => 'image_protection'],
            'watermark_enabled' => ['value' => true, 'type' => 'boolean', 'group' => 'watermark'],
        ]);

        // Test settings caching
        $cachedValue = $this->settingsService->get('image_protection_enabled');
        $this->assertTrue((bool)$cachedValue);

        // Test cache invalidation
        $this->settingsService->set('image_protection_enabled', false);
        $newValue = $this->settingsService->get('image_protection_enabled');
        $this->assertFalse((bool)$newValue);

        // Test watermark cache integration
        if (extension_loaded('gd')) {
            // Create test image
            $image = imagecreatetruecolor(200, 150);
            $color = imagecolorallocate($image, 100, 150, 200);
            imagefill($image, 0, 0, $color);
            
            $imagePath = 'products/cache_integration_test.jpg';
            $fullPath = Storage::disk('public')->path($imagePath);
            $directory = dirname($fullPath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            
            imagejpeg($image, $fullPath, 90);
            imagedestroy($image);

            // First watermark generation
            $watermarkedPath1 = $this->watermarkService->applyWatermark($imagePath);
            
            // Second watermark generation (should use cache)
            $watermarkedPath2 = $this->watermarkService->applyWatermark($imagePath);
            
            $this->assertEquals($watermarkedPath1, $watermarkedPath2);
        }
    }

    /** @test */
    public function test_mobile_integration()
    {
        $this->actingAs($this->adminUser);

        // Enable protection
        $this->settingsService->set('image_protection_enabled', true);

        // Get protection script
        $script = $this->imageProtectionService->getProtectionScript();

        // Verify mobile-specific features
        $this->assertStringContainsString('touchstart', $script);
        $this->assertStringContainsString('touchmove', $script);
        $this->assertStringContainsString('touchend', $script);
        $this->assertStringContainsString('gesturestart', $script);
        $this->assertStringContainsString('gesturechange', $script);
        $this->assertStringContainsString('gestureend', $script);

        // Verify mobile browser detection
        $this->assertStringContainsString('iOS', $script);
        $this->assertStringContainsString('Android', $script);
        $this->assertStringContainsString('Mobile', $script);

        // Verify touch-specific protection
        $this->assertStringContainsString('webkitTouchCallout', $script);
        $this->assertStringContainsString('touchAction', $script);
        $this->assertStringContainsString('touchforcechange', $script);

        // Get protection styles
        $styles = $this->imageProtectionService->getProtectionStyles();

        // Verify mobile-responsive styles
        $this->assertStringContainsString('@media (max-width: 767px)', $styles);
        $this->assertStringContainsString('touch-action: none', $styles);
        $this->assertStringContainsString('-webkit-touch-callout: none', $styles);
    }
}