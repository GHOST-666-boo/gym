<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test categories
        $this->cardioCategory = Category::factory()->create(['name' => 'Cardio Equipment']);
        $this->strengthCategory = Category::factory()->create(['name' => 'Strength Training']);
        
        // Create test products
        Product::factory()->create([
            'name' => 'Treadmill Pro X1',
            'short_description' => 'Professional treadmill for cardio workouts',
            'long_description' => 'High-quality treadmill with advanced features for professional use',
            'price' => 2500.00,
            'category_id' => $this->cardioCategory->id,
        ]);
        
        Product::factory()->create([
            'name' => 'Bench Press Station',
            'short_description' => 'Heavy-duty bench press for strength training',
            'long_description' => 'Professional bench press station for serious strength training',
            'price' => 1800.00,
            'category_id' => $this->strengthCategory->id,
        ]);
        
        Product::factory()->create([
            'name' => 'Elliptical Machine',
            'short_description' => 'Low-impact cardio machine',
            'long_description' => 'Smooth elliptical machine for low-impact cardiovascular exercise',
            'price' => 1200.00,
            'category_id' => $this->cardioCategory->id,
        ]);
    }

    public function test_products_index_displays_search_form()
    {
        $response = $this->get(route('products.index'));
        
        $response->assertStatus(200);
        $response->assertSee('Search gym machines...');
        $response->assertSee('All Categories');
        $response->assertSee('Min Price');
        $response->assertSee('Max Price');
        $response->assertSee('Sort By');
    }

    public function test_search_by_product_name()
    {
        $response = $this->get(route('products.search', ['search' => 'Treadmill']));
        
        $response->assertStatus(200);
        $response->assertSee('Treadmill Pro X1');
        $response->assertDontSee('Bench Press Station');
        $response->assertDontSee('Elliptical Machine');
    }

    public function test_search_by_description()
    {
        $response = $this->get(route('products.search', ['search' => 'cardio']));
        
        $response->assertStatus(200);
        $response->assertSee('Treadmill Pro X1');
        $response->assertSee('Elliptical Machine');
        $response->assertDontSee('Bench Press Station');
    }

    public function test_search_by_category_name()
    {
        $response = $this->get(route('products.search', ['search' => 'Strength']));
        
        $response->assertStatus(200);
        $response->assertSee('Bench Press Station');
        $response->assertDontSee('Treadmill Pro X1');
        $response->assertDontSee('Elliptical Machine');
    }

    public function test_filter_by_category()
    {
        $response = $this->get(route('products.search', ['category' => $this->cardioCategory->id]));
        
        $response->assertStatus(200);
        $response->assertSee('Treadmill Pro X1');
        $response->assertSee('Elliptical Machine');
        $response->assertDontSee('Bench Press Station');
    }

    public function test_filter_by_price_range()
    {
        $response = $this->get(route('products.search', [
            'min_price' => 1500,
            'max_price' => 2000
        ]));
        
        $response->assertStatus(200);
        $response->assertSee('Bench Press Station'); // $1800
        $response->assertDontSee('Treadmill Pro X1'); // $2500
        $response->assertDontSee('Elliptical Machine'); // $1200
    }

    public function test_filter_by_minimum_price_only()
    {
        $response = $this->get(route('products.search', ['min_price' => 2000]));
        
        $response->assertStatus(200);
        $response->assertSee('Treadmill Pro X1'); // $2500
        $response->assertDontSee('Bench Press Station'); // $1800
        $response->assertDontSee('Elliptical Machine'); // $1200
    }

    public function test_filter_by_maximum_price_only()
    {
        $response = $this->get(route('products.search', ['max_price' => 1500]));
        
        $response->assertStatus(200);
        $response->assertSee('Elliptical Machine'); // $1200
        $response->assertDontSee('Treadmill Pro X1'); // $2500
        $response->assertDontSee('Bench Press Station'); // $1800
    }

    public function test_sort_by_name_ascending()
    {
        $response = $this->get(route('products.search', [
            'sort_by' => 'name',
            'sort_direction' => 'asc'
        ]));
        
        $response->assertStatus(200);
        $content = $response->getContent();
        
        // Check that products appear in alphabetical order
        $benchPosition = strpos($content, 'Bench Press Station');
        $ellipticalPosition = strpos($content, 'Elliptical Machine');
        $treadmillPosition = strpos($content, 'Treadmill Pro X1');
        
        $this->assertLessThan($ellipticalPosition, $benchPosition);
        $this->assertLessThan($treadmillPosition, $ellipticalPosition);
    }

    public function test_sort_by_price_ascending()
    {
        $response = $this->get(route('products.search', [
            'sort_by' => 'price',
            'sort_direction' => 'asc'
        ]));
        
        $response->assertStatus(200);
        $content = $response->getContent();
        
        // Check that products appear in price order (lowest to highest)
        $ellipticalPosition = strpos($content, 'Elliptical Machine'); // $1200
        $benchPosition = strpos($content, 'Bench Press Station'); // $1800
        $treadmillPosition = strpos($content, 'Treadmill Pro X1'); // $2500
        
        $this->assertLessThan($benchPosition, $ellipticalPosition);
        $this->assertLessThan($treadmillPosition, $benchPosition);
    }

    public function test_sort_by_price_descending()
    {
        $response = $this->get(route('products.search', [
            'sort_by' => 'price',
            'sort_direction' => 'desc'
        ]));
        
        $response->assertStatus(200);
        $content = $response->getContent();
        
        // Check that products appear in price order (highest to lowest)
        $treadmillPosition = strpos($content, 'Treadmill Pro X1'); // $2500
        $benchPosition = strpos($content, 'Bench Press Station'); // $1800
        $ellipticalPosition = strpos($content, 'Elliptical Machine'); // $1200
        
        $this->assertLessThan($benchPosition, $treadmillPosition);
        $this->assertLessThan($ellipticalPosition, $benchPosition);
    }

    public function test_combined_search_and_filters()
    {
        $response = $this->get(route('products.search', [
            'search' => 'cardio',
            'category' => $this->cardioCategory->id,
            'max_price' => 2000,
            'sort_by' => 'price',
            'sort_direction' => 'asc'
        ]));
        
        $response->assertStatus(200);
        $response->assertSee('Elliptical Machine'); // Matches all criteria
        $response->assertDontSee('Treadmill Pro X1'); // Over price limit
        $response->assertDontSee('Bench Press Station'); // Wrong category
    }

    public function test_search_with_no_results()
    {
        $response = $this->get(route('products.search', ['search' => 'nonexistent']));
        
        $response->assertStatus(200);
        $response->assertSee('No Products Found');
        $response->assertSee('No products match your search');
    }

    public function test_search_displays_active_filters()
    {
        $response = $this->get(route('products.search', [
            'search' => 'treadmill',
            'category' => $this->cardioCategory->id,
            'min_price' => 1000,
            'max_price' => 3000
        ]));
        
        $response->assertStatus(200);
        $response->assertSee('Active filters:');
        $response->assertSee('Search: "treadmill"');
        $response->assertSee('Category: Cardio Equipment');
        $response->assertSee('Min: $1,000.00');
        $response->assertSee('Max: $3,000.00');
    }

    public function test_search_pagination_preserves_query_parameters()
    {
        // Create more products to trigger pagination
        Product::factory()->count(15)->create([
            'name' => 'Test Product',
            'category_id' => $this->cardioCategory->id,
        ]);
        
        $response = $this->get(route('products.search', [
            'search' => 'Test',
            'category' => $this->cardioCategory->id,
            'page' => 2
        ]));
        
        $response->assertStatus(200);
        // Check that pagination links preserve search parameters
        $response->assertSee('search=Test');
        $response->assertSee('category=' . $this->cardioCategory->id);
    }

    public function test_empty_search_shows_all_products()
    {
        $response = $this->get(route('products.search'));
        
        $response->assertStatus(200);
        $response->assertSee('Treadmill Pro X1');
        $response->assertSee('Bench Press Station');
        $response->assertSee('Elliptical Machine');
    }

    public function test_invalid_sort_parameters_use_defaults()
    {
        $response = $this->get(route('products.search', [
            'sort_by' => 'invalid_field',
            'sort_direction' => 'invalid_direction'
        ]));
        
        $response->assertStatus(200);
        // Should still work with default sorting (name, asc)
        $response->assertSee('Treadmill Pro X1');
        $response->assertSee('Bench Press Station');
        $response->assertSee('Elliptical Machine');
    }
}