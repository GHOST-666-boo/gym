<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Log;

class EnsureSettingsCacheWarmed
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $settingsService = app(SettingsService::class);
        
        // Check if cache is warm, if not, warm it
        if (!$settingsService->isCacheWarm()) {
            try {
                $settingsService->warmCache();
                Log::info('Settings cache automatically warmed');
            } catch (\Exception $e) {
                Log::warning('Failed to warm settings cache', ['error' => $e->getMessage()]);
            }
        }
        
        return $next($request);
    }
}
