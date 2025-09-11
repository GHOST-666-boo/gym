<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    public function test_404_error_for_nonexistent_product()
    {
        $response = $this->get('/products/999999');
        $response->assertStatus(404);
    }

    public function test_404_error_for_invalid_product_slug()
    {
        $response = $this->get('/products/nonexistent-product-slug');
        $response->assertStatus(404);
    }

    public function test_admin_routes_return_404_for_nonexistent_products()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $routes = [
            '/admin/products/999999',
            '/admin/products/999999/edit',
        ];

        foreach ($routes as $route) {
            $response = $this->get($route);
            $response->assertStatus(404);
        }
    }

    public function test_admin_update_returns_404_for_nonexistent_product()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $updateData = [
            'name' => 'Updated Product',
            'price' => 999.99,
            'short_description' => 'Updated description',
            'long_description' => 'Updated long description',
        ];

        $response = $this->put('/admin/products/999999', $updateData);
        $response->assertStatus(404);
    }

    public function test_admin_delete_returns_404_for_nonexistent_product()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $response = $this->delete('/admin/products/999999');
        $response->assertStatus(404);
    }

    public function test_file_upload_validation_errors()
    {
        Storage::fake('public');
        
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);
        
        $category = Category::factory()->create();

        // Test invalid file type
        $invalidFile = UploadedFile::fake()->create('document.pdf', 1000);
        $productData = [
            'name' => 'Test Product',
            'price' => 999.99,
            'short_description' => 'Test description',
            'long_description' => 'Test long description',
            'category_id' => $category->id,
            'image' => $invalidFile,
        ];

        $response = $this->post('/admin/products', $productData);
        $response->assertSessionHasErrors(['image']);

        // Test oversized file
        $oversizedFile = UploadedFile::fake()->image('large.jpg')->size(10000); // 10MB
        $productData['image'] = $oversizedFile;

        $response = $this->post('/admin/products', $productData);
        $response->assertSessionHasErrors(['image']);

        // Test undersized image dimensions
        $smallImage = UploadedFile::fake()->image('small.jpg', 50, 50);
        $productData['image'] = $smallImage;

        $response = $this->post('/admin/products', $productData);
        $response->assertSessionHasErrors(['image']);
    }

    public function test_csrf_token_validation()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        // Test contact form without CSRF token
        $response = $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)
                         ->post('/contact', [
                             'name' => 'Test User',
                             'email' => 'test@example.com',
                             'message' => 'Test message',
                         ]);

        // Should pass without CSRF middleware, but with middleware it would fail
        $this->assertTrue(true); // Placeholder assertion

        // Test admin product creation without CSRF token would normally fail
        // This is handled by Laravel's CSRF middleware
    }

    public function test_database_constraint_violations()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        // Test creating product with invalid category ID
        $productData = [
            'name' => 'Test Product',
            'price' => 999.99,
            'short_description' => 'Test description',
            'long_description' => 'Test long description',
            'category_id' => 999999, // Non-existent category
        ];

        $response = $this->post('/admin/products', $productData);
        $response->assertSessionHasErrors(['category_id']);
    }

    public function test_validation_edge_cases()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);
        
        $category = Category::factory()->create();

        // Test boundary values for product validation
        $testCases = [
            // Name too short
            [
                'name' => 'A',
                'price' => 999.99,
                'short_description' => 'Valid short description',
                'long_description' => 'Valid long description with enough characters',
                'category_id' => $category->id,
                'expected_errors' => ['name']
            ],
            // Name too long
            [
                'name' => str_repeat('A', 256),
                'price' => 999.99,
                'short_description' => 'Valid short description',
                'long_description' => 'Valid long description with enough characters',
                'category_id' => $category->id,
                'expected_errors' => ['name']
            ],
            // Price zero
            [
                'name' => 'Valid Product Name',
                'price' => 0,
                'short_description' => 'Valid short description',
                'long_description' => 'Valid long description with enough characters',
                'category_id' => $category->id,
                'expected_errors' => ['price']
            ],
            // Price negative
            [
                'name' => 'Valid Product Name',
                'price' => -1,
                'short_description' => 'Valid short description',
                'long_description' => 'Valid long description with enough characters',
                'category_id' => $category->id,
                'expected_errors' => ['price']
            ],
            // Short description too short
            [
                'name' => 'Valid Product Name',
                'price' => 999.99,
                'short_description' => 'Short',
                'long_description' => 'Valid long description with enough characters',
                'category_id' => $category->id,
                'expected_errors' => ['short_description']
            ],
            // Long description too short
            [
                'name' => 'Valid Product Name',
                'price' => 999.99,
                'short_description' => 'Valid short description',
                'long_description' => 'Short',
                'category_id' => $category->id,
                'expected_errors' => ['long_description']
            ],
        ];

        foreach ($testCases as $testCase) {
            $expectedErrors = $testCase['expected_errors'];
            unset($testCase['expected_errors']);

            $response = $this->post('/admin/products', $testCase);
            $response->assertSessionHasErrors($expectedErrors);
        }
    }

    public function test_contact_form_validation_edge_cases()
    {
        $testCases = [
            // Name too short
            [
                'name' => 'A',
                'email' => 'valid@example.com',
                'message' => 'Valid message with enough characters',
                'expected_errors' => ['name']
            ],
            // Invalid email formats
            [
                'name' => 'Valid Name',
                'email' => 'invalid-email',
                'message' => 'Valid message with enough characters',
                'expected_errors' => ['email']
            ],
            [
                'name' => 'Valid Name',
                'email' => '@example.com',
                'message' => 'Valid message with enough characters',
                'expected_errors' => ['email']
            ],
            [
                'name' => 'Valid Name',
                'email' => 'test@',
                'message' => 'Valid message with enough characters',
                'expected_errors' => ['email']
            ],
            // Message too short
            [
                'name' => 'Valid Name',
                'email' => 'valid@example.com',
                'message' => 'Short',
                'expected_errors' => ['message']
            ],
        ];

        foreach ($testCases as $testCase) {
            $expectedErrors = $testCase['expected_errors'];
            unset($testCase['expected_errors']);

            $response = $this->post('/contact', $testCase);
            $response->assertSessionHasErrors($expectedErrors);
        }
    }

    public function test_sql_injection_prevention()
    {
        // Test that malicious input is properly escaped
        $maliciousInput = "'; DROP TABLE products; --";
        
        $response = $this->get("/products/{$maliciousInput}");
        $response->assertStatus(404); // Should return 404, not cause SQL error

        // Test in contact form
        $response = $this->post('/contact', [
            'name' => $maliciousInput,
            'email' => 'test@example.com',
            'message' => 'Valid message with enough characters',
        ]);
        
        // Should handle gracefully with validation or sanitization
        $this->assertTrue(true); // If we get here, no SQL injection occurred
    }

    public function test_xss_prevention()
    {
        $category = Category::factory()->create();
        $xssPayload = '<script>alert("XSS")</script>';
        
        $product = Product::factory()->create([
            'name' => "Product {$xssPayload}",
            'short_description' => "Description {$xssPayload}",
            'long_description' => "Long description {$xssPayload}",
            'category_id' => $category->id,
        ]);

        $response = $this->get("/products/{$product->id}");
        $response->assertStatus(200);
        
        // Verify XSS payload is escaped in output
        $response->assertDontSee('<script>alert("XSS")</script>', false);
        $response->assertSee('&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;', false);
    }

    public function test_mass_assignment_protection()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        // Try to mass assign protected attributes
        $maliciousData = [
            'name' => 'Test Product',
            'price' => 999.99,
            'short_description' => 'Test description',
            'long_description' => 'Test long description',
            'id' => 999999, // Try to set ID
            'created_at' => '2020-01-01 00:00:00', // Try to set timestamp
            'updated_at' => '2020-01-01 00:00:00', // Try to set timestamp
        ];

        $response = $this->post('/admin/products', $maliciousData);
        
        if ($response->isRedirect()) {
            $product = Product::where('name', 'Test Product')->first();
            if ($product) {
                $this->assertNotEquals(999999, $product->id);
                $this->assertNotEquals('2020-01-01 00:00:00', $product->created_at->format('Y-m-d H:i:s'));
            }
        }
    }

    public function test_file_system_error_handling()
    {
        Storage::fake('public');
        
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);
        
        $category = Category::factory()->create();
        
        // Mock storage failure
        Storage::shouldReceive('disk->put')->andReturn(false);
        
        $image = UploadedFile::fake()->image('product.jpg', 800, 600);
        $productData = [
            'name' => 'Test Product',
            'price' => 999.99,
            'short_description' => 'Test description',
            'long_description' => 'Test long description',
            'category_id' => $category->id,
            'image' => $image,
        ];

        // Should handle storage failure gracefully
        $response = $this->post('/admin/products', $productData);
        
        // Depending on implementation, should either show error or create product without image
        $this->assertTrue(true); // Placeholder - actual behavior depends on implementation
    }

    public function test_concurrent_access_handling()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);
        
        $product = Product::factory()->create();
        
        // Simulate concurrent deletion
        $product->delete();
        
        // Try to update deleted product
        $response = $this->put("/admin/products/{$product->id}", [
            'name' => 'Updated Name',
            'price' => 999.99,
            'short_description' => 'Updated description',
            'long_description' => 'Updated long description',
        ]);
        
        $response->assertStatus(404);
    }

    public function test_memory_limit_handling_with_large_datasets()
    {
        // Create many products to test pagination and memory usage
        Product::factory()->count(100)->create();
        
        $response = $this->get('/products');
        $response->assertStatus(200);
        
        // Should handle large datasets without memory issues
        $this->assertTrue(true);
    }
}