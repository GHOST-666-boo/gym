<?php

namespace App\Observers;

use App\Models\Category;
use App\Services\ProductCacheService;

class CategoryObserver
{
    protected ProductCacheService $cacheService;

    public function __construct(ProductCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Handle the Category "created" event.
     */
    public function created(Category $category): void
    {
        $this->clearCaches();
    }

    /**
     * Handle the Category "updated" event.
     */
    public function updated(Category $category): void
    {
        $this->clearCaches();
    }

    /**
     * Handle the Category "deleted" event.
     */
    public function deleted(Category $category): void
    {
        $this->clearCaches();
    }

    /**
     * Handle the Category "restored" event.
     */
    public function restored(Category $category): void
    {
        $this->clearCaches();
    }

    /**
     * Handle the Category "force deleted" event.
     */
    public function forceDeleted(Category $category): void
    {
        $this->clearCaches();
    }

    /**
     * Clear all product-related caches
     */
    protected function clearCaches(): void
    {
        $this->cacheService->clearProductCaches();
    }
}