<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdvancedCacheService;
use App\Services\ProductCacheService;
use App\Services\CdnService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CacheController extends Controller
{
    /**
     * Advanced cache service
     */
    protected AdvancedCacheService $advancedCache;

    /**
     * Product cache service
     */
    protected ProductCacheService $productCache;

    /**
     * CDN service
     */
    protected CdnService $cdnService;

    /**
     * Create a new controller instance.
     */
    public function __construct(
        AdvancedCacheService $advancedCache,
        ProductCacheService $productCache,
        CdnService $cdnService
    ) {
        $this->advancedCache = $advancedCache;
        $this->productCache = $productCache;
        $this->cdnService = $cdnService;
    }

    /**
     * Display cache management dashboard
     */
    public function index(): View
    {
        $stats = $this->productCache->getCacheStats();
        
        return view('admin.cache.index', compact('stats'));
    }

    /**
     * Clear all caches
     */
    public function clearAll(): RedirectResponse
    {
        try {
            $this->productCache->clearProductCaches();
            
            return redirect()
                ->route('admin.cache.index')
                ->with('success', 'All caches have been cleared successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.cache.index')
                ->with('error', 'Failed to clear caches: ' . $e->getMessage());
        }
    }

    /**
     * Clear specific cache tags
     */
    public function clearTags(Request $request): RedirectResponse
    {
        $request->validate([
            'tags' => 'required|array',
            'tags.*' => 'in:products,categories,analytics,static'
        ]);

        try {
            $this->advancedCache->clearCacheByTags($request->tags);
            
            $tagNames = implode(', ', $request->tags);
            
            return redirect()
                ->route('admin.cache.index')
                ->with('success', "Cache cleared for tags: {$tagNames}");
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.cache.index')
                ->with('error', 'Failed to clear cache tags: ' . $e->getMessage());
        }
    }

    /**
     * Warm up caches
     */
    public function warmUp(): RedirectResponse
    {
        try {
            $this->productCache->warmUpCaches();
            
            return redirect()
                ->route('admin.cache.index')
                ->with('success', 'Caches have been warmed up successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.cache.index')
                ->with('error', 'Failed to warm up caches: ' . $e->getMessage());
        }
    }

    /**
     * Clear full-page cache
     */
    public function clearFullPage(): RedirectResponse
    {
        try {
            $this->productCache->clearFullPageCache();
            
            return redirect()
                ->route('admin.cache.index')
                ->with('success', 'Full-page cache has been cleared successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.cache.index')
                ->with('error', 'Failed to clear full-page cache: ' . $e->getMessage());
        }
    }

    /**
     * Clear image cache
     */
    public function clearImages(): RedirectResponse
    {
        try {
            $this->cdnService->clearImageCache();
            
            return redirect()
                ->route('admin.cache.index')
                ->with('success', 'Image cache has been cleared successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.cache.index')
                ->with('error', 'Failed to clear image cache: ' . $e->getMessage());
        }
    }

    /**
     * Get cache statistics as JSON
     */
    public function stats(): \Illuminate\Http\JsonResponse
    {
        $stats = $this->productCache->getCacheStats();
        
        return response()->json($stats);
    }
}
