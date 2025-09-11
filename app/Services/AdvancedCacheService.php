<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class AdvancedCacheService
{
    /**
     * Cache durations in minutes
     */
    const SHORT_CACHE = 15;      // 15 minutes for frequently changing data
    const MEDIUM_CACHE = 60;     // 1 hour for moderately changing data
    const LONG_CACHE = 1440;     // 24 hours for rarely changing data
    const STATIC_CACHE = 10080;  // 1 week for static content

    /**
     * Cache tags for organized cache management
     */
    const PRODUCT_TAG = 'products';
    const CATEGORY_TAG = 'categories';
    const ANALYTICS_TAG = 'analytics';
    const STATIC_TAG = 'static';

    /**
     * Get cached complex query results with Redis optimization
     */
    public function getCachedComplexQuery(string $key, callable $callback, int $duration = self::MEDIUM_CACHE, array $tags = []): mixed
    {
        // Use Redis for complex queries with compression
        if ($this->isRedisAvailable()) {
            return $this->getFromRedisWithCompression($key, $callback, $duration, $tags);
        }

        // Fallback to regular cache
        return Cache::remember($key, $duration, $callback);
    }

    /**
     * Cache product search results with advanced filtering
     */
    public function getCachedSearchResults(array $filters, int $perPage = 12): array
    {
        $cacheKey = $this->generateSearchCacheKey($filters, $perPage);
        
        return $this->getCachedComplexQuery($cacheKey, function () use ($filters, $perPage) {
            $query = Product::with(['category:id,name,slug', 'images'])->forListing();

            // Apply filters
            if (!empty($filters['search'])) {
                $query->search($filters['search']);
            }

            if (!empty($filters['category'])) {
                $query->filterByCategory($filters['category']);
            }

            if (!empty($filters['min_price']) || !empty($filters['max_price'])) {
                $query->priceRange($filters['min_price'] ?? null, $filters['max_price'] ?? null);
            }

            if (!empty($filters['sort_by'])) {
                $query->sortBy($filters['sort_by'], $filters['sort_direction'] ?? 'asc');
            }

            $products = $query->paginate($perPage);
            
            return [
                'products' => $products->items(),
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'from' => $products->firstItem(),
                    'to' => $products->lastItem(),
                ]
            ];
        }, self::SHORT_CACHE, [self::PRODUCT_TAG]);
    }

    /**
     * Cache product aggregations (price ranges, category counts, etc.)
     */
    public function getCachedAggregations(): array
    {
        return $this->getCachedComplexQuery('product_aggregations', function () {
            // Get price range
            $priceRange = Product::selectRaw('MIN(price) as min_price, MAX(price) as max_price')->first();
            
            // Get category counts
            $categoryCounts = Category::withCount('products')->get();
            
            // Get product count by price ranges
            $priceRanges = [
                '0-500' => Product::whereBetween('price', [0, 500])->count(),
                '500-1000' => Product::whereBetween('price', [500, 1000])->count(),
                '1000-2000' => Product::whereBetween('price', [1000, 2000])->count(),
                '2000+' => Product::where('price', '>', 2000)->count(),
            ];

            return [
                'price_range' => [
                    'min' => $priceRange->min_price ? floor($priceRange->min_price) : 0,
                    'max' => $priceRange->max_price ? ceil($priceRange->max_price) : 10000,
                ],
                'category_counts' => $categoryCounts,
                'price_ranges' => $priceRanges,
                'total_products' => Product::count(),
            ];
        }, self::LONG_CACHE, [self::PRODUCT_TAG, self::CATEGORY_TAG]);
    }

    /**
     * Cache popular products based on views
     */
    public function getCachedPopularProducts(int $limit = 10): Collection
    {
        return $this->getCachedComplexQuery("popular_products_{$limit}", function () use ($limit) {
            // Check if product_views table exists
            if (!Schema::hasTable('product_views')) {
                // Fallback to latest products if no views table
                return Product::with('category:id,name,slug')
                    ->forListing()
                    ->latest()
                    ->take($limit)
                    ->get();
            }
            
            return Product::select([
                    'products.id',
                    'products.name', 
                    'products.slug', 
                    'products.price', 
                    'products.short_description', 
                    'products.image_path', 
                    'products.category_id', 
                    'products.created_at'
                ])
                ->leftJoin('product_views', 'products.id', '=', 'product_views.product_id')
                ->selectRaw('COALESCE(COUNT(product_views.id), 0) as total_views')
                ->groupBy([
                    'products.id',
                    'products.name', 
                    'products.slug', 
                    'products.price', 
                    'products.short_description', 
                    'products.image_path', 
                    'products.category_id', 
                    'products.created_at'
                ])
                ->orderByDesc('total_views')
                ->with('category:id,name,slug')
                ->take($limit)
                ->get();
        }, self::MEDIUM_CACHE, [self::PRODUCT_TAG, self::ANALYTICS_TAG]);
    }

    /**
     * Cache related products with advanced similarity
     */
    public function getCachedRelatedProducts(Product $product, int $limit = 4): Collection
    {
        $cacheKey = "related_products_{$product->id}_{$limit}";
        
        return $this->getCachedComplexQuery($cacheKey, function () use ($product, $limit) {
            // First try same category
            $related = Product::where('category_id', $product->category_id)
                ->where('id', '!=', $product->id)
                ->with('category:id,name,slug')
                ->forListing()
                ->take($limit)
                ->get();

            // If not enough products in same category, get from other categories
            if ($related->count() < $limit) {
                $remaining = $limit - $related->count();
                $additional = Product::where('category_id', '!=', $product->category_id)
                    ->where('id', '!=', $product->id)
                    ->with('category:id,name,slug')
                    ->forListing()
                    ->take($remaining)
                    ->get();
                
                $related = $related->merge($additional);
            }

            return $related;
        }, self::MEDIUM_CACHE, [self::PRODUCT_TAG]);
    }

    /**
     * Implement full-page caching for static content
     */
    public function getCachedStaticPage(string $page, callable $callback): string
    {
        $cacheKey = "static_page_{$page}";
        
        return $this->getCachedComplexQuery($cacheKey, $callback, self::STATIC_CACHE, [self::STATIC_TAG]);
    }

    /**
     * Cache sitemap with compression
     */
    public function getCachedSitemap(): string
    {
        return $this->getCachedComplexQuery('sitemap_xml', function () {
            $products = Product::select('slug', 'updated_at')->get();
            $categories = Category::select('slug', 'updated_at')->get();
            
            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
            
            // Add home page
            $xml .= '<url><loc>' . url('/') . '</loc><changefreq>weekly</changefreq><priority>1.0</priority></url>' . "\n";
            
            // Add products page
            $xml .= '<url><loc>' . url('/products') . '</loc><changefreq>daily</changefreq><priority>0.8</priority></url>' . "\n";
            
            // Add contact page
            $xml .= '<url><loc>' . url('/contact') . '</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>' . "\n";
            
            // Add category pages
            foreach ($categories as $category) {
                $xml .= '<url><loc>' . url("/category/{$category->slug}") . '</loc>';
                $xml .= '<lastmod>' . $category->updated_at->toAtomString() . '</lastmod>';
                $xml .= '<changefreq>weekly</changefreq><priority>0.7</priority></url>' . "\n";
            }
            
            // Add product pages
            foreach ($products as $product) {
                $xml .= '<url><loc>' . url("/products/{$product->slug}") . '</loc>';
                $xml .= '<lastmod>' . $product->updated_at->toAtomString() . '</lastmod>';
                $xml .= '<changefreq>weekly</changefreq><priority>0.6</priority></url>' . "\n";
            }
            
            $xml .= '</urlset>';
            
            return $xml;
        }, self::STATIC_CACHE, [self::STATIC_TAG]);
    }

    /**
     * Clear caches by tags
     */
    public function clearCacheByTags(array $tags): void
    {
        if ($this->isRedisAvailable() && method_exists(Cache::getStore(), 'tags')) {
            Cache::tags($tags)->flush();
        } else {
            // Fallback: clear specific cache keys
            $this->clearCacheByPatterns($this->getPatternsByTags($tags));
        }
    }

    /**
     * Clear all product-related caches
     */
    public function clearAllProductCaches(): void
    {
        $this->clearCacheByTags([self::PRODUCT_TAG, self::CATEGORY_TAG, self::ANALYTICS_TAG]);
    }

    /**
     * Warm up critical caches
     */
    public function warmUpCaches(): void
    {
        // Warm up aggregations
        $this->getCachedAggregations();
        
        // Warm up popular products
        $this->getCachedPopularProducts();
        
        // Warm up sitemap
        $this->getCachedSitemap();
        
        // Warm up featured products for different limits
        foreach ([3, 6, 9] as $limit) {
            $this->getCachedComplexQuery("featured_products_{$limit}", function () use ($limit) {
                return Product::featured($limit)->get();
            }, self::MEDIUM_CACHE, [self::PRODUCT_TAG]);
        }
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        if (!$this->isRedisAvailable()) {
            return ['error' => 'Redis not available'];
        }

        try {
            $redis = Redis::connection();
            $info = $redis->info();
            
            return [
                'redis_version' => $info['redis_version'] ?? 'unknown',
                'used_memory' => $info['used_memory_human'] ?? 'unknown',
                'connected_clients' => $info['connected_clients'] ?? 'unknown',
                'total_commands_processed' => $info['total_commands_processed'] ?? 'unknown',
                'keyspace_hits' => $info['keyspace_hits'] ?? 0,
                'keyspace_misses' => $info['keyspace_misses'] ?? 0,
                'hit_rate' => $this->calculateHitRate($info),
            ];
        } catch (\Exception $e) {
            return ['error' => 'Could not retrieve Redis stats: ' . $e->getMessage()];
        }
    }

    /**
     * Check if Redis is available
     */
    private function isRedisAvailable(): bool
    {
        try {
            // Check if Redis extension is loaded
            if (!extension_loaded('redis') && !class_exists('Predis\Client')) {
                return false;
            }
            
            // Check if Redis is configured as cache driver
            if (config('cache.default') !== 'redis') {
                return false;
            }
            
            Redis::connection()->ping();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get data from Redis with compression
     */
    private function getFromRedisWithCompression(string $key, callable $callback, int $duration, array $tags = []): mixed
    {
        try {
            $redis = Redis::connection();
            $compressedData = $redis->get($key);
            
            if ($compressedData !== null) {
                return unserialize(gzuncompress($compressedData));
            }
            
            // Data not in cache, generate it
            $data = $callback();
            
            // Compress and store
            $compressed = gzcompress(serialize($data), 6);
            $redis->setex($key, $duration * 60, $compressed);
            
            // Add to tags if supported
            if (!empty($tags) && method_exists(Cache::getStore(), 'tags')) {
                Cache::tags($tags)->put($key, true, $duration);
            }
            
            return $data;
        } catch (\Exception $e) {
            // Fallback to regular cache
            return Cache::remember($key, $duration, $callback);
        }
    }

    /**
     * Generate cache key for search results
     */
    private function generateSearchCacheKey(array $filters, int $perPage): string
    {
        $keyParts = [
            'search_results',
            md5(serialize($filters)),
            $perPage,
            request('page', 1)
        ];
        
        return implode('_', $keyParts);
    }

    /**
     * Get cache patterns by tags
     */
    private function getPatternsByTags(array $tags): array
    {
        $patterns = [];
        
        foreach ($tags as $tag) {
            switch ($tag) {
                case self::PRODUCT_TAG:
                    $patterns = array_merge($patterns, [
                        'featured_products_*',
                        'popular_products_*',
                        'related_products_*',
                        'search_results_*',
                        'product_aggregations',
                    ]);
                    break;
                case self::CATEGORY_TAG:
                    $patterns = array_merge($patterns, [
                        'categories_with_counts',
                        'product_aggregations',
                    ]);
                    break;
                case self::STATIC_TAG:
                    $patterns = array_merge($patterns, [
                        'static_page_*',
                        'sitemap_xml',
                    ]);
                    break;
                case self::ANALYTICS_TAG:
                    $patterns = array_merge($patterns, [
                        'popular_products_*',
                        'analytics_*',
                    ]);
                    break;
            }
        }
        
        return array_unique($patterns);
    }

    /**
     * Clear cache by patterns
     */
    private function clearCacheByPatterns(array $patterns): void
    {
        foreach ($patterns as $pattern) {
            if ($this->isRedisAvailable()) {
                $redis = Redis::connection();
                $keys = $redis->keys($pattern);
                if (!empty($keys)) {
                    $redis->del($keys);
                }
            } else {
                // For non-Redis cache stores, we can't use patterns
                // This is a limitation of file/database cache drivers
                Cache::flush();
                break;
            }
        }
    }

    /**
     * Calculate cache hit rate
     */
    private function calculateHitRate(array $info): string
    {
        $hits = $info['keyspace_hits'] ?? 0;
        $misses = $info['keyspace_misses'] ?? 0;
        $total = $hits + $misses;
        
        if ($total === 0) {
            return '0%';
        }
        
        return round(($hits / $total) * 100, 2) . '%';
    }
}