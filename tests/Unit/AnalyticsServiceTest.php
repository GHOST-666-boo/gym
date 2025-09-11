<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AnalyticsService;
use App\Models\Product;
use App\Models\ProductView;
use App\Models\ContactSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AnalyticsService $analyticsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyticsService = new AnalyticsService();
    }

    public function test_can_get_dashboard_analytics()
    {
        // Create test data
        $product = Product::factory()->create();
        
        ProductView::create([
            'product_id' => $product->id,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Test Agent',
            'viewed_at' => now(),
        ]);

        ContactSubmission::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'message' => 'Test message',
            'email_sent' => true,
            'submitted_at' => now(),
        ]);

        $analytics = $this->analyticsService->getDashboardAnalytics('7days');

        $this->assertArrayHasKey('overview', $analytics);
        $this->assertArrayHasKey('product_views', $analytics);
        $this->assertArrayHasKey('popular_products', $analytics);
        $this->assertArrayHasKey('contact_analytics', $analytics);
        $this->assertArrayHasKey('trends', $analytics);
        $this->assertArrayHasKey('category_performance', $analytics);
    }

    public function test_can_track_product_view()
    {
        $product = Product::factory()->create();
        $request = Request::create('/test', 'GET', [], [], [], [
            'REMOTE_ADDR' => '192.168.1.1',
            'HTTP_USER_AGENT' => 'Test Agent',
        ]);
        
        // Mock session
        $session = $this->createMock(\Illuminate\Session\Store::class);
        $session->method('getId')->willReturn('test-session-id');
        $request->setLaravelSession($session);

        $this->analyticsService->trackProductView($product, $request);

        $this->assertDatabaseHas('product_views', [
            'product_id' => $product->id,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Test Agent',
            'session_id' => 'test-session-id',
        ]);
    }

    public function test_can_track_contact_submission()
    {
        $contactData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'message' => 'Test message',
        ];

        $request = Request::create('/test', 'POST', [], [], [], [
            'REMOTE_ADDR' => '192.168.1.1',
            'HTTP_USER_AGENT' => 'Test Agent',
        ]);

        $this->analyticsService->trackContactSubmission($contactData, true, $request);

        $this->assertDatabaseHas('contact_submissions', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'message' => 'Test message',
            'email_sent' => true,
            'ip_address' => '192.168.1.1',
        ]);
    }

    public function test_can_get_real_time_analytics()
    {
        $product = Product::factory()->create();
        
        // Create today's data
        ProductView::create([
            'product_id' => $product->id,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Test Agent',
            'viewed_at' => now(),
        ]);

        ContactSubmission::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'message' => 'Test message',
            'email_sent' => true,
            'submitted_at' => now(),
        ]);

        $realTimeData = $this->analyticsService->getRealTimeAnalytics();

        $this->assertArrayHasKey('views_today', $realTimeData);
        $this->assertArrayHasKey('unique_visitors_today', $realTimeData);
        $this->assertArrayHasKey('contacts_today', $realTimeData);
        $this->assertArrayHasKey('views_this_hour', $realTimeData);
        $this->assertArrayHasKey('recent_views', $realTimeData);

        $this->assertEquals(1, $realTimeData['views_today']);
        $this->assertEquals(1, $realTimeData['contacts_today']);
    }

    public function test_analytics_scopes_work_correctly()
    {
        $product = Product::factory()->create();
        
        // Create views for different time periods
        ProductView::create([
            'product_id' => $product->id,
            'ip_address' => '192.168.1.1',
            'viewed_at' => now(),
        ]);

        ProductView::create([
            'product_id' => $product->id,
            'ip_address' => '192.168.1.2',
            'viewed_at' => now()->startOfWeek()->addDay(), // Within this week
        ]);

        ProductView::create([
            'product_id' => $product->id,
            'ip_address' => '192.168.1.3',
            'viewed_at' => now()->subWeeks(2), // Outside this week
        ]);

        // Test today scope
        $todayViews = ProductView::today()->count();
        $this->assertEquals(1, $todayViews);

        // Test this week scope
        $weekViews = ProductView::thisWeek()->count();
        $this->assertEquals(2, $weekViews);

        // Test unique views
        $uniqueViews = ProductView::uniqueViews()->count();
        $this->assertEquals(3, $uniqueViews);
    }
}
