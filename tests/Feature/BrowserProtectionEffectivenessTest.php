<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\ImageProtectionService;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

class BrowserProtectionEffectivenessTest extends TestCase
{
    use RefreshDatabase;

    protected ImageProtectionService $imageProtectionService;
    protected SettingsService $settingsService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->settingsService = app(SettingsService::class);
        $this->imageProtectionService = app(ImageProtectionService::class);
        
        // Enable all protection features for testing
        $this->settingsService->updateMultiple([
            'image_protection_enabled' => ['value' => true, 'type' => 'boolean', 'group' => 'image_protection'],
            'right_click_protection' => ['value' => true, 'type' => 'boolean', 'group' => 'image_protection'],
            'drag_drop_protection' => ['value' => true, 'type' => 'boolean', 'group' => 'image_protection'],
            'keyboard_protection' => ['value' => true, 'type' => 'boolean', 'group' => 'image_protection'],
        ]);
        
        Storage::fake('public');
    }

    /** @test */
    public function test_protection_script_includes_chrome_specific_protections()
    {
        $script = $this->imageProtectionService->getProtectionScript();

        // Chrome-specific protections
        $this->assertStringContainsString('webkitUserSelect', $script);
        $this->assertStringContainsString('webkitTouchCallout', $script);
        $this->assertStringContainsString('webkitUserDrag', $script);
        
        // Chrome DevTools detection
        $this->assertStringContainsString('devtools', $script);
        $this->assertStringContainsString('console.clear', $script);
        
        // Chrome-specific keyboard shortcuts
        $this->assertStringContainsString('F12', $script);
        $this->assertStringContainsString('ctrlKey', $script);
        $this->assertStringContainsString('shiftKey', $script);
    }

    /** @test */
    public function test_protection_script_includes_firefox_specific_protections()
    {
        $script = $this->imageProtectionService->getProtectionScript();

        // Firefox-specific protections
        $this->assertStringContainsString('MozUserSelect', $script);
        $this->assertStringContainsString('MozUserDrag', $script);
        
        // Firefox context menu handling
        $this->assertStringContainsString('contextmenu', $script);
        $this->assertStringContainsString('preventDefault', $script);
        
        // Firefox-specific keyboard shortcuts
        $this->assertStringContainsString('keyCode === 123', $script); // F12
        $this->assertStringContainsString('keyCode === 85', $script);  // Ctrl+U
    }

    /** @test */
    public function test_protection_script_includes_safari_specific_protections()
    {
        $script = $this->imageProtectionService->getProtectionScript();

        // Safari-specific protections
        $this->assertStringContainsString('webkitTouchCallout', $script);
        $this->assertStringContainsString('webkitUserSelect', $script);
        
        // Safari touch handling
        $this->assertStringContainsString('touchstart', $script);
        $this->assertStringContainsString('touchend', $script);
        $this->assertStringContainsString('touchmove', $script);
        
        // Safari gesture prevention
        $this->assertStringContainsString('gesturestart', $script);
        $this->assertStringContainsString('gesturechange', $script);
        $this->assertStringContainsString('gestureend', $script);
    }

    /** @test */
    public function test_protection_script_includes_edge_specific_protections()
    {
        $script = $this->imageProtectionService->getProtectionScript();

        // Edge-specific protections (similar to Chrome but with Edge detection)
        $this->assertStringContainsString('msUserSelect', $script);
        $this->assertStringContainsString('msTouchAction', $script);
        
        // Edge browser detection
        $this->assertStringContainsString('navigator.userAgent', $script);
        $this->assertStringContainsString('Edge', $script);
    }

    /** @test */
    public function test_protection_styles_include_cross_browser_compatibility()
    {
        $styles = $this->imageProtectionService->getProtectionStyles();

        // Cross-browser user-select prevention
        $this->assertStringContainsString('-webkit-user-select: none', $styles);
        $this->assertStringContainsString('-moz-user-select: none', $styles);
        $this->assertStringContainsString('-ms-user-select: none', $styles);
        $this->assertStringContainsString('user-select: none', $styles);
        
        // Cross-browser drag prevention
        $this->assertStringContainsString('-webkit-user-drag: none', $styles);
        $this->assertStringContainsString('-moz-user-drag: none', $styles);
        $this->assertStringContainsString('user-drag: none', $styles);
        
        // Cross-browser touch callout prevention
        $this->assertStringContainsString('-webkit-touch-callout: none', $styles);
        $this->assertStringContainsString('-ms-touch-action: none', $styles);
        $this->assertStringContainsString('touch-action: none', $styles);
    }

    /** @test */
    public function test_mobile_browser_specific_protections()
    {
        $script = $this->imageProtectionService->getProtectionScript();

        // iOS Safari specific
        $this->assertStringContainsString('iOS', $script);
        $this->assertStringContainsString('iPhone', $script);
        $this->assertStringContainsString('iPad', $script);
        
        // Android Chrome specific
        $this->assertStringContainsString('Android', $script);
        $this->assertStringContainsString('Chrome', $script);
        
        // Samsung Internet specific
        $this->assertStringContainsString('Samsung', $script);
        
        // Mobile-specific touch events
        $this->assertStringContainsString('touchforcechange', $script);
        $this->assertStringContainsString('longpress', $script);
        $this->assertStringContainsString('doubletap', $script);
    }

    /** @test */
    public function test_protection_effectiveness_against_common_bypass_methods()
    {
        $script = $this->imageProtectionService->getProtectionScript();

        // Protection against inspect element
        $this->assertStringContainsString('devtools', $script);
        $this->assertStringContainsString('debugger', $script);
        
        // Protection against save shortcuts
        $this->assertStringContainsString('Ctrl+S', $script);
        $this->assertStringContainsString('Cmd+S', $script);
        
        // Protection against print screen
        $this->assertStringContainsString('PrintScreen', $script);
        $this->assertStringContainsString('keyCode === 44', $script);
        
        // Protection against drag and drop
        $this->assertStringContainsString('dragstart', $script);
        $this->assertStringContainsString('dragend', $script);
        $this->assertStringContainsString('drop', $script);
    }

    /** @test */
    public function test_browser_compatibility_detection()
    {
        $script = $this->imageProtectionService->getProtectionScript();

        // Browser detection logic
        $this->assertStringContainsString('detectBrowser', $script);
        $this->assertStringContainsString('isChrome', $script);
        $this->assertStringContainsString('isFirefox', $script);
        $this->assertStringContainsString('isSafari', $script);
        $this->assertStringContainsString('isEdge', $script);
        $this->assertStringContainsString('isIE', $script);
        
        // Feature detection
        $this->assertStringContainsString('supportsTouch', $script);
        $this->assertStringContainsString('supportsGestures', $script);
        $this->assertStringContainsString('supportsPointer', $script);
    }

    /** @test */
    public function test_fallback_mechanisms_for_unsupported_browsers()
    {
        $script = $this->imageProtectionService->getProtectionScript();

        // Fallback for older browsers
        $this->assertStringContainsString('fallback', $script);
        $this->assertStringContainsString('legacy', $script);
        
        // CSS-only fallbacks
        $styles = $this->imageProtectionService->getProtectionStyles();
        $this->assertStringContainsString('pointer-events: none', $styles);
        $this->assertStringContainsString('outline: none', $styles);
    }

    /** @test */
    public function test_protection_script_handles_browser_extensions()
    {
        $script = $this->imageProtectionService->getProtectionScript();

        // Protection against common image downloading extensions
        $this->assertStringContainsString('extension', $script);
        $this->assertStringContainsString('addon', $script);
        
        // Detection of automated tools
        $this->assertStringContainsString('webdriver', $script);
        $this->assertStringContainsString('selenium', $script);
        $this->assertStringContainsString('phantom', $script);
    }

    /** @test */
    public function test_cross_browser_event_handling()
    {
        $script = $this->imageProtectionService->getProtectionScript();

        // Cross-browser event attachment
        $this->assertStringContainsString('addEventListener', $script);
        $this->assertStringContainsString('attachEvent', $script); // IE fallback
        
        // Cross-browser event prevention
        $this->assertStringContainsString('preventDefault', $script);
        $this->assertStringContainsString('stopPropagation', $script);
        $this->assertStringContainsString('returnValue = false', $script); // IE fallback
    }

    /** @test */
    public function test_browser_specific_css_hacks()
    {
        $styles = $this->imageProtectionService->getProtectionStyles();

        // Chrome/Safari specific
        $this->assertStringContainsString('@media screen and (-webkit-min-device-pixel-ratio:0)', $styles);
        
        // Firefox specific
        $this->assertStringContainsString('@-moz-document url-prefix()', $styles);
        
        // IE specific
        $this->assertStringContainsString('@media screen and (min-width:0\\0)', $styles);
        
        // Edge specific
        $this->assertStringContainsString('@supports (-ms-ime-align:auto)', $styles);
    }

    /** @test */
    public function test_protection_performance_across_browsers()
    {
        $script = $this->imageProtectionService->getProtectionScript();

        // Performance optimization techniques
        $this->assertStringContainsString('requestAnimationFrame', $script);
        $this->assertStringContainsString('debounce', $script);
        $this->assertStringContainsString('throttle', $script);
        
        // Efficient event delegation
        $this->assertStringContainsString('event.target', $script);
        $this->assertStringContainsString('closest', $script);
    }

    /** @test */
    public function test_browser_console_protection()
    {
        $script = $this->imageProtectionService->getProtectionScript();

        // Console protection methods
        $this->assertStringContainsString('console.log', $script);
        $this->assertStringContainsString('console.clear', $script);
        $this->assertStringContainsString('console.warn', $script);
        
        // DevTools detection
        $this->assertStringContainsString('devtools-detector', $script);
        $this->assertStringContainsString('window.outerHeight', $script);
        $this->assertStringContainsString('window.innerHeight', $script);
    }

    /** @test */
    public function test_protection_against_headless_browsers()
    {
        $script = $this->imageProtectionService->getProtectionScript();

        // Headless browser detection
        $this->assertStringContainsString('headless', $script);
        $this->assertStringContainsString('navigator.webdriver', $script);
        $this->assertStringContainsString('window.chrome', $script);
        $this->assertStringContainsString('window.navigator.plugins.length', $script);
        
        // Puppeteer detection
        $this->assertStringContainsString('puppeteer', $script);
        $this->assertStringContainsString('__nightmare', $script);
        $this->assertStringContainsString('_phantom', $script);
    }

    /** @test */
    public function test_browser_specific_image_handling()
    {
        $script = $this->imageProtectionService->getProtectionScript();

        // Image loading protection
        $this->assertStringContainsString('img.onload', $script);
        $this->assertStringContainsString('img.onerror', $script);
        
        // Canvas protection (prevents screenshot via canvas)
        $this->assertStringContainsString('canvas', $script);
        $this->assertStringContainsString('toDataURL', $script);
        $this->assertStringContainsString('getImageData', $script);
        
        // WebGL protection
        $this->assertStringContainsString('webgl', $script);
        $this->assertStringContainsString('readPixels', $script);
    }

    /** @test */
    public function test_responsive_protection_across_viewports()
    {
        $styles = $this->imageProtectionService->getProtectionStyles();

        // Desktop viewport protections
        $this->assertStringContainsString('@media (min-width: 1024px)', $styles);
        
        // Tablet viewport protections
        $this->assertStringContainsString('@media (min-width: 768px) and (max-width: 1023px)', $styles);
        
        // Mobile viewport protections
        $this->assertStringContainsString('@media (max-width: 767px)', $styles);
        
        // High-DPI display protections
        $this->assertStringContainsString('@media (-webkit-min-device-pixel-ratio: 2)', $styles);
        $this->assertStringContainsString('@media (min-resolution: 192dpi)', $styles);
    }

    /** @test */
    public function test_accessibility_preservation_across_browsers()
    {
        $script = $this->imageProtectionService->getProtectionScript();

        // Screen reader compatibility
        $this->assertStringContainsString('aria-', $script);
        $this->assertStringContainsString('role=', $script);
        
        // Keyboard navigation preservation
        $this->assertStringContainsString('tabindex', $script);
        $this->assertStringContainsString('focus', $script);
        
        // Skip protection for assistive technologies
        $this->assertStringContainsString('screen reader', $script);
        $this->assertStringContainsString('assistive', $script);
    }
}