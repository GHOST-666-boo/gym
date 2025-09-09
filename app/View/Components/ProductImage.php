<?php

namespace App\View\Components;

use App\Helpers\ImageHelper;
use App\Services\WatermarkService;
use App\Services\ImageProtectionService;
use App\Services\SettingsService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ProductImage extends Component
{
    public string $src;
    public string $alt;
    public string $class;
    public int $width;
    public int $height;
    public bool $lazy;
    public array $imageAttributes;
    public bool $watermarkEnabled;
    public bool $protectionEnabled;
    public string $protectionScript;
    public string $protectionStyles;
    public ?string $watermarkError;

    protected WatermarkService $watermarkService;
    protected ImageProtectionService $protectionService;
    protected SettingsService $settingsService;

    /**
     * Create a new component instance.
     */
    public function __construct(
        ?string $imagePath = null,
        string $alt = '',
        string $class = '',
        int $width = 300,
        int $height = 200,
        bool $lazy = true,
        ?bool $protected = null,
        ?bool $watermarked = null
    ) {
        $this->watermarkService = app(WatermarkService::class);
        $this->protectionService = app(ImageProtectionService::class);
        $this->settingsService = app(SettingsService::class);
        
        $this->alt = $alt;
        $this->class = $class;
        $this->width = $width;
        $this->height = $height;
        $this->lazy = $lazy;

        // Auto-detect protection and watermarking settings if not explicitly provided
        $this->protectionEnabled = $protected ?? $this->protectionService->isProtectionEnabled();
        
        // Check if watermarking is enabled - can be explicitly disabled
        if ($watermarked === false) {
            $this->watermarkEnabled = false; // Explicitly disabled
        } else {
            $this->watermarkEnabled = $watermarked ?? $this->settingsService->get('watermark_enabled', false);
        }

        // Apply watermark if enabled and image path is provided
        $processedImagePath = $imagePath;
        $watermarkError = null;
        
        if ($imagePath && $this->watermarkEnabled === true) {
            try {
                // PERFORMANCE OPTIMIZED: Non-blocking watermark application
                // This will return cached watermark if available, or original image if not
                // Watermark generation happens in background for future requests
                $processedImagePath = $this->watermarkService->applyWatermark($imagePath);
                
            } catch (\Exception $e) {
                // Log error and fallback to original image
                \Log::error('Watermark application failed in component', [
                    'image_path' => $imagePath,
                    'error' => $e->getMessage()
                ]);
                
                $processedImagePath = $imagePath;
                $watermarkError = 'exception';
            }
        } else {
            // Watermark is disabled for this component - force original image
            if ($imagePath) {
                $processedImagePath = $this->watermarkService->applyWatermark($imagePath, ['force_original' => true]);
            }
        }

        // Generate image attributes
        if ($processedImagePath && $lazy) {
            $this->imageAttributes = ImageHelper::lazyImageAttributes($processedImagePath, $alt, [
                'width' => $width,
                'height' => $height,
                'class' => $this->buildImageClass($class)
            ]);
            $this->src = $this->imageAttributes['data-src'];
        } else {
            $this->src = $processedImagePath ? 
                ImageHelper::getImageSrc($processedImagePath, ['width' => $width, 'height' => $height]) : 
                ImageHelper::getPlaceholderImage($width, $height);
            
            $this->imageAttributes = [
                'src' => $this->src,
                'alt' => $alt,
                'class' => $this->buildImageClass($class),
                'width' => $width,
                'height' => $height,
                'loading' => $lazy ? 'lazy' : 'eager',
                'decoding' => 'async'
            ];
        }

        // Generate protection scripts and styles if enabled with error handling
        if ($this->protectionEnabled) {
            try {
                $this->protectionScript = $this->protectionService->getEnhancedProtectionScript();
                $this->protectionStyles = $this->protectionService->getProtectionStyles();
                
                // Add no-script fallback styles
                $this->protectionStyles .= $this->protectionService->getNoScriptFallbackStyles();
            } catch (\Exception $e) {
                \Log::error('Protection script/style generation failed', [
                    'image_path' => $imagePath,
                    'error' => $e->getMessage()
                ]);
                
                // Fallback to basic CSS protection
                $this->protectionScript = '';
                $this->protectionStyles = $this->getBasicProtectionStyles();
            }
        } else {
            $this->protectionScript = '';
            $this->protectionStyles = '';
        }
        
        // Store error information for template use
        $this->watermarkError = $watermarkError;
    }

    /**
     * Build CSS classes for the image including protection classes
     */
    protected function buildImageClass(string $baseClass): string
    {
        $classes = [$baseClass];
        
        if ($this->protectionEnabled) {
            $classes[] = 'product-image';
        }
        
        return trim(implode(' ', array_filter($classes)));
    }

    /**
     * Check if watermarking is enabled for this image
     */
    public function isWatermarkEnabled(): bool
    {
        return $this->watermarkEnabled;
    }

    /**
     * Check if protection is enabled for this image
     */
    public function isProtectionEnabled(): bool
    {
        return $this->protectionEnabled;
    }

    /**
     * Get protection script for this image
     */
    public function getProtectionScript(): string
    {
        return $this->protectionScript;
    }

    /**
     * Get protection styles for this image
     */
    public function getProtectionStyles(): string
    {
        return $this->protectionStyles;
    }

    /**
     * Get basic CSS protection styles as fallback
     */
    protected function getBasicProtectionStyles(): string
    {
        return "
<style>
.product-image img, .product-gallery img {
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
    -webkit-user-drag: none;
    user-drag: none;
    -webkit-touch-callout: none;
    pointer-events: auto;
}
.product-image img::-webkit-media-controls,
.product-gallery img::-webkit-media-controls {
    display: none !important;
}
</style>
";
    }

    /**
     * Check if there was a watermark error
     */
    public function hasWatermarkError(): bool
    {
        return $this->watermarkError !== null;
    }

    /**
     * Get watermark error type
     */
    public function getWatermarkError(): ?string
    {
        return $this->watermarkError;
    }

    /**
     * Get CSS watermark styles for fallback rendering
     */
    public function getCssWatermarkStyles(): string
    {
        if (!$this->hasWatermarkError() || $this->watermarkError !== 'css_fallback') {
            return '';
        }

        // Get cached CSS watermark data
        $fallbackKey = 'css_watermark_' . md5($this->src);
        $watermarkData = \Cache::get($fallbackKey);
        
        if (!$watermarkData) {
            return '';
        }

        $position = $watermarkData['position'] ?? 'bottom-right';
        $opacity = ($watermarkData['opacity'] ?? 50) / 100;
        $color = $watermarkData['color'] ?? '#FFFFFF';
        $text = $watermarkData['text'] ?? '';

        // Convert position to CSS values
        $positionStyles = $this->getPositionStyles($position);

        return "
<style>
.product-image-{$this->getImageId()}::after {
    content: '{$text}';
    position: absolute;
    {$positionStyles}
    color: {$color};
    opacity: {$opacity};
    font-size: 14px;
    font-weight: bold;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
    pointer-events: none;
    z-index: 10;
    white-space: nowrap;
}
</style>
";
    }

    /**
     * Get CSS position styles for watermark
     */
    protected function getPositionStyles(string $position): string
    {
        $positions = [
            'top-left' => 'top: 10px; left: 10px;',
            'top-center' => 'top: 10px; left: 50%; transform: translateX(-50%);',
            'top-right' => 'top: 10px; right: 10px;',
            'center-left' => 'top: 50%; left: 10px; transform: translateY(-50%);',
            'center' => 'top: 50%; left: 50%; transform: translate(-50%, -50%);',
            'center-right' => 'top: 50%; right: 10px; transform: translateY(-50%);',
            'bottom-left' => 'bottom: 10px; left: 10px;',
            'bottom-center' => 'bottom: 10px; left: 50%; transform: translateX(-50%);',
            'bottom-right' => 'bottom: 10px; right: 10px;'
        ];

        return $positions[$position] ?? $positions['bottom-right'];
    }

    /**
     * Get unique image ID for CSS targeting
     */
    protected function getImageId(): string
    {
        return substr(md5($this->src), 0, 8);
    }

    /**
     * Get error message for display
     */
    public function getErrorMessage(): string
    {
        if (!$this->hasWatermarkError()) {
            return '';
        }

        $messages = [
            'css_fallback' => 'Watermark applied using CSS fallback',
            'processing_failed' => 'Watermark processing failed, showing original image',
            'exception' => 'Watermark service error, showing original image'
        ];

        return $messages[$this->watermarkError] ?? 'Unknown watermark error';
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.product-image');
    }
}