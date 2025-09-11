<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\WatermarkService;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Mockery;

class MobileWatermarkServiceTest extends TestCase
{
    use RefreshDatabase;

    protected WatermarkService $watermarkService;
    protected $settingsServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock SettingsService
        $this->settingsServiceMock = Mockery::mock(SettingsService::class);
        $this->watermarkService = new WatermarkService($this->settingsServiceMock);
        
        // Set up storage for testing
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_detect_device_context_identifies_mobile_correctly()
    {
        $reflection = new \ReflectionClass($this->watermarkService);
        $method = $reflection->getMethod('detectDeviceContext');
        $method->setAccessible(true);

        // Test very small mobile (iPhone SE)
        $context = $method->invoke($this->watermarkService, 320, 568);
        $this->assertTrue($context['isMobile']);
        $this->assertTrue($context['isVerySmall']); // 320 <= 320, so very small
        $this->assertTrue($context['isPortrait']);
        $this->assertEquals('medium', $context['category']); // max(320, 568) = 568 <= 768, so 'medium'
        $this->assertFalse($context['isTablet']);
        $this->assertFalse($context['isDesktop']);

        // Test small mobile (iPhone 12 Mini)
        $context = $method->invoke($this->watermarkService, 375, 812);
        $this->assertTrue($context['isMobile']);
        $this->assertFalse($context['isVerySmall']);
        $this->assertTrue($context['isPortrait']);
        $this->assertEquals('large', $context['category']); // max(375, 812) = 812 > 768, so 'large'

        // Test medium mobile (iPhone 12 Pro Max)
        $context = $method->invoke($this->watermarkService, 428, 926);
        $this->assertTrue($context['isMobile']);
        $this->assertFalse($context['isVerySmall']);
        $this->assertTrue($context['isPortrait']);
        $this->assertEquals('large', $context['category']); // max(428, 926) = 926 > 768 but <= 1024, so 'large'
    }

    public function test_detect_device_context_identifies_tablet_correctly()
    {
        $reflection = new \ReflectionClass($this->watermarkService);
        $method = $reflection->getMethod('detectDeviceContext');
        $method->setAccessible(true);

        // Test iPad (portrait)
        $context = $method->invoke($this->watermarkService, 768, 1024);
        $this->assertFalse($context['isMobile']);
        $this->assertTrue($context['isTablet']);
        $this->assertTrue($context['isPortrait']);
        $this->assertEquals('large', $context['category']);
        $this->assertFalse($context['isDesktop']);

        // Test iPad (landscape)
        $context = $method->invoke($this->watermarkService, 1024, 768);
        $this->assertFalse($context['isMobile']);
        $this->assertTrue($context['isTablet']);
        $this->assertTrue($context['isLandscape']);
        $this->assertEquals('large', $context['category']);

        // Test iPad Pro
        $context = $method->invoke($this->watermarkService, 834, 1194);
        $this->assertFalse($context['isMobile']);
        $this->assertTrue($context['isTablet']);
        $this->assertTrue($context['isPortrait']);
        $this->assertEquals('very_large', $context['category']); // max(834, 1194) = 1194 > 1024, so 'very_large'
    }

    public function test_detect_device_context_identifies_desktop_correctly()
    {
        $reflection = new \ReflectionClass($this->watermarkService);
        $method = $reflection->getMethod('detectDeviceContext');
        $method->setAccessible(true);

        // Test desktop
        $context = $method->invoke($this->watermarkService, 1920, 1080);
        $this->assertFalse($context['isMobile']);
        $this->assertFalse($context['isTablet']);
        $this->assertTrue($context['isDesktop']);
        $this->assertTrue($context['isLandscape']);
        $this->assertEquals('very_large', $context['category']);

        // Test ultrawide desktop
        $context = $method->invoke($this->watermarkService, 2560, 1080);
        $this->assertTrue($context['isDesktop']);
        $this->assertTrue($context['isLandscape']);
        $this->assertGreaterThan(2.0, $context['aspectRatio']);
    }

