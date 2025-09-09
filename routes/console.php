<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Services\WatermarkService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Register watermark cache cleanup command
Artisan::command('watermark:cleanup {--days=7 : Number of days old files to delete} {--force : Force cleanup without confirmation}', function () {
    $watermarkService = app(WatermarkService::class);
    $days = (int) $this->option('days');
    $force = $this->option('force');

    $this->info("Cleaning up watermark cache files older than {$days} days...");

    // Get cache stats before cleanup
    $statsBefore = $watermarkService->getCacheStats();
    
    if ($statsBefore['total_cached_files'] === 0) {
        $this->info('No cached files found.');
        return 0;
    }

    $this->info("Found {$statsBefore['total_cached_files']} cached files ({$statsBefore['total_cache_size_human']})");

    // Ask for confirmation unless force flag is used
    if (!$force && !$this->confirm('Do you want to proceed with cleanup?')) {
        $this->info('Cleanup cancelled.');
        return 0;
    }

    // Perform cleanup
    $deletedCount = $watermarkService->cleanupOldCachedImages($days);

    // Get cache stats after cleanup
    $statsAfter = $watermarkService->getCacheStats();

    $this->info("Cleanup completed:");
    $this->info("- Files deleted: {$deletedCount}");
    $this->info("- Files remaining: {$statsAfter['total_cached_files']}");
    $this->info("- Space remaining: {$statsAfter['total_cache_size_human']}");

    return 0;
})->purpose('Clean up old watermarked image cache files');

// Register watermark cache clear command
Artisan::command('watermark:clear {--force : Force clear without confirmation}', function () {
    $watermarkService = app(WatermarkService::class);
    $force = $this->option('force');

    // Get cache stats before clearing
    $stats = $watermarkService->getCacheStats();
    
    if ($stats['total_cached_files'] === 0) {
        $this->info('No cached files found.');
        return 0;
    }

    $this->info("Found {$stats['total_cached_files']} cached files ({$stats['total_cache_size_human']})");

    // Ask for confirmation unless force flag is used
    if (!$force && !$this->confirm('Do you want to clear all watermark cache?')) {
        $this->info('Cache clear cancelled.');
        return 0;
    }

    // Clear the cache
    $watermarkService->clearWatermarkCache();

    $this->info('Watermark cache cleared successfully.');

    return 0;
})->purpose('Clear all watermarked image cache files');

// Schedule automatic watermark cache cleanup
use Illuminate\Console\Scheduling\Schedule;

app()->booted(function () {
    $schedule = app(Schedule::class);
    
    // Daily cleanup of files older than 7 days
    $schedule->job(\App\Jobs\CleanupOldWatermarkCache::class, [7, false, 1000])
        ->daily()
        ->at('02:00')
        ->name('watermark-daily-cleanup')
        ->withoutOverlapping();
    
    // Weekly aggressive cleanup of files older than 3 days
    $schedule->job(\App\Jobs\CleanupOldWatermarkCache::class, [3, true, 5000])
        ->weekly()
        ->sundays()
        ->at('03:00')
        ->name('watermark-weekly-cleanup')
        ->withoutOverlapping();
    
    // Monthly cache optimization
    $schedule->call(function () {
        $watermarkService = app(\App\Services\WatermarkService::class);
        $stats = $watermarkService->optimizeCache();
        
        \Illuminate\Support\Facades\Log::info('Monthly watermark cache optimization completed', $stats);
    })
        ->monthly()
        ->name('watermark-monthly-optimization')
        ->withoutOverlapping();
});
