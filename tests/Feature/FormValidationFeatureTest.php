<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FormValidationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_form_requires_csrf_token()
    {
        $response = $this->post('/contact', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'This is a test message',
        ]);

        $response->assertStatus(419); // CSRF token mismatch
    }

    public function test_contact_form_validation_with_valid_data()
    {
        $response = $this->post('/contact', [
            '_token' => csrf_token(),
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'This is a test message with enough characters',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_contact_form_validation_with_invalid_data()
    {
        $response = $this->post('/contact', [
            '_token' => csrf_token(),
            'name' => '', // Required field empty
            'email' => 'invalid-email', // Invalid email
            'message' => 'Short', // Too short
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['name', 'email', 'message']);
    }

    public function test_admin_product_form_requires_authentication()
    {
        $response = $this->post('/admin/products', [
            '_token' => csrf_token(),
            'name' => 'Test Product',
            'price' => '99.99',
            'short_description' => 'A short description',
            'long_description' => 'A longer description',
        ]);

        $response->assertRedirect('/login');
    }

    public function test_admin_product_form_requires_admin_privileges()
    {
        $user = User::factory()->create(['is_admin' => false]);
        
        $response = $this->actingAs($user)->post('/admin/products', [
            '_token' => csrf_token(),
            'name' => 'Test Product',
            'price' => '99.99',
            'short_description' => 'A short description',
            'long_description' => 'A longer description',
        ]);

        $response->assertStatus(403); // Forbidden
    }

    public function test_admin_product_form_validation_with_valid_data()
    {
        Storage::fake('public');
        $admin = User::factory()->create(['is_admin' => true]);
        
        $response = $this->actingAs($admin)->post('/admin/products', [
            '_token' => csrf_token(),
            'name' => 'Test Product',
            'price' => '99.99',
            'short_description' => 'A short description for the product',
            'long_description' => 'A much longer and detailed description of the product with all its features and benefits',
            'image' => UploadedFile::fake()->image('product.jpg', 800, 600),
        ]);

        $response->assertRedirect('/admin/products');
        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('products', [
            'name' => 'Test Product',
            'price' => '99.99',
        ]);
    }

    public function test_admin_product_form_validation_with_invalid_data()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        
        $response = $this->actingAs($admin)->post('/admin/products', [
            '_token' => csrf_token(),
            'name' => '', // Required field empty
            'price' => 'invalid', // Invalid price
            'short_description' => '', // Required field empty
            'long_description' => '', // Required field empty
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['name', 'price', 'short_description', 'long_description']);
    }

    public function test_admin_product_update_form_validation()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $product = Product::factory()->create();
        
        $response = $this->actingAs($admin)->put("/admin/products/{$product->id}", [
            '_token' => csrf_token(),
            'name' => 'Updated Product Name',
            'price' => '149.99',
            'short_description' => 'Updated short description',
            'long_description' => 'Updated long description with more details',
        ]);

        $response->assertRedirect('/admin/products');
        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product Name',
            'price' => '149.99',
        ]);
    }

    public function test_csrf_token_is_present_in_contact_form()
    {
        $response = $this->get('/contact');
        
        $response->assertStatus(200);
        $response->assertSee('name="_token"', false);
    }

    public function test_csrf_token_is_present_in_admin_product_forms()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        
        $response = $this->actingAs($admin)->get('/admin/products/create');
        
        $response->assertStatus(200);
        $response->assertSee('name="_token"', false);
    }
}