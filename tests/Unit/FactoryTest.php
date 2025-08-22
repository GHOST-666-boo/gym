<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_factory_creates_valid_category(): void
    {
        $category = Category::factory()->create();

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
        ]);

        $this->assertNotEmpty($category->name);
        $this->assertNotEmpty($category->slug);
        $this->assertNotEmpty($category->description);
    }

    public function test_category_factory_with_custom_name(): void
    {
        $customName = 'Custom Test Category';
        $category = Category::factory()->withName($customName, 'Custom description')->create();

        $this->assertEquals($customName, $category->name);
        $this->assertEquals('custom-test-category', $category->slug);
        $this->assertEquals('Custom description', $category->description);
    }

    public function test_product_factory_creates_valid_product(): void
    {
        $product = Product::factory()->create();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => $product->name,
            'price' => $product->price,
            'short_description' => $product->short_description,
            'long_description' => $product->long_description,
        ]);

        $this->assertNotEmpty($product->name);
        $this->assertIsNumeric($product->price);
        $this->assertGreaterThan(0, $product->price);
        $this->assertNotEmpty($product->short_description);
        $this->assertNotEmpty($product->long_description);
        $this->assertNotEmpty($product->slug);
    }

    public function test_product_factory_with_category(): void
    {
        $product = Product::factory()->withCategory()->create();

        $this->assertNotNull($product->category_id);
        $this->assertInstanceOf(Category::class, $product->category);
        $this->assertDatabaseHas('categories', ['id' => $product->category_id]);
    }

    public function test_product_factory_with_image(): void
    {
        $product = Product::factory()->withImage()->create();

        $this->assertNotNull($product->image_path);
        $this->assertStringContainsString('products/', $product->image_path);
        $this->assertStringContainsString('.jpg', $product->image_path);
    }

    public function test_product_factory_creates_realistic_gym_equipment(): void
    {
        $product = Product::factory()->create();

        // Check that the product name contains gym-related terms
        $gymTerms = [
            'Machine', 'Trainer', 'Equipment', 'System', 'Treadmill', 'Elliptical', 'Rowing', 'Press', 'Cable', 'Smith',
            'Bench', 'Rack', 'Station', 'Bike', 'Ball', 'Roller', 'Gun', 'Mat', 'Bands', 'Plates', 'Barbell', 'Dumbbell',
            'Kettlebell', 'Curl', 'Extension', 'Pulldown', 'Crossover', 'Climber', 'Cross', 'Vibration', 'Inversion'
        ];
        $containsGymTerm = false;
        
        foreach ($gymTerms as $term) {
            if (str_contains($product->name, $term)) {
                $containsGymTerm = true;
                break;
            }
        }

        $this->assertTrue($containsGymTerm, "Product name '{$product->name}' should contain gym-related terms");

        // Check price is in realistic range for gym equipment
        $this->assertGreaterThanOrEqual(299.99, $product->price);
        $this->assertLessThanOrEqual(4999.99, $product->price);
    }

    public function test_multiple_products_have_unique_names(): void
    {
        $products = Product::factory(10)->create();
        $names = $products->pluck('name')->toArray();
        
        $this->assertEquals(count($names), count(array_unique($names)), 'All product names should be unique');
    }

    public function test_multiple_categories_have_unique_names(): void
    {
        $categories = Category::factory(6)->create();
        $names = $categories->pluck('name')->toArray();
        
        $this->assertEquals(count($names), count(array_unique($names)), 'All category names should be unique');
    }
}