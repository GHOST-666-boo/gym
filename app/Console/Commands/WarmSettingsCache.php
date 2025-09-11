<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SettingsService;

class WarmSettingsCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'settings:cache-warm 
                            {--clear : Clear existing cache before warming}
                            {--stats : Show cache statistics after warming}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Warm up the settings cache by preloading all settings';

    /**
     * Execute the console command.
     */
    public function handle(SettingsService $settingsService)
    {
        $this->info('Starting settings cache warming...');
        
        // Clear existing cache if requested
        if ($this->option('clear')) {
            $this->info('Clearing existing cache...');
            $settingsService->clearCache();
        }
        
        // Warm the cache
        $startTime = microtime(true);
        $warmedSettings = $settingsService->warmCache();
        $endTime = microtime(true);
        
        $duration = round(($endTime - $startTime) * 1000, 2);
        $count = count($warmedSettings);
        
        $this->info("✅ Successfully warmed cache with {$count} settings in {$duration}ms");
        
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
