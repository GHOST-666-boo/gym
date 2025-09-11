<?php

namespace App\Console\Commands;

use App\Services\AdvancedCacheService;
use App\Services\ProductCacheService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CacheManagementCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cache:manage 
                            {action : The action to perform (clear, warm, stats, clear-full-page)}
                            {--tags=* : Cache tags to target (products, categories, analytics, static)}';

    /**
     * The console command description.
     */
    protected $description = 'Manage advanced caching system for the gym machines website';

    /**
     * Advanced cache service
     */
    protected AdvancedCacheService $advancedCache;

    /**
     * Product cache service
     */
    protected ProductCacheService $productCache;

    /**
     * Create a new command instance.
     */
    public function __construct(AdvancedCacheService $advancedCache, ProductCacheService $productCache)
    {
        parent::__construct();
        $this->advancedCache = $advancedCache;
        $this->productCache = $productCache;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');
        $tags = $this->option('tags');

        switch ($action) {
            case 'clear':
                return $this->clearCache($tags);
            
            case 'warm':
                return $this->warmUpCache();
            
            case 'stats':
                return $this->showCacheStats();
            
            case 'clear-full-page':
                return $this->clearFullPageCache();
            
            default:
                $this->error("Unknown action: {$action}");
                $this->info('Available actions: clear, warm, stats, clear-full-page');
                return 1;
        }
    }

    /**
     * Clear cache by tags or all caches
     */
    private function clearCache(array $tags): int
    {
        $this->info('Clearing cache...');

        if (empty($tags)) {
            // Clear all product-related caches
            $this->productCache->clearProductCaches();
            $this->info('✓ All product caches cleared');
        } else {
            // Clear specific cache tags
            $this->advancedCache->clearCacheByTags($tags);
            $this->info('✓ Cache cleared for tags: ' . implode(', ', $tags));
        }

        return 0;
    }

    /**
     * Warm up caches
     */
    private function warmUpCache(): int
    {
        $this->info('Warming up caches...');
        
        $bar = $this->output->createProgressBar(5);
        $bar->start();

        // Warm up featured products
        $this->productCache->getFeaturedProducts(6);
        $bar->advance();

        // Warm up categories
        $this->productCache->getCategoriesWithCounts();
        $bar->advance();

        // Warm up popular products
        $this->productCache->getPopularProducts(10);
        $bar->advance();

        // Warm up aggregations
        $this->productCache->getAggregations();
        $bar->advance();

        // Warm up advanced caches
        $this->advancedCache->warmUpCaches();
        $bar->advance();

        $bar->finish();
        $this->newLine();
        $this->info('✓ Cache warm-up completed');

        return 0;
    }

    /**
     * Show cache statistics
     */
    private function showCacheStats(): int
    {
        $this->info('Cache Statistics:');
        $this->newLine();

        $stats = $this->productCache->getCacheStats();

        if (isset($stats['error'])) {
            $this->error($stats['error']);
            return 1;
        }

        $this->table(
            ['Metric', 'Value'],
            [
                ['Redis Version', $stats['redis_version']],
                ['Memory Used', $stats['used_memory']],
                ['Connected Clients', $stats['connected_clients']],
                ['Total Commands', $stats['total_commands_processed']],
                ['Cache Hit Rate', $stats['hit_rate']],
                ['Keyspace Hits', $stats['keyspace_hits']],
                ['Keyspace Misses', $stats['keyspace_misses']],
            ]
        );

        return 0;
    }

    /**
     * Clear full-page cache
     */
    private function clearFullPageCache(): int
    {
        $this->info('Clearing full-page cache...');
        
        $this->productCache->clearFullPageCache();
        
        $this->info('✓ Full-page cache cleared');

        return 0;
    }
}
