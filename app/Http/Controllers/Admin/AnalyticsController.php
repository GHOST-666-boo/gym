<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    protected AnalyticsService $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Display the analytics dashboard.
     */
    public function index(Request $request): View
    {
        $period = $request->get('period', '30days');
        $analytics = $this->analyticsService->getDashboardAnalytics($period);
        
        return view('admin.analytics.index', compact('analytics', 'period'));
    }

    /**
     * Get analytics data as JSON for AJAX requests.
     */
    public function data(Request $request): JsonResponse
    {
        $period = $request->get('period', '30days');
        $analytics = $this->analyticsService->getDashboardAnalytics($period);
        
        return response()->json($analytics);
    }

    /**
     * Get real-time analytics data.
     */
    public function realTime(): JsonResponse
    {
        $realTimeData = $this->analyticsService->getRealTimeAnalytics();
        
        return response()->json($realTimeData);
    }

    /**
     * Clear analytics cache.
     */
    public function clearCache(): JsonResponse
    {
        $this->analyticsService->clearCache();
        
        return response()->json([
            'success' => true,
            'message' => 'Analytics cache cleared successfully.'
        ]);
    }

    /**
     * Export analytics data as CSV.
     */
    public function export(Request $request)
    {
        $period = $request->get('period', '30days');
        $analytics = $this->analyticsService->getDashboardAnalytics($period);
        
        $filename = "analytics_export_{$period}_" . now()->format('Y-m-d') . ".csv";
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];
        
        $callback = function() use ($analytics) {
            $file = fopen('php://output', 'w');
            
            // Overview data
            fputcsv($file, ['Analytics Overview']);
            fputcsv($file, ['Metric', 'Value']);
            foreach ($analytics['overview'] as $key => $value) {
                fputcsv($file, [ucwords(str_replace('_', ' ', $key)), $value]);
            }
            
            fputcsv($file, []); // Empty row
            
            // Popular products
            fputcsv($file, ['Popular Products']);
            fputcsv($file, ['Product Name', 'Total Views', 'Unique Views']);
            foreach ($analytics['popular_products']['popular_products'] as $product) {
                fputcsv($file, [
                    $product->product->name,
                    $product->total_views,
                    $product->unique_views
                ]);
            }
            
            fputcsv($file, []); // Empty row
            
            // Contact analytics
            fputcsv($file, ['Contact Form Analytics']);
            fputcsv($file, ['Metric', 'Value']);
            fputcsv($file, ['Total Submissions', $analytics['contact_analytics']['total_submissions']]);
            fputcsv($file, ['Successful Submissions', $analytics['contact_analytics']['successful_submissions']]);
            fputcsv($file, ['Success Rate (%)', $analytics['contact_analytics']['success_rate']]);
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}
