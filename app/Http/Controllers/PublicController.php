<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Services\ProductCacheService;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicController extends Controller
{
    protected ProductCacheService $cacheService;
    protected AnalyticsService $analyticsService;

    public function __construct(ProductCacheService $cacheService, AnalyticsService $analyticsService)
    {
        $this->cacheService = $cacheService;
        $this->analyticsService = $analyticsService;
    }

    /**
     * Display the home page with brand introduction and featured products.
     */
    public function home(): View
    {
        // Get cached featured products (latest 6 products for display)
        $featuredProducts = $this->cacheService->getFeaturedProducts(6);

        return view('public.home', compact('featuredProducts'));
    }

    /**
     * Display a listing of all products with search and filtering.
     */
    public function products(Request $request): View
    {
        // Build the query
        $query = Product::with(['category:id,name,slug', 'images'])->forListing();

        // Apply multiple category filter
        if ($request->has('categories') && is_array($request->categories)) {
            $categoryIds = array_filter($request->categories);
            if (!empty($categoryIds)) {
                $query->whereIn('category_id', $categoryIds);
            }
        }

        // Apply search filter
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('description', 'like', "%{$searchTerm}%")
                  ->orWhere('short_description', 'like', "%{$searchTerm}%");
            });
        }

        // Apply sorting (default to name ascending)
        $query->orderBy('name', 'asc');

        $products = $query->paginate(12)->withQueryString();

        // Get all categories with product counts
        $categories = Category::withCount('products')
            ->orderBy('name')
            ->get();

        // Get price range for filters
        $priceRange = $this->getPriceRange();

        return view('public.products.index', compact('products', 'categories', 'priceRange'));
    }

    /**
     * Display search results with advanced filtering and sorting.
     */
    public function search(Request $request): View
    {
        $searchTerm = $request->get('search', '');
        $categoryId = $request->get('category');
        $minPrice = $request->get('min_price');
        $maxPrice = $request->get('max_price');
        $sortBy = $request->get('sort_by', 'name');
        $sortDirection = $request->get('sort_direction', 'asc');

        // Build the search query
        $query = Product::with(['category:id,name,slug', 'images'])->forListing();

        // Apply search filters
        if (!empty($searchTerm)) {
            $query->search($searchTerm);
        }

        if (!empty($categoryId)) {
            $query->filterByCategory($categoryId);
        }

        if (!empty($minPrice) || !empty($maxPrice)) {
            $query->priceRange($minPrice, $maxPrice);
        }

        // Apply sorting
        $query->sortBy($sortBy, $sortDirection);

        $products = $query->paginate(12)->withQueryString();

        // Get all categories for filter dropdown
        $categories = Category::select('id', 'name')->orderBy('name')->get();

        // Get price range for filter
        $priceRange = $this->getPriceRange();

        return view('public.products.search', compact(
            'products', 
            'categories', 
            'priceRange', 
            'searchTerm',
            'categoryId',
            'minPrice',
            'maxPrice',
            'sortBy',
            'sortDirection'
        ));
    }

    /**
     * Get the minimum and maximum product prices for filtering.
     */
    private function getPriceRange(): array
    {
        $prices = Product::selectRaw('MIN(price) as min_price, MAX(price) as max_price')->first();
        
        return [
            'min' => $prices->min_price ? floor($prices->min_price) : 0,
            'max' => $prices->max_price ? ceil($prices->max_price) : 10000,
        ];
    }

    /**
     * Display products by category.
     */
    public function category(Category $category): View
    {
        // Use cached method for category products
        $products = $this->cacheService->getProductsByCategory($category, 12);

        return view('public.products.category', compact('products', 'category'));
    }

    /**
     * Display the specified product details.
     */
    public function show(Product $product, Request $request): View
    {
        // Track product view for analytics
        $this->analyticsService->trackProductView($product, $request);
        
        // Load category, images, and approved reviews relationships
        $product->load([
            'category',
            'images',
            'primaryImage',
            'approvedReviews' => function ($query) {
                $query->with('user:id,name')->orderBy('created_at', 'desc');
            }
        ]);
        
        // Get optimized related products using scope
        $relatedProducts = Product::related($product->category_id, $product->id, 4)->get();

        return view('public.products.show', compact('product', 'relatedProducts'));
    }

    /**
     * Generate XML sitemap for SEO.
     */
    public function sitemap()
    {
        // Use advanced cached sitemap
        $sitemapXml = app(AdvancedCacheService::class)->getCachedSitemap();

        return response($sitemapXml)
            ->header('Content-Type', 'text/xml');
    }

    /**
     * Generate robots.txt for SEO.
     */
    public function robots()
    {
        return response()->view('public.robots')
            ->header('Content-Type', 'text/plain');
    }
}