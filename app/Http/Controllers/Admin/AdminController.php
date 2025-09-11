<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\User;
use App\Services\AnalyticsService;
use App\Services\WatermarkSettingsService;
use Illuminate\View\View;

class AdminController extends Controller
{
    protected AnalyticsService $analyticsService;
    protected WatermarkSettingsService $watermarkSettingsService;

    public function __construct(AnalyticsService $analyticsService, WatermarkSettingsService $watermarkSettingsService)
    {
        $this->analyticsService = $analyticsService;
        $this->watermarkSettingsService = $watermarkSettingsService;
    }

    /**
     * Display the admin dashboard with statistics overview.
     */
    public function dashboard(): View
    {
        // Get statistics for dashboard
        $stats = [
            'total_products' => Product::count(),
            'total_categories' => Category::count(),
            'total_users' => User::count(),
            'low_stock_products' => Product::lowStock()->count(),
            'out_of_stock_products' => Product::outOfStock()->count(),
            'recent_products' => Product::with('category')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
            'low_stock_alerts' => Product::lowStock()
                ->with('category')
                ->orderBy('stock_quantity', 'asc')
                ->limit(10)
                ->get(),
        ];

        // Get analytics data for dashboard
        $analytics = $this->analyticsService->getDashboardAnalytics('7days');
        $realTimeAnalytics = $this->analyticsService->getRealTimeAnalytics();

        // Get image protection and watermark status
        $imageProtectionStatus = [
            'protection_enabled' => $this->watermarkSettingsService->isImageProtectionEnabled(),
            'watermark_enabled' => $this->watermarkSettingsService->isWatermarkEnabled(),
            'protection_methods' => $this->watermarkSettingsService->getProtectionConfig(),
            'watermark_config' => $this->watermarkSettingsService->getWatermarkConfig(),
            'has_watermark_text' => !empty($this->watermarkSettingsService->getWatermarkText()),
            'has_watermark_logo' => !empty($this->watermarkSettingsService->getWatermarkLogoPath()),
        ];

        return view('admin.dashboard', compact('stats', 'analytics', 'realTimeAnalytics', 'imageProtectionStatus'));
    }
}