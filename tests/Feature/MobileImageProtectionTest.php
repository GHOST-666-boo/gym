<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\ImageProtectionService;
use App\Services\SettingsService;
use App\Services\WatermarkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Mockery;

class MobileImageProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected ImageProtectionService $imageProtectionService;
    protected WatermarkService $watermarkService;
    protected $settingsServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock SettingsService
        $this->settingsServiceMock = Mockery::mock(SettingsService::class);
        $this->imageProtectionService = new ImageProtectionService($this->settingsServiceMock);
        $this->watermarkService = new WatermarkService($this->settingsServiceMock);
        
        // Set up storage for testing
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_mobile_protection_script_includes_enhanced_touch_protection()
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

        $script = $this->imageProtectionService->getProtectionScript();

        // Test enhanced mobile touch protection features
        $this->assertStringContainsString('addMobileTouchProtection', $script);
        $this->assertStringContainsString('detectMobileDevice', $script);
        $this->assertStringContainsString('touchStartTime', $script);
        $this->assertStringContainsString('longPressTimer', $script);
        $this->assertStringContainsString('doubleTapTimer', $script);
        
        // Test device-specific protections
        $this->assertStringContainsString('addDeviceSpecificProtections', $script);
        $this->assertStringContainsString('addIOSSpecificProtections', $script);
        $this->assertStringContainsString('addAndroidSpecificProtections', $script);
        $this->assertStringContainsString('addTabletSpecificProtections', $script);
        
        // Test gesture prevention
        $this->assertStringContainsString('gesturestart', $script);
        $this->assertStringContainsString('gesturechange', $script);
        $this->assertStringContainsString('gestureend', $script);
        $this->assertStringContainsString('touchforcechange', $script);
        
        // Test orientation and viewport handling
        $this->assertStringContainsString('addOrientationChangeHandling', $script);
        $this->assertStringContainsString('addViewportChangeHandling', $script);
        $this->assertStringContainsString('orientationchange', $script);
    }

    public function test_mobile_protection_script_includes_device_detection()
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

        $script = $this->imageProtectionService->getProtectionScript();

        // Test device detection capabilities
        $this->assertStringContainsString('isIOS', $script);
        $this->assertStringContainsString('isAndroid', $script);
        $this->assertStringContainsString('isTablet', $script);
        $this->assertStringContainsString('isPhone', $script);
        $this->assertStringContainsString('maxTouchPoints', $script);
        $this->assertStringContainsString('devicePixelRatio', $script);
        
        // Test specific device model detection
        $this->assertStringContainsString('isIOSPhone', $script);
        $this->assertStringContainsString('isIOSTablet', $script);
        $this->assertStringContainsString('isSamsung', $script);
        $this->assertStringContainsString('isPixel', $script);
    }

    public function test_mobile_protection_styles_include_comprehensive_mobile_support()
    {
        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('image_protection_enabled', false)
            ->andReturn(true);

        $styles = $this->imageProtectionService->getProtectionStyles();

        // Test comprehensive mobile CSS protections
        $this->assertStringContainsString('-webkit-touch-callout: none !important', $styles);
        $this->assertStringContainsString('touch-action: none !important', $styles);
        $this->assertStringContainsString('-webkit-user-select: none !important', $styles);
        $this->assertStringContainsString('-webkit-tap-highlight-color: transparent !important', $styles);
        
        // Test mobile-specific media queries
        $this->assertStringContainsString('@media (max-width: 768px)', $styles);
        $this->assertStringContainsString('@media (max-width: 480px) and (orientation: portrait)', $styles);
        $this->assertStringContainsString('@media (max-width: 768px) and (orientation: landscape)', $styles);
        
        // Test tablet-specific protections
        $this->assertStringContainsString('@media (min-width: 769px) and (max-width: 1024px)', $styles);
        
        // Test high-DPI device support
        $this->assertStringContainsString('@media (-webkit-min-device-pixel-ratio: 2)', $styles);
        $this->assertStringContainsString('(min-resolution: 192dpi)', $styles);
        
        // Test iOS-specific protections
        $this->assertStringContainsString('@supports (-webkit-touch-callout: none)', $styles);
        
        // Test browser-specific protections
        $this->assertStringContainsString('@-moz-document url-prefix()', $styles);
    }

    public function test_mobile_watermark_responsive_scaling()
    {
        // Mock watermark settings
        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('watermark_enabled', false)
            ->andReturn(true);

        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('watermark_text', Mockery::any())
            ->andReturn('Test Watermark');

        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('watermark_opacity', 50)
            ->andReturn(50);

        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('watermark_position', 'bottom-right')
            ->andReturn('bottom-right');

        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('watermark_size', 24)
            ->andReturn(24);

        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('watermark_text_color', '#FFFFFF')
            ->andReturn('#FFFFFF');

        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('watermark_logo_path', '')
            ->andReturn('');

        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('watermark_logo_size', 'medium')
            ->andReturn('medium');

        // Create test images of different sizes
        $testImages = [
            'mobile_small.jpg' => [320, 240],    // Very small mobile
            'mobile_medium.jpg' => [480, 360],   // Small mobile
            'tablet.jpg' => [768, 576],          // Tablet
            'desktop.jpg' => [1200, 800],        // Desktop
        ];

        foreach ($testImages as $filename => $dimensions) {
            [$width, $height] = $dimensions;
            
            // Create a test image
            $image = imagecreatetruecolor($width, $height);
            $white = imagecolorallocate($image, 255, 255, 255);
            imagefill($image, 0, 0, $white);
            
            // Test mobile-responsive settings
            $reflection = new \ReflectionClass($this->watermarkService);
            $method = $reflection->getMethod('applyMobileResponsiveSettings');
            $method->setAccessible(true);
            
            $settings = $this->watermarkService->getWatermarkSettings();
            $responsiveSettings = $method->invoke($this->watermarkService, $image, $settings);
            
            // Verify mobile adjustments were applied
            if ($width <= 480 || $height <= 360) {
                // Small mobile images should have adjusted settings
                $this->assertLessThanOrEqual($settings['size'], $responsiveSettings['size']);
                $this->assertGreaterThanOrEqual($settings['opacity'], $responsiveSettings['opacity']);
            }
            
            // Clean up
            imagedestroy($image);
        }
    }

    public function test_mobile_watermark_position_optimization()
    {
        // Test position optimization for different mobile contexts
        $reflection = new \ReflectionClass($this->watermarkService);
        $method = $reflection->getMethod('optimizePositionForMobile');
        $method->setAccessible(true);

        // Test very small image context
        $verySmallContext = [
            'isVerySmall' => true,
            'isSmallScreen' => true,
            'isPortrait' => true,
            'aspectRatio' => 0.75,
            'category' => 'very_small'
        ];

        $optimizedPosition = $method->invoke($this->watermarkService, 'top-left', $verySmallContext);
        $this->assertEquals('bottom-left', $optimizedPosition);

        $optimizedPosition = $method->invoke($this->watermarkService, 'center', $verySmallContext);
        $this->assertEquals('bottom-center', $optimizedPosition);

        // Test portrait image context
        $portraitContext = [
            'isVerySmall' => false,
            'isSmallScreen' => false,
            'isPortrait' => true,
            'aspectRatio' => 0.6,
            'category' => 'medium'
        ];

        $optimizedPosition = $method->invoke($this->watermarkService, 'top-right', $portraitContext);
        $this->assertEquals('bottom-right', $optimizedPosition);

        // Test landscape image context
        $landscapeContext = [
            'isVerySmall' => false,
            'isSmallScreen' => false,
            'isPortrait' => false,
            'isLandscape' => true,
            'aspectRatio' => 2.0,
            'category' => 'large'
        ];

        $optimizedPosition = $method->invoke($this->watermarkService, 'center', $landscapeContext);
        $this->assertEquals('bottom-right', $optimizedPosition);
    }

    public function test_mobile_text_size_calculation()
    {
        $reflection = new \ReflectionClass($this->watermarkService);
        $method = $reflection->getMethod('calculateMobileTextSize');
        $method->setAccessible(true);

        // Test very small mobile context
        $verySmallContext = [
            'category' => 'very_small',
            'isVerySmall' => true,
            'isPortrait' => true,
            'isLandscape' => false,
            'aspectRatio' => 0.75,
            'isHighDensity' => false
        ];

        $textSize = $method->invoke($this->watermarkService, 24, 320, 240, $verySmallContext);
        $this->assertLessThanOrEqual(18, $textSize);
        $this->assertGreaterThanOrEqual(8, $textSize);

        // Test high-density context
        $highDensityContext = [
            'category' => 'small',
            'isVerySmall' => false,
            'isPortrait' => false,
            'isLandscape' => true,
            'aspectRatio' => 1.33,
            'isHighDensity' => true
        ];

        $normalTextSize = $method->invoke($this->watermarkService, 24, 480, 360, array_merge($highDensityContext, ['isHighDensity' => false]));
        $highDensityTextSize = $method->invoke($this->watermarkService, 24, 480, 360, $highDensityContext);
        
        $this->assertGreaterThan($normalTextSize, $highDensityTextSize);
    }

    public function test_mobile_logo_size_calculation()
    {
        $reflection = new \ReflectionClass($this->watermarkService);
        $method = $reflection->getMethod('calculateMobileLogoSize');
        $method->setAccessible(true);

        // Test very small context
        $verySmallContext = [
            'category' => 'very_small',
            'isVerySmall' => true,
            'isSquare' => false,
            'isPortrait' => true,
            'aspectRatio' => 0.75
        ];

        $logoSize = $method->invoke($this->watermarkService, 'large', 320, 240, $verySmallContext);
        $this->assertEquals('small', $logoSize);

        // Test square image context
        $squareContext = [
            'category' => 'medium',
            'isVerySmall' => false,
            'isSquare' => true,
            'isPortrait' => false,
            'aspectRatio' => 1.0
        ];

        $logoSize = $method->invoke($this->watermarkService, 'medium', 500, 500, $squareContext);
        $this->assertEquals('medium', $logoSize);

        // Test portrait context
        $portraitContext = [
            'category' => 'small',
            'isVerySmall' => false,
            'isSquare' => false,
            'isPortrait' => true,
            'aspectRatio' => 0.6
        ];

        $logoSize = $method->invoke($this->watermarkService, 'medium', 400, 600, $portraitContext);
        $this->assertEquals('small', $logoSize);
    }

    public function test_mobile_opacity_optimization()
    {
        $reflection = new \ReflectionClass($this->watermarkService);
        $method = $reflection->getMethod('optimizeOpacityForMobile');
        $method->setAccessible(true);

        // Test very small screen optimization
        $verySmallContext = [
            'isVerySmall' => true,
            'isSmallScreen' => true,
            'isHighDensity' => false
        ];

        $optimizedOpacity = $method->invoke($this->watermarkService, 50, $verySmallContext);
        $this->assertGreaterThanOrEqual(65, $optimizedOpacity);
        $this->assertLessThanOrEqual(90, $optimizedOpacity);

        // Test high-density optimization
        $highDensityContext = [
            'isVerySmall' => false,
            'isSmallScreen' => false,
            'isHighDensity' => true
        ];

        $normalOpacity = $method->invoke($this->watermarkService, 50, ['isVerySmall' => false, 'isSmallScreen' => false, 'isHighDensity' => false]);
        $highDensityOpacity = $method->invoke($this->watermarkService, 50, $highDensityContext);
        
        $this->assertGreaterThan($normalOpacity, $highDensityOpacity);
    }

    public function test_mobile_color_optimization()
    {
        $reflection = new \ReflectionClass($this->watermarkService);
        $method = $reflection->getMethod('optimizeColorForMobile');
        $method->setAccessible(true);

        // Test very small screen color optimization
        $verySmallContext = [
            'isVerySmall' => true,
            'isSmallScreen' => true
        ];

        // Test dim color - should become white
        $optimizedColor = $method->invoke($this->watermarkService, '#333333', $verySmallContext);
        $this->assertEquals('#FFFFFF', $optimizedColor);

        // Test bright color - should become black
        $optimizedColor = $method->invoke($this->watermarkService, '#EEEEEE', $verySmallContext);
        $this->assertEquals('#000000', $optimizedColor);

        // Test medium color - should remain unchanged
        $optimizedColor = $method->invoke($this->watermarkService, '#888888', $verySmallContext);
        $this->assertEquals('#888888', $optimizedColor);

        // Test normal screen - should keep original color
        $normalContext = [
            'isVerySmall' => false,
            'isSmallScreen' => false
        ];

        $optimizedColor = $method->invoke($this->watermarkService, '#333333', $normalContext);
        $this->assertEquals('#333333', $optimizedColor);
    }

    public function test_device_context_detection()
    {
        $reflection = new \ReflectionClass($this->watermarkService);
        $method = $reflection->getMethod('detectDeviceContext');
        $method->setAccessible(true);

        // Test very small mobile detection
        $context = $method->invoke($this->watermarkService, 320, 240);
        $this->assertTrue($context['isMobile']);
        $this->assertTrue($context['isVerySmall']);
        $this->assertEquals('very_small', $context['category']);

        // Test tablet detection
        $context = $method->invoke($this->watermarkService, 768, 1024);
        $this->assertTrue($context['isTablet']);
        $this->assertTrue($context['isPortrait']);
        $this->assertEquals('large', $context['category']);

        // Test desktop detection
        $context = $method->invoke($this->watermarkService, 1200, 1080);
        $this->assertTrue($context['isDesktop']);
        $this->assertTrue($context['isLandscape']);
        $this->assertEquals('very_large', $context['category']);

        // Test high-density detection (needs > 1M pixels and one dimension <= 800 or 600)
        $context = $method->invoke($this->watermarkService, 1400, 600); // 840K pixels - let me try 2000x600
        $context = $method->invoke($this->watermarkService, 2000, 600); // 1.2M pixels, height <= 600
        $this->assertTrue($context['isHighDensity']);
    }

    public function test_mobile_protection_handles_orientation_changes()
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

        $script = $this->imageProtectionService->getProtectionScript();

        // Test orientation change handling
        $this->assertStringContainsString('orientationchange', $script);
        $this->assertStringContainsString('addOrientationChangeHandling', $script);
        $this->assertStringContainsString('setTimeout', $script);
        
        // Test viewport change handling
        $this->assertStringContainsString('addViewportChangeHandling', $script);
        $this->assertStringContainsString('resize', $script);
        $this->assertStringContainsString('scroll', $script);
    }

    public function test_mobile_protection_prevents_multiple_touch_gestures()
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

        $script = $this->imageProtectionService->getProtectionScript();

        // Test multi-touch prevention
        $this->assertStringContainsString('e.touches.length > 1', $script);
        $this->assertStringContainsString('preventDefault', $script);
        $this->assertStringContainsString('stopPropagation', $script);
        
        // Test gesture prevention
        $this->assertStringContainsString('gesturestart', $script);
        $this->assertStringContainsString('gesturechange', $script);
        $this->assertStringContainsString('gestureend', $script);
        
        // Test force touch prevention
        $this->assertStringContainsString('touchforcechange', $script);
        $this->assertStringContainsString('force > 0.3', $script);
    }

    public function test_mobile_protection_includes_browser_specific_handling()
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

        $styles = $this->imageProtectionService->getProtectionStyles();

        // Test iOS Safari specific protections
        $this->assertStringContainsString('@supports (-webkit-touch-callout: none)', $styles);
        $this->assertStringContainsString('-webkit-touch-callout: none', $styles);
        
        // Test Android Chrome specific protections
        $this->assertStringContainsString('@media screen and (-webkit-min-device-pixel-ratio: 0)', $styles);
        
        // Test Samsung Internet specific protections
        $this->assertStringContainsString('touch-action: none', $styles);
        
        // Test Firefox Mobile specific protections
        $this->assertStringContainsString('@-moz-document url-prefix()', $styles);
        $this->assertStringContainsString('-moz-user-select: none', $styles);
    }
}