    public function test_detect_device_context_identifies_high_density_correctly()
    {
        $reflection = new \ReflectionClass($this->watermarkService);
        $method = $reflection->getMethod('detectDeviceContext');
        $method->setAccessible(true);

        // Test high-density mobile (Retina)
        $context = $method->invoke($this->watermarkService, 750, 1334);
        $this->assertTrue($context['isHighDensity']);
        $this->assertTrue($context['isMobile']);

        // Test normal density
        $context = $method->invoke($this->watermarkService, 320, 568);
        $this->assertFalse($context['isHighDensity']);
    }

    public function test_categorize_image_size_returns_correct_categories()
    {
        $reflection = new \ReflectionClass($this->watermarkService);
        $method = $reflection->getMethod('categorizeImageSize');
        $method->setAccessible(true);

        $this->assertEquals('very_small', $method->invoke($this->watermarkService, 320, 240));
        $this->assertEquals('very_small', $method->invoke($this->watermarkService, 300, 200));
        
        $this->assertEquals('small', $method->invoke($this->watermarkService, 480, 360));
        $this->assertEquals('small', $method->invoke($this->watermarkService, 400, 300));
        
        $this->assertEquals('medium', $method->invoke($this->watermarkService, 768, 576));
        $this->assertEquals('medium', $method->invoke($this->watermarkService, 600, 400));
        
        $this->assertEquals('large', $method->invoke($this->watermarkService, 1024, 768));
        $this->assertEquals('large', $method->invoke($this->watermarkService, 800, 600));
        
        $this->assertEquals('very_large', $method->invoke($this->watermarkService, 1920, 1080));
        $this->assertEquals('very_large', $method->invoke($this->watermarkService, 1200, 800));
    }

    public function test_apply_mobile_watermark_adjustments_modifies_settings_correctly()
    {
        $reflection = new \ReflectionClass($this->watermarkService);
        $method = $reflection->getMethod('applyMobileWatermarkAdjustments');
        $method->setAccessible(true);

        $originalSettings = [
            'text' => 'Test Watermark',
            'size' => 24,
            'opacity' => 50,
            'position' => 'center',
            'color' => '#888888',
            'logo_size' => 'large'
        ];

        $mobileContext = [
            'isMobile' => true,
            'isVerySmall' => true,
            'isSmallScreen' => true,
            'category' => 'very_small',
            'isPortrait' => true,
            'isLandscape' => false,
            'isSquare' => false,
            'aspectRatio' => 0.75,
            'isHighDensity' => false
        ];

        $adjustedSettings = $method->invoke($this->watermarkService, $originalSettings, $mobileContext, 320, 240);

        // Text size should be adjusted for mobile
        $this->assertNotEquals($originalSettings['size'], $adjustedSettings['size']);
        $this->assertLessThanOrEqual(18, $adjustedSettings['size']); // Very small screen limit
        
        // Logo size should be reduced for mobile
        $this->assertEquals('small', $adjustedSettings['logo_size']);
        
        // Position should be optimized for mobile
        $this->assertEquals('bottom-center', $adjustedSettings['position']);
        
        // Opacity should be increased for better visibility
        $this->assertGreaterThan($originalSettings['opacity'], $adjustedSettings['opacity']);
        
        // Color should remain unchanged for medium brightness colors
        $this->assertEquals('#888888', $adjustedSettings['color']);
    }

    public function test_apply_tablet_watermark_adjustments_modifies_settings_appropriately()
    {
        $reflection = new \ReflectionClass($this->watermarkService);
        $method = $reflection->getMethod('applyTabletWatermarkAdjustments');
        $method->setAccessible(true);

        $originalSettings = [
            'size' => 24,
            'opacity' => 50,
            'logo_size' => 'large'
        ];

        $tabletContext = [
            'isTablet' => true,
            'category' => 'large',
            'isPortrait' => true
        ];

        $adjustedSettings = $method->invoke($this->watermarkService, $originalSettings, $tabletContext, 768, 1024);

        // Text size should be slightly reduced for tablet
        $this->assertLessThan($originalSettings['size'], $adjustedSettings['size']);
        $this->assertEquals(21, $adjustedSettings['size']); // 24 * 0.9 = 21.6, rounded to 21
        
        // Logo size should be reduced from large to medium
        $this->assertEquals('medium', $adjustedSettings['logo_size']);
        
        // Opacity should be slightly increased
        $this->assertGreaterThan($originalSettings['opacity'], $adjustedSettings['opacity']);
        $this->assertEquals(55, $adjustedSettings['opacity']); // 50 + 5
    }

