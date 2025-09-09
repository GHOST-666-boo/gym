<?php

namespace App\Console\Commands;

use App\Services\WatermarkService;
use Illuminate\Console\Command;

class CleanupWatermarkCache extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'watermark:cleanup {--days=7 : Number of days old files to delete} {--force : Force cleanup without confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Clean up old watermarked image cache files';

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
        $days = (int) $this->option('days');
        $force = $this->option('force');

        $this->info("Cleaning up watermark cache files older than {$days} days...");

        // Get cache stats before cleanup
        $statsBefore = $this->watermarkService->getCacheStats();
        
        if ($statsBefore['total_cached_files'] === 0) {
            $this->info('No cached files found.');
            return self::SUCCESS;
        }

        $this->info("Found {$statsBefore['total_cached_files']} cached files ({$statsBefore['total_cache_size_human']})");

        // Ask for confirmation unless force flag is used
        if (!$force && !$this->confirm('Do you want to proceed with cleanup?')) {
            $this->info('Cleanup cancelled.');
            return self::SUCCESS;
        }

        // Perform cleanup
        $deletedCount = $this->watermarkService->cleanupOldCachedImages($days);

        // Get cache stats after cleanup
        $statsAfter = $this->watermarkService->getCacheStats();

        $this->info("Cleanup completed:");
        $this->info("- Files deleted: {$deletedCount}");
        $this->info("- Files remaining: {$statsAfter['total_cached_files']}");
        $this->info("- Space remaining: {$statsAfter['total_cache_size_human']}");

        return self::SUCCESS;
    }
}