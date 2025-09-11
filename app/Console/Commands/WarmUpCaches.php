<?php

namespace App\Console\Commands;

use App\Services\ProductCacheService;
use Illuminate\Console\Command;

class WarmUpCaches extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:warm-up {--clear : Clear existing caches before warming up}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Warm up application caches for better performance';

    protected ProductCacheService $cacheService;

    public function __construct(ProductCacheService $cacheService)
    {
        parent::__construct();
        $this->cacheService = $cacheService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting cache warm-up process...');

        if ($this->option('clear')) {
            $this->info('Clearing existing caches...');
            $this->cacheService->clearProductCaches();
        }

        $this->info('Warming up product caches...');
        
        try {
            // Warm up essential caches
            $this->cacheService->warmUpCaches();
            
            $this->info('✓ Featured products cache warmed up');
            $this->info('✓ Categories cache warmed up');
            $this->info('✓ Sitemap cache warmed up');
            
            $this->newLine();
            $this->info('Cache warm-up completed successfully!');
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Cache warm-up failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}