<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class InventoryManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create an admin user for testing
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    public function test_product_can_track_inventory(): void
    {
        $product = Product::factory()->create([
            'track_inventory' => true,
            'stock_quantity' => 50,
            'low_stock_threshold' => 10,
        ]);

        $this->assertTrue($product->track_inventory);
        $this->assertEquals(50, $product->stock_quantity);
        $this->assertEquals(10, $product->low_stock_threshold);
    }

    public function test_product_stock_status_methods(): void
    {
        // Test in stock product
        $inStockProduct = Product::factory()->create([
            'track_inventory' => true,
            'stock_quantity' => 50,
            'low_stock_threshold' => 10,
        ]);

        $this->assertTrue($inStockProduct->isInStock());
        $this->assertFalse($inStockProduct->isLowStock());
        $this->assertFalse($inStockProduct->isOutOfStock());
        $this->assertEquals('In Stock', $inStockProduct->stock_status);

        // Test low stock product
        $lowStockProduct = Product::factory()->create([
            'track_inventory' => true,
            'stock_quantity' => 5,
            'low_stock_threshold' => 10,
        ]);

        $this->assertTrue($lowStockProduct->isInStock());
        $this->assertTrue($lowStockProduct->isLowStock());
        $this->assertFalse($lowStockProduct->isOutOfStock());
        $this->assertEquals('Low Stock', $lowStockProduct->stock_status);

        // Test out of stock product
        $outOfStockProduct = Product::factory()->create([
            'track_inventory' => true,
            'stock_quantity' => 0,
            'low_stock_threshold' => 10,
        ]);

        $this->assertFalse($outOfStockProduct->isInStock());
        $this->assertFalse($outOfStockProduct->isLowStock());
        $this->assertTrue($outOfStockProduct->isOutOfStock());
        $this->assertEquals('Out of Stock', $outOfStockProduct->stock_status);

        // Test non-tracked product
        $nonTrackedProduct = Product::factory()->create([
            'track_inventory' => false,
            'stock_quantity' => 0,
        ]);

        $this->assertTrue($nonTrackedProduct->isInStock());
        $this->assertFalse($nonTrackedProduct->isLowStock());
        $this->assertFalse($nonTrackedProduct->isOutOfStock());
        $this->assertEquals('Available', $nonTrackedProduct->stock_status);
    }

    public function test_product_scopes_work_correctly(): void
    {
        // Create products with different stock levels
        Product::factory()->create(['track_inventory' => true, 'stock_quantity' => 50, 'low_stock_threshold' => 10]);
        Product::factory()->create(['track_inventory' => true, 'stock_quantity' => 5, 'low_stock_threshold' => 10]);
        Product::factory()->create(['track_inventory' => true, 'stock_quantity' => 0, 'low_stock_threshold' => 10]);
        Product::factory()->create(['track_inventory' => false, 'stock_quantity' => 0]);

        $this->assertEquals(3, Product::inStock()->count());
        $this->assertEquals(1, Product::lowStock()->count());
        $this->assertEquals(1, Product::outOfStock()->count());
    }

    public function test_admin_can_create_product_with_inventory(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.products.store'), [
            'name' => 'Test Treadmill',
            'price' => 1999.99,
            'short_description' => 'A great treadmill for cardio',
            'long_description' => 'This is a detailed description of the treadmill.',
            'stock_quantity' => 25,
            'low_stock_threshold' => 5,
            'track_inventory' => true,
        ]);

        $response->assertRedirect(route('admin.products.index'));
        
        $product = Product::where('name', 'Test Treadmill')->first();
        $this->assertNotNull($product);
        $this->assertEquals(25, $product->stock_quantity);
        $this->assertEquals(5, $product->low_stock_threshold);
        $this->assertTrue($product->track_inventory);
    }

    public function test_admin_can_update_product_inventory(): void
    {
        $product = Product::factory()->create([
            'track_inventory' => true,
            'stock_quantity' => 10,
            'low_stock_threshold' => 5,
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.products.update', $product), [
            'name' => $product->name,
            'price' => $product->price,
            'short_description' => $product->short_description,
            'long_description' => $product->long_description,
            'stock_quantity' => 50,
            'low_stock_threshold' => 10,
            'track_inventory' => true,
        ]);

        $response->assertRedirect(route('admin.products.index'));
        
        $product->refresh();
        $this->assertEquals(50, $product->stock_quantity);
        $this->assertEquals(10, $product->low_stock_threshold);
    }

    public function test_admin_dashboard_shows_inventory_statistics(): void
    {
        // Create products with different stock levels
        Product::factory()->create(['track_inventory' => true, 'stock_quantity' => 5, 'low_stock_threshold' => 10]);
        Product::factory()->create(['track_inventory' => true, 'stock_quantity' => 0, 'low_stock_threshold' => 10]);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('stats');
        
        $stats = $response->viewData('stats');
        $this->assertArrayHasKey('low_stock_products', $stats);
        $this->assertArrayHasKey('out_of_stock_products', $stats);
        $this->assertArrayHasKey('low_stock_alerts', $stats);
    }

    public function test_public_product_page_shows_stock_status(): void
    {
        $product = Product::factory()->create([
            'track_inventory' => true,
            'stock_quantity' => 5,
            'low_stock_threshold' => 10,
        ]);

        // Test that the product methods work correctly first
        $this->assertTrue($product->isLowStock());
        $this->assertEquals('Low Stock', $product->stock_status);

        $response = $this->get(route('products.show', $product));

        $response->assertStatus(200);
        $response->assertSee('Low Stock');
        $response->assertSee('Availability:');
    }

    public function test_inventory_validation_rules(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.products.store'), [
            'name' => 'Test Product',
            'price' => 100,
            'short_description' => 'Short desc',
            'long_description' => 'Long description',
            'stock_quantity' => -5, // Invalid negative stock
            'low_stock_threshold' => -1, // Invalid negative threshold
            'track_inventory' => true,
        ]);

        $response->assertSessionHasErrors(['stock_quantity', 'low_stock_threshold']);
    }
}
