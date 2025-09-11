<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class WatermarkService
{
    protected SettingsService $settingsService;

    // 9 predefined watermark positions
    const POSITIONS = [
        'top-left', 'top-center', 'top-right',
        'center-left', 'center', 'center-right',
        'bottom-left', 'bottom-center', 'bottom-right'
    ];

    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    /**
     * Apply watermark to an image using optimized lazy loading and caching
     * PERFORMANCE OPTIMIZED: Returns original image immediately, generates watermark in background
     */
    public function applyWatermark(string $imagePath, array $options = []): string
    {
        try {
            // Quick early return if watermarking is disabled globally or explicitly for this call
            if (!$this->settingsService->get('watermark_enabled', false) || ($options['force_original'] ?? false)) {
                return $imagePath;
            }

            // Fast cache check first - this should be the most common case
            $cachedPath = $this->getCachedWatermarkedImage($imagePath, $options);
            if ($cachedPath) {
                return $cachedPath;
            }

            // Check if original image exists
            if (!Storage::disk('public')->exists($imagePath)) {
                return $imagePath;
            }

            $settings = array_merge($this->getWatermarkSettings(), $options);
            
            // Only process if we have watermark content
            if (empty($settings['text']) && empty($settings['logo_path'])) {
                return $imagePath;
            }

            // PERFORMANCE FIX: Return original image immediately, generate watermark in background
            $this->scheduleWatermarkGeneration($imagePath, $settings);
            
            // Return original image for immediate display - no blocking
            return $imagePath;
            
        } catch (\Exception $e) {
            Log::error('Watermark application failed', [
                'image_path' => $imagePath,
                'error' => $e->getMessage()
            ]);
            
            return $imagePath;
        }
    }

    /**
     * Schedule watermark generation in background for better performance
     */
    protected function scheduleWatermarkGeneration(string $imagePath, array $settings): void
    {
        // Use cache to track scheduled generations to avoid duplicates
        $scheduleKey = 'watermark_scheduled_' . md5($imagePath . json_encode($settings));
        
        if (Cache::has($scheduleKey)) {
            return; // Already scheduled
        }
        
        // Mark as scheduled for 5 minutes
        Cache::put($scheduleKey, true, 300);
        $this->trackScheduleKey($scheduleKey);
        
        // Schedule generation using Laravel's defer for immediate background processing
        defer(function () use ($imagePath, $settings, $scheduleKey) {
            try {
                $this->generateWatermarkNow($imagePath, $settings);
                Cache::forget($scheduleKey);
            } catch (\Exception $e) {
                Log::error('Background watermark generation failed', [
                    'image_path' => $imagePath,
                    'error' => $e->getMessage()
                ]);
                Cache::forget($scheduleKey);
            }
        });
    }

    /**
     * Check if cached watermarked image exists and return path
     */
    protected function getCachedWatermarkedImage(string $imagePath, array $options = []): ?string
    {
        $settings = array_merge($this->getWatermarkSettings(), $options);
        $cachedPath = $this->generateCachedWatermarkedPath($imagePath, $settings);
        
        // Use Laravel's Storage facade for better performance
        if (Storage::disk('public')->exists($cachedPath)) {
            // Quick timestamp check without full file operations
            $cacheKey = 'watermark_timestamp_' . md5($cachedPath);
            $cachedTimestamp = Cache::get($cacheKey);
            
            if ($cachedTimestamp) {
                return $cachedPath; // Trust the cached timestamp
            }
            
            // Only do file operations if timestamp not cached
            try {
                $cachedFullPath = Storage::disk('public')->path($cachedPath);
                $originalFullPath = Storage::disk('public')->path($imagePath);
                
                if (file_exists($cachedFullPath)) {
                    if (!file_exists($originalFullPath)) {
                        // Original doesn't exist, return cached version
                        Cache::put($cacheKey, time(), 3600);
                        $this->trackCacheKey($cacheKey);
                        return $cachedPath;
                    }
                    
                    $originalTime = filemtime($originalFullPath);
                    $cachedTime = filemtime($cachedFullPath);
                    
                    if ($cachedTime >= $originalTime) {
                        // Cache the timestamp for future quick checks
                        Cache::put($cacheKey, $cachedTime, 3600);
                        $this->trackCacheKey($cacheKey);
                        return $cachedPath;
                    }
                }
            } catch (\Exception $e) {
                // If file operations fail, assume cache is invalid
                Log::debug('Cache check failed', ['error' => $e->getMessage()]);
            }
        }
        
        return null;
    }

    /**
     * Generate watermark synchronously (for admin preview or when needed immediately)
     */
    public function generateWatermarkNow(string $imagePath, array $options = []): string
    {
        try {
            // Check if watermarking is enabled
            if (!$this->settingsService->get('watermark_enabled', false)) {
                return $imagePath;
            }

            // Check if cached watermarked image exists
            $cachedPath = $this->getCachedWatermarkedImage($imagePath, $options);
            if ($cachedPath) {
                return $cachedPath;
            }

            // Check image extensions
            if (!extension_loaded('gd')) {
                Log::error('GD extension not available for watermarking');
                return $imagePath;
            }

            $fullPath = Storage::disk('public')->path($imagePath);
            
            if (!file_exists($fullPath)) {
                return $imagePath;
            }

            // Load the main image
            $image = $this->loadImageWithGD($fullPath);
            if (!$image) {
                Log::error('Failed to load image', ['path' => $fullPath]);
                return $imagePath;
            }
            
            // Get watermark settings
            $settings = array_merge($this->getWatermarkSettings(), $options);
            
            // Apply text watermark if text is provided
            if (!empty($settings['text'])) {
                $this->applyTextWatermark($image, $settings);
            }
            
            // Apply logo watermark if logo path is provided
            if (!empty($settings['logo_path'])) {
                $this->applyLogoWatermark($image, $settings);
            }
            
            // Generate cached watermarked image path
            $watermarkedPath = $this->generateCachedWatermarkedPath($imagePath, $settings);
            $watermarkedFullPath = Storage::disk('public')->path($watermarkedPath);
            
            // Ensure cache directory exists
            $directory = dirname($watermarkedFullPath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            
            // Save image
            $this->saveImageWithGD($image, $watermarkedFullPath, $fullPath);
            
            // Clean up memory
            imagedestroy($image);
            
            return $watermarkedPath;
            
        } catch (\Exception $e) {
            Log::error('Watermark generation failed', [
                'image_path' => $imagePath,
                'error' => $e->getMessage()
            ]);
            
            return $imagePath;
        }
    }

    /**
     * Get optimized watermark settings with caching
     */
    public function getWatermarkSettings(): array
    {
        return Cache::remember('watermark_settings_cache', 300, function () {
            return [
                'text' => $this->settingsService->get('watermark_text', ''),
                'opacity' => (int) $this->settingsService->get('watermark_opacity', 50),
                'position' => $this->settingsService->get('watermark_position', 'bottom-right'),
                'size' => (int) $this->settingsService->get('watermark_size', 24),
                'text_size' => $this->settingsService->get('watermark_text_size', 'medium'), // small/medium/large
                'color' => $this->settingsService->get('watermark_text_color', '#FFFFFF'),
                'logo_path' => $this->settingsService->get('watermark_logo_path', ''),
                'logo_size' => $this->settingsService->get('watermark_logo_size', 'medium'), // small/medium/large
            ];
        });
    }

    /**
     * Generate cached watermarked image path with settings hash
     */
    public function generateCachedWatermarkedPath(string $originalPath, array $settings): string
    {
        $pathInfo = pathinfo($originalPath);
        $filename = $pathInfo['filename'];
        $extension = $pathInfo['extension'];
        
        // Create a hash of the settings to ensure unique cached versions
        $settingsHash = $this->generateSettingsHash($settings);
        
        // Store in watermarks/cache directory
        return 'watermarks/cache/' . $filename . '_' . $settingsHash . '.' . $extension;
    }

    /**
     * Generate a hash of watermark settings for cache key
     */
    protected function generateSettingsHash(array $settings): string
    {
        // Include only relevant settings that affect the watermark appearance
        $relevantSettings = [
            'text' => $settings['text'] ?? '',
            'opacity' => $settings['opacity'] ?? 50,
            'position' => $settings['position'] ?? 'bottom-right',
            'size' => $settings['size'] ?? 24,
            'color' => $settings['color'] ?? '#FFFFFF',
            'logo_path' => $settings['logo_path'] ?? '',
            'logo_size' => $settings['logo_size'] ?? 'medium',
        ];
        
        return substr(md5(json_encode($relevantSettings)), 0, 8);
    }

    /**
     * Apply text watermark to image using GD
     */
    protected function applyTextWatermark($image, array $settings): void
    {
        $imageWidth = imagesx($image);
        $imageHeight = imagesy($image);
        
        $text = $settings['text'];
        $fontSize = $settings['size'];
        $opacity = $settings['opacity'];
        $position = $settings['position'];
        $color = $settings['color'] ?? '#FFFFFF';
        
        // ALWAYS use fixed font sizes - completely ignore image dimensions
        $fixedSizes = [
            'small' => 16,
            'medium' => 24,
            'large' => 32
        ];
        
        // Determine size key from settings (priority order)
        $sizeKey = 'medium'; // default
        
        // 1. Check text_size setting first (new setting)
        if (isset($settings['text_size']) && in_array($settings['text_size'], ['small', 'medium', 'large'])) {
            $sizeKey = $settings['text_size'];
        }
        // 2. Check main size setting (could be string or numeric)
        elseif (isset($settings['size'])) {
            if (in_array($settings['size'], ['small', 'medium', 'large'])) {
                // String size setting
                $sizeKey = $settings['size'];
            } elseif (is_numeric($settings['size'])) {
                // Convert numeric to nearest fixed size
                $numSize = (int)$settings['size'];
                if ($numSize <= 18) {
                    $sizeKey = 'small';
                } elseif ($numSize <= 28) {
                    $sizeKey = 'medium';
                } else {
                    $sizeKey = 'large';
                }
            }
        }
        
        // Always use fixed size - no exceptions
        $fontSize = $fixedSizes[$sizeKey];
        
        // Calculate text dimensions
        $textBox = imagettfbbox($fontSize, 0, $this->getDefaultFont(), $text);
        $textWidth = abs($textBox[4] - $textBox[0]);
        $textHeight = abs($textBox[5] - $textBox[1]);
        
        // Calculate position coordinates
        [$x, $y] = $this->calculateWatermarkPosition(
            $imageWidth, 
            $imageHeight, 
            $position, 
            $textWidth, 
            $textHeight
        );
        
        // Convert hex color to RGB
        $rgb = $this->hexToRgb($color);
        
        // Create color with opacity
        $textColor = imagecolorallocatealpha(
            $image, 
            $rgb['r'], 
            $rgb['g'], 
            $rgb['b'], 
            127 - (127 * $opacity / 100)
        );
        
        // Add text shadow for better visibility
        $shadowColor = imagecolorallocatealpha($image, 0, 0, 0, 127 - (127 * $opacity / 100));
        imagettftext($image, $fontSize, 0, $x + 1, $y + 1, $shadowColor, $this->getDefaultFont(), $text);
        
        // Add main text
        imagettftext($image, $fontSize, 0, $x, $y, $textColor, $this->getDefaultFont(), $text);
    }

    /**
     * Apply logo watermark to image using GD
     */
    protected function applyLogoWatermark($image, array $settings): void
    {
        $logoPath = $settings['logo_path'];
        $opacity = $settings['opacity'];
        $position = $settings['position'];
        $logoSize = $settings['logo_size'] ?? 'medium';
        
        // Load logo image
        $logoImage = $this->loadLogoImage($logoPath);
        if (!$logoImage) {
            Log::warning("Failed to load logo image: {$logoPath}");
            return;
        }
        
        $imageWidth = imagesx($image);
        $imageHeight = imagesy($image);
        $logoWidth = imagesx($logoImage);
        $logoHeight = imagesy($logoImage);
        
        // Scale logo based on size setting
        [$scaledWidth, $scaledHeight] = $this->calculateLogoSize($logoWidth, $logoHeight, $imageWidth, $imageHeight, $logoSize);
        
        // Create scaled logo image
        $scaledLogo = imagecreatetruecolor($scaledWidth, $scaledHeight);
        
        // Preserve transparency
        imagealphablending($scaledLogo, false);
        imagesavealpha($scaledLogo, true);
        $transparent = imagecolorallocatealpha($scaledLogo, 0, 0, 0, 127);
        imagefill($scaledLogo, 0, 0, $transparent);
        imagealphablending($scaledLogo, true);
        
        // Scale the logo
        imagecopyresampled($scaledLogo, $logoImage, 0, 0, 0, 0, $scaledWidth, $scaledHeight, $logoWidth, $logoHeight);
        
        // Calculate position coordinates
        [$x, $y] = $this->calculateWatermarkPosition(
            $imageWidth, 
            $imageHeight, 
            $position, 
            $scaledWidth, 
            $scaledHeight
        );
        
        // Merge logo with main image
        imagecopymerge($image, $scaledLogo, $x, $y, 0, 0, $scaledWidth, $scaledHeight, $opacity);
        
        // Clean up memory
        imagedestroy($logoImage);
        imagedestroy($scaledLogo);
    }

    /**
     * Calculate watermark position coordinates for 9 predefined positions
     */
    public function calculateWatermarkPosition(int $imageWidth, int $imageHeight, string $position, int $watermarkWidth, int $watermarkHeight): array
    {
        $padding = 20;
        
        switch ($position) {
            case 'top-left':
                return [$padding, $watermarkHeight + $padding];
            case 'top-center':
                return [($imageWidth - $watermarkWidth) / 2, $watermarkHeight + $padding];
            case 'top-right':
                return [$imageWidth - $watermarkWidth - $padding, $watermarkHeight + $padding];
            case 'center-left':
                return [$padding, ($imageHeight + $watermarkHeight) / 2];
            case 'center':
                return [($imageWidth - $watermarkWidth) / 2, ($imageHeight + $watermarkHeight) / 2];
            case 'center-right':
                return [$imageWidth - $watermarkWidth - $padding, ($imageHeight + $watermarkHeight) / 2];
            case 'bottom-left':
                return [$padding, $imageHeight - $padding];
            case 'bottom-center':
                return [($imageWidth - $watermarkWidth) / 2, $imageHeight - $padding];
            case 'bottom-right':
            default:
                return [$imageWidth - $watermarkWidth - $padding, $imageHeight - $padding];
        }
    }

    /**
     * Load image using GD library
     */
    protected function loadImageWithGD(string $imagePath)
    {
        $imageInfo = getimagesize($imagePath);
        if (!$imageInfo) {
            return false;
        }
        
        switch ($imageInfo[2]) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($imagePath);
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($imagePath);
                imagealphablending($image, false);
                imagesavealpha($image, true);
                return $image;
            case IMAGETYPE_GIF:
                return imagecreatefromgif($imagePath);
            case IMAGETYPE_WEBP:
                return imagecreatefromwebp($imagePath);
            default:
                return false;
        }
    }
    
    /**
     * Save image using GD library
     */
    protected function saveImageWithGD($image, string $outputPath, string $originalPath): bool
    {
        $imageInfo = getimagesize($originalPath);
        if (!$imageInfo) {
            return false;
        }
        
        switch ($imageInfo[2]) {
            case IMAGETYPE_JPEG:
                return imagejpeg($image, $outputPath, 90);
            case IMAGETYPE_PNG:
                return imagepng($image, $outputPath, 9);
            case IMAGETYPE_GIF:
                return imagegif($image, $outputPath);
            case IMAGETYPE_WEBP:
                return imagewebp($image, $outputPath, 90);
            default:
                return false;
        }
    }

    /**
     * Load logo image
     */
    protected function loadLogoImage(string $logoPath)
    {
        $fullPath = $this->resolveLogoPath($logoPath);
        
        if (!file_exists($fullPath)) {
            return false;
        }
        
        $imageInfo = getimagesize($fullPath);
        if (!$imageInfo) {
            return false;
        }
        
        switch ($imageInfo[2]) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($fullPath);
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($fullPath);
                imagealphablending($image, false);
                imagesavealpha($image, true);
                return $image;
            case IMAGETYPE_GIF:
                return imagecreatefromgif($fullPath);
            case IMAGETYPE_WEBP:
                return imagecreatefromwebp($fullPath);
            default:
                return false;
        }
    }

    /**
     * Resolve logo path to full system path
     */
    protected function resolveLogoPath(string $logoPath): string
    {
        if (str_starts_with($logoPath, '/') || str_contains($logoPath, ':\\')) {
            return $logoPath;
        }
        
        $storagePath = Storage::disk('public')->path($logoPath);
        if (file_exists($storagePath)) {
            return $storagePath;
        }
        
        return $logoPath;
    }

    /**
     * Calculate logo size based on image dimensions
     */
    protected function calculateLogoSize(int $logoWidth, int $logoHeight, int $imageWidth, int $imageHeight, string $size): array
    {
        // Fixed logo sizes (in pixels) - independent of image size
        $fixedSizes = [
            'small' => 80,   // 80px width
            'medium' => 120, // 120px width
            'large' => 160   // 160px width
        ];
        
        $targetWidth = $fixedSizes[$size] ?? $fixedSizes['medium'];
        $aspectRatio = $logoHeight / $logoWidth;
        $targetHeight = $targetWidth * $aspectRatio;
        
        // Ensure logo doesn't exceed image dimensions (safety check)
        if ($targetWidth > $imageWidth * 0.8) {
            $targetWidth = $imageWidth * 0.8;
            $targetHeight = $targetWidth * $aspectRatio;
        }
        
        if ($targetHeight > $imageHeight * 0.8) {
            $targetHeight = $imageHeight * 0.8;
            $targetWidth = $targetHeight / $aspectRatio;
        }
        
        return [(int) $targetWidth, (int) $targetHeight];
    }

    /**
     * Get default font path
     */
    protected function getDefaultFont(): string
    {
        $fontPaths = [
            '/System/Library/Fonts/Arial.ttf',
            'C:\Windows\Fonts\arial.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
        ];
        
        foreach ($fontPaths as $fontPath) {
            if (file_exists($fontPath)) {
                return $fontPath;
            }
        }
        
        return 5; // Built-in font
    }
    
    /**
     * Convert hex color to RGB array
     */
    protected function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        
        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2))
        ];
    }

    /**
     * Preload watermarks for multiple images in background
     */
    public function preloadWatermarks(array $imagePaths): void
    {
        $settings = $this->getWatermarkSettings();
        
        if (!$this->settingsService->get('watermark_enabled', false) || 
            (empty($settings['text']) && empty($settings['logo_path']))) {
            return;
        }
        
        foreach ($imagePaths as $imagePath) {
            if (!$this->getCachedWatermarkedImage($imagePath)) {
                $this->scheduleWatermarkGeneration($imagePath, $settings);
            }
        }
    }

    /**
     * Clean up old cached watermarks
     */
    public function cleanupOldCache(int $daysOld = 7): int
    {
        $cacheDir = Storage::disk('public')->path('watermarks/cache');
        $deletedCount = 0;
        
        if (!is_dir($cacheDir)) {
            return 0;
        }
        
        $cutoffTime = time() - ($daysOld * 24 * 60 * 60);
        
        $files = glob($cacheDir . '/*');
        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $cutoffTime) {
                if (unlink($file)) {
                    $deletedCount++;
                }
            }
        }
        
        Log::info("Cleaned up {$deletedCount} old watermark cache files");
        return $deletedCount;
    }

    /**
     * Invalidate watermark cache when settings change
     */
    public function invalidateCacheOnSettingsChange(): void
    {
        try {
            // Clear the watermark settings cache
            Cache::forget('watermark_settings_cache');
            
            // Clear all watermark timestamp caches
            $cacheKeys = Cache::get('watermark_cache_keys', []);
            foreach ($cacheKeys as $key) {
                Cache::forget($key);
            }
            Cache::forget('watermark_cache_keys');
            
            // Clear the entire watermarks cache directory
            $cacheDir = 'watermarks/cache';
            if (Storage::disk('public')->exists($cacheDir)) {
                $files = Storage::disk('public')->files($cacheDir);
                foreach ($files as $file) {
                    Storage::disk('public')->delete($file);
                }
                
                Log::info('Watermark cache invalidated', [
                    'files_deleted' => count($files)
                ]);
            }
            
            // Clear any scheduled watermark generations
            $scheduleKeys = Cache::get('watermark_schedule_keys', []);
            foreach ($scheduleKeys as $key) {
                Cache::forget($key);
            }
            Cache::forget('watermark_schedule_keys');
            
        } catch (\Exception $e) {
            Log::error('Failed to invalidate watermark cache', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Track cache keys for later invalidation
     */
    protected function trackCacheKey(string $key): void
    {
        $cacheKeys = Cache::get('watermark_cache_keys', []);
        if (!in_array($key, $cacheKeys)) {
            $cacheKeys[] = $key;
            Cache::put('watermark_cache_keys', $cacheKeys, 86400); // 24 hours
        }
    }

    /**
     * Track schedule keys for later invalidation
     */
    protected function trackScheduleKey(string $key): void
    {
        $scheduleKeys = Cache::get('watermark_schedule_keys', []);
        if (!in_array($key, $scheduleKeys)) {
            $scheduleKeys[] = $key;
            Cache::put('watermark_schedule_keys', $scheduleKeys, 86400); // 24 hours
        }
    }
}