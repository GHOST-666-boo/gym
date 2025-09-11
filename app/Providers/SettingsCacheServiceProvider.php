<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Log;

class SettingsCacheServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the SettingsService as a singleton for better performance
        $this->app->singleton(SettingsService::class, function ($app) {
            return new SettingsService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Only warm cache in production or when explicitly requested
        if ($this->app->environment('production') || config('app.warm_settings_cache', false)) {
            $this->warmCacheOnBoot();
        }
    }
    
    /**
     * Warm settings cache on application boot
     */
    protected function warmCacheOnBoot(): void
    {
        try {
            $settingsService = $this->app->make(SettingsService::class);
            
            // Only warm if not already warm to avoid unnecessary work
            if (!$settingsService->isCacheWarm()) {
                $settingsService->warmCache();
                Log::info('Settings cache warmed on application boot');
            }
        } catch (\Exception $e) {
            Log::warning('Failed to warm settings cache on boot', ['error' => $e->getMessage()]);
        }
    }
}
