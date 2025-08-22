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
    }
}
