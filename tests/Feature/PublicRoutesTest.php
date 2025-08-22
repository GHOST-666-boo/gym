<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_route_is_defined(): void
    {
        // Test that the home route exists and points to correct controller
        $response = $this->get('/');
        
        // Should not be 404 (route exists)
        $this->assertNotEquals(404, $response->getStatusCode());
    }

    public function test_products_index_route_is_defined(): void
    {
        // Test that the products index route exists
        $response = $this->get('/products');
        
        // Should not be 404 (route exists)
        $this->assertNotEquals(404, $response->getStatusCode());
    }

    public function test_product_show_route_is_defined(): void
    {
        // Create a test product directly in database
        $product = Product::create([
            'name' => 'Test Machine',
            'price' => 999.99,
            'short_description' => 'Test description',
            'long_description' => 'Test long description',
        ]);

        $response = $this->get("/products/{$product->id}");
        
        // Should not be 404 (route exists)
        $this->assertNotEquals(404, $response->getStatusCode());
    }

    public function test_product_show_returns_404_for_nonexistent_product(): void
    {
        $response = $this->get('/products/999');

        $response->assertStatus(404);
    }

    public function test_contact_show_route_is_defined(): void
    {
        // Test that the contact route exists
        $response = $this->get('/contact');
        
        // Should not be 404 (route exists)
        $this->assertNotEquals(404, $response->getStatusCode());
    }

    public function test_contact_store_route_is_defined(): void
    {
        // Test that the contact POST route exists
        $response = $this->post('/contact', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'message' => 'Test message'
        ]);
        
        // Should not be 404 (route exists) - may be 500 due to missing views but route exists
        $this->assertNotEquals(404, $response->getStatusCode());
    }

    public function test_public_routes_do_not_require_authentication(): void
    {
        // Create a test product
        $product = Product::create([
            'name' => 'Test Machine',
            'price' => 999.99,
            'short_description' => 'Test description',
            'long_description' => 'Test long description',
        ]);

        // Test all public routes are accessible without authentication (not 401/403)
        $homeResponse = $this->get('/');
        $this->assertNotEquals(401, $homeResponse->getStatusCode());
        $this->assertNotEquals(403, $homeResponse->getStatusCode());

        $productsResponse = $this->get('/products');
        $this->assertNotEquals(401, $productsResponse->getStatusCode());
        $this->assertNotEquals(403, $productsResponse->getStatusCode());

        $productResponse = $this->get("/products/{$product->id}");
        $this->assertNotEquals(401, $productResponse->getStatusCode());
        $this->assertNotEquals(403, $productResponse->getStatusCode());

        $contactResponse = $this->get('/contact');
        $this->assertNotEquals(401, $contactResponse->getStatusCode());
        $this->assertNotEquals(403, $contactResponse->getStatusCode());
    }

    public function test_routes_use_correct_controller_methods(): void
    {
        // Test that routes are properly mapped to controller methods
        $routes = \Route::getRoutes();
        
        // Check home route
        $homeRoute = $routes->getByName('home');
        $this->assertNotNull($homeRoute);
        $this->assertEquals('App\Http\Controllers\PublicController@home', $homeRoute->getActionName());

        // Check products index route
        $productsRoute = $routes->getByName('products.index');
        $this->assertNotNull($productsRoute);
        $this->assertEquals('App\Http\Controllers\PublicController@products', $productsRoute->getActionName());

        // Check product show route
        $productShowRoute = $routes->getByName('products.show');
        $this->assertNotNull($productShowRoute);
        $this->assertEquals('App\Http\Controllers\PublicController@show', $productShowRoute->getActionName());

        // Check contact show route
        $contactShowRoute = $routes->getByName('contact');
        $this->assertNotNull($contactShowRoute);
        $this->assertEquals('App\Http\Controllers\ContactController@show', $contactShowRoute->getActionName());

        // Check contact store route
        $contactStoreRoute = $routes->getByName('contact.store');
        $this->assertNotNull($contactStoreRoute);
        $this->assertEquals('App\Http\Controllers\ContactController@store', $contactStoreRoute->getActionName());
    }
}