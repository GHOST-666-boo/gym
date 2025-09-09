<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class WatermarkSettingsService
{
    /**
     * Cache key for watermark settings
     */
    const CACHE_KEY = 'watermark_settings_all';
    
    /**
     * Cache TTL for watermark settings (1 hour)
     */
    const CACHE_TTL = 3600;

    /**
     * Valid watermark positions
     */
    const VALID_POSITIONS = [
        'top-left',
        'top-center', 
        'top-right',
        'center-left',
        'center',
        'center-right',
        'bottom-left',
        'bottom-center',
        'bottom-right'
    ];

    /**
     * Valid watermark sizes
     */
    const VALID_SIZES = ['small', 'medium', 'large'];

    /**
     * Get all watermark settings with caching
     */
    public function getAllSettings(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            $imageProtectionSettings = SiteSetting::getByGroup('image_protection');
            $watermarkSettings = SiteSetting::getByGroup('watermark');
            
            return array_merge($imageProtectionSettings->toArray(), $watermarkSettings->toArray());
        });
    }

    /**
     * Get a specific watermark setting
     */
    public function getSetting(string $key, $default = null)
    {
        $settings = $this->getAllSettings();
        return $settings[$key] ?? $default;
    }

    /**
     * Check if watermarking is enabled
     */
    public function isWatermarkEnabled(): bool
    {
        return (bool) $this->getSetting('watermark_enabled', false);
    }

    /**
     * Check if image protection is enabled
     */
    public function isImageProtectionEnabled(): bool
    {
        return (bool) $this->getSetting('image_protection_enabled', false);
    }

    /**
     * Get watermark text with fallback to site name
     */
    public function getWatermarkText(): string
    {
        $text = $this->getSetting('watermark_text');
        
        if (empty($text)) {
            $text = SiteSetting::get('site_name', 'Watermark');
        }
        
        return $text;
    }

    /**
     * Get watermark logo path
     */
    public function getWatermarkLogoPath(): ?string
    {
        $path = $this->getSetting('watermark_logo_path');
        return !empty($path) ? $path : null;
    }

    /**
     * Get watermark position
     */
    public function getWatermarkPosition(): string
    {
        return $this->getSetting('watermark_position', 'bottom-right');
    }

    /**
     * Get watermark opacity (10-90)
     */
    public function getWatermarkOpacity(): int
    {
        return (int) $this->getSetting('watermark_opacity', 50);
    }

    /**
     * Get watermark size
     */
    public function getWatermarkSize(): string
    {
        return $this->getSetting('watermark_size', 'medium');
    }

    /**
     * Get watermark text color
     */
    public function getWatermarkTextColor(): string
    {
        return $this->getSetting('watermark_text_color', '#ffffff');
    }

    /**
     * Validate watermark settings
     */
    public function validateSettings(array $settings): array
    {
        $rules = [
            'watermark_text' => 'nullable|string|max:100',
            'watermark_position' => 'required|string|in:' . implode(',', self::VALID_POSITIONS),
            'watermark_opacity' => 'required|integer|min:10|max:90',
            'watermark_size' => 'required|string|in:' . implode(',', self::VALID_SIZES),
            'watermark_text_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'watermark_logo_path' => 'nullable|string|max:255',
            'watermark_enabled' => 'boolean',
            'image_protection_enabled' => 'boolean',
            'right_click_protection' => 'boolean',
            'drag_drop_protection' => 'boolean',
            'keyboard_protection' => 'boolean',
        ];

        $messages = [
            'watermark_text.max' => 'Watermark text cannot exceed 100 characters.',
            'watermark_position.in' => 'Invalid watermark position selected.',
            'watermark_opacity.min' => 'Watermark opacity must be at least 10%.',
            'watermark_opacity.max' => 'Watermark opacity cannot exceed 90%.',
            'watermark_size.in' => 'Invalid watermark size selected.',
            'watermark_text_color.regex' => 'Watermark text color must be a valid hex color (e.g., #ffffff).',
        ];

        $validator = Validator::make($settings, $rules, $messages);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Update watermark settings with validation
     */
    public function updateSettings(array $settings): bool
    {
        $validatedSettings = $this->validateSettings($settings);
        
        foreach ($validatedSettings as $key => $value) {
            $group = $this->getSettingGroup($key);
            $type = $this->getSettingType($key);
            
            SiteSetting::set($key, $value, $type, $group);
        }

        // Clear watermark settings cache
        $this->clearCache();
        
        return true;
    }

    /**
     * Get the group for a setting key
     */
    private function getSettingGroup(string $key): string
    {
        $watermarkKeys = [
            'watermark_text',
            'watermark_logo_path',
            'watermark_position',
            'watermark_opacity',
            'watermark_size',
            'watermark_text_color'
        ];

        return in_array($key, $watermarkKeys) ? 'watermark' : 'image_protection';
    }

    /**
     * Get the type for a setting key
     */
    private function getSettingType(string $key): string
    {
        $booleanKeys = [
            'watermark_enabled',
            'image_protection_enabled',
            'right_click_protection',
            'drag_drop_protection',
            'keyboard_protection'
        ];

        $integerKeys = ['watermark_opacity'];
        $fileKeys = ['watermark_logo_path'];

        if (in_array($key, $booleanKeys)) {
            return 'boolean';
        }

        if (in_array($key, $integerKeys)) {
            return 'integer';
        }

        if (in_array($key, $fileKeys)) {
            return 'file';
        }

        return 'string';
    }

    /**
     * Clear watermark settings cache
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        
        // Also clear the SiteSetting group caches
        Cache::forget('site_settings_group_watermark');
        Cache::forget('site_settings_group_image_protection');
    }

    /**
     * Get watermark configuration for the WatermarkService
     */
    public function getWatermarkConfig(): array
    {
        return [
            'enabled' => $this->isWatermarkEnabled(),
            'text' => $this->getWatermarkText(),
            'logo_path' => $this->getWatermarkLogoPath(),
            'position' => $this->getWatermarkPosition(),
            'opacity' => $this->getWatermarkOpacity(),
            'size' => $this->getWatermarkSize(),
            'text_color' => $this->getWatermarkTextColor(),
        ];
    }

    /**
     * Get image protection configuration
     */
    public function getProtectionConfig(): array
    {
        return [
            'enabled' => $this->isImageProtectionEnabled(),
            'right_click' => (bool) $this->getSetting('right_click_protection', true),
            'drag_drop' => (bool) $this->getSetting('drag_drop_protection', true),
            'keyboard' => (bool) $this->getSetting('keyboard_protection', true),
        ];
    }

    /**
     * Check if any protection features are enabled
     */
    public function hasAnyProtectionEnabled(): bool
    {
        if (!$this->isImageProtectionEnabled()) {
            return false;
        }

        $config = $this->getProtectionConfig();
        return $config['right_click'] || $config['drag_drop'] || $config['keyboard'];
    }

    /**
     * Validate that at least one protection method is enabled when protection is on
     */
    public function validateProtectionSettings(array $settings): bool
    {
        if (!isset($settings['image_protection_enabled']) || !$settings['image_protection_enabled']) {
            return true; // No validation needed if protection is disabled
        }

        $hasProtection = ($settings['right_click_protection'] ?? false) ||
                        ($settings['drag_drop_protection'] ?? false) ||
                        ($settings['keyboard_protection'] ?? false);

        if (!$hasProtection) {
            throw ValidationException::withMessages([
                'image_protection_enabled' => 'At least one protection method must be enabled when image protection is active.'
            ]);
        }

        return true;
    }
}