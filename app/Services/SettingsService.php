<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\DB;

class SettingsService
{
    /**
     * Cache TTL for settings (24 hours)
     */
    const CACHE_TTL = 86400;
    
    /**
     * Cache key prefix for settings
     */
    const CACHE_PREFIX = 'site_settings';
    
    /**
     * Get a setting value by key with enhanced caching
     */
    public function get(string $key, $default = null)
    {
        return SiteSetting::get($key, $default);
    }

    /**
     * Set a setting value
     */
    public function set(string $key, $value, string $type = 'string', string $group = 'general')
    {
        return SiteSetting::set($key, $value, $type, $group);
    }

    /**
     * Get all settings by group
     */
    public function getByGroup(string $group)
    {
        return SiteSetting::getByGroup($group);
    }

    /**
     * Update multiple settings at once with optimized caching
     */
    public function updateMultiple(array $settings)
    {
        $results = [];
        $affectedGroups = [];
        
        // Use database transaction for consistency
        DB::transaction(function () use ($settings, &$results, &$affectedGroups) {
            foreach ($settings as $key => $data) {
                $value = $data['value'] ?? $data;
                $type = $data['type'] ?? 'string';
                $group = $data['group'] ?? 'general';
                
                $results[$key] = $this->set($key, $value, $type, $group);
                $affectedGroups[] = $group;
            }
        });
        
        // Clear cache for all affected groups at once
        $this->clearGroupCaches(array_unique($affectedGroups));
        
        return $results;
    }

