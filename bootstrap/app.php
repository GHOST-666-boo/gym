<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'admin.errors' => \App\Http\Middleware\HandleAdminErrors::class,
            'cache.response' => \App\Http\Middleware\CacheResponse::class,
            'full.page.cache' => \App\Http\Middleware\FullPageCacheMiddleware::class,
            'maintenance.mode' => \App\Http\Middleware\MaintenanceModeMiddleware::class,
            'settings.cache' => \App\Http\Middleware\EnsureSettingsCacheWarmed::class,
            'image.access.control' => \App\Http\Middleware\ImageAccessControlMiddleware::class,
        ]);
        
        // Apply maintenance mode and settings cache middleware to web routes
        $middleware->web(append: [
            \App\Http\Middleware\EnsureSettingsCacheWarmed::class,
            \App\Http\Middleware\MaintenanceModeMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
