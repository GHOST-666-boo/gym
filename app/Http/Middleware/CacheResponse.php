<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CacheResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, int $minutes = 60): Response
    {
        // Only cache GET requests
        if (!$request->isMethod('GET')) {
            return $next($request);
        }

        // Don't cache authenticated requests
        if ($request->user()) {
            return $next($request);
        }

        // Generate cache key based on URL and query parameters
        $cacheKey = 'response_cache_' . md5($request->fullUrl());

        // Try to get cached response
        $cachedResponse = Cache::get($cacheKey);
        
        if ($cachedResponse) {
            return response($cachedResponse['content'])
                ->withHeaders($cachedResponse['headers'])
                ->header('X-Cache-Status', 'HIT');
        }

        // Process request
        $response = $next($request);

        // Only cache successful responses
        if ($response->getStatusCode() === 200) {
            $cacheData = [
                'content' => $response->getContent(),
                'headers' => $response->headers->all()
            ];

            Cache::put($cacheKey, $cacheData, now()->addMinutes($minutes));
            $response->header('X-Cache-Status', 'MISS');
        }

        return $response;
    }
}