    /**
     * Upload logo file and update setting
     */
    public function uploadLogo(UploadedFile $file): array
    {
        try {
            // Validate logo file
            $this->validateLogoFile($file);
            
            // Get current logo path for deletion
            $currentLogo = $this->get('logo_path');
            
            // Generate unique filename with proper extension
            $extension = $this->getOptimizedExtension($file);
            $filename = 'logo_' . time() . '.' . $extension;
            
            // Ensure the logos directory exists
            Storage::disk('public')->makeDirectory('logos');
            
            // Process and optimize the image, then store it
            $this->processAndStoreLogoImage($file, $filename);
            
            // Set the storage path
            $path = 'logos/' . $filename;
            
            // Update setting
            $this->set('logo_path', $path, 'string', 'general');
            
            // Delete old logo if exists
            if ($currentLogo && Storage::disk('public')->exists($currentLogo)) {
                try {
                    Storage::disk('public')->delete($currentLogo);
                } catch (\Exception $e) {
                    Log::warning("Failed to delete old logo: {$currentLogo}", ['error' => $e->getMessage()]);
                }
            }
            
            return [
                'success' => true,
                'path' => $path,
                'url' => asset('storage/' . $path),
                'message' => 'Logo uploaded and optimized successfully.'
            ];
            
        } catch (\Exception $e) {
            Log::error('Logo upload failed', [
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'path' => null,
                'url' => null,
                'message' => $this->getUploadErrorMessage($e)
            ];
        }
    }

    /**
     * Upload favicon file and update setting
     */
    public function uploadFavicon(UploadedFile $file): array
    {
        try {
            // Validate favicon file
            $this->validateFaviconFile($file);
            
            // Get current favicon path for deletion
            $currentFavicon = $this->get('favicon_path');
            
            // Generate unique filename with proper extension
            $extension = $this->getOptimizedExtension($file, true);
            $filename = 'favicon_' . time() . '.' . $extension;
            
            // Ensure the favicons directory exists
            Storage::disk('public')->makeDirectory('favicons');
            
            // Process and optimize the favicon, then store it
            $this->processAndStoreFaviconImage($file, $filename);
            
            // Set the storage path
            $path = 'favicons/' . $filename;
            
            // Update setting
            $this->set('favicon_path', $path, 'string', 'seo');
            
            // Delete old favicon if exists
            if ($currentFavicon && Storage::disk('public')->exists($currentFavicon)) {
                try {
                    Storage::disk('public')->delete($currentFavicon);
                } catch (\Exception $e) {
                    Log::warning("Failed to delete old favicon: {$currentFavicon}", ['error' => $e->getMessage()]);
                }
            }
            
            return [
                'success' => true,
                'path' => $path,
                'url' => asset('storage/' . $path),
                'message' => 'Favicon uploaded and optimized successfully.'
            ];
            
        } catch (\Exception $e) {
            Log::error('Favicon upload failed', [
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'path' => null,
                'url' => null,
                'message' => $this->getUploadErrorMessage($e)
            ];
        }
    }

    /**
     * Upload watermark logo file and update setting
     */
    public function uploadWatermarkLogo(UploadedFile $file): array
    {
        try {
            // Validate watermark logo file
            $this->validateWatermarkLogoFile($file);
            
            // Get current watermark logo path for deletion
            $currentLogo = $this->get('watermark_logo_path');
            
            // Generate unique filename with proper extension
            $extension = $this->getOptimizedExtension($file);
            $filename = 'watermark_logo_' . time() . '.' . $extension;
            
            // Ensure the watermarks/logos directory exists
            Storage::disk('public')->makeDirectory('watermarks/logos');
            
            // Process and optimize the watermark logo, then store it
            $this->processAndStoreWatermarkLogoImage($file, $filename);
            
            // Set the storage path
            $path = 'watermarks/logos/' . $filename;
            
            // Update setting
            $this->set('watermark_logo_path', $path, 'file', 'watermark');
            
            // Delete old watermark logo if exists
            if ($currentLogo && Storage::disk('public')->exists($currentLogo)) {
                try {
                    Storage::disk('public')->delete($currentLogo);
                } catch (\Exception $e) {
                    Log::warning("Failed to delete old watermark logo: {$currentLogo}", ['error' => $e->getMessage()]);
                }
            }
            
            return [
                'success' => true,
                'path' => $path,
                'url' => asset('storage/' . $path),
                'message' => 'Watermark logo uploaded and optimized successfully.'
            ];
            
        } catch (\Exception $e) {
            Log::error('Watermark logo upload failed', [
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'path' => null,
                'url' => null,
                'message' => $this->getUploadErrorMessage($e)
            ];
        }
    }

    /**
     * Get logo URL with fallback
     */
    public function getLogoUrl(): string
    {
        $logoPath = $this->get('logo_path');
        
        if ($logoPath && Storage::disk('public')->exists($logoPath)) {
            return asset('storage/' . $logoPath);
        }
        
        // Return default logo or placeholder
        return asset('images/default-logo.png');
    }

    /**
     * Get favicon URL with fallback
     */
    public function getFaviconUrl(): string
    {
        $faviconPath = $this->get('favicon_path');
        
        if ($faviconPath && Storage::disk('public')->exists($faviconPath)) {
            return asset('storage/' . $faviconPath);
        }
        
        // Return default favicon
        return asset('favicon.ico');
    }

    /**
     * Clear all settings cache
     */
    public function clearCache(): void
    {
        SiteSetting::clearCache();
    }
    
    /**
     * Clear cache for specific groups
     */
    public function clearGroupCaches(array $groups): void
    {
        foreach ($groups as $group) {
            Cache::forget(self::CACHE_PREFIX . "_group_{$group}");
        }
    }
    
    /**
     * Warm up the settings cache by preloading all settings
     */
    public function warmCache(): array
    {
        $warmedSettings = [];
        
        // Get all settings from database
        $allSettings = SiteSetting::all();
        
        // Group settings by their group
        $groupedSettings = $allSettings->groupBy('group');
        
        foreach ($groupedSettings as $group => $settings) {
            $groupData = [];
            
            foreach ($settings as $setting) {
                // Cache individual setting
                $cacheKey = "site_setting_{$setting->key}";
                $value = SiteSetting::castValue($setting->value, $setting->type);
                Cache::put($cacheKey, $value, self::CACHE_TTL);
                
                $groupData[$setting->key] = $value;
                $warmedSettings[$setting->key] = $value;
            }
            
            // Cache group settings
            $groupCacheKey = self::CACHE_PREFIX . "_group_{$group}";
            Cache::put($groupCacheKey, collect($groupData), self::CACHE_TTL);
        }
        
        // Cache all settings together for quick access
        Cache::put(self::CACHE_PREFIX . '_all', collect($warmedSettings), self::CACHE_TTL);
        
        return $warmedSettings;
    }
    
    /**
     * Get all settings with optimized caching
     */
    public function getAll(): array
    {
        $cacheKey = self::CACHE_PREFIX . '_all';
        
        $result = Cache::remember($cacheKey, self::CACHE_TTL, function () {
            $settings = [];
            $allSettings = SiteSetting::all();
            
            foreach ($allSettings as $setting) {
                $settings[$setting->key] = SiteSetting::castValue($setting->value, $setting->type);
            }
            
            return $settings;
        });
        
        // Ensure we return an array, not a Collection
        return is_array($result) ? $result : $result->toArray();
    }
    
    /**
     * Get multiple settings by keys with single cache lookup
     */
    public function getMultiple(array $keys, $default = null): array
    {
        $results = [];
        $uncachedKeys = [];
        
        // Try to get from cache first
        foreach ($keys as $key) {
            $cacheKey = "site_setting_{$key}";
            $cached = Cache::get($cacheKey);
            
            if ($cached !== null) {
                $results[$key] = $cached;
            } else {
                $uncachedKeys[] = $key;
            }
        }
        
        // Get uncached settings from database in single query
        if (!empty($uncachedKeys)) {
            $settings = SiteSetting::whereIn('key', $uncachedKeys)->get();
            
            foreach ($settings as $setting) {
                $value = SiteSetting::castValue($setting->value, $setting->type);
                $results[$setting->key] = $value;
                
                // Cache for future use
                Cache::put("site_setting_{$setting->key}", $value, self::CACHE_TTL);
            }
            
            // Set default for missing keys
            foreach ($uncachedKeys as $key) {
                if (!isset($results[$key])) {
                    $results[$key] = $default;
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Check if cache is warm (has been preloaded)
     */
    public function isCacheWarm(): bool
    {
        return Cache::has(self::CACHE_PREFIX . '_all');
    }
    
    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        $stats = [
            'is_warm' => $this->isCacheWarm(),
            'cached_settings' => 0,
            'cached_groups' => 0,
            'cache_driver' => config('cache.default'),
        ];
        
        // Count cached individual settings
        $allSettings = SiteSetting::pluck('key');
        foreach ($allSettings as $key) {
            if (Cache::has("site_setting_{$key}")) {
                $stats['cached_settings']++;
            }
        }
        
        // Count cached groups
        $groups = SiteSetting::distinct('group')->pluck('group');
        foreach ($groups as $group) {
            if (Cache::has(self::CACHE_PREFIX . "_group_{$group}")) {
                $stats['cached_groups']++;
            }
        }
        
        return $stats;
    }

    /**
     * Get all settings grouped by category
     */
    public function getAllGrouped(): array
    {
        $groups = ['general', 'contact', 'social', 'seo', 'image_protection', 'watermark', 'advanced'];
        $result = [];
        
        foreach ($groups as $group) {
            $result[$group] = $this->getByGroup($group);
        }
        
        return $result;
    }

    /**
     * Process and store logo image
     */
    protected function processAndStoreLogoImage(UploadedFile $file, string $filename): void
    {
        try {
            // Handle SVG files separately (no processing needed)
            if ($file->getMimeType() === 'image/svg+xml') {
                Storage::disk('public')->putFileAs('logos', $file, $filename);
                return;
            }
            
            // Create image manager instance
            $manager = new ImageManager(new Driver());
            
            // Load and process the image
            $image = $manager->read($file->getPathname());
            
            // Resize if too large (max 400px width, maintain aspect ratio)
            if ($image->width() > 400) {
                $image->scaleDown(width: 400);
            }
            
            // Get the file extension to determine output format
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            
            // Encode the image based on file type
            switch (strtolower($extension)) {
                case 'jpg':
                case 'jpeg':
                    $encodedImage = $image->toJpeg(quality: 85);
                    break;
                case 'png':
                    $encodedImage = $image->toPng();
                    break;
                case 'webp':
                    $encodedImage = $image->toWebp(quality: 85);
                    break;
                case 'gif':
                    $encodedImage = $image->toGif();
                    break;
                default:
                    $encodedImage = $image->toPng(); // Default to PNG
            }
            
            // Store the processed image
            Storage::disk('public')->put('logos/' . $filename, $encodedImage);
            
        } catch (\Exception $e) {
            // If image processing fails, just store the original file
            Log::warning('Image processing failed, storing original file', [
                'error' => $e->getMessage(),
                'file' => $file->getClientOriginalName()
            ]);
            
            Storage::disk('public')->putFileAs('logos', $file, $filename);
        }
    }

    /**
     * Process and store favicon image
     */
    protected function processAndStoreFaviconImage(UploadedFile $file, string $filename): void
    {
        try {
            // Handle ICO files separately (no processing needed)
            if (in_array($file->getMimeType(), ['image/x-icon', 'image/vnd.microsoft.icon'])) {
                Storage::disk('public')->putFileAs('favicons', $file, $filename);
                return;
            }
            
            // Create image manager instance
            $manager = new ImageManager(new Driver());
            
            // Load and process the image
            $image = $manager->read($file->getPathname());
            
            // Resize to standard favicon sizes (32x32 is most common)
            $image->resize(32, 32);
            
            // Convert to PNG for better web compatibility
            $encodedImage = $image->toPng();
            
            // Store the processed image
            Storage::disk('public')->put('favicons/' . $filename, $encodedImage);
            
        } catch (\Exception $e) {
            // If image processing fails, just store the original file
            Log::warning('Favicon processing failed, storing original file', [
                'error' => $e->getMessage(),
                'file' => $file->getClientOriginalName()
            ]);
            
            Storage::disk('public')->putFileAs('favicons', $file, $filename);
        }
    }

    /**
     * Process and store watermark logo image
     */
    protected function processAndStoreWatermarkLogoImage(UploadedFile $file, string $filename): void
    {
        try {
            // Handle SVG files separately (no processing needed)
            if ($file->getMimeType() === 'image/svg+xml') {
                Storage::disk('public')->putFileAs('watermarks/logos', $file, $filename);
                return;
            }
            
            // Create image manager instance
            $manager = new ImageManager(new Driver());
            
            // Load and process the image
            $image = $manager->read($file->getPathname());
            
            // Resize if too large (max 200px width/height, maintain aspect ratio)
            if ($image->width() > 200 || $image->height() > 200) {
                $image->scaleDown(width: 200, height: 200);
            }
            
            // Get the file extension to determine output format
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            
            // Encode the image based on file type, preserving transparency for PNG
            switch (strtolower($extension)) {
                case 'jpg':
                case 'jpeg':
                    $encodedImage = $image->toJpeg(quality: 90);
                    break;
                case 'png':
                    $encodedImage = $image->toPng();
                    break;
                case 'webp':
                    $encodedImage = $image->toWebp(quality: 90);
                    break;
                case 'gif':
                    $encodedImage = $image->toGif();
                    break;
                default:
                    $encodedImage = $image->toPng(); // Default to PNG to preserve transparency
            }
            
            // Store the processed image
            Storage::disk('public')->put('watermarks/logos/' . $filename, $encodedImage);
            
        } catch (\Exception $e) {
            // If image processing fails, just store the original file
            Log::warning('Watermark logo processing failed, storing original file', [
                'error' => $e->getMessage(),
                'file' => $file->getClientOriginalName()
            ]);
            
            Storage::disk('public')->putFileAs('watermarks/logos', $file, $filename);
        }
    }

    /**
     * Get optimized file extension based on input file
     */
    protected function getOptimizedExtension(UploadedFile $file, bool $isFavicon = false): string
    {
        $mimeType = $file->getMimeType();
        
        // For favicons, prefer PNG unless it's already ICO
        if ($isFavicon) {
            if (in_array($mimeType, ['image/x-icon', 'image/vnd.microsoft.icon'])) {
                return 'ico';
            }
            return 'png';
        }
        
        // For logos, keep original format or optimize
        switch ($mimeType) {
            case 'image/jpeg':
                return 'jpg';
            case 'image/png':
                return 'png';
            case 'image/gif':
                return 'gif';
            case 'image/webp':
                return 'webp';
            case 'image/svg+xml':
                return 'svg';
            default:
                return 'png'; // Default to PNG
        }
    }

    /**
     * Validate logo file
     */
    protected function validateLogoFile(UploadedFile $file): void
    {
        // Check if file was uploaded successfully
        if (!$file->isValid()) {
            throw new \Exception('File upload failed. Please try again.');
        }
        
        // Check file size (max 5MB for logos)
        if ($file->getSize() > 5 * 1024 * 1024) {
            throw new \Exception('Logo file size exceeds 5MB limit.');
        }
        
        // Check file type
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new \Exception('Invalid logo file type. Only JPEG, PNG, GIF, WebP, and SVG images are allowed.');
        }
        
        // Check file extension matches MIME type
        $extension = strtolower($file->getClientOriginalExtension());
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        if (!in_array($extension, $allowedExtensions)) {
            throw new \Exception('Invalid file extension. Only JPG, PNG, GIF, WebP, and SVG files are allowed.');
        }
        
        // For non-SVG files, validate as actual images
        if ($file->getMimeType() !== 'image/svg+xml') {
            $imageInfo = getimagesize($file->getPathname());
            if ($imageInfo === false) {
                throw new \Exception('Invalid logo image file. File appears to be corrupted.');
            }
            
            // Check image dimensions (reasonable logo size)
            if ($imageInfo[0] > 2000 || $imageInfo[1] > 2000) {
                throw new \Exception('Logo dimensions too large. Maximum size is 2000x2000 pixels.');
            }
            
            // Check minimum dimensions
            if ($imageInfo[0] < 50 || $imageInfo[1] < 50) {
                throw new \Exception('Logo dimensions too small. Minimum size is 50x50 pixels.');
            }
        }
        
        // Validate SVG files for security
        if ($file->getMimeType() === 'image/svg+xml') {
            $this->validateSvgFile($file);
        }
    }

    /**
     * Validate favicon file
     */
    protected function validateFaviconFile(UploadedFile $file): void
    {
        // Check if file was uploaded successfully
        if (!$file->isValid()) {
            throw new \Exception('File upload failed. Please try again.');
        }
        
        // Check file size (max 2MB for favicons)
        if ($file->getSize() > 2 * 1024 * 1024) {
            throw new \Exception('Favicon file size exceeds 2MB limit.');
        }
        
        // Check file type
        $allowedMimes = ['image/x-icon', 'image/vnd.microsoft.icon', 'image/png', 'image/jpeg', 'image/gif'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new \Exception('Invalid favicon file type. Only ICO, PNG, JPEG, and GIF files are allowed.');
        }
        
        // Check file extension
        $extension = strtolower($file->getClientOriginalExtension());
        $allowedExtensions = ['ico', 'png', 'jpg', 'jpeg', 'gif'];
        if (!in_array($extension, $allowedExtensions)) {
            throw new \Exception('Invalid file extension. Only ICO, PNG, JPG, and GIF files are allowed.');
        }
        
        // For image files (not ICO), validate dimensions
        if (!in_array($file->getMimeType(), ['image/x-icon', 'image/vnd.microsoft.icon'])) {
            $imageInfo = getimagesize($file->getPathname());
            if ($imageInfo === false) {
                throw new \Exception('Invalid favicon image file. File appears to be corrupted.');
            }
            
            // Favicon should be square and reasonably sized
            if ($imageInfo[0] !== $imageInfo[1]) {
                throw new \Exception('Favicon should be square (equal width and height).');
            }
            
            if ($imageInfo[0] > 512 || $imageInfo[0] < 16) {
                throw new \Exception('Favicon size should be between 16x16 and 512x512 pixels.');
            }
        }
    }

    /**
     * Validate watermark logo file
     */
    protected function validateWatermarkLogoFile(UploadedFile $file): void
    {
        // Check if file was uploaded successfully
        if (!$file->isValid()) {
            throw new \Exception('File upload failed. Please try again.');
        }
        
        // Check file size (max 2MB for watermark logos)
        if ($file->getSize() > 2 * 1024 * 1024) {
            throw new \Exception('Watermark logo file size exceeds 2MB limit.');
        }
        
        // Check file type
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new \Exception('Invalid watermark logo file type. Only JPEG, PNG, GIF, WebP, and SVG images are allowed.');
        }
        
        // Check file extension matches MIME type
        $extension = strtolower($file->getClientOriginalExtension());
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        if (!in_array($extension, $allowedExtensions)) {
            throw new \Exception('Invalid file extension. Only JPG, PNG, GIF, WebP, and SVG files are allowed.');
        }
        
        // For non-SVG files, validate as actual images
        if ($file->getMimeType() !== 'image/svg+xml') {
            $imageInfo = getimagesize($file->getPathname());
            if ($imageInfo === false) {
                throw new \Exception('Invalid watermark logo image file. File appears to be corrupted.');
            }
            
            // Check image dimensions (reasonable watermark logo size)
            if ($imageInfo[0] > 500 || $imageInfo[1] > 500) {
                throw new \Exception('Watermark logo dimensions too large. Maximum size is 500x500 pixels.');
            }
            
            // Check minimum dimensions
            if ($imageInfo[0] < 20 || $imageInfo[1] < 20) {
                throw new \Exception('Watermark logo dimensions too small. Minimum size is 20x20 pixels.');
            }
        }
        
        // Validate SVG files for security
        if ($file->getMimeType() === 'image/svg+xml') {
            $this->validateSvgFile($file);
        }
    }

    /**
     * Validate SVG file for security
     */
    protected function validateSvgFile(UploadedFile $file): void
    {
        $content = file_get_contents($file->getPathname());
        
        // Check for potentially dangerous elements/attributes
        $dangerousPatterns = [
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
            '/javascript:/i',
            '/on\w+\s*=/i', // onclick, onload, etc.
            '/<iframe\b/i',
            '/<object\b/i',
            '/<embed\b/i',
            '/<link\b/i',
            '/<meta\b/i',
        ];
        
        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                throw new \Exception('SVG file contains potentially unsafe content and cannot be uploaded.');
            }
        }
        
        // Validate that it's actually an SVG
        if (!str_contains($content, '<svg') || !str_contains($content, '</svg>')) {
            throw new \Exception('Invalid SVG file format.');
        }
    }

    /**
     * Update watermark settings and trigger regeneration if needed
     */
    public function updateWatermarkSettings(array $settings): array
    {
        try {
            // Get current watermark settings for comparison
            $oldSettings = $this->getByGroup('watermark');
            
            // Update the settings
            $results = [];
            foreach ($settings as $key => $value) {
                $results[$key] = $this->set($key, $value, 'string', 'watermark');
            }
            
            // Check if watermark appearance settings changed (requiring regeneration)
            $regenerationTriggers = [
                'watermark_text',
                'watermark_opacity', 
                'watermark_position',
                'watermark_size',
                'watermark_text_color',
                'watermark_logo_path',
                'watermark_logo_size'
            ];
            
            $needsRegeneration = false;
            foreach ($regenerationTriggers as $trigger) {
                if (isset($settings[$trigger]) && 
                    ($oldSettings[$trigger] ?? null) !== $settings[$trigger]) {
                    $needsRegeneration = true;
                    break;
                }
            }
            
            // Trigger bulk regeneration if needed
            $batchId = null;
            if ($needsRegeneration && ($settings['watermark_enabled'] ?? false)) {
                try {
                    $watermarkService = app(\App\Services\WatermarkService::class);
                    $batchId = $watermarkService->triggerBulkRegeneration($settings, $oldSettings);
                    
                    Log::info('Bulk watermark regeneration triggered from settings update', [
                        'batch_id' => $batchId,
                        'changed_settings' => array_keys($settings)
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to trigger bulk watermark regeneration', [
                        'error' => $e->getMessage(),
                        'settings' => $settings
                    ]);
                }
            }
            
            return [
                'success' => true,
                'updated_settings' => $results,
                'regeneration_triggered' => $needsRegeneration,
                'batch_id' => $batchId,
                'message' => $needsRegeneration 
                    ? 'Settings updated successfully. Watermark regeneration has been queued.'
                    : 'Settings updated successfully.'
            ];
            
        } catch (\Exception $e) {
            Log::error('Failed to update watermark settings', [
                'error' => $e->getMessage(),
                'settings' => $settings
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to update watermark settings: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get user-friendly error message based on exception
     */
    protected function getUploadErrorMessage(\Exception $e): string
    {
        $message = $e->getMessage();
        
        // Return specific validation messages
        if (str_contains($message, 'size exceeds') || 
            str_contains($message, 'Invalid') || 
            str_contains($message, 'dimensions') ||
            str_contains($message, 'should be square') ||
            str_contains($message, 'too large') ||
            str_contains($message, 'too small') ||
            str_contains($message, 'corrupted') ||
            str_contains($message, 'unsafe content') ||
            str_contains($message, 'upload failed')) {
            return $message;
        }
        
        // Generic error for other issues
        return 'Failed to upload file. Please try again with a different file.';
    }
}