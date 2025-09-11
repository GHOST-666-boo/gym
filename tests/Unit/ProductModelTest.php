<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_has_fillable_attributes(): void
    {
        $product = new Product();
        
        $expectedFillable = [
            'name',
            'slug',
            'price',
            'short_description',
            'long_description',
            'image_path',
            'category_id',
        ];

        $this->assertEquals($expectedFillable, $product->getFillable());
    }

    public function test_product_price_is_cast_to_decimal(): void
    {
        $product = Product::create([
            'name' => 'Test Machine',
            'price' => '1999.99',
            'short_description' => 'Test short description',
            'long_description' => 'Test long description',
        ]);

        // Laravel's decimal cast returns a string with proper decimal formatting
        $this->assertIsString($product->price);
        $this->assertEquals('1999.99', $product->price);
        
        // Verify it can be converted to float for calculations
        $this->assertEquals(1999.99, (float) $product->price);
    }

    public function test_product_belongs_to_category(): void
    {
        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'description' => 'Test description',
        ]);

        $product = Product::create([
            'name' => 'Test Machine',
            'price' => 1999.99,
            'short_description' => 'Test short description',
            'long_description' => 'Test long description',
            'category_id' => $category->id,
        ]);

        $this->assertInstanceOf(Category::class, $product->category);
        $this->assertEquals($category->id, $product->category->id);
        $this->assertEquals('Test Category', $product->category->name);
    }

    public function test_product_can_exist_without_category(): void
    {
        $product = Product::create([
            'name' => 'Test Machine',
            'price' => 1999.99,
            'short_description' => 'Test short description',
            'long_description' => 'Test long description',
        ]);

        $this->assertNull($product->category_id);
        $this->assertNull($product->category);
    }
}