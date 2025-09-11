<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ImageAccessControlMiddleware
{
    /**
     * Handle an incoming request for protected image access.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Rate limiting for image requests
        if (!$this->checkRateLimit($request)) {
            Log::warning('Image access rate limit exceeded', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->url(),
            ]);
            
            return response('Too Many Requests', 429);
        }

        // Referrer checking for additional protection
        if (!$this->checkReferrer($request)) {
            Log::warning('Invalid referrer for image access', [
                'ip' => $request->ip(),
                'referrer' => $request->header('referer'),
                'url' => $request->url(),
            ]);
            
            return response('Forbidden', 403);
        }

        // User agent validation (basic bot detection)
        if (!$this->validateUserAgent($request)) {
            Log::warning('Suspicious user agent for image access', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->url(),
            ]);
            
            return response('Forbidden', 403);
        }

        return $next($request);
    }

    /**
     * Check rate limiting for image requests
     */
    protected function checkRateLimit(Request $request): bool
    {
        $key = 'image_access_' . $request->ip();
        $maxRequests = config('image_protection.rate_limit.max_requests', 100);
        $timeWindow = config('image_protection.rate_limit.time_window', 60); // seconds
        
        $currentRequests = Cache::get($key, 0);
        
        if ($currentRequests >= $maxRequests) {
            return false;
        }
        
        // Increment counter
        Cache::put($key, $currentRequests + 1, $timeWindow);
        
        return true;
    }

    /**
     * Check if referrer is valid (from same domain or allowed domains)
     */
    protected function checkReferrer(Request $request): bool
    {
        $referrer = $request->header('referer');
        
        // Allow direct access (no referrer) for now - can be made stricter
        if (empty($referrer)) {
            return true;
        }
        
        $allowedDomains = [
            $request->getHost(),
            config('app.url'),
        ];
        
        // Add additional allowed domains from config
        $configDomains = config('image_protection.allowed_referrer_domains', []);
        $allowedDomains = array_merge($allowedDomains, $configDomains);
        
        foreach ($allowedDomains as $domain) {
            $domain = parse_url($domain, PHP_URL_HOST) ?: $domain;
            if (str_contains($referrer, $domain)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Validate user agent to detect suspicious bots
     */
    protected function validateUserAgent(Request $request): bool
    {
        $userAgent = $request->userAgent();
        
        if (empty($userAgent) || $userAgent === null) {
            return false;
        }
        
        // Block known scraping user agents
        $blockedAgents = config('image_protection.blocked_user_agents', [
            'wget',
            'curl',
            'python-requests',
            'scrapy',
            'bot',
            'crawler',
            'spider',
            'scraper',
        ]);
        
        $userAgentLower = strtolower($userAgent);
        
        foreach ($blockedAgents as $blocked) {
            if (str_contains($userAgentLower, $blocked)) {
                return false;
            }
        }
        
        return true;
    }
}