<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class FullPageCacheMiddleware
{
    /**
     * Pages that should be cached (in minutes)
     */
    private array $cacheablePages = [
        '/' => 60,                    // Home page - 1 hour
        '/products' => 30,            // Products listing - 30 minutes
        '/contact' => 1440,           // Contact page - 24 hours
        '/category/*' => 60,          // Category pages - 1 hour
    ];

    /**
     * Pages that should NOT be cached
     */
    private array $excludedPages = [
        '/admin/*',
        '/login',
        '/register',
        '/logout',
        '/dashboard',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip caching for non-GET requests
        if (!$request->isMethod('GET')) {
            return $next($request);
        }

        // Skip caching for authenticated users (admin panel)
        if ($request->user()) {
            return $next($request);
        }

        // Skip caching for excluded pages
        if ($this->shouldExcludePage($request)) {
            return $next($request);
        }

        // Check if page should be cached
        $cacheDuration = $this->getCacheDuration($request);
        if ($cacheDuration === null) {
            return $next($request);
        }

        // Generate cache key
        $cacheKey = $this->generateCacheKey($request);

        // Try to get cached response
        $cachedResponse = Cache::get($cacheKey);
        if ($cachedResponse !== null) {
            $response = new Response($cachedResponse['content']);
            $response->headers->replace($cachedResponse['headers']);
            $response->headers->set('X-Cache-Status', 'HIT');
            return $response;
        }

        // Generate response
        $response = $next($request);

        // Cache successful responses only
        if ($response->getStatusCode() === 200 && $response instanceof \Illuminate\Http\Response) {
            $this->cacheResponse($cacheKey, $response, $cacheDuration);
            $response->headers->set('X-Cache-Status', 'MISS');
        }

        return $response;
    }

    /**
     * Check if page should be excluded from caching
     */
    private function shouldExcludePage(Request $request): bool
    {
        $path = $request->getPathInfo();
        
        foreach ($this->excludedPages as $excludedPattern) {
            if ($this->matchesPattern($path, $excludedPattern)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get cache duration for the current request
     */
    private function getCacheDuration(Request $request): ?int
    {
        $path = $request->getPathInfo();
        
        foreach ($this->cacheablePages as $pattern => $duration) {
            if ($this->matchesPattern($path, $pattern)) {
                return $duration;
            }
        }
        
        return null;
    }

    /**
     * Check if path matches pattern
     */
    private function matchesPattern(string $path, string $pattern): bool
    {
        // Convert pattern to regex
        $regex = str_replace(['*', '/'], ['.*', '\/'], $pattern);
        $regex = '/^' . $regex . '$/';
        
        return preg_match($regex, $path) === 1;
    }

    /**
     * Generate cache key for request
     */
    private function generateCacheKey(Request $request): string
    {
        $key = 'full_page_cache_' . md5($request->getUri());
        
        // Include query parameters in cache key for search/filter pages
        if ($request->getQueryString()) {
            $key .= '_' . md5($request->getQueryString());
        }
        
        return $key;
    }

    /**
     * Cache the response
     */
    private function cacheResponse(string $cacheKey, Response $response, int $duration): void
    {
        $cacheData = [
            'content' => $response->getContent(),
            'headers' => $response->headers->all(),
        ];
        
        // Add cache headers
        $cacheData['headers']['Cache-Control'] = ["public, max-age=" . ($duration * 60)];
        $cacheData['headers']['X-Cached-At'] = [now()->toISOString()];
        
        Cache::put($cacheKey, $cacheData, $duration);
    }
}
