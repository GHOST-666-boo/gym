<?php

namespace App\Traits;

use App\Services\WatermarkSettingsService;

trait HasWatermarkSettings
{
    /**
     * Get the watermark settings service instance
     */
    protected function watermarkSettings(): WatermarkSettingsService
    {
        return app(WatermarkSettingsService::class);
    }

    /**
     * Check if watermarking is enabled
     */
    protected function isWatermarkEnabled(): bool
    {
        return $this->watermarkSettings()->isWatermarkEnabled();
    }

    /**
     * Check if image protection is enabled
     */
    protected function isImageProtectionEnabled(): bool
    {
        return $this->watermarkSettings()->isImageProtectionEnabled();
    }

    /**
     * Get watermark configuration for processing
     */
    protected function getWatermarkConfig(): array
    {
        return $this->watermarkSettings()->getWatermarkConfig();
    }

    /**
     * Get image protection configuration
     */
    protected function getProtectionConfig(): array
    {
        return $this->watermarkSettings()->getProtectionConfig();
    }

    /**
     * Check if any protection features are enabled
     */
    protected function hasAnyProtectionEnabled(): bool
    {
        return $this->watermarkSettings()->hasAnyProtectionEnabled();
    }
}