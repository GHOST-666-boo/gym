<?php

namespace App\Listeners;

use App\Services\WatermarkService;
use Illuminate\Support\Facades\Log;

class InvalidateWatermarkCache
{
    protected WatermarkService $watermarkService;

    public function __construct(WatermarkService $watermarkService)
    {
        $this->watermarkService = $watermarkService;
    }

    /**
     * Handle the event when watermark settings change
     */
    public function handle($event): void
    {
        // Check if the changed setting is watermark-related
        if ($this->isWatermarkSetting($event)) {
            Log::info('Watermark setting changed, invalidating cache', [
                'setting_key' => $event->key ?? 'unknown',
                'setting_group' => $event->group ?? 'unknown'
            ]);
            
            $this->watermarkService->invalidateCacheOnSettingsChange();
        }
    }

    /**
     * Check if the setting is watermark-related
     */
    protected function isWatermarkSetting($event): bool
    {
        // Handle different event types
        $key = null;
        $group = null;
        
        if (is_object($event)) {
            $key = $event->key ?? null;
            $group = $event->group ?? null;
        } elseif (is_array($event)) {
            $key = $event['key'] ?? null;
            $group = $event['group'] ?? null;
        }
        
        // Check if it's a watermark-related setting
        $watermarkKeys = [
            'watermark_enabled',
            'watermark_text',
            'watermark_opacity',
            'watermark_position',
            'watermark_size',
            'watermark_text_color',
            'watermark_logo_path',
            'watermark_logo_size',
        ];
        
        // Check by key
        if ($key && in_array($key, $watermarkKeys)) {
            return true;
        }
        
        // Check by group
        if ($group && in_array($group, ['watermark', 'image_protection'])) {
            return true;
        }
        
        return false;
    }
}