<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class CdnService
{
    /**
     * CDN configuration
     */
    private array $config;
    
    /**
     * Image sizes for responsive images
     */
    private array $imageSizes = [
        'thumbnail' => ['width' => 300, 'height' => 300],
        'medium' => ['width' => 600, 'height' => 600],
        'large' => ['width' => 1200, 'height' => 1200],
        'hero' => ['width' => 1920, 'height' => 1080],
    ];

    public function __construct()
    {
        $this->config = [
            'cdn_url' => env('CDN_URL', ''),
            'enable_webp' => env('CDN_ENABLE_WEBP', true),
            'enable_compression' => env('CDN_ENABLE_COMPRESSION', true),
            'quality' => env('CDN_IMAGE_QUALITY', 85),
        ];
    }

    /**
     * Get optimized image URL with CDN support
     */
    public function getImageUrl(string $imagePath, string $size = 'medium', bool $webp = null): string
    {
        if (empty($imagePath)) {
            return $this->getPlaceholderUrl($size);
        }

        // Use WebP if supported and enabled
        $useWebp = $webp ?? $this->config['enable_webp'];
        
        // Generate cache key
        $cacheKey = "cdn_image_url_{$imagePath}_{$size}_" . ($useWebp ? 'webp' : 'original');
        
        return Cache::remember($cacheKey, 1440, function () use ($imagePath, $size, $useWebp) {
            // If CDN is configured, use CDN URL
            if (!empty($this->config['cdn_url'])) {
                return $this->getCdnImageUrl($imagePath, $size, $useWebp);
            }
            
            // Otherwise, generate optimized local URL
            return $this->getOptimizedLocalUrl($imagePath, $size, $useWebp);
        });
    }

    /**
     * Generate responsive image srcset
     */
    public function getResponsiveImageSrcset(string $imagePath, bool $webp = null): string
    {
        if (empty($imagePath)) {
            return '';
        }

        $srcset = [];
        $useWebp = $webp ?? $this->config['enable_webp'];
        
        foreach ($this->imageSizes as $sizeName => $dimensions) {
            $url = $this->getImageUrl($imagePath, $sizeName, $useWebp);
            $srcset[] = "{$url} {$dimensions['width']}w";
        }
        
        return implode(', ', $srcset);
    }

    /**
     * Generate optimized images for different sizes
     */
    public function generateOptimizedImages(string $originalPath): array
    {
        $results = [];
        
        if (!Storage::disk('public')->exists($originalPath)) {
            return $results;
        }

        try {
            $manager = new ImageManager(new Driver());
            $originalImage = $manager->read(Storage::disk('public')->path($originalPath));
            
            foreach ($this->imageSizes as $sizeName => $dimensions) {
                // Generate regular version
                $optimizedPath = $this->generateOptimizedImage(
                    $originalImage, 
                    $originalPath, 
                    $sizeName, 
                    $dimensions,
                    false
                );
                
                if ($optimizedPath) {
                    $results[$sizeName] = $optimizedPath;
                }
                
                // Generate WebP version if enabled
                if ($this->config['enable_webp']) {
                    $webpPath = $this->generateOptimizedImage(
                        $originalImage, 
                        $originalPath, 
                        $sizeName, 
                        $dimensions,
                        true
                    );
                    
                    if ($webpPath) {
                        $results[$sizeName . '_webp'] = $webpPath;
                    }
                }
            }
            
        } catch (\Exception $e) {
            \Log::error('Failed to generate optimized images: ' . $e->getMessage());
        }
        
        return $results;
    }

    /**
     * Preload critical images
     */
    public function preloadCriticalImages(): array
    {
        $criticalImages = Cache::remember('critical_images', 60, function () {
            // Get featured products images
            $featuredProducts = \App\Models\Product::featured(6)->get();
            $images = [];
            
            foreach ($featuredProducts as $product) {
                if ($product->image_path) {
                    $images[] = [
                        'url' => $this->getImageUrl($product->image_path, 'medium'),
                        'webp_url' => $this->getImageUrl($product->image_path, 'medium', true),
                        'type' => 'image'
                    ];
                }
            }
            
            return $images;
        });
        
        return $criticalImages;
    }

    /**
     * Get image metadata for optimization
     */
    public function getImageMetadata(string $imagePath): array
    {
        $cacheKey = "image_metadata_{$imagePath}";
        
        return Cache::remember($cacheKey, 1440, function () use ($imagePath) {
            if (!Storage::disk('public')->exists($imagePath)) {
                return [];
            }
            
            try {
                $fullPath = Storage::disk('public')->path($imagePath);
                $imageInfo = getimagesize($fullPath);
                $fileSize = Storage::disk('public')->size($imagePath);
                
                return [
                    'width' => $imageInfo[0] ?? 0,
                    'height' => $imageInfo[1] ?? 0,
                    'mime_type' => $imageInfo['mime'] ?? '',
                    'file_size' => $fileSize,
                    'file_size_human' => $this->formatBytes($fileSize),
                    'aspect_ratio' => $imageInfo[0] && $imageInfo[1] ? round($imageInfo[0] / $imageInfo[1], 2) : 0,
                ];
            } catch (\Exception $e) {
                return [];
            }
        });
    }

    /**
     * Clear image cache
     */
    public function clearImageCache(string $imagePath = null): void
    {
        if ($imagePath) {
            // Clear specific image cache
            $patterns = [
                "cdn_image_url_{$imagePath}_*",
                "image_metadata_{$imagePath}",
            ];
            
            foreach ($patterns as $pattern) {
                Cache::forget($pattern);
            }
        } else {
            // Clear all image cache
            Cache::flush(); // This is a simple approach; in production, you'd want more targeted clearing
        }
    }

    /**
     * Get CDN image URL
     */
    private function getCdnImageUrl(string $imagePath, string $size, bool $webp): string
    {
        $baseUrl = rtrim($this->config['cdn_url'], '/');
        $extension = $webp ? 'webp' : pathinfo($imagePath, PATHINFO_EXTENSION);
        $filename = pathinfo($imagePath, PATHINFO_FILENAME);
        $directory = dirname($imagePath);
        
        // CDN URL format: https://cdn.example.com/images/products/filename_size.extension
        return "{$baseUrl}/{$directory}/{$filename}_{$size}.{$extension}";
    }

    /**
     * Get optimized local URL
     */
    private function getOptimizedLocalUrl(string $imagePath, string $size, bool $webp): string
    {
        $extension = $webp ? 'webp' : pathinfo($imagePath, PATHINFO_EXTENSION);
        $filename = pathinfo($imagePath, PATHINFO_FILENAME);
        $directory = dirname($imagePath);
        
        $optimizedPath = "{$directory}/optimized/{$filename}_{$size}.{$extension}";
        
        // Check if optimized version exists, if not create it
        if (!Storage::disk('public')->exists($optimizedPath)) {
            $this->generateOptimizedImageOnDemand($imagePath, $size, $webp);
        }
        
        return asset("storage/{$optimizedPath}");
    }

    /**
     * Generate optimized image on demand
     */
    private function generateOptimizedImageOnDemand(string $originalPath, string $size, bool $webp): void
    {
        if (!Storage::disk('public')->exists($originalPath)) {
            return;
        }

        try {
            $manager = new ImageManager(new Driver());
            $image = $manager->read(Storage::disk('public')->path($originalPath));
            
            $dimensions = $this->imageSizes[$size] ?? $this->imageSizes['medium'];
            
            $this->generateOptimizedImage($image, $originalPath, $size, $dimensions, $webp);
        } catch (\Exception $e) {
            \Log::error("Failed to generate optimized image on demand: {$e->getMessage()}");
        }
    }

    /**
     * Generate single optimized image
     */
    private function generateOptimizedImage($image, string $originalPath, string $sizeName, array $dimensions, bool $webp): ?string
    {
        try {
            $extension = $webp ? 'webp' : pathinfo($originalPath, PATHINFO_EXTENSION);
            $filename = pathinfo($originalPath, PATHINFO_FILENAME);
            $directory = dirname($originalPath);
            
            $optimizedPath = "{$directory}/optimized/{$filename}_{$sizeName}.{$extension}";
            
            // Create optimized directory if it doesn't exist
            $optimizedDir = dirname($optimizedPath);
            if (!Storage::disk('public')->exists($optimizedDir)) {
                Storage::disk('public')->makeDirectory($optimizedDir);
            }
            
            // Resize image
            $resized = $image->cover($dimensions['width'], $dimensions['height']);
            
            // Apply compression and save
            if ($webp) {
                $encoded = $resized->toWebp($this->config['quality']);
            } else {
                $encoded = $resized->toJpeg($this->config['quality']);
            }
            
            Storage::disk('public')->put($optimizedPath, $encoded);
            
            return $optimizedPath;
        } catch (\Exception $e) {
            \Log::error("Failed to generate optimized image: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Get placeholder image URL
     */
    private function getPlaceholderUrl(string $size): string
    {
        $dimensions = $this->imageSizes[$size] ?? $this->imageSizes['medium'];
        return "https://via.placeholder.com/{$dimensions['width']}x{$dimensions['height']}/cccccc/666666?text=No+Image";
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}