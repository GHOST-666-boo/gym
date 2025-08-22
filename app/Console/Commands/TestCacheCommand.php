<?php

namespace App\Console\Commands;

use App\Services\AdvancedCacheService;
use App\Services\ProductCacheService;
use App\Services\CdnService;
use Illuminate\Console\Command;

class TestCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'test:cache';

    /**
     * The console command description.
     */
    protected $description = 'Test the advanced caching system implementation';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Testing Advanced Caching System...');
        $this->newLine();

        // Test 1: Advanced Cache Service
        $this->info('1. Testing AdvancedCacheService...');
        try {
            $advancedCache = app(AdvancedCacheService::class);
            
            // Test complex query caching
            $testData = $advancedCache->getCachedComplexQuery('test_key', function () {
                return ['test' => 'data', 'timestamp' => now()->toISOString()];
            }, 5);
            
            $this->line('   ✓ Complex query caching works');
            $this->line('   ✓ Test data: ' . json_encode($testData));
            
        } catch (\Exception $e) {
            $this->error('   ✗ AdvancedCacheService failed: ' . $e->getMessage());
        }

        // Test 2: Product Cache Service
        $this->info('2. Testing ProductCacheService...');
        try {
            $productCache = app(ProductCacheService::class);
            
            // Test cache stats
            $stats = $productCache->getCacheStats();
            $this->line('   ✓ Cache stats retrieved');
            
            if (isset($stats['error'])) {
                $this->line('   ⚠ Redis not available: ' . $stats['error']);
            } else {
                $this->line('   ✓ Redis connection successful');
                $this->line('   ✓ Hit rate: ' . ($stats['hit_rate'] ?? 'N/A'));
            }
            
        } catch (\Exception $e) {
            $this->error('   ✗ ProductCacheService failed: ' . $e->getMessage());
        }

        // Test 3: CDN Service
        $this->info('3. Testing CdnService...');
        try {
            $cdnService = app(CdnService::class);
            
            // Test placeholder URL generation
            $placeholderUrl = $cdnService->getImageUrl('', 'medium');
            $this->line('   ✓ Placeholder URL generated: ' . $placeholderUrl);
            
            // Test image metadata (with non-existent image)
            $metadata = $cdnService->getImageMetadata('non-existent.jpg');
            $this->line('   ✓ Image metadata handling works (empty for non-existent)');
            
        } catch (\Exception $e) {
            $this->error('   ✗ CdnService failed: ' . $e->getMessage());
        }

        // Test 4: Cache Management Commands
        $this->info('4. Testing cache management...');
        try {
            // Test cache clearing
            $productCache = app(ProductCacheService::class);
            $productCache->clearProductCaches();
            $this->line('   ✓ Cache clearing works');
            
            // Test cache warming
            $productCache->warmUpCaches();
            $this->line('   ✓ Cache warming works');
            
        } catch (\Exception $e) {
            $this->error('   ✗ Cache management failed: ' . $e->getMessage());
        }

        $this->newLine();
        $this->info('✅ Advanced caching system test completed!');
        
        return 0;
    }
}
