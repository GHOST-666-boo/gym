<?php

namespace App\Console\Commands;

use App\Services\WatermarkService;
use Illuminate\Console\Command;

class ClearWatermarkCache extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'watermark:clear {--force : Force clear without confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Clear all watermarked image cache files';

    protected WatermarkService $watermarkService;

    public function __construct(WatermarkService $watermarkService)
    {
        parent::__construct();
        $this->watermarkService = $watermarkService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $force = $this->option('force');

        // Get cache stats before clearing
        $stats = $this->watermarkService->getCacheStats();
        
        if ($stats['total_cached_files'] === 0) {
            $this->info('No cached files found.');
            return self::SUCCESS;
        }

        $this->info("Found {$stats['total_cached_files']} cached files ({$stats['total_cache_size_human']})");

        // Ask for confirmation unless force flag is used
        if (!$force && !$this->confirm('Do you want to clear all watermark cache?')) {
            $this->info('Cache clear cancelled.');
            return self::SUCCESS;
        }

        // Clear the cache
        $this->watermarkService->clearWatermarkCache();

        $this->info('Watermark cache cleared successfully.');

        return self::SUCCESS;
    }
}