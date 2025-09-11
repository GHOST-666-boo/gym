<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_has_fillable_attributes(): void
    {
        $category = new Category();
        
        $expectedFillable = [
            'name',
            'slug',
            'description',
        ];

        $this->assertEquals($expectedFillable, $category->getFillable());
    }

    public function test_category_has_many_products(): void
    {
        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'description' => 'Test description',
        ]);

        $product1 = Product::create([
            'name' => 'Test Machine 1',
            'price' => 1999.99,
            'short_description' => 'Test short description 1',
            'long_description' => 'Test long description 1',
            'category_id' => $category->id,
        ]);

        $product2 = Product::create([
            'name' => 'Test Machine 2',
            'price' => 2999.99,
            'short_description' => 'Test short description 2',
            'long_description' => 'Test long description 2',
            'category_id' => $category->id,
        ]);

        $this->assertCount(2, $category->products);
        $this->assertInstanceOf(Product::class, $category->products->first());
        $this->assertEquals('Test Machine 1', $category->products->first()->name);
        $this->assertEquals('Test Machine 2', $category->products->last()->name);
    }

    public function test_category_can_have_no_products(): void
    {
        $category = Category::create([
            'name' => 'Empty Category',
            'slug' => 'empty-category',
            'description' => 'Category with no products',
        ]);

        $this->assertCount(0, $category->products);
        $this->assertTrue($category->products->isEmpty());
    }
}