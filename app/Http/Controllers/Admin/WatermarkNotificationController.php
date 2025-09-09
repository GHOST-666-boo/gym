<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WatermarkService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WatermarkNotificationController extends Controller
{
    protected WatermarkService $watermarkService;

    public function __construct(WatermarkService $watermarkService)
    {
        $this->watermarkService = $watermarkService;
    }

    /**
     * Get all watermark notifications
     */
    public function index(): JsonResponse
    {
        $notifications = $this->watermarkService->getAdminNotifications();
        $healthStatus = $this->watermarkService->getSystemHealthStatus();
        $counts = $this->watermarkService->getNotificationCounts();

        return response()->json([
            'notifications' => $notifications,
            'health_status' => $healthStatus,
            'counts' => $counts,
            'summary' => [
                'total_notifications' => $counts['total'],
                'active_notifications' => $counts['active'],
                'critical_count' => $counts['critical'],
                'system_status' => $healthStatus['status']
            ]
        ]);
    }

    /**
     * Dismiss a notification
     */
    public function dismiss(Request $request, string $notificationId): JsonResponse
    {
        $success = $this->watermarkService->dismissAdminNotification($notificationId);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Notification dismissed' : 'Notification not found'
        ]);
    }

    /**
     * Clear old dismissed notifications
     */
    public function clearOld(Request $request): JsonResponse
    {
        $daysOld = $request->input('days_old', 7);
        $clearedCount = $this->watermarkService->clearOldNotifications($daysOld);

        return response()->json([
            'success' => true,
            'message' => "Cleared {$clearedCount} old notifications",
            'cleared_count' => $clearedCount
        ]);
    }

    /**
     * Get system health status
     */
    public function healthStatus(): JsonResponse
    {
        $status = $this->watermarkService->getSystemHealthStatus();

        return response()->json($status);
    }

    /**
     * Test watermark functionality
     */
    public function testWatermark(Request $request): JsonResponse
    {
        $request->validate([
            'image_path' => 'required|string'
        ]);

        try {
            $imagePath = $request->input('image_path');
            $result = $this->watermarkService->applyWatermark($imagePath, ['test_mode' => true]);
            
            $isSuccess = !str_contains($result, 'error=') && !str_contains($result, 'css_watermark=');

            return response()->json([
                'success' => $isSuccess,
                'result_path' => $result,
                'message' => $isSuccess ? 'Watermark test successful' : 'Watermark test failed - using fallback',
                'fallback_used' => !$isSuccess
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Watermark test failed: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get cache statistics
     */
    public function cacheStats(): JsonResponse
    {
        $stats = $this->watermarkService->getCacheStats();

        return response()->json($stats);
    }

    /**
     * Clear watermark cache
     */
    public function clearCache(): JsonResponse
    {
        try {
            $this->watermarkService->clearWatermarkCache();

            return response()->json([
                'success' => true,
                'message' => 'Watermark cache cleared successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detailed error report
     */
    public function errorReport(): JsonResponse
    {
        $notifications = $this->watermarkService->getAdminNotifications();
        $healthStatus = $this->watermarkService->getSystemHealthStatus();
        $counts = $this->watermarkService->getNotificationCounts();
        
        // Group errors by type and severity
        $errorsByType = [];
        $errorsBySeverity = ['critical' => [], 'high' => [], 'medium' => [], 'low' => []];
        
        foreach ($notifications as $notification) {
            if ($notification['type'] === 'watermark_error') {
                $errorType = $notification['error_type'];
                $severity = $notification['severity'];
                
                if (!isset($errorsByType[$errorType])) {
                    $errorsByType[$errorType] = [];
                }
                $errorsByType[$errorType][] = $notification;
                
                if (isset($errorsBySeverity[$severity])) {
                    $errorsBySeverity[$severity][] = $notification;
                }
            }
        }

        // Get recent errors (last 24 hours)
        $recentErrors = array_filter($notifications, function($notification) {
            return $notification['type'] === 'watermark_error' && 
                   !$notification['dismissed'] &&
                   $notification['last_occurrence']->isAfter(now()->subDay());
        });

        return response()->json([
            'summary' => [
                'total_notifications' => $counts['total'],
                'active_notifications' => $counts['active'],
                'critical_errors' => $counts['critical'],
                'recent_errors' => count($recentErrors),
                'error_types' => count($errorsByType),
                'system_health' => $healthStatus['status']
            ],
            'errors_by_type' => $errorsByType,
            'errors_by_severity' => $errorsBySeverity,
            'recent_errors' => array_values($recentErrors),
            'system_info' => [
                'extensions' => $healthStatus['extensions'],
                'cache_status' => $healthStatus['cache']
            ],
            'recommendations' => $this->getErrorRecommendations($errorsByType, $healthStatus)
        ]);
    }

    /**
     * Get recommendations based on error patterns
     */
    protected function getErrorRecommendations(array $errorsByType, array $healthStatus): array
    {
        $recommendations = [];

        if (isset($errorsByType['missing_extensions'])) {
            $recommendations[] = [
                'type' => 'critical',
                'title' => 'Install Image Processing Extensions',
                'description' => 'Install GD or Imagick PHP extensions to enable server-side watermarking',
                'action' => 'Contact your hosting provider or system administrator'
            ];
        }

        if (isset($errorsByType['corrupted_image'])) {
            $recommendations[] = [
                'type' => 'warning',
                'title' => 'Check Image File Integrity',
                'description' => 'Some image files appear to be corrupted or invalid',
                'action' => 'Review and replace corrupted image files'
            ];
        }

        if (isset($errorsByType['cache_write_failed'])) {
            $recommendations[] = [
                'type' => 'warning',
                'title' => 'Check Storage Permissions',
                'description' => 'Unable to write watermarked images to cache directory',
                'action' => 'Verify storage/app/public/watermarks/cache directory permissions'
            ];
        }

        if ($healthStatus['cache']['total_cached_files'] > 1000) {
            $recommendations[] = [
                'type' => 'info',
                'title' => 'Consider Cache Cleanup',
                'description' => 'Large number of cached watermarked images detected',
                'action' => 'Run cache cleanup to free disk space'
            ];
        }

        return $recommendations;
    }

    /**
     * Test error handling system
     */
    public function testErrorHandling(): JsonResponse
    {
        try {
            $results = $this->watermarkService->testErrorHandling();
            
            return response()->json([
                'success' => true,
                'message' => 'Error handling test completed',
                'results' => $results,
                'test_timestamp' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error handling test failed: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get protection effectiveness report
     */
    public function protectionReport(): JsonResponse
    {
        $protectionService = app(\App\Services\ImageProtectionService::class);
        $report = $protectionService->getProtectionEffectivenessReport();
        
        return response()->json([
            'protection_report' => $report,
            'generated_at' => now()->toISOString()
        ]);
    }
}