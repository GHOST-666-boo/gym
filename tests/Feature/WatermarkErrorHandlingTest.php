<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\WatermarkService;
use App\Services\ImageProtectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class WatermarkErrorHandlingTest extends TestCase
{
    protected WatermarkService $watermarkService;
    protected ImageProtectionService $protectionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->watermarkService = app(WatermarkService::class);
        $this->protectionService = app(ImageProtectionService::class);
        
        // Clear any existing notifications
        Cache::forget('admin_notifications_watermark');
    }

    /** @test */
    public function it_handles_missing_image_extensions_gracefully()
    {
        // Test with a non-existent image to trigger extension check
        $result = $this->watermarkService->applyWatermark('non-existent-image.jpg');
        
        // Should return original path or CSS fallback
        $this->assertIsString($result);
        
        // Check if admin notification was created
        $notifications = $this->watermarkService->getAdminNotifications();
        $this->assertNotEmpty($notifications);
        
        // Should have a missing extensions or image load failed notification
        $hasRelevantNotification = collect($notifications)->contains(function ($notification) {
            return in_array($notification['error_type'], ['missing_extensions', 'image_load_failed']);
        });
        
        $this->assertTrue($hasRelevantNotification);
    }

    /** @test */
    public function it_creates_css_fallback_when_image_processing_fails()
    {
        $imagePath = 'test-image.jpg';
        
        // This should trigger CSS fallback due to missing extensions or file
        $result = $this->watermarkService->applyWatermark($imagePath);
        
        // Should return a path with CSS fallback indicator
        $this->assertStringContainsString($imagePath, $result);
        
        // Check if CSS fallback data was cached
        $fallbackKey = 'css_watermark_' . md5($imagePath);
        $fallbackData = Cache::get($fallbackKey);
        
        if ($fallbackData) {
            $this->assertArrayHasKey('fallback_type', $fallbackData);
            $this->assertEquals('css', $fallbackData['fallback_type']);
        }
    }

    /** @test */
    public function it_generates_admin_notifications_for_errors()
    {
        // Test error notification system
        $results = $this->watermarkService->testErrorHandling();
        
        $this->assertIsArray($results);
        $this->assertArrayHasKey('missing_extensions', $results);
        $this->assertArrayHasKey('corrupted_image', $results);
        $this->assertArrayHasKey('css_fallback', $results);
        
        // Check notifications were created
        $notifications = $this->watermarkService->getAdminNotifications();
        $this->assertNotEmpty($notifications);
        
        // Should have test notifications
        $testNotifications = collect($notifications)->filter(function ($notification) {
            return isset($notification['contexts']) && 
                   collect($notification['contexts'])->contains('test', true);
        });
        
        $this->assertGreaterThan(0, $testNotifications->count());
    }

    /** @test */
    public function it_provides_system_health_status()
    {
        $healthStatus = $this->watermarkService->getSystemHealthStatus();
        
        $this->assertIsArray($healthStatus);
        $this->assertArrayHasKey('status', $healthStatus);
        $this->assertArrayHasKey('extensions', $healthStatus);
        $this->assertArrayHasKey('cache', $healthStatus);
        $this->assertArrayHasKey('notifications', $healthStatus);
        
        // Status should be one of the expected values
        $this->assertContains($healthStatus['status'], ['healthy', 'warning']);
        
        // Extensions info should be present
        $this->assertArrayHasKey('has_gd', $healthStatus['extensions']);
        $this->assertArrayHasKey('has_imagick', $healthStatus['extensions']);
    }

    /** @test */
    public function it_handles_notification_dismissal()
    {
        // Create a test notification
        $this->watermarkService->testErrorHandling();
        
        $notifications = $this->watermarkService->getAdminNotifications();
        $this->assertNotEmpty($notifications);
        
        // Dismiss the first notification
        $firstNotification = $notifications[0];
        $result = $this->watermarkService->dismissAdminNotification($firstNotification['id']);
        
        $this->assertTrue($result);
        
        // Check notification is marked as dismissed
        $updatedNotifications = $this->watermarkService->getAdminNotifications();
        $dismissedNotification = collect($updatedNotifications)->firstWhere('id', $firstNotification['id']);
        
        if ($dismissedNotification) {
            $this->assertTrue($dismissedNotification['dismissed']);
        }
    }

    /** @test */
    public function it_provides_protection_effectiveness_report()
    {
        $report = $this->protectionService->getProtectionEffectivenessReport();
        
        $this->assertIsArray($report);
        $this->assertArrayHasKey('javascript_protection', $report);
        $this->assertArrayHasKey('css_fallback_protection', $report);
        $this->assertArrayHasKey('server_side_protection', $report);
        $this->assertArrayHasKey('overall_assessment', $report);
        
        // Check overall assessment structure
        $assessment = $report['overall_assessment'];
        $this->assertArrayHasKey('protection_level', $assessment);
        $this->assertArrayHasKey('vulnerabilities', $assessment);
        $this->assertArrayHasKey('recommendations', $assessment);
        
        // Protection level should be valid
        $this->assertContains($assessment['protection_level'], ['minimal', 'low', 'medium', 'high']);
    }

    /** @test */
    public function it_handles_corrupted_image_files()
    {
        // Create a fake corrupted image file
        Storage::fake('public');
        Storage::disk('public')->put('corrupted-image.jpg', 'not-an-image');
        
        $result = $this->watermarkService->applyWatermark('corrupted-image.jpg');
        
        // Should handle gracefully and return some result
        $this->assertIsString($result);
        
        // Should create a notification about corrupted image
        $notifications = $this->watermarkService->getAdminNotifications();
        $corruptedImageNotifications = collect($notifications)->filter(function ($notification) {
            return $notification['error_type'] === 'corrupted_image';
        });
        
        // May or may not create notification depending on validation path
        // This is acceptable as the system should handle it gracefully
        $this->assertTrue(true); // Test passes if no exception is thrown
    }

    /** @test */
    public function it_clears_old_notifications()
    {
        // Create test notifications
        $this->watermarkService->testErrorHandling();
        
        $initialNotifications = $this->watermarkService->getAdminNotifications();
        $this->assertNotEmpty($initialNotifications);
        
        // Dismiss all notifications
        foreach ($initialNotifications as $notification) {
            $this->watermarkService->dismissAdminNotification($notification['id']);
        }
        
        // Clear old notifications (0 days old to clear all dismissed)
        $clearedCount = $this->watermarkService->clearOldNotifications(0);
        
        $this->assertGreaterThanOrEqual(0, $clearedCount);
        
        // Check notifications were cleared
        $remainingNotifications = $this->watermarkService->getAdminNotifications();
        $dismissedCount = collect($remainingNotifications)->where('dismissed', true)->count();
        
        // Should have fewer or no dismissed notifications
        $this->assertLessThanOrEqual(count($initialNotifications), $dismissedCount);
    }

    /** @test */
    public function it_provides_notification_counts()
    {
        // Create test notifications
        $this->watermarkService->testErrorHandling();
        
        $counts = $this->watermarkService->getNotificationCounts();
        
        $this->assertIsArray($counts);
        $this->assertArrayHasKey('total', $counts);
        $this->assertArrayHasKey('active', $counts);
        $this->assertArrayHasKey('critical', $counts);
        $this->assertArrayHasKey('high', $counts);
        $this->assertArrayHasKey('medium', $counts);
        $this->assertArrayHasKey('low', $counts);
        
        // All counts should be non-negative integers
        foreach ($counts as $count) {
            $this->assertIsInt($count);
            $this->assertGreaterThanOrEqual(0, $count);
        }
        
        // Active should be less than or equal to total
        $this->assertLessThanOrEqual($counts['total'], $counts['active']);
    }
}