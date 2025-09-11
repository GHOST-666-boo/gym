<?php

namespace App\Providers;

use App\Models\Product;
use App\Models\Category;
use App\Observers\ProductObserver;
use App\Observers\CategoryObserver;
use App\Services\SettingsService;
use App\Services\WatermarkSettingsService;
use App\Listeners\InvalidateWatermarkCache;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Event;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register SettingsService as singleton
        $this->app->singleton(SettingsService::class, function ($app) {
            return new SettingsService();
        });

        // Register WatermarkSettingsService as singleton
        $this->app->singleton(WatermarkSettingsService::class, function ($app) {
            return new WatermarkSettingsService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register model observers for cache invalidation
        Product::observe(ProductObserver::class);
        Category::observe(CategoryObserver::class);
        
        // Register event listeners for watermark cache invalidation
        Event::listen('site_setting.saved', InvalidateWatermarkCache::class);
        Event::listen('site_setting.deleted', InvalidateWatermarkCache::class);
    }
}
