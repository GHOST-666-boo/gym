<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ProductCacheService
{
    /**
     * Advanced cache service instance
     */
    protected AdvancedCacheService $advancedCache;

    /**
     * Cache duration in minutes
     */
    const CACHE_DURATION = 60; // 1 hour

    public function __construct(AdvancedCacheService $advancedCache)
    {
        $this->advancedCache = $advancedCache;
    }

    /**
     * Get cached featured products for home page
     */
    public function getFeaturedProducts(int $limit = 6): Collection
    {
        return $this->advancedCache->getCachedComplexQuery(
            "featured_products_{$limit}",
            function () use ($limit) {
                return Product::featured($limit)->get();
            },
            AdvancedCacheService::MEDIUM_CACHE,
            [AdvancedCacheService::PRODUCT_TAG]
        );
    }

    /**
     * Get cached products with pagination and advanced filtering
     */
    public function getProductsWithPagination(int $perPage = 12, array $filters = []): LengthAwarePaginator
    {
        // Use advanced cache service for complex search results
        if (!empty($filters)) {
            $cachedResults = $this->advancedCache->getCachedSearchResults($filters, $perPage);
            
            // Convert cached results back to paginator
            return new LengthAwarePaginator(
                collect($cachedResults['products']),
                $cachedResults['pagination']['total'],
                $cachedResults['pagination']['per_page'],
                $cachedResults['pagination']['current_page'],
                [
                    'path' => request()->url(),
                    'pageName' => 'page',
                ]
            );
        }

        // Simple pagination without filters
        return Product::with(['category:id,name,slug', 'images'])->forListing()->paginate($perPage);
    }

    /**
     * Get cached product by slug with related products
     */
    public function getProductWithRelated(string $slug): array
    {
        return $this->advancedCache->getCachedComplexQuery(
            "product_with_related_{$slug}",
            function () use ($slug) {
                $product = Product::with(['category', 'images', 'primaryImage'])
                    ->where('slug', $slug)
                    ->firstOrFail();
                
                $relatedProducts = $this->advancedCache->getCachedRelatedProducts($product, 4);

                return [
                    'product' => $product,
                    'relatedProducts' => $relatedProducts
                ];
            },
            AdvancedCacheService::MEDIUM_CACHE,
            [AdvancedCacheService::PRODUCT_TAG]
        );
    }

    /**
     * Get cached products by category
     */
    public function getProductsByCategory(Category $category, int $perPage = 12): LengthAwarePaginator
    {
        // Use optimized scope for category products
        return Product::byCategory($category->id)->with('images')->paginate($perPage);
    }

    /**
     * Get cached categories with product counts
     */
    public function getCategoriesWithCounts(): Collection
    {
        return $this->advancedCache->getCachedComplexQuery(
            'categories_with_counts',
            function () {
                return Category::withCount('products')
                    ->orderBy('name')
                    ->get();
            },
            AdvancedCacheService::LONG_CACHE,
            [AdvancedCacheService::CATEGORY_TAG]
        );
    }

    /**
     * Get cached sitemap data
     */
    public function getSitemapData(): array
    {
        return [
            'products' => Product::select('slug', 'updated_at')->get(),
            'categories' => Category::select('slug', 'updated_at')->get()
        ];
    }

    /**
     * Get cached popular products
     */
    public function getPopularProducts(int $limit = 10): Collection
    {
        return $this->advancedCache->getCachedPopularProducts($limit);
    }

    /**
     * Get cached aggregations for filters
     */
    public function getAggregations(): array
    {
        return $this->advancedCache->getCachedAggregations();
    }

    /**
     * Clear all product-related caches
     */
    public function clearProductCaches(): void
    {
        $this->advancedCache->clearAllProductCaches();
        
        // Also clear full-page cache
        $this->clearFullPageCache();
    }

    /**
     * Clear full-page cache
     */
    public function clearFullPageCache(): void
    {
        $patterns = [
            'full_page_cache_*',
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }

    /**
     * Warm up essential caches
     */
    public function warmUpCaches(): void
    {
        // Use advanced cache service warm up
        $this->advancedCache->warmUpCaches();
        
        // Additional warm up for product-specific caches
        $this->getFeaturedProducts();
        $this->getCategoriesWithCounts();
        $this->getPopularProducts();
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        return $this->advancedCache->getCacheStats();
    }
}