    public function test_apply_responsive_scaling_scales_correctly()
    {
        $reflection = new \ReflectionClass($this->watermarkService);
        $method = $reflection->getMethod('applyResponsiveScaling');
        $method->setAccessible(true);

        $originalSettings = ['size' => 24, 'opacity' => 50];

        // Test very small image scaling
        $verySmallContext = ['isVerySmall' => true, 'category' => 'very_small'];
        $scaledSettings = $method->invoke($this->watermarkService, $originalSettings, 200, 150, $verySmallContext);
        
        $this->assertLessThanOrEqual(16, $scaledSettings['size']); // Very small limit
        $this->assertGreaterThanOrEqual(10, $scaledSettings['size']); // Very small minimum
        $this->assertGreaterThanOrEqual(60, $scaledSettings['opacity']); // Minimum opacity for very small

        // Test large image scaling
        $largeContext = ['isVerySmall' => false, 'category' => 'very_large'];
        $scaledSettings = $method->invoke($this->watermarkService, $originalSettings, 2000, 1500, $largeContext);
        
        $this->assertLessThanOrEqual(36, $scaledSettings['size']); // Very large limit
    }

    public function test_calculate_mobile_text_size_handles_different_contexts()
    {
        $reflection = new \ReflectionClass($this->watermarkService);
        $method = $reflection->getMethod('calculateMobileTextSize');
        $method->setAccessible(true);

        $originalSize = 24;

        // Test very small context
        $verySmallContext = [
            'category' => 'very_small',
            'isVerySmall' => true,
            'isPortrait' => true,
            'isLandscape' => false,
            'aspectRatio' => 0.75,
            'isHighDensity' => false
        ];
        
        $textSize = $method->invoke($this->watermarkService, $originalSize, 320, 240, $verySmallContext);
        $this->assertLessThanOrEqual(18, $textSize);
        $this->assertGreaterThanOrEqual(8, $textSize);

        // Test portrait context with extreme aspect ratio
        $portraitContext = [
            'category' => 'small',
            'isVerySmall' => false,
            'isPortrait' => true,
            'isLandscape' => false,
            'aspectRatio' => 0.5, // Very tall
            'isHighDensity' => false
        ];
        
        $portraitSize = $method->invoke($this->watermarkService, $originalSize, 400, 800, $portraitContext);
        
        // Test landscape context with extreme aspect ratio
        $landscapeContext = [
            'category' => 'small',
            'isVerySmall' => false,
            'isPortrait' => false,
            'isLandscape' => true,
            'aspectRatio' => 2.5, // Very wide
            'isHighDensity' => false
        ];
        
        $landscapeSize = $method->invoke($this->watermarkService, $originalSize, 1000, 400, $landscapeContext);
        $this->assertGreaterThan($portraitSize, $landscapeSize); // Landscape should be larger

        // Test high-density context
        $highDensityContext = [
            'category' => 'medium',
            'isVerySmall' => false,
            'isPortrait' => false,
            'isLandscape' => true,
            'aspectRatio' => 1.33,
            'isHighDensity' => true
        ];
        
        $normalContext = array_merge($highDensityContext, ['isHighDensity' => false, 'isLandscape' => true]);
        
        $normalSize = $method->invoke($this->watermarkService, $originalSize, 600, 450, $normalContext);
        $highDensitySize = $method->invoke($this->watermarkService, $originalSize, 600, 450, $highDensityContext);
        
        $this->assertGreaterThan($normalSize, $highDensitySize); // High-density should be larger
    }

