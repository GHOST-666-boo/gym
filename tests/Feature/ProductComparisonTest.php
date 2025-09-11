<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductComparisonTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test category
        $this->category = Category::factory()->create([
            'name' => 'Test Category',
            'slug' => 'test-category'
        ]);
        
        // Create test products
        $this->products = Product::factory()->count(3)->create([
            'category_id' => $this->category->id
        ]);
    }

    public function test_comparison_page_displays_correctly()
    {
        $response = $this->get(route('products.compare'));
        
        $response->assertStatus(200);
        $response->assertViewIs('public.products.compare');
        $response->assertSee('Compare Products');
    }

    public function test_can_add_product_to_comparison()
    {
        $product = $this->products->first();
        
        $response = $this->postJson(route('products.compare.add'), [
            'product_id' => $product->id
        ]);
        
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'count' => 1
        ]);
        
        // Check session
        $this->assertEquals([$product->id], session('comparison'));
    }

    public function test_can_remove_product_from_comparison()
    {
        $product = $this->products->first();
        
        // Add product first
        session(['comparison' => [$product->id]]);
        
        $response = $this->postJson(route('products.compare.remove'), [
            'product_id' => $product->id
        ]);
        
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'count' => 0
        ]);
        
        // Check session
        $this->assertEquals([], session('comparison'));
    }

    public function test_can_clear_all_comparison_products()
    {
        // Add multiple products
        $productIds = $this->products->pluck('id')->toArray();
        session(['comparison' => $productIds]);
        
        $response = $this->postJson(route('products.compare.clear'));
        
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'count' => 0
        ]);
        
        // Check session
        $this->assertNull(session('comparison'));
    }

    public function test_comparison_count_endpoint()
    {
        $productIds = $this->products->take(2)->pluck('id')->toArray();
        session(['comparison' => $productIds]);
        
        $response = $this->getJson(route('products.compare.count'));
        
        $response->assertStatus(200);
        $response->assertJson(['count' => 2]);
    }

    public function test_comparison_products_endpoint()
    {
        $products = $this->products->take(2);
        $productIds = $products->pluck('id')->toArray();
        session(['comparison' => $productIds]);
        
        $response = $this->getJson(route('products.compare.products'));
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'products' => [
                '*' => [
                    'id',
                    'name',
                    'slug',
                    'price',
                    'short_description',
                    'image',
                    'category',
                    'url'
                ]
            ]
        ]);
    }

    public function test_comparison_page_with_products()
    {
        $products = $this->products->take(2);
        $productIds = $products->pluck('id')->toArray();
        
        $response = $this->get(route('products.compare', ['products' => implode(',', $productIds)]));
        
        $response->assertStatus(200);
        $response->assertViewHas('products');
        
        $viewProducts = $response->viewData('products');
        $this->assertCount(2, $viewProducts);
    }

    public function test_cannot_add_more_than_four_products()
    {
        // Add 4 products to comparison
        $productIds = Product::factory()->count(4)->create()->pluck('id')->toArray();
        session(['comparison' => $productIds]);
        
        // Try to add a 5th product
        $newProduct = Product::factory()->create();
        
        $response = $this->postJson(route('products.compare.add'), [
            'product_id' => $newProduct->id
        ]);
        
        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'Maximum 4 products can be compared'
        ]);
    }

    public function test_cannot_add_duplicate_product()
    {
        $product = $this->products->first();
        session(['comparison' => [$product->id]]);
        
        $response = $this->postJson(route('products.compare.add'), [
            'product_id' => $product->id
        ]);
        
        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'Product already in comparison'
        ]);
    }

    public function test_comparison_page_shows_empty_state_when_no_products()
    {
        $response = $this->get(route('products.compare'));
        
        $response->assertStatus(200);
        $response->assertSee('No Products to Compare');
        $response->assertSee('Browse Products');
    }
}