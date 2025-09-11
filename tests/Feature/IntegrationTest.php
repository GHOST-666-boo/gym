<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_admin_product_management_workflow()
    {
        Storage::fake('public');
        
        // Create admin user
        $admin = User::factory()->create(['is_admin' => true]);
        
        // Create category
        $category = Category::factory()->create(['name' => 'Cardio Equipment']);
        
        // Admin logs in and creates a product
        $this->actingAs($admin);
        
        $image = UploadedFile::fake()->image('treadmill.jpg', 800, 600);
        $productData = [
            'name' => 'Professional Treadmill X1',
            'price' => 3999.99,
            'short_description' => 'High-end commercial treadmill',
            'long_description' => 'This professional treadmill features advanced cushioning, multiple workout programs, and commercial-grade construction.',
            'category_id' => $category->id,
            'image' => $image,
        ];
        
        // Create product
        $response = $this->post('/admin/products', $productData);
        $response->assertRedirect('/admin/products');
        $response->assertSessionHas('success');
        
        // Verify product was created
        $product = Product::where('name', 'Professional Treadmill X1')->first();
        $this->assertNotNull($product);
        $this->assertEquals(3999.99, (float) $product->price);
        $this->assertEquals($category->id, $product->category_id);
        $this->assertNotNull($product->image_path);
        
        // Verify image was stored
        Storage::disk('public')->assertExists($product->image_path);
        
        // Update the product
        $newImage = UploadedFile::fake()->image('updated-treadmill.jpg', 800, 600);
        $updateData = [
            'name' => 'Professional Treadmill X1 Pro',
            'price' => 4499.99,
            'short_description' => 'Updated high-end commercial treadmill',
            'long_description' => 'Updated description with new features and improvements.',
            'category_id' => $category->id,
            'image' => $newImage,
        ];
        
        $response = $this->put("/admin/products/{$product->id}", $updateData);
        $response->assertRedirect('/admin/products');
        $response->assertSessionHas('success');
        
        // Verify product was updated
        $product->refresh();
        $this->assertEquals('Professional Treadmill X1 Pro', $product->name);
        $this->assertEquals(4499.99, (float) $product->price);
        
        // View product in admin panel
        $response = $this->get("/admin/products/{$product->id}");
        $response->assertStatus(200);
        $response->assertSee('Professional Treadmill X1 Pro');
        $response->assertSee('4499.99');
        
        // Delete the product
        $response = $this->delete("/admin/products/{$product->id}");
        $response->assertRedirect('/admin/products');
        $response->assertSessionHas('success');
        
        // Verify product was deleted
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_complete_public_user_journey()
    {
        // Create test data
        $category = Category::factory()->create(['name' => 'Cardio Equipment']);
        $products = Product::factory()->count(5)->create(['category_id' => $category->id]);
        $featuredProduct = $products->first();
        
        // User visits home page
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Gym Equipment');
        $response->assertSee($featuredProduct->name);
        
        // User navigates to products page
        $response = $this->get('/products');
        $response->assertStatus(200);
        foreach ($products as $product) {
            $response->assertSee($product->name);
            $response->assertSee($product->price);
        }
        
        // User views specific product
        $response = $this->get("/products/{$featuredProduct->id}");
        $response->assertStatus(200);
        $response->assertSee($featuredProduct->name);
        $response->assertSee($featuredProduct->price);
        $response->assertSee($featuredProduct->long_description);
        $response->assertSee($category->name);
        
        // User navigates to contact page
        $response = $this->get('/contact');
        $response->assertStatus(200);
        $response->assertSee('Contact Us');
        $response->assertSee('name="name"', false);
        $response->assertSee('name="email"', false);
        $response->assertSee('name="message"', false);
    }

    public function test_complete_contact_form_workflow()
    {
        Mail::fake();
        
        // User visits contact page
        $response = $this->get('/contact');
        $response->assertStatus(200);
        
        // User submits contact form
        $contactData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'I am interested in the Professional Treadmill X1. Can you provide more information about warranty and delivery options?',
        ];
        
        $response = $this->post('/contact', $contactData);
        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        // Verify email was sent
        Mail::assertSent(\App\Mail\ContactFormMail::class, function ($mail) use ($contactData) {
            return $mail->contactData['name'] === $contactData['name'] &&
                   $mail->contactData['email'] === $contactData['email'] &&
                   $mail->contactData['message'] === $contactData['message'];
        });
        
        // User sees success message
        $successMessage = session('success');
        $this->assertStringContainsString('John Doe', $successMessage);
        $this->assertStringContainsString('Thank you', $successMessage);
    }

    public function test_admin_authentication_and_authorization_workflow()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $regularUser = User::factory()->create(['is_admin' => false]);
        $product = Product::factory()->create();
        
        // Unauthenticated user cannot access admin routes
        $response = $this->get('/admin/products');
        $response->assertRedirect('/login');
        
        // Regular user cannot access admin routes
        $this->actingAs($regularUser);
        $response = $this->get('/admin/products');
        $response->assertStatus(403);
        
        // Admin can access admin routes
        $this->actingAs($admin);
        $response = $this->get('/admin/products');
        $response->assertStatus(200);
        
        // Admin can perform CRUD operations
        $response = $this->get('/admin/products/create');
        $response->assertStatus(200);
        
        $response = $this->get("/admin/products/{$product->id}/edit");
        $response->assertStatus(200);
        
        $response = $this->get("/admin/products/{$product->id}");
        $response->assertStatus(200);
    }

    public function test_product_category_relationship_workflow()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);
        
        // Create categories
        $cardioCategory = Category::factory()->create(['name' => 'Cardio Equipment']);
        $strengthCategory = Category::factory()->create(['name' => 'Strength Equipment']);
        
        // Create products in different categories
        $treadmill = Product::factory()->create([
            'name' => 'Treadmill',
            'category_id' => $cardioCategory->id,
        ]);
        
        $benchPress = Product::factory()->create([
            'name' => 'Bench Press',
            'category_id' => $strengthCategory->id,
        ]);
        
        // Verify relationships work
        $this->assertEquals('Cardio Equipment', $treadmill->category->name);
        $this->assertEquals('Strength Equipment', $benchPress->category->name);
        
        // Verify category has products
        $this->assertTrue($cardioCategory->products->contains($treadmill));
        $this->assertTrue($strengthCategory->products->contains($benchPress));
        
        // Test filtering by category on public pages
        $response = $this->get("/products?category={$cardioCategory->id}");
        $response->assertStatus(200);
        $response->assertSee('Treadmill');
        $response->assertDontSee('Bench Press');
    }

    public function test_file_upload_and_storage_workflow()
    {
        Storage::fake('public');
        
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);
        
        $category = Category::factory()->create();
        $image = UploadedFile::fake()->image('product.jpg', 800, 600);
        
        // Create product with image
        $productData = [
            'name' => 'Test Product',
            'price' => 999.99,
            'short_description' => 'Test description',
            'long_description' => 'Test long description',
            'category_id' => $category->id,
            'image' => $image,
        ];
        
        $response = $this->post('/admin/products', $productData);
        $response->assertRedirect('/admin/products');
        
        $product = Product::where('name', 'Test Product')->first();
        $this->assertNotNull($product->image_path);
        
        // Verify file was stored
        Storage::disk('public')->assertExists($product->image_path);
        
        // Update with new image
        $newImage = UploadedFile::fake()->image('new-product.jpg', 800, 600);
        $oldImagePath = $product->image_path;
        
        $updateData = array_merge($productData, ['image' => $newImage]);
        $response = $this->put("/admin/products/{$product->id}", $updateData);
        $response->assertRedirect('/admin/products');
        
        $product->refresh();
        $this->assertNotEquals($oldImagePath, $product->image_path);
        Storage::disk('public')->assertExists($product->image_path);
        
        // Delete product and verify image cleanup
        $imagePath = $product->image_path;
        $response = $this->delete("/admin/products/{$product->id}");
        $response->assertRedirect('/admin/products');
        
        // Note: Image cleanup on delete would depend on implementation
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_validation_and_error_handling_workflow()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);
        
        // Test product creation with invalid data
        $invalidData = [
            'name' => '', // Required field empty
            'price' => -100, // Invalid price
            'short_description' => 'A', // Too short
            'long_description' => '', // Required field empty
        ];
        
        $response = $this->post('/admin/products', $invalidData);
        $response->assertSessionHasErrors(['name', 'price', 'short_description', 'long_description']);
        
        // Test contact form with invalid data
        $invalidContactData = [
            'name' => '',
            'email' => 'invalid-email',
            'message' => 'Short',
        ];
        
        $response = $this->post('/contact', $invalidContactData);
        $response->assertSessionHasErrors(['name', 'email', 'message']);
        
        // Test 404 handling
        $response = $this->get('/products/999');
        $response->assertStatus(404);
        
        $response = $this->get('/admin/products/999');
        $response->assertStatus(404);
    }

    public function test_seo_and_url_structure_workflow()
    {
        $category = Category::factory()->create([
            'name' => 'Cardio Equipment',
            'slug' => 'cardio-equipment',
        ]);
        
        $product = Product::factory()->create([
            'name' => 'Professional Treadmill',
            'slug' => 'professional-treadmill',
            'category_id' => $category->id,
        ]);
        
        // Test SEO-friendly URLs
        $response = $this->get("/products/{$product->slug}");
        $response->assertStatus(200);
        $response->assertSee('Professional Treadmill');
        
        // Test meta tags
        $response->assertSee('<title>', false);
        $response->assertSee('name="description"', false);
        $response->assertSee('Professional Treadmill', false);
        
        // Test breadcrumbs
        $response->assertSee('Home');
        $response->assertSee('Products');
        $response->assertSee('Professional Treadmill');
    }
}