    public function test_calculate_mobile_logo_size_handles_different_contexts()
    {
        $reflection = new \ReflectionClass($this->watermarkService);
        $method = $reflection->getMethod('calculateMobileLogoSize');
        $method->setAccessible(true);

        // Test very small context - should always return small
        $verySmallContext = [
            'category' => 'very_small',
            'isVerySmall' => true,
            'isSquare' => false,
            'isPortrait' => true,
            'aspectRatio' => 0.75
        ];
        
        $this->assertEquals('small', $method->invoke($this->watermarkService, 'large', 320, 240, $verySmallContext));
        $this->assertEquals('small', $method->invoke($this->watermarkService, 'medium', 320, 240, $verySmallContext));
        $this->assertEquals('small', $method->invoke($this->watermarkService, 'small', 320, 240, $verySmallContext));

        // Test square context - should allow larger sizes
        $squareContext = [
            'category' => 'medium',
            'isVerySmall' => false,
            'isSquare' => true,
            'isPortrait' => false,
            'aspectRatio' => 1.0
        ];
        
        $this->assertEquals('medium', $method->invoke($this->watermarkService, 'large', 500, 500, $squareContext));
        $this->assertEquals('medium', $method->invoke($this->watermarkService, 'medium', 500, 500, $squareContext));
        $this->assertEquals('small', $method->invoke($this->watermarkService, 'small', 500, 500, $squareContext));

        // Test portrait context with extreme aspect ratio - should return small
        $portraitContext = [
            'category' => 'small',
            'isVerySmall' => false,
            'isSquare' => false,
            'isPortrait' => true,
            'aspectRatio' => 0.6
        ];
        
        $this->assertEquals('small', $method->invoke($this->watermarkService, 'large', 400, 600, $portraitContext));
    }

    public function test_optimize_position_for_mobile_handles_different_contexts()
    {
        $reflection = new \ReflectionClass($this->watermarkService);
        $method = $reflection->getMethod('optimizePositionForMobile');
        $method->setAccessible(true);

        // Test very small context - should move to bottom
        $verySmallContext = ['isVerySmall' => true];
        
        $this->assertEquals('bottom-left', $method->invoke($this->watermarkService, 'top-left', $verySmallContext));
        $this->assertEquals('bottom-center', $method->invoke($this->watermarkService, 'center', $verySmallContext));
        $this->assertEquals('bottom-right', $method->invoke($this->watermarkService, 'top-right', $verySmallContext));

        // Test portrait context - should move to bottom
        $portraitContext = [
            'isVerySmall' => false,
            'isPortrait' => true,
            'aspectRatio' => 0.7
        ];
        
        $this->assertEquals('bottom-left', $method->invoke($this->watermarkService, 'top-left', $portraitContext));
        $this->assertEquals('bottom-center', $method->invoke($this->watermarkService, 'center', $portraitContext));

        // Test landscape context - should prefer corners
        $landscapeContext = [
            'isVerySmall' => false,
            'isPortrait' => false,
            'isLandscape' => true,
            'aspectRatio' => 2.0
        ];
        
        $this->assertEquals('bottom-right', $method->invoke($this->watermarkService, 'center', $landscapeContext));
        $this->assertEquals('bottom-left', $method->invoke($this->watermarkService, 'center-left', $landscapeContext));

        // Test normal context - should keep original position
        $normalContext = [
            'isVerySmall' => false,
            'isPortrait' => false,
            'isLandscape' => true,
            'aspectRatio' => 1.33
        ];
        
        $this->assertEquals('top-left', $method->invoke($this->watermarkService, 'top-left', $normalContext));
        $this->assertEquals('center', $method->invoke($this->watermarkService, 'center', $normalContext));
    }

