<?php

namespace App\Console\Commands;

use App\Services\WatermarkService;
use App\Jobs\CleanupOldWatermarkCache;
use App\Jobs\BulkWatermarkRegeneration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class WatermarkCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'watermark:cache 
                            {action : The action to perform (clear|cleanup|stats|optimize|regenerate)}
                            {--days=7 : Number of days for cleanup operations}
                            {--aggressive : Use aggressive cleanup mode}
                            {--max-files=1000 : Maximum number of files to process}
                            {--batch-size=50 : Batch size for bulk operations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage watermark cache operations (clear, cleanup, stats, optimize, regenerate)';

    protected WatermarkService $watermarkService;

    /**
     * Create a new command instance.
     */
    public function __construct(WatermarkService $watermarkService)
    {
        parent::__construct();
        $this->watermarkService = $watermarkService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'clear':
                return $this->clearCache();
            case 'cleanup':
                return $this->cleanupCache();
            case 'stats':
                return $this->showStats();
            case 'optimize':
                return $this->optimizeCache();
            case 'regenerate':
                return $this->regenerateCache();
            default:
                $this->error("Unknown action: {$action}");
                $this->info('Available actions: clear, cleanup, stats, optimize, regenerate');
                return 1;
        }
    }

    /**
     * Clear all watermark cache
     */
    protected function clearCache(): int
    {
        $this->info('Clearing watermark cache...');
        
        try {
            $this->watermarkService->clearWatermarkCache();
            $this->info('✅ Watermark cache cleared successfully');
            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Failed to clear watermark cache: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Cleanup old cache files
     */
    protected function cleanupCache(): int
    {
        $days = (int) $this->option('days');
        $aggressive = $this->option('aggressive');
        $maxFiles = (int) $this->option('max-files');

        $this->info("Cleaning up watermark cache files older than {$days} days...");
        if ($aggressive) {
            $this->warn('Using aggressive cleanup mode');
        }

        try {
            // Dispatch cleanup job
            CleanupOldWatermarkCache::dispatch($days, $aggressive, $maxFiles);
            
            $this->info('✅ Cleanup job dispatched successfully');
            $this->info('Monitor the job progress in your queue worker logs');
            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Failed to dispatch cleanup job: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Show cache statistics
     */
    protected function showStats(): int
    {
        $this->info('Watermark Cache Statistics');
        $this->line('================================');

        try {
            // Get cache stats
            $cacheStats = $this->watermarkService->getCacheStats();
            
            $this->table([
                'Metric', 'Value'
            ], [
                ['Total Cached Files', $cacheStats['total_cached_files']],
                ['Total Cache Size', $cacheStats['total_cache_size_human'] ?? $this->formatBytes($cacheStats['total_cache_size'])],
                ['Cache Directory Exists', $cacheStats['cache_directory_exists'] ? 'Yes' : 'No'],
                ['Oldest Cache File', $cacheStats['oldest_cache_file'] ?? 'N/A'],
                ['Newest Cache File', $cacheStats['newest_cache_file'] ?? 'N/A'],
            ]);

            // Get performance metrics
            $performanceMetrics = $this->watermarkService->getPerformanceMetrics();
            
            if (!empty($performanceMetrics['generation'])) {
                $this->line('');
                $this->info('Performance Metrics');
                $this->line('==================');
                
                $generation = $performanceMetrics['generation'];
                $this->table([
                    'Metric', 'Value'
                ], [
                    ['Total Jobs', $generation['total_jobs'] ?? 0],
                    ['Successful Jobs', $generation['successful_jobs'] ?? 0],
                    ['Failed Jobs', $generation['failed_jobs'] ?? 0],
                    ['Success Rate', $this->calculateSuccessRate($generation)],
                    ['Average Processing Time', round($generation['average_processing_time'] ?? 0, 2) . 's'],
                    ['Last Updated', $generation['last_updated'] ?? 'N/A'],
                ]);
            }

            // Get cleanup metrics
            if (!empty($performanceMetrics['cleanup'])) {
                $this->line('');
                $this->info('Cleanup Metrics');
                $this->line('===============');
                
                $cleanup = $performanceMetrics['cleanup'];
                $this->table([
                    'Metric', 'Value'
                ], [
                    ['Total Cleanups', $cleanup['total_cleanups'] ?? 0],
                    ['Total Files Deleted', $cleanup['total_files_deleted'] ?? 0],
                    ['Total Space Freed', $this->formatBytes($cleanup['total_space_freed'] ?? 0)],
                    ['Average Processing Time', round($cleanup['average_processing_time'] ?? 0, 2) . 's'],
                    ['Last Cleanup', $cleanup['last_cleanup'] ?? 'N/A'],
                ]);
            }

            // Get system info
            if (!empty($performanceMetrics['system_info'])) {
                $this->line('');
                $this->info('System Information');
                $this->line('==================');
                
                $system = $performanceMetrics['system_info'];
                $this->table([
                    'Metric', 'Value'
                ], [
                    ['PHP Version', $system['php_version'] ?? 'Unknown'],
                    ['Memory Limit', $system['memory_limit'] ?? 'Unknown'],
                    ['Max Execution Time', $system['max_execution_time'] ?? 'Unknown'],
                    ['GD Extension', $system['extensions']['has_gd'] ?? false ? 'Available' : 'Not Available'],
                    ['Imagick Extension', $system['extensions']['has_imagick'] ?? false ? 'Available' : 'Not Available'],
                ]);

                if (!empty($system['disk_space'])) {
                    $disk = $system['disk_space'];
                    $this->line('');
                    $this->info('Disk Space');
                    $this->line('==========');
                    $this->table([
                        'Metric', 'Value'
                    ], [
                        ['Free Space', $disk['free_space'] ?? 'Unknown'],
                        ['Total Space', $disk['total_space'] ?? 'Unknown'],
                        ['Used Percentage', ($disk['used_percentage'] ?? 0) . '%'],
                    ]);
                }
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Failed to get cache statistics: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Optimize cache
     */
    protected function optimizeCache(): int
    {
        $this->info('Optimizing watermark cache...');

        try {
            $stats = $this->watermarkService->optimizeCache();
            
            $this->info('✅ Cache optimization completed');
            $this->table([
                'Metric', 'Value'
            ], [
                ['Optimized Entries', $stats['optimized_entries']],
                ['Space Saved', $this->formatBytes($stats['space_saved'])],
                ['Errors', count($stats['errors'])],
            ]);

            if (!empty($stats['errors'])) {
                $this->warn('Errors encountered during optimization:');
                foreach ($stats['errors'] as $error) {
                    $this->line("  • {$error}");
                }
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Failed to optimize cache: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Regenerate all watermarks
     */
    protected function regenerateCache(): int
    {
        $batchSize = (int) $this->option('batch-size');

        $this->warn('This will regenerate ALL watermarked images. This may take a long time.');
        
        if (!$this->confirm('Do you want to continue?')) {
            $this->info('Operation cancelled');
            return 0;
        }

        $this->info('Starting bulk watermark regeneration...');

        try {
            $newSettings = $this->watermarkService->getWatermarkSettings();
            $batchId = $this->watermarkService->triggerBulkRegeneration($newSettings);

            $this->info("✅ Bulk regeneration job dispatched successfully");
            $this->info("Batch ID: {$batchId}");
            $this->info("Monitor progress with: php artisan watermark:batch-status {$batchId}");

            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Failed to start bulk regeneration: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Calculate success rate
     */
    protected function calculateSuccessRate(array $metrics): string
    {
        $total = $metrics['total_jobs'] ?? 0;
        $successful = $metrics['successful_jobs'] ?? 0;

        if ($total === 0) {
            return 'N/A';
        }

        $rate = ($successful / $total) * 100;
        return round($rate, 2) . '%';
    }

    /**
     * Format bytes to human readable format
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        
        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }
}
