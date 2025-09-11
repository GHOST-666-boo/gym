<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SettingsService;

class ClearSettingsCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'settings:cache-clear 
                            {--warm : Warm cache after clearing}
                            {--stats : Show cache statistics}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all settings cache';

    /**
     * Execute the console command.
     */
    public function handle(SettingsService $settingsService)
    {
        $this->info('Clearing settings cache...');
        
        $startTime = microtime(true);
        $settingsService->clearCache();
        $endTime = microtime(true);
        
        $duration = round(($endTime - $startTime) * 1000, 2);
        
        $this->info("✅ Settings cache cleared in {$duration}ms");
        
        // Warm cache if requested
        if ($this->option('warm')) {
            $this->info('Warming cache...');
            $warmedSettings = $settingsService->warmCache();
            $this->info("✅ Cache warmed with " . count($warmedSettings) . " settings");
        }
        
        // Show statistics if requested
        if ($this->option('stats')) {
            $this->showCacheStats($settingsService);
        }
        
        return Command::SUCCESS;
    }
    
    /**
     * Display cache statistics
     */
    protected function showCacheStats(SettingsService $settingsService): void
    {
        $stats = $settingsService->getCacheStats();
        
        $this->newLine();
        $this->info('📊 Cache Statistics:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Cache Driver', $stats['cache_driver']],
                ['Cache Warm', $stats['is_warm'] ? '✅ Yes' : '❌ No'],
                ['Cached Settings', $stats['cached_settings']],
                ['Cached Groups', $stats['cached_groups']],
            ]
        );
    }
}
