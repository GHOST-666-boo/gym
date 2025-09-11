<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\ProductView;
use App\Models\ContactSubmission;

class AnalyticsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user
        $this->admin = User::factory()->create([
            'email' => 'admin@example.com',
            'is_admin' => true,
        ]);
    }

    public function test_analytics_dashboard_requires_authentication()
    {
        $response = $this->get(route('admin.analytics.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_analytics_dashboard_requires_admin_access()
    {
        $user = User::factory()->create(['is_admin' => false]);
        
        $response = $this->actingAs($user)->get(route('admin.analytics.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_access_analytics_dashboard()
    {
        $product = Product::factory()->create();
        
        // Create some test data
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

        $response = $this->actingAs($this->admin)->get(route('admin.analytics.index'));
        
        $response->assertStatus(200);
        $response->assertViewIs('admin.analytics.index');
        $response->assertViewHas(['analytics', 'period']);
    }

    public function test_analytics_data_endpoint_returns_json()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.analytics.data'));
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'overview',
            'product_views',
            'popular_products',
            'contact_analytics',
            'trends',
            'category_performance',
        ]);
    }

    public function test_real_time_analytics_endpoint_returns_json()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.analytics.real-time'));
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'views_today',
            'unique_visitors_today',
            'contacts_today',
            'views_this_hour',
            'recent_views',
        ]);
    }

    public function test_analytics_export_returns_csv()
    {
        $product = Product::factory()->create();
        
        ProductView::create([
            'product_id' => $product->id,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Test Agent',
            'viewed_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.analytics.export'));
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Analytics Overview', $response->getContent());
    }

    public function test_clear_cache_endpoint_works()
    {
        $response = $this->actingAs($this->admin)->post(route('admin.analytics.clear-cache'));
        
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Analytics cache cleared successfully.'
        ]);
    }

    public function test_analytics_dashboard_with_different_periods()
    {
        $periods = ['7days', '30days', '90days', 'year'];
        
        foreach ($periods as $period) {
            $response = $this->actingAs($this->admin)->get(route('admin.analytics.index', ['period' => $period]));
            
            $response->assertStatus(200);
            $response->assertViewHas('period', $period);
        }
    }

    public function test_product_view_tracking_works()
    {
        $product = Product::factory()->create();
        
        // Visit product page
        $response = $this->get(route('products.show', $product));
        
        $response->assertStatus(200);
        
        // Check that view was tracked
        $this->assertDatabaseHas('product_views', [
            'product_id' => $product->id,
        ]);
    }

    public function test_contact_submission_tracking_works()
    {
        $contactData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'message' => 'Test message',
        ];

        $response = $this->post(route('contact.store'), $contactData);
        
        // Check that submission was tracked
        $this->assertDatabaseHas('contact_submissions', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'message' => 'Test message',
        ]);
    }
}
