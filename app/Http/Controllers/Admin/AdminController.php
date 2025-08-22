<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\User;
use App\Services\AnalyticsService;
use Illuminate\View\View;

class AdminController extends Controller
{
    protected AnalyticsService $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
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

        return view('admin.dashboard', compact('stats', 'analytics', 'realTimeAnalytics'));
    }
}