<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductView;
use App\Models\ContactSubmission;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AnalyticsService
{
    /**
     * Get comprehensive analytics data for the dashboard.
     */
    public function getDashboardAnalytics(string $period = '30days'): array
    {
        $cacheKey = "analytics_dashboard_{$period}";
        
        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($period) {
            $dateRange = $this->getDateRange($period);
            
            return [
                'overview' => $this->getOverviewStats($dateRange),
                'product_views' => $this->getProductViewsAnalytics($dateRange),
                'popular_products' => $this->getPopularProducts($dateRange),
                'contact_analytics' => $this->getContactAnalytics($dateRange),
                'trends' => $this->getTrendsData($dateRange),
                'category_performance' => $this->getCategoryPerformance($dateRange),
            ];
        });
    }

    /**
     * Get overview statistics.
     */
    private function getOverviewStats(array $dateRange): array
    {
        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];
        
        return [
            'total_views' => ProductView::dateRange($startDate, $endDate)->count(),
            'unique_views' => ProductView::dateRange($startDate, $endDate)
                ->distinct('ip_address')->count('ip_address'),
            'total_contacts' => ContactSubmission::dateRange($startDate, $endDate)->count(),
            'successful_contacts' => ContactSubmission::dateRange($startDate, $endDate)
                ->emailSent()->count(),
            'total_products' => Product::count(),
            'total_categories' => Category::count(),
            'views_today' => ProductView::today()->count(),
            'contacts_today' => ContactSubmission::today()->count(),
        ];
    }

    /**
     * Get product views analytics with daily breakdown.
     */
    private function getProductViewsAnalytics(array $dateRange): array
    {
        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];
        
        // Daily views breakdown (database agnostic)
        $dailyViews = ProductView::select(
                DB::raw($this->getDateExpression() . ' as date'),
                DB::raw('COUNT(*) as total_views'),
                DB::raw('COUNT(DISTINCT ip_address) as unique_views')
            )
            ->dateRange($startDate, $endDate)
            ->groupBy(DB::raw($this->getDateExpression()))
            ->orderBy('date')
            ->get();

        // Hourly views for today (database agnostic)
        $hourlyViews = ProductView::select(
                DB::raw($this->getHourExpression() . ' as hour'),
                DB::raw('COUNT(*) as views')
            )
            ->today()
            ->groupBy(DB::raw($this->getHourExpression()))
            ->orderBy('hour')
            ->get();

        return [
            'daily_views' => $dailyViews,
            'hourly_views' => $hourlyViews,
            'total_period_views' => $dailyViews->sum('total_views'),
            'total_unique_views' => ProductView::dateRange($startDate, $endDate)
                ->distinct('ip_address')->count('ip_address'),
        ];
    }

    /**
     * Get popular products analytics.
     */
    private function getPopularProducts(array $dateRange, int $limit = 10): array
    {
        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];
        
        $popularProducts = ProductView::select(
                'product_id',
                DB::raw('COUNT(*) as total_views'),
                DB::raw('COUNT(DISTINCT ip_address) as unique_views')
            )
            ->with(['product:id,name,slug,price,image_path'])
            ->dateRange($startDate, $endDate)
            ->groupBy('product_id')
            ->orderBy('total_views', 'desc')
            ->limit($limit)
            ->get();

        // Get products with no views in the period
        $viewedProductIds = $popularProducts->pluck('product_id');
        $unviewedProducts = Product::whereNotIn('id', $viewedProductIds)
            ->select('id', 'name', 'slug', 'price', 'image_path')
            ->limit(5)
            ->get();

        return [
            'popular_products' => $popularProducts,
            'unviewed_products' => $unviewedProducts,
            'total_products_viewed' => $popularProducts->count(),
        ];
    }

    /**
     * Get contact form analytics.
     */
    private function getContactAnalytics(array $dateRange): array
    {
        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];
        
        // Daily contact submissions (database agnostic)
        $dailyContacts = ContactSubmission::select(
                DB::raw($this->getDateExpression('submitted_at') . ' as date'),
                DB::raw('COUNT(*) as total_submissions'),
                DB::raw($this->getSumCaseExpression() . ' as successful_submissions')
            )
            ->dateRange($startDate, $endDate)
            ->groupBy(DB::raw($this->getDateExpression('submitted_at')))
            ->orderBy('date')
            ->get();

        // Success rate
        $totalSubmissions = ContactSubmission::dateRange($startDate, $endDate)->count();
        $successfulSubmissions = ContactSubmission::dateRange($startDate, $endDate)
            ->emailSent()->count();
        
        $successRate = $totalSubmissions > 0 ? 
            round(($successfulSubmissions / $totalSubmissions) * 100, 2) : 0;

        // Recent contact submissions
        $recentContacts = ContactSubmission::dateRange($startDate, $endDate)
            ->orderBy('submitted_at', 'desc')
            ->limit(10)
            ->get();

        return [
            'daily_contacts' => $dailyContacts,
            'total_submissions' => $totalSubmissions,
            'successful_submissions' => $successfulSubmissions,
            'failed_submissions' => $totalSubmissions - $successfulSubmissions,
            'success_rate' => $successRate,
            'recent_contacts' => $recentContacts,
        ];
    }

    /**
     * Get trends data for charts.
     */
    private function getTrendsData(array $dateRange): array
    {
        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];
        
        // Compare with previous period
        $periodLength = $startDate->diffInDays($endDate);
        $previousStart = $startDate->copy()->subDays($periodLength);
        $previousEnd = $startDate->copy()->subDay();
        
        $currentViews = ProductView::dateRange($startDate, $endDate)->count();
        $previousViews = ProductView::dateRange($previousStart, $previousEnd)->count();
        
        $currentContacts = ContactSubmission::dateRange($startDate, $endDate)->count();
        $previousContacts = ContactSubmission::dateRange($previousStart, $previousEnd)->count();
        
        return [
            'views_trend' => $this->calculateTrend($currentViews, $previousViews),
            'contacts_trend' => $this->calculateTrend($currentContacts, $previousContacts),
            'current_period' => [
                'views' => $currentViews,
                'contacts' => $currentContacts,
            ],
            'previous_period' => [
                'views' => $previousViews,
                'contacts' => $previousContacts,
            ],
        ];
    }

    /**
     * Get category performance analytics.
     */
    private function getCategoryPerformance(array $dateRange): array
    {
        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];
        
        $categoryPerformance = ProductView::select(
                'products.category_id',
                'categories.name as category_name',
                DB::raw('COUNT(product_views.id) as total_views'),
                DB::raw('COUNT(DISTINCT product_views.ip_address) as unique_views'),
                DB::raw('COUNT(DISTINCT product_views.product_id) as products_viewed')
            )
            ->join('products', 'product_views.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->dateRange($startDate, $endDate)
            ->groupBy('products.category_id', 'categories.name')
            ->orderBy('total_views', 'desc')
            ->get();

        return [
            'category_performance' => $categoryPerformance,
            'total_categories_viewed' => $categoryPerformance->count(),
        ];
    }

    /**
     * Track a product view.
     */
    public function trackProductView(Product $product, $request): void
    {
        ProductView::create([
            'product_id' => $product->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referrer' => $request->header('referer'),
            'session_id' => $request->session()->getId(),
            'viewed_at' => now(),
        ]);
    }

    /**
     * Track a contact form submission.
     */
    public function trackContactSubmission(array $contactData, bool $emailSent, $request): void
    {
        ContactSubmission::create([
            'name' => $contactData['name'],
            'email' => $contactData['email'],
            'message' => $contactData['message'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referrer' => $request->header('referer'),
            'email_sent' => $emailSent,
            'submitted_at' => now(),
        ]);
    }

    /**
     * Get date range based on period.
     */
    private function getDateRange(string $period): array
    {
        $endDate = now();
        
        switch ($period) {
            case '7days':
                $startDate = now()->subDays(7);
                break;
            case '30days':
                $startDate = now()->subDays(30);
                break;
            case '90days':
                $startDate = now()->subDays(90);
                break;
            case 'year':
                $startDate = now()->subYear();
                break;
            default:
                $startDate = now()->subDays(30);
        }
        
        return [
            'start' => $startDate,
            'end' => $endDate,
        ];
    }

    /**
     * Calculate trend percentage.
     */
    private function calculateTrend(int $current, int $previous): array
    {
        if ($previous == 0) {
            $percentage = $current > 0 ? 100 : 0;
            $direction = $current > 0 ? 'up' : 'neutral';
        } else {
            $percentage = round((($current - $previous) / $previous) * 100, 2);
            $direction = $percentage > 0 ? 'up' : ($percentage < 0 ? 'down' : 'neutral');
        }
        
        return [
            'percentage' => abs($percentage),
            'direction' => $direction,
        ];
    }

    /**
     * Get real-time analytics data.
     */
    public function getRealTimeAnalytics(): array
    {
        return [
            'views_today' => ProductView::today()->count(),
            'unique_visitors_today' => ProductView::today()->distinct('ip_address')->count('ip_address'),
            'contacts_today' => ContactSubmission::today()->count(),
            'views_this_hour' => ProductView::where('viewed_at', '>=', now()->subHour())->count(),
            'recent_views' => ProductView::with(['product:id,name,slug'])
                ->orderBy('viewed_at', 'desc')
                ->limit(10)
                ->get(),
        ];
    }

    /**
     * Clear analytics cache.
     */
    public function clearCache(): void
    {
        $periods = ['7days', '30days', '90days', 'year'];
        
        foreach ($periods as $period) {
            Cache::forget("analytics_dashboard_{$period}");
        }
    }

    /**
     * Get database-agnostic DATE expression.
     */
    private function getDateExpression(string $column = 'viewed_at'): string
    {
        $driver = DB::connection()->getDriverName();
        
        switch ($driver) {
            case 'sqlite':
                return "DATE({$column})";
            case 'mysql':
                return "DATE({$column})";
            case 'pgsql':
                return "DATE({$column})";
            default:
                return "DATE({$column})";
        }
    }

    /**
     * Get database-agnostic HOUR expression.
     */
    private function getHourExpression(string $column = 'viewed_at'): string
    {
        $driver = DB::connection()->getDriverName();
        
        switch ($driver) {
            case 'sqlite':
                return "CAST(strftime('%H', {$column}) AS INTEGER)";
            case 'mysql':
                return "HOUR({$column})";
            case 'pgsql':
                return "EXTRACT(HOUR FROM {$column})";
            default:
                return "HOUR({$column})";
        }
    }

    /**
     * Get database-agnostic SUM CASE expression for boolean fields.
     */
    private function getSumCaseExpression(string $column = 'email_sent'): string
    {
        $driver = DB::connection()->getDriverName();
        
        switch ($driver) {
            case 'sqlite':
                return "SUM(CASE WHEN {$column} = 1 THEN 1 ELSE 0 END)";
            case 'mysql':
                return "SUM(CASE WHEN {$column} = 1 THEN 1 ELSE 0 END)";
            case 'pgsql':
                return "SUM(CASE WHEN {$column} = true THEN 1 ELSE 0 END)";
            default:
                return "SUM(CASE WHEN {$column} = 1 THEN 1 ELSE 0 END)";
        }
    }
}