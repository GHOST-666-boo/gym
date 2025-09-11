<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ImageProtectionService
{
    protected SettingsService $settingsService;

    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    /**
     * Generate client-side protection JavaScript code
     */
    public function getProtectionScript(): string
    {
        if (!$this->isProtectionEnabled()) {
            return '';
        }

        $settings = $this->getProtectionSettings();
        $script = '';

        // Base protection wrapper
        $script .= "(function() {\n";
        $script .= "    'use strict';\n\n";

        // Add accessibility detection
        $script .= $this->getAccessibilityDetectionScript();

        // Add browser detection
        $script .= $this->getBrowserDetectionScript();

        // Right-click context menu protection
        if ($settings['right_click_protection']) {
            $script .= $this->getRightClickProtectionScript();
        }

        // Drag and drop protection
        if ($settings['drag_drop_protection']) {
            $script .= $this->getDragDropProtectionScript();
        }

        // Keyboard shortcut protection
        if ($settings['keyboard_protection']) {
            $script .= $this->getKeyboardProtectionScript();
        }

        // Mobile touch protection
        $script .= $this->getMobileTouchProtectionScript();

        // Initialize protection when DOM is ready
        $script .= $this->getInitializationScript();

        $script .= "})();\n";

        return $script;
    }

    /**
     * Check if image protection is enabled
     */
    public function isProtectionEnabled(): bool
    {
        return (bool) $this->settingsService->get('image_protection_enabled', false);
    }

    /**
     * Generate protected image URL with obfuscation
     */
    public function generateProtectedImageUrl(string $originalUrl): string
    {
        if (!$this->isProtectionEnabled()) {
            return $originalUrl;
        }

        // Extract the image path from the URL
        $imagePath = $this->extractImagePathFromUrl($originalUrl);
        if (!$imagePath) {
            return $originalUrl;
        }

        // Generate obfuscated token
        $token = $this->generateImageToken($imagePath);
        
        // Return protected URL
        return route('protected.image', ['token' => $token]);
    }

    /**
     * Extract image path from URL for token generation
     */
    protected function extractImagePathFromUrl(string $url): ?string
    {
        // Remove the base URL to get the relative path
        $baseUrl = config('app.url');
        $storageUrl = asset('storage/');
        
        if (str_starts_with($url, $storageUrl)) {
            // Extract path after /storage/
            return str_replace($storageUrl . '/', '', $url);
        }
        
        if (str_starts_with($url, $baseUrl)) {
            $relativePath = str_replace($baseUrl, '', $url);
            if (str_starts_with($relativePath, '/storage/')) {
                return str_replace('/storage/', '', $relativePath);
            }
        }
        
        return null;
    }

    /**
     * Generate an obfuscated token for an image path
     */
    protected function generateImageToken(string $imagePath): string
    {
        $data = [
            'path' => $imagePath,
            'timestamp' => time(),
            'expires' => time() + config('image_protection.token.expires_in', 24 * 60 * 60),
        ];
        
        $payload = base64_encode(json_encode($data));
        $signature = hash_hmac(
            config('image_protection.token.algorithm', 'sha256'),
            $payload,
            config('app.key')
        );
        
        return base64_encode($payload . '.' . $signature);
    }

    /**
     * Get protection settings from site settings
     */
    public function getProtectionSettings(): array
    {
        return [
            'right_click_protection' => (bool) $this->settingsService->get('right_click_protection', true),
            'drag_drop_protection' => (bool) $this->settingsService->get('drag_drop_protection', true),
            'keyboard_protection' => (bool) $this->settingsService->get('keyboard_protection', true),
        ];
    }

    /**
     * Generate accessibility detection script
     */
    protected function getAccessibilityDetectionScript(): string
    {
        return "
    // Accessibility and assistive technology detection
    function detectAssistiveTechnology() {
        const userAgent = navigator.userAgent;
        const isScreenReader = userAgent.includes('NVDA') || 
                              userAgent.includes('JAWS') || 
                              userAgent.includes('VoiceOver') || 
                              userAgent.includes('TalkBack') || 
                              userAgent.includes('Dragon') ||
                              userAgent.includes('ZoomText') ||
                              userAgent.includes('MAGic') ||
                              userAgent.toLowerCase().includes('nvda') || 
                              userAgent.toLowerCase().includes('jaws') || 
                              userAgent.toLowerCase().includes('voiceover') || 
                              userAgent.toLowerCase().includes('talkback') || 
                              userAgent.toLowerCase().includes('dragon') ||
                              userAgent.toLowerCase().includes('zoomtext') ||
                              userAgent.toLowerCase().includes('magic');
        
        // Check for accessibility preferences
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const prefersHighContrast = window.matchMedia('(prefers-contrast: high)').matches;
        
        return {
            isScreenReader: isScreenReader,
            prefersReducedMotion: prefersReducedMotion,
            prefersHighContrast: prefersHighContrast,
            hasAccessibilityFeatures: isScreenReader || prefersReducedMotion || prefersHighContrast
        };
    }

    const accessibility = detectAssistiveTechnology();
";
    }

    /**
     * Generate browser detection script
     */
    protected function getBrowserDetectionScript(): string
    {
        return "
    // Browser detection for cross-browser compatibility
    function detectBrowser() {
        const userAgent = navigator.userAgent;
        const isChrome = userAgent.includes('Chrome') && !userAgent.includes('Edge');
        const isFirefox = userAgent.includes('Firefox');
        const isSafari = userAgent.includes('Safari') && !userAgent.includes('Chrome');
        const isEdge = userAgent.includes('Edge') || userAgent.includes('Edg/');
        const isIE = userAgent.includes('MSIE') || userAgent.includes('Trident/');
        const isIOS = /iPad|iPhone|iPod/.test(userAgent);
        const isAndroid = userAgent.includes('Android');
        const isMobile = isIOS || isAndroid || /Mobile/.test(userAgent);
        
        // Additional mobile browser detection
        const isSamsung = userAgent.includes('Samsung');
        
        // Headless browser detection
        const isHeadless = userAgent.includes('headless') || 
                          navigator.webdriver || 
                          window.navigator.plugins.length === 0 ||
                          userAgent.includes('puppeteer') ||
                          userAgent.includes('__nightmare') ||
                          userAgent.includes('_phantom');
        
        // Feature detection
        const supportsTouch = 'ontouchstart' in window;
        const supportsGestures = 'ongesturestart' in window;
        const supportsPointer = 'onpointerdown' in window;
        
        return {
            isChrome, isFirefox, isSafari, isEdge, isIE,
            isIOS, isAndroid, isMobile, isSamsung,
            supportsTouch, supportsGestures, supportsPointer,
            isHeadless
        };
    }

    const browser = detectBrowser();
";
    }

    /**
     * Generate right-click context menu protection script
     */
    protected function getRightClickProtectionScript(): string
    {
        return "
    // Right-click context menu protection
    function disableRightClick(e) {
        // Skip protection for screen readers and assistive technology
        if (accessibility.hasAccessibilityFeatures) {
            return true;
        }
        
        if (e.target.tagName === 'IMG' && e.target.closest('.product-image, .product-gallery')) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
    }

    function addRightClickProtection() {
        // Skip if assistive technology detected
        if (accessibility.isScreenReader) {
            return;
        }
        
        document.addEventListener('contextmenu', disableRightClick, true);
        
        // Additional protection for product images
        const productImages = document.querySelectorAll('.product-image img, .product-gallery img');
        productImages.forEach(function(img) {
            // Preserve accessibility attributes
            if (!img.getAttribute('aria-label') && !img.getAttribute('alt')) {
                img.setAttribute('aria-label', 'Product image');
            }
            
            // Ensure proper role for screen readers
            if (!img.getAttribute('role')) {
                img.setAttribute('role', 'img');
            }
            
            img.addEventListener('contextmenu', function(e) {
                if (!accessibility.hasAccessibilityFeatures) {
                    e.preventDefault();
                    return false;
                }
            });
            
            // Disable selection (but preserve for screen readers)
            if (!accessibility.isScreenReader) {
                img.style.webkitUserSelect = 'none';
                img.style.mozUserSelect = 'none';
                img.style.msUserSelect = 'none';
                img.style.userSelect = 'none';
            }
        });
    }
";
    }

    /**
     * Generate drag and drop protection script
     */
    protected function getDragDropProtectionScript(): string
    {
        return "
    // Drag and drop protection
    function disableDragDrop(e) {
        if (e.target.tagName === 'IMG' && e.target.closest('.product-image, .product-gallery')) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
    }

    function addDragDropProtection() {
        // Prevent drag start on product images
        document.addEventListener('dragstart', disableDragDrop, true);
        
        // Additional protection for product images
        const productImages = document.querySelectorAll('.product-image img, .product-gallery img');
        productImages.forEach(function(img) {
            img.draggable = false;
            img.addEventListener('dragstart', function(e) {
                e.preventDefault();
                return false;
            });
        });
    }
";
    }

    /**
     * Generate keyboard shortcut protection script
     */
    protected function getKeyboardProtectionScript(): string
    {
        return "
    // Keyboard shortcut protection
    function disableKeyboardShortcuts(e) {
        // Skip protection for screen readers and assistive technology
        if (accessibility.hasAccessibilityFeatures) {
            // Allow essential accessibility keys
            const accessibilityKeys = ['Tab', 'Enter', 'Space', 'Escape', 'ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'];
            if (accessibilityKeys.includes(e.key)) {
                return true;
            }
        }
        
        // Check if focus is on a product image or its container
        const activeElement = document.activeElement;
        const isProductImageFocused = activeElement && (
            activeElement.tagName === 'IMG' && activeElement.closest('.product-image, .product-gallery') ||
            activeElement.closest('.product-image, .product-gallery')
        );
        
        if (isProductImageFocused || document.querySelector('.product-image:hover, .product-gallery:hover')) {
            // Disable Ctrl+S (Save) - but allow Cmd+S on Mac for screen readers
            if (e.ctrlKey && e.key === 's' && !accessibility.isScreenReader) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
            
            // Disable F12 (Developer Tools) - but bypass for accessibility tools
            if (e.key === 'F12' && !accessibility.hasAccessibilityFeatures) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
            
            // Disable Ctrl+Shift+I (Developer Tools) - but bypass for accessibility
            if (e.ctrlKey && e.shiftKey && e.key === 'I' && !accessibility.hasAccessibilityFeatures) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
            
            // Disable Ctrl+U (View Source) - but bypass for screen readers
            if (e.ctrlKey && e.keyCode === 85 && !accessibility.isScreenReader) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        }
    }

    function addKeyboardProtection() {
        // Skip if screen reader detected
        if (accessibility.isScreenReader) {
            return;
        }
        
        document.addEventListener('keydown', disableKeyboardShortcuts, true);
        
        // Ensure focus management works properly
        const productImages = document.querySelectorAll('.product-image img, .product-gallery img');
        productImages.forEach(function(img) {
            // Ensure images are focusable for keyboard navigation
            if (!img.hasAttribute('tabindex')) {
                img.setAttribute('tabindex', '0');
            }
            
            // Add focus and blur event handlers
            img.addEventListener('focus', function() {
                this.setAttribute('aria-describedby', 'image-protection-notice');
            });
            
            img.addEventListener('blur', function() {
                this.removeAttribute('aria-describedby');
            });
        });
    }
";
    }

    /**
     * Generate mobile touch protection script
     */
    protected function getMobileTouchProtectionScript(): string
    {
        return "
    // Mobile touch protection
    function addMobileTouchProtection() {
        const productImages = document.querySelectorAll('.product-image img, .product-gallery img');
        
        productImages.forEach(function(img) {
            // Prevent long press context menu
            img.addEventListener('touchstart', function(e) {
                this.style.webkitTouchCallout = 'none';
                this.style.webkitUserSelect = 'none';
                this.style.userSelect = 'none';
            }, { passive: false });
            
            // Prevent touch gestures
            img.addEventListener('touchmove', function(e) {
                if (e.touches.length > 1) {
                    e.preventDefault();
                }
            }, { passive: false });
            
            // Apply mobile styles
            img.style.webkitTouchCallout = 'none';
            img.style.webkitUserSelect = 'none';
            img.style.userSelect = 'none';
            img.style.touchAction = 'none';
        });
    }
";
    }

    /**
     * Generate initialization script
     */
    protected function getInitializationScript(): string
    {
        return "
    // Initialize protection when DOM is ready
    function initializeImageProtection() {
        if (typeof addRightClickProtection === 'function') {
            addRightClickProtection();
        }
        if (typeof addDragDropProtection === 'function') {
            addDragDropProtection();
        }
        if (typeof addKeyboardProtection === 'function') {
            addKeyboardProtection();
        }
        if (typeof addMobileTouchProtection === 'function') {
            addMobileTouchProtection();
        }
    }

    // Initialize immediately if DOM is already loaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeImageProtection);
    } else {
        initializeImageProtection();
    }
";
    }

    /**
     * Get enhanced protection script with error handling
     */
    public function getEnhancedProtectionScript(): string
    {
        if (!$this->isProtectionEnabled()) {
            return '';
        }

        $baseScript = $this->getProtectionScript();
        
        // Add error handling wrapper
        $errorHandlingScript = "
(function() {
    'use strict';
    
    // Error handling for protection script
    window.addEventListener('error', function(e) {
        if (e.filename && e.filename.includes('protection')) {
            console.warn('Image protection script error:', e.message);
            // Fallback to CSS-only protection
            document.body.classList.add('js-protection-failed');
        }
    });
    
    // Test if JavaScript is working and apply enhanced protection
    try {
        " . $baseScript . "
        
        // Mark JavaScript as working
        document.documentElement.classList.add('js-enabled');
        document.documentElement.classList.remove('no-js');
        
    } catch (error) {
        console.warn('Protection script failed to initialize:', error);
        document.body.classList.add('js-protection-failed');
        
        // Apply basic CSS fallback protection
        var style = document.createElement('style');
        style.textContent = `
            .product-image img, .product-gallery img {
                -webkit-user-select: none !important;
                -moz-user-select: none !important;
                -ms-user-select: none !important;
                user-select: none !important;
                -webkit-user-drag: none !important;
                user-drag: none !important;
                -webkit-touch-callout: none !important;
                pointer-events: auto !important;
            }
        `;
        document.head.appendChild(style);
    }
})();
";
        
        return $errorHandlingScript;
    }

    /**
     * Get protection styles including no-script fallbacks
     */
    public function getProtectionStyles(): string
    {
        if (!$this->isProtectionEnabled()) {
            return '';
        }

        return "
<style>
/* Base protection styles */
.product-image img, .product-gallery img {
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
    -webkit-user-drag: none;
    user-drag: none;
    -webkit-touch-callout: none;
    -webkit-tap-highlight-color: transparent;
    touch-action: none;
}

/* Enhanced protection when JavaScript is enabled */
.js-enabled .product-image img,
.js-enabled .product-gallery img {
    pointer-events: auto;
}

/* Fallback protection when JavaScript fails */
.js-protection-failed .product-image img,
.js-protection-failed .product-gallery img,
.no-js .product-image img,
.no-js .product-gallery img {
    -webkit-user-select: none !important;
    -moz-user-select: none !important;
    -ms-user-select: none !important;
    user-select: none !important;
    -webkit-user-drag: none !important;
    user-drag: none !important;
    -webkit-touch-callout: none !important;
    pointer-events: none !important;
}

/* Cross-browser compatibility styles */
@media screen and (-webkit-min-device-pixel-ratio:0) {
    /* Chrome/Safari specific */
    .product-image img, .product-gallery img {
        -webkit-user-select: none;
        -webkit-user-drag: none;
        -webkit-touch-callout: none;
    }
}

@-moz-document url-prefix() {
    /* Firefox specific */
    .product-image img, .product-gallery img {
        -moz-user-select: none;
        -moz-user-drag: none;
    }
}

@media screen and (min-width:0\\0) {
    /* IE specific */
    .product-image img, .product-gallery img {
        -ms-user-select: none;
    }
}

@supports (-ms-ime-align:auto) {
    /* Edge specific */
    .product-image img, .product-gallery img {
        -ms-user-select: none;
        -ms-touch-action: none;
    }
}

/* High contrast mode compatibility */
@media (prefers-contrast: high) {
    .product-image img:focus,
    .product-gallery img:focus {
        outline: 2px solid;
        outline-offset: 2px;
    }
}

@media (-ms-high-contrast: active) {
    .product-image img:focus,
    .product-gallery img:focus {
        outline: 2px solid;
    }
}

/* Focus management for accessibility */
.product-image img:focus,
.product-gallery img:focus {
    outline: 2px solid #0066cc;
    outline-offset: 2px;
}

/* Responsive protection across viewports */
@media (min-width: 1024px) {
    /* Desktop viewport protections */
    .product-image img, .product-gallery img {
        -webkit-user-select: none;
        -moz-user-select: none;
        user-select: none;
    }
}

@media (min-width: 768px) and (max-width: 1023px) {
    /* Tablet viewport protections */
    .product-image img, .product-gallery img {
        -webkit-touch-callout: none;
        touch-action: none;
    }
}

@media (max-width: 767px) {
    /* Mobile viewport protections */
    .product-image img, .product-gallery img {
        -webkit-touch-callout: none !important;
        -webkit-user-select: none !important;
        user-select: none !important;
        touch-action: none !important;
    }
}

/* High-DPI display protections */
@media (-webkit-min-device-pixel-ratio: 2) {
    .product-image img, .product-gallery img {
        -webkit-user-select: none;
        -webkit-touch-callout: none;
    }
}

@media (min-resolution: 192dpi) {
    .product-image img, .product-gallery img {
        -webkit-user-select: none;
        -moz-user-select: none;
        user-select: none;
    }
}

/* Mobile-specific fallback protection */
@media (max-width: 768px) {
    .no-js .product-image img,
    .no-js .product-gallery img,
    .js-protection-failed .product-image img,
    .js-protection-failed .product-gallery img {
        -webkit-touch-callout: none !important;
        -webkit-user-select: none !important;
        user-select: none !important;
        touch-action: none !important;
    }
}
</style>
";
    }

    /**
     * Get no-script fallback styles for when JavaScript is disabled
     */
    public function getNoScriptFallbackStyles(): string
    {
        if (!$this->isProtectionEnabled()) {
            return '';
        }

        return "
<noscript>
<style>
/* Enhanced protection when JavaScript is disabled */
.product-image img, .product-gallery img {
    -webkit-user-select: none !important;
    -moz-user-select: none !important;
    -ms-user-select: none !important;
    user-select: none !important;
    -webkit-user-drag: none !important;
    user-drag: none !important;
    -webkit-touch-callout: none !important;
    pointer-events: none !important;
    touch-action: none !important;
}

/* Prevent right-click context menu via CSS (limited effectiveness) */
.product-image, .product-gallery {
    -webkit-user-select: none !important;
    -moz-user-select: none !important;
    -ms-user-select: none !important;
    user-select: none !important;
}
</style>
</noscript>
";
    }

    /**
     * Validate and decode image token
     */
    public function validateImageToken(string $token): ?array
    {
        try {
            $decoded = base64_decode($token);
            if (!$decoded) {
                return null;
            }
            
            $parts = explode('.', $decoded);
            if (count($parts) !== 2) {
                return null;
            }
            
            [$payload, $signature] = $parts;
            
            // Verify signature
            $expectedSignature = hash_hmac('sha256', $payload, config('app.key'));
            if (!hash_equals($expectedSignature, $signature)) {
                return null;
            }
            
            // Decode payload
            $data = json_decode(base64_decode($payload), true);
            if (!$data) {
                return null;
            }
            
            // Check expiration
            if (isset($data['expires']) && $data['expires'] < time()) {
                return null;
            }
            
            return $data;
            
        } catch (\Exception $e) {
            Log::warning('Failed to validate image token', [
                'token' => substr($token, 0, 20) . '...',
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get protection effectiveness report
     */
    public function getProtectionEffectivenessReport(): array
    {
        $settings = $this->getProtectionSettings();
        $extensions = extension_loaded('gd') || extension_loaded('imagick');
        
        return [
            'javascript_protection' => [
                'right_click' => $settings['right_click_protection'] ? 'enabled' : 'disabled',
                'drag_drop' => $settings['drag_drop_protection'] ? 'enabled' : 'disabled',
                'keyboard_shortcuts' => $settings['keyboard_protection'] ? 'enabled' : 'disabled',
                'mobile_touch' => 'enabled', // Always enabled when protection is on
                'effectiveness' => 'high_with_js'
            ],
            'css_fallback_protection' => [
                'user_select' => 'enabled',
                'drag_prevention' => 'enabled',
                'touch_callout' => 'enabled',
                'effectiveness' => 'medium_without_js'
            ],
            'server_side_protection' => [
                'url_obfuscation' => $this->isProtectionEnabled() ? 'enabled' : 'disabled',
                'token_validation' => $this->isProtectionEnabled() ? 'enabled' : 'disabled',
                'effectiveness' => 'low_advanced_users'
            ],
            'overall_assessment' => [
                'protection_level' => $this->calculateProtectionLevel($settings, $extensions),
                'vulnerabilities' => $this->identifyVulnerabilities($settings),
                'recommendations' => $this->getProtectionRecommendations($settings)
            ]
        ];
    }

    /**
     * Calculate overall protection level
     */
    protected function calculateProtectionLevel(array $settings, bool $hasImageExtensions): string
    {
        $score = 0;
        
        if ($settings['right_click_protection']) $score += 25;
        if ($settings['drag_drop_protection']) $score += 25;
        if ($settings['keyboard_protection']) $score += 20;
        if ($hasImageExtensions) $score += 20; // Watermarking capability
        if ($this->isProtectionEnabled()) $score += 10; // URL obfuscation
        
        if ($score >= 80) return 'high';
        if ($score >= 60) return 'medium';
        if ($score >= 40) return 'low';
        return 'minimal';
    }

    /**
     * Identify protection vulnerabilities
     */
    protected function identifyVulnerabilities(array $settings): array
    {
        $vulnerabilities = [];
        
        if (!$settings['right_click_protection']) {
            $vulnerabilities[] = 'Right-click context menu is not disabled';
        }
        
        if (!$settings['drag_drop_protection']) {
            $vulnerabilities[] = 'Drag and drop is not prevented';
        }
        
        if (!$settings['keyboard_protection']) {
            $vulnerabilities[] = 'Keyboard shortcuts are not blocked';
        }
        
        $vulnerabilities[] = 'View source access cannot be completely prevented';
        $vulnerabilities[] = 'Browser developer tools can bypass client-side protection';
        $vulnerabilities[] = 'Direct URL access may be possible without server-side protection';
        
        return $vulnerabilities;
    }

    /**
     * Get protection recommendations
     */
    protected function getProtectionRecommendations(array $settings): array
    {
        $recommendations = [];
        
        if (!$settings['right_click_protection']) {
            $recommendations[] = 'Enable right-click protection to prevent easy context menu access';
        }
        
        if (!$settings['drag_drop_protection']) {
            $recommendations[] = 'Enable drag and drop protection to prevent easy image saving';
        }
        
        if (!$settings['keyboard_protection']) {
            $recommendations[] = 'Enable keyboard shortcut protection to block common save shortcuts';
        }
        
        $recommendations[] = 'Consider implementing server-side image processing to embed watermarks';
        $recommendations[] = 'Use HTTPS to prevent man-in-the-middle attacks on image URLs';
        $recommendations[] = 'Implement rate limiting on image requests to prevent bulk downloading';
        $recommendations[] = 'Consider using lower resolution images for public display';
        
        return $recommendations;
    }
}