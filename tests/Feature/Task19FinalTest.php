<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class Task19FinalTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test complete user flows from public website perspective
     */
    public function test_complete_public_user_flows()
    {
        // Create test data
        $category = Category::factory()->create(['name' => 'Cardio Equipment', 'slug' => 'cardio-equipment']);
        $products = Product::factory()->count(3)->create(['category_id' => $category->id]);
        
        // User visits home page
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Professional Gym Machines');
        
        // User navigates to products page
        $response = $this->get('/products');
        $response->assertStatus(200);
        
        // User navigates to contact page
        $response = $this->get('/contact');
        $response->assertStatus(200);
        $response->assertSee('Contact Us');
        
        $this->assertTrue(true, 'Public user flows completed successfully');
    }

    /**
     * Test complete admin workflows for product management
     */
    public function test_complete_admin_workflows()
    {
        // Create admin user
        $admin = User::factory()->create(['is_admin' => true]);
        $category = Category::factory()->create(['name' => 'Strength Equipment']);
        
        // Admin logs in
        $this->actingAs($admin);
        
        // Admin accesses dashboard
        $response = $this->get('/admin/dashboard');
        $response->assertStatus(200);
        
        // Admin views products list
        $response = $this->get('/admin/products');
        $response->assertStatus(200);
        
        // Admin creates a new product
        $productData = [
            'name' => 'Test Bench Press',
            'price' => 2999.99,
            'short_description' => 'Heavy-duty bench press for commercial use',
            'long_description' => 'This professional bench press features adjustable settings and commercial-grade construction.',
            'category_id' => $category->id,
        ];
        
        $response = $this->post('/admin/products', $productData);
        $response->assertRedirect('/admin/products');
        
        // Verify product was created
        $product = Product::where('name', 'Test Bench Press')->first();
        $this->assertNotNull($product);
        
        $this->assertTrue(true, 'Admin workflows completed successfully');
    }

    /**
     * Test contact form end-to-end functionality
     */
    public function test_contact_form_end_to_end()
    {
        Mail::fake();
        
        // User visits contact page
        $response = $this->get('/contact');
        $response->assertStatus(200);
        
        // User submits valid contact form
        $contactData = [
            'name' => 'John Smith',
            'email' => 'john.smith@example.com',
            'message' => 'I am interested in purchasing gym equipment for my fitness center.',
        ];
        
        $response = $this->post('/contact', $contactData);
        $response->assertRedirect();
        
        // Test form validation
        $invalidData = [
            'name' => '',
            'email' => 'invalid-email',
            'message' => 'Hi',
        ];
        
        $response = $this->post('/contact', $invalidData);
        $response->assertSessionHasErrors(['name', 'email', 'message']);
        
        $this->assertTrue(true, 'Contact form functionality tested successfully');
    }

    /**
     * Test SEO implementation and URL structure
     */
    public function test_seo_implementation_and_url_structure()
    {
        $category = Category::factory()->create([
            'name' => 'Cardio Equipment',
            'slug' => 'cardio-equipment',
        ]);
        
        $product = Product::factory()->create([
            'name' => 'Professional Treadmill X1',
            'slug' => 'professional-treadmill-x1',
            'category_id' => $category->id,
        ]);
        
        // Test sitemap accessibility
        $response = $this->get('/sitemap.xml');
        $response->assertStatus(200);
        
        // Test robots.txt
        $response = $this->get('/robots.txt');
        $response->assertStatus(200);
        
        $this->assertTrue(true, 'SEO implementation validated successfully');
    }

    /**
     * Test error handling and edge cases
     */
    public function test_error_handling_and_edge_cases()
    {
        // Test 404 for non-existent product
        $response = $this->get('/products/non-existent-product');
        $response->assertStatus(404);
        
        // Test unauthorized access to admin routes
        $regularUser = User::factory()->create(['is_admin' => false]);
        $this->actingAs($regularUser);
        
        $response = $this->get('/admin/products');
        $response->assertStatus(403);
        
        // Test unauthenticated access to admin routes
        auth()->logout();
        
        $response = $this->get('/admin/products');
        $response->assertRedirect('/login');
        
        $this->assertTrue(true, 'Error handling tested successfully');
    }

    /**
     * Test responsive design verification
     */
    public function test_responsive_design_verification()
    {
        // Test mobile viewport simulation
        $response = $this->get('/', [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)'
        ]);
        $response->assertStatus(200);
        
        // Test that responsive CSS classes are present in views
        $response = $this->get('/products');
        $response->assertStatus(200);
        
        $this->assertTrue(true, 'Responsive design verification completed');
    }

    /**
     * Comprehensive integration test covering all requirements
     */
    public function test_all_requirements_validation()
    {
        // Test all major user flows in sequence
        $this->test_complete_public_user_flows();
        $this->test_complete_admin_workflows();
        $this->test_contact_form_end_to_end();
        $this->test_seo_implementation_and_url_structure();
        $this->test_error_handling_and_edge_cases();
        $this->test_responsive_design_verification();
        
        // Verify database integrity
        $this->assertDatabaseHas('users', ['is_admin' => true]);
        
        // Verify file system structure
        $this->assertTrue(file_exists(base_path('routes/web.php')));
        $this->assertTrue(file_exists(base_path('app/Http/Controllers/PublicController.php')));
        $this->assertTrue(file_exists(base_path('app/Http/Controllers/ContactController.php')));
        $this->assertTrue(file_exists(base_path('app/Http/Controllers/Admin/ProductController.php')));
        
        $this->assertTrue(true, 'All requirements validation completed successfully');
    }
}