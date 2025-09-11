<?php

namespace App\Providers;

use App\Models\Category;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewComposerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Share categories with navigation views
        View::composer(['layouts.public-navigation'], function ($view) {
            $categories = Category::orderBy('name')->get();
            $view->with('categories', $categories);
        });

        // Share settings with all views
        View::composer('*', function ($view) {
            // Make settings helper functions available globally
            // This ensures settings are cached and available throughout the application
            $view->with([
                'siteName' => site_name(),
                'siteTagline' => site_tagline(),
                'siteLogo' => site_logo(),
                'siteFavicon' => site_favicon(),
                'contactInfo' => contact_info(),
                'socialMedia' => active_social_media(),
                'seoSettings' => seo_settings(),
            ]);
        });
    }
}