    public function test_optimize_opacity_for_mobile_increases_appropriately()
    {
        $reflection = new \ReflectionClass($this->watermarkService);
        $method = $reflection->getMethod('optimizeOpacityForMobile');
        $method->setAccessible(true);

        $originalOpacity = 50;

        // Test very small context - should increase significantly
        $verySmallContext = [
            'isVerySmall' => true,
            'isSmallScreen' => true,
            'isHighDensity' => false
        ];
        
        $optimizedOpacity = $method->invoke($this->watermarkService, $originalOpacity, $verySmallContext);
        $this->assertEquals(65, $optimizedOpacity); // 50 + 15

        // Test small screen context
        $smallContext = [
            'isVerySmall' => false,
            'isSmallScreen' => true,
            'isHighDensity' => false
        ];
        
        $optimizedOpacity = $method->invoke($this->watermarkService, $originalOpacity, $smallContext);
        $this->assertEquals(60, $optimizedOpacity); // 50 + 10

        // Test high-density context
        $highDensityContext = [
            'isVerySmall' => false,
            'isSmallScreen' => false,
            'isHighDensity' => true
        ];
        
        $optimizedOpacity = $method->invoke($this->watermarkService, $originalOpacity, $highDensityContext);
        $this->assertEquals(60, $optimizedOpacity); // 50 + 5 + 5

        // Test maximum opacity limit
        $highOpacity = 85;
        $optimizedOpacity = $method->invoke($this->watermarkService, $highOpacity, $verySmallContext);
        $this->assertEquals(90, $optimizedOpacity); // Should not exceed 90

        // Test minimum opacity
        $lowOpacity = 20;
        $optimizedOpacity = $method->invoke($this->watermarkService, $lowOpacity, $verySmallContext);
        $this->assertGreaterThanOrEqual(30, $optimizedOpacity); // Should not go below 30
    }

    public function test_optimize_color_for_mobile_adjusts_contrast()
    {
        $reflection = new \ReflectionClass($this->watermarkService);
        $method = $reflection->getMethod('optimizeColorForMobile');
        $method->setAccessible(true);

        $mobileContext = [
            'isVerySmall' => true,
            'isSmallScreen' => true
        ];

        // Test dark color - should become white
        $optimizedColor = $method->invoke($this->watermarkService, '#333333', $mobileContext);
        $this->assertEquals('#FFFFFF', $optimizedColor);

        $optimizedColor = $method->invoke($this->watermarkService, '#000000', $mobileContext);
        $this->assertEquals('#FFFFFF', $optimizedColor);

        // Test bright color - should become black
        $optimizedColor = $method->invoke($this->watermarkService, '#EEEEEE', $mobileContext);
        $this->assertEquals('#000000', $optimizedColor);

        $optimizedColor = $method->invoke($this->watermarkService, '#FFFFFF', $mobileContext);
        $this->assertEquals('#000000', $optimizedColor);

        // Test medium color - should remain unchanged
        $optimizedColor = $method->invoke($this->watermarkService, '#888888', $mobileContext);
        $this->assertEquals('#888888', $optimizedColor);

        $optimizedColor = $method->invoke($this->watermarkService, '#666666', $mobileContext);
        $this->assertEquals('#666666', $optimizedColor);

        // Test non-mobile context - should keep original
        $desktopContext = [
            'isVerySmall' => false,
            'isSmallScreen' => false
        ];

        $optimizedColor = $method->invoke($this->watermarkService, '#333333', $desktopContext);
        $this->assertEquals('#333333', $optimizedColor);

        $optimizedColor = $method->invoke($this->watermarkService, '#EEEEEE', $desktopContext);
        $this->assertEquals('#EEEEEE', $optimizedColor);
    }

    public function test_is_mobile_sized_image_detects_correctly()
    {
        $reflection = new \ReflectionClass($this->watermarkService);
        $method = $reflection->getMethod('isMobileSizedImage');
        $method->setAccessible(true);

        // Test mobile-sized images
        $this->assertTrue($method->invoke($this->watermarkService, 320, 568)); // iPhone SE
        $this->assertTrue($method->invoke($this->watermarkService, 375, 812)); // iPhone 12 Mini
        $this->assertTrue($method->invoke($this->watermarkService, 480, 800)); // Small mobile
        $this->assertTrue($method->invoke($this->watermarkService, 600, 400)); // Small width
        $this->assertTrue($method->invoke($this->watermarkService, 400, 600)); // Portrait mobile

        // Test non-mobile-sized images
        $this->assertFalse($method->invoke($this->watermarkService, 1024, 768)); // Tablet
        $this->assertFalse($method->invoke($this->watermarkService, 1200, 800)); // Desktop
        $this->assertFalse($method->invoke($this->watermarkService, 900, 600)); // Large landscape
    }
}