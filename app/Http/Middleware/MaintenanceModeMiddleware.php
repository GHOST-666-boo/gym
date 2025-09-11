<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\SettingsService;

class MaintenanceModeMiddleware
{
    protected SettingsService $settingsService;

    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if maintenance mode is enabled
        $maintenanceMode = $this->settingsService->get('maintenance_mode', false);
        
        if ($maintenanceMode) {
            // Allow admin users to bypass maintenance mode
            if ($request->user() && $request->user()->is_admin) {
                return $next($request);
            }
            
            // Allow access to admin routes
            if ($request->is('admin/*') || $request->is('login') || $request->is('logout')) {
                return $next($request);
            }
            
            // Show maintenance page for all other requests
            return response()->view('maintenance', [], 503);
        }

        return $next($request);
    }
}