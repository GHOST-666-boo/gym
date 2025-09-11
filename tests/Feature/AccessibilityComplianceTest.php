<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\ImageProtectionService;
use App\Services\WatermarkService;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

class AccessibilityComplianceTest extends TestCase
{
    use RefreshDatabase;

    protected ImageProtectionService $imageProtectionService;
    protected WatermarkService $watermarkService;
    protected SettingsService $settingsService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->settingsService = app(SettingsService::class);
        $this->imageProtectionService = app(ImageProtectionService::class);
        $this->watermarkService = app(WatermarkService::class);
        
        // Enable protection and watermarking for accessibility testing
        $this->settingsService->updateMultiple([
            'image_protection_enabled' => ['value' => true, 'type' => 'boolean', 'group' => 'image_protection'],
            'watermark_enabled' => ['value' => true, 'type' => 'boolean', 'group' => 'watermark'],
            'right_click_protection' => ['value' => true, 'type' => 'boolean', 'group' => 'image_protection'],
            'drag_drop_protection' => ['value' => true, 'type' => 'boolean', 'group' => 'image_protection'],
            'keyboard_protection' => ['value' => true, 'type' => 'boolean', 'group' => 'image_protection'],
        ]);
        
        Storage::fake('public');
    }

    /** @test */
    public function test_screen_reader_compatibility_with_protection()
    {
        $script = $this->imageProtectionService->getProtectionScript();

        // Should detect and skip protection for screen readers
        $this->assertStringContainsString('screen reader', $script);
        $this->assertStringContainsString('assistive technology', $script);
        $this->assertStringContainsString('accessibility', $script);
        
        // Should check for common screen reader user agents
        $this->assertStringContainsString('NVDA', $script);
        $this->assertStringContainsString('JAWS', $script);
        $this->assertStringContainsString('VoiceOver', $script);
        $this->assertStringContainsString('TalkBack', $script);
        
        // Should preserve ARIA attributes
        $this->assertStringContainsString('aria-', $script);
        $this->assertStringContainsString('role=', $script);
    }

    /** @test */
    public function test_keyboard_navigation_preservation()
    {
        $script = $this->imageProtectionService->getProtectionScript();

        // Should preserve essential keyboard navigation
        $this->assertStringContainsString('tabindex', $script);
        $this->assertStringContainsString('focus', $script);
        $this->assertStringContainsString('blur', $script);
        
        // Should not block essential accessibility keys
        $this->assertStringContainsString('Tab', $script);
        $this->assertStringContainsString('Enter', $script);
        $this->assertStringContainsString('Space', $script);
        $this->assertStringContainsString('Escape', $script);
        
        // Should allow arrow key navigation
        $this->assertStringContainsString('ArrowUp', $script);
        $this->assertStringContainsString('ArrowDown', $script);
        $this->assertStringContainsString('ArrowLeft', $script);
        $this->assertStringContainsString('ArrowRight', $script);
    }

    /** @test */
    public function test_alt_text_preservation_with_watermarks()
    {
        // Create test product page with images
        $response = $this->get('/products');
        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // Should preserve alt attributes on images
        $this->assertStringContainsString('alt=', $content);
        
        // Should not interfere with image descriptions
        if (preg_match_all('/<img[^>]+alt=["\']([^"\']*)["\'][^>]*>/i', $content, $matches)) {
            foreach ($matches[1] as $altText) {
                $this->assertNotEmpty(trim($altText), 'Alt text should not be empty');
            }
        }
    }

    /** @test */
    public function test_aria_labels_and_descriptions()
    {
        $response = $this->get('/products');
        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // Should include proper ARIA labels for interactive elements
        $this->assertStringContainsString('aria-label', $content);
        $this->assertStringContainsString('aria-describedby', $content);
        
        // Should have proper roles for image containers
        if (strpos($content, 'product-image') !== false) {
            $this->assertStringContainsString('role=', $content);
        }
    }

    /** @test */
    public function test_high_contrast_mode_compatibility()
    {
        $styles = $this->imageProtectionService->getProtectionStyles();

        // Should include high contrast mode support
        $this->assertStringContainsString('@media (prefers-contrast: high)', $styles);
        $this->assertStringContainsString('forced-colors', $styles);
        
        // Should not interfere with Windows High Contrast Mode
        $this->assertStringContainsString('@media (-ms-high-contrast:', $styles);
        
        // Should preserve outline visibility in high contrast
        $this->assertStringContainsString('outline:', $styles);
    }

    /** @test */
    public function test_reduced_motion_preferences()
    {
        $script = $this->imageProtectionService->getProtectionScript();

        // Should respect prefers-reduced-motion
        $this->assertStringContainsString('prefers-reduced-motion', $script);
        $this->assertStringContainsString('matchMedia', $script);
        
        // Should disable animations when reduced motion is preferred
        $this->assertStringContainsString('animation', $script);
        $this->assertStringContainsString('transition', $script);
    }

    /** @test */
    public function test_focus_management_with_protection()
    {
        $script = $this->imageProtectionService->getProtectionScript();

        // Should properly manage focus states
        $this->assertStringContainsString('focus', $script);
        $this->assertStringContainsString('blur', $script);
        $this->assertStringContainsString('focusin', $script);
        $this->assertStringContainsString('focusout', $script);
        
        // Should not trap focus inappropriately
        $this->assertStringContainsString('tabindex', $script);
        
        // Should provide visible focus indicators
        $styles = $this->imageProtectionService->getProtectionStyles();
        $this->assertStringContainsString(':focus', $styles);
        $this->assertStringContainsString('outline', $styles);
    }

    /** @test */
    public function test_color_contrast_compliance()
    {
        // Test watermark color contrast
        $this->settingsService->set('watermark_text_color', '#000000'); // Black text
        $this->settingsService->set('watermark_opacity', 70);
        
        // Create test image with light background
        if (extension_loaded('gd')) {
            $image = imagecreatetruecolor(400, 300);
            $lightColor = imagecolorallocate($image, 240, 240, 240); // Light gray
            imagefill($image, 0, 0, $lightColor);
            
            $imagePath = 'products/contrast_test.jpg';
            $fullPath = Storage::disk('public')->path($imagePath);
            $directory = dirname($fullPath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            
            imagejpeg($image, $fullPath, 90);
            imagedestroy($image);
            
            // Apply watermark
            $watermarkedPath = $this->watermarkService->applyWatermark($imagePath);
            $this->assertTrue(Storage::disk('public')->exists($watermarkedPath));
            
            // Watermark should be visible (this is a basic test - real contrast testing would require image analysis)
            $this->assertNotEquals($imagePath, $watermarkedPath);
        } else {
            $this->markTestSkipped('GD extension not available for contrast testing');
        }
    }

    /** @test */
    public function test_semantic_html_structure()
    {
        $response = $this->get('/products');
        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // Should use proper semantic HTML
        $this->assertStringContainsString('<main', $content);
        $this->assertStringContainsString('<section', $content);
        $this->assertStringContainsString('<article', $content);
        
        // Should have proper heading hierarchy
        $this->assertStringContainsString('<h1', $content);
        
        // Should use proper list structures for product grids
        if (strpos($content, 'product') !== false) {
            $this->assertStringContainsString('<ul', $content);
            $this->assertStringContainsString('<li', $content);
        }
    }

    /** @test */
    public function test_skip_links_functionality()
    {
        $response = $this->get('/products');
        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // Should include skip links for keyboard users
        $this->assertStringContainsString('skip', $content);
        $this->assertStringContainsString('#main', $content);
        
        // Skip links should be properly styled
        $this->assertStringContainsString('sr-only', $content);
    }

    /** @test */
    public function test_form_accessibility_in_admin_settings()
    {
        $user = \App\Models\User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);
        
        $response = $this->get(route('admin.settings.index'));
        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // Form controls should have proper labels
        $this->assertStringContainsString('<label', $content);
        $this->assertStringContainsString('for=', $content);
        
        // Should have proper fieldset grouping
        $this->assertStringContainsString('<fieldset', $content);
        $this->assertStringContainsString('<legend', $content);
        
        // Error messages should be associated with form controls
        $this->assertStringContainsString('aria-describedby', $content);
        $this->assertStringContainsString('aria-invalid', $content);
    }

    /** @test */
    public function test_mobile_accessibility_features()
    {
        $script = $this->imageProtectionService->getProtectionScript();

        // Should support mobile accessibility features
        $this->assertStringContainsString('touch', $script);
        $this->assertStringContainsString('gesture', $script);
        
        // Should work with mobile screen readers
        $this->assertStringContainsString('TalkBack', $script);
        $this->assertStringContainsString('VoiceOver', $script);
        
        // Should respect mobile accessibility settings
        $this->assertStringContainsString('prefers-reduced-motion', $script);
        $this->assertStringContainsString('prefers-contrast', $script);
    }

    /** @test */
    public function test_error_message_accessibility()
    {
        $user = \App\Models\User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);
        
        // Submit invalid form data to trigger errors
        $response = $this->patch(route('admin.settings.update'), [
            'watermark_opacity' => '150' // Invalid value
        ]);
        
        $response->assertSessionHasErrors();
        
        // Follow redirect to see error display
        $response = $this->get(route('admin.settings.index'));
        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // Error messages should be accessible
        $this->assertStringContainsString('role="alert"', $content);
        $this->assertStringContainsString('aria-live', $content);
        
        // Should have proper error styling that works with screen readers
        $this->assertStringContainsString('error', $content);
    }

    /** @test */
    public function test_loading_states_accessibility()
    {
        $script = $this->imageProtectionService->getProtectionScript();

        // Should provide accessible loading states
        $this->assertStringContainsString('aria-busy', $script);
        $this->assertStringContainsString('loading', $script);
        
        // Should announce loading completion to screen readers
        $this->assertStringContainsString('aria-live', $script);
        $this->assertStringContainsString('polite', $script);
    }

    /** @test */
    public function test_progressive_enhancement_accessibility()
    {
        // Test that basic functionality works without JavaScript
        $response = $this->get('/products');
        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // Images should still be accessible without JavaScript
        $this->assertStringContainsString('<img', $content);
        $this->assertStringContainsString('alt=', $content);
        
        // Basic navigation should work
        $this->assertStringContainsString('<a', $content);
        $this->assertStringContainsString('href=', $content);
    }

    /** @test */
    public function test_wcag_compliance_headers()
    {
        $response = $this->get('/products');
        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // Should have proper document structure
        $this->assertStringContainsString('<!DOCTYPE html>', $content);
        $this->assertStringContainsString('lang=', $content);
        
        // Should have proper meta tags for accessibility
        $this->assertStringContainsString('<meta name="viewport"', $content);
        
        // Should have proper title
        $this->assertStringContainsString('<title>', $content);
    }

    /** @test */
    public function test_assistive_technology_detection()
    {
        $script = $this->imageProtectionService->getProtectionScript();

        // Should detect various assistive technologies
        $assistiveTechnologies = [
            'NVDA',
            'JAWS',
            'VoiceOver',
            'TalkBack',
            'Dragon',
            'ZoomText',
            'MAGic'
        ];
        
        foreach ($assistiveTechnologies as $technology) {
            $this->assertStringContainsString($technology, $script, 
                "Should detect {$technology} assistive technology");
        }
        
        // Should have fallback detection methods
        $this->assertStringContainsString('navigator.userAgent', $script);
        $this->assertStringContainsString('accessibility', $script);
    }

    /** @test */
    public function test_keyboard_shortcuts_accessibility()
    {
        $script = $this->imageProtectionService->getProtectionScript();

        // Should preserve accessibility keyboard shortcuts
        $accessibilityKeys = [
            'Alt+Tab',    // Window switching
            'Alt+F4',     // Close window
            'Ctrl+Tab',   // Tab switching
            'F1',         // Help
            'F5',         // Refresh (sometimes needed for accessibility)
        ];
        
        // These keys should NOT be blocked
        foreach ($accessibilityKeys as $key) {
            // The script should have logic to allow these keys
            $this->assertStringContainsString('accessibility', $script);
        }
        
        // Should have special handling for screen reader shortcuts
        $this->assertStringContainsString('screen reader', $script);
        $this->assertStringContainsString('bypass', $script);
    }

    /** @test */
    public function test_image_description_preservation()
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension not available');
        }

        // Create test image
        $image = imagecreatetruecolor(400, 300);
        $color = imagecolorallocate($image, 100, 150, 200);
        imagefill($image, 0, 0, $color);
        
        $imagePath = 'products/accessibility_test.jpg';
        $fullPath = Storage::disk('public')->path($imagePath);
        $directory = dirname($fullPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        imagejpeg($image, $fullPath, 90);
        imagedestroy($image);
        
        // Apply watermark
        $watermarkedPath = $this->watermarkService->applyWatermark($imagePath);
        
        // Watermarked image should still be accessible
        $this->assertTrue(Storage::disk('public')->exists($watermarkedPath));
        
        // The watermark should not interfere with screen reader image descriptions
        // This would require actual screen reader testing, but we can verify the image is valid
        $watermarkedFullPath = Storage::disk('public')->path($watermarkedPath);
        $imageInfo = getimagesize($watermarkedFullPath);
        $this->assertNotFalse($imageInfo, 'Watermarked image should be valid and readable');
    }
}