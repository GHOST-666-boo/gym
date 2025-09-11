<?php

namespace App\Helpers;

class ImageHelper
{
    /**
     * Generate responsive image attributes for lazy loading
     */
    public static function lazyImageAttributes(string $imagePath, string $alt, array $options = []): array
    {
        $baseUrl = asset('storage/' . $imagePath);
        
        // Default options
        $defaults = [
            'loading' => 'lazy',
            'decoding' => 'async',
            'class' => '',
            'placeholder' => self::getPlaceholderImage($options['width'] ?? 300, $options['height'] ?? 200)
        ];
        
        $options = array_merge($defaults, $options);
        
        return [
            'src' => $options['placeholder'],
            'data-src' => $baseUrl,
            'alt' => $alt,
            'loading' => $options['loading'],
            'decoding' => $options['decoding'],
            'class' => trim($options['class'] . ' lazy-image'),
            'onerror' => "this.src='{$options['placeholder']}'"
        ];
    }

    /**
     * Generate a placeholder image SVG
     */
    public static function getPlaceholderImage(int $width = 300, int $height = 200): string
    {
        $svg = '<svg width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $width . ' ' . $height . '" fill="none" xmlns="http://www.w3.org/2000/svg">';
        $svg .= '<rect width="' . $width . '" height="' . $height . '" fill="#F3F4F6"/>';
        $svg .= '<path d="M' . ($width/2 - 25) . ' ' . ($height/2 - 20) . 'H' . ($width/2 + 25) . 'V' . ($height/2 + 20) . 'H' . ($width/2 - 25) . 'V' . ($height/2 - 20) . 'Z" fill="#9CA3AF"/>';
        $svg .= '</svg>';
        
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * Generate optimized image URL with quality and size parameters
     */
    public static function optimizedImageUrl(string $imagePath, array $options = []): string
    {
        $baseUrl = asset('storage/' . $imagePath);
        
        // For future implementation with image optimization service
        // This could be extended to use services like Cloudinary, ImageKit, etc.
        
        return $baseUrl;
    }

    /**
     * Check if image exists and return appropriate src
     */
    public static function getImageSrc(?string $imagePath, array $options = []): string
    {
        if (!$imagePath || !file_exists(storage_path('app/public/' . $imagePath))) {
            return self::getPlaceholderImage($options['width'] ?? 300, $options['height'] ?? 200);
        }
        
        return asset('storage/' . $imagePath);
    }
}