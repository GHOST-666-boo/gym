<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_belongs_to_category()
    {
        $category = Category::factory()->create([
            'name' => 'Cardio Equipment',
            'slug' => 'cardio-equipment',
        ]);

        $product = Product::factory()->create([
            'category_id' => $category->id,
        ]);

        $this->assertInstanceOf(Category::class, $product->category);
        $this->assertEquals($category->id, $product->category->id);
        $this->assertEquals('Cardio Equipment', $product->category->name);
    }

    public function test_product_can_exist_without_category()
    {
        $product = Product::factory()->create([
            'category_id' => null,
        ]);

        $this->assertNull($product->category_id);
        $this->assertNull($product->category);
    }

    public function test_category_has_many_products()
    {
        $category = Category::factory()->create();
        $products = Product::factory()->count(3)->create([
            'category_id' => $category->id,
        ]);

        $this->assertCount(3, $category->products);
        
        foreach ($products as $product) {
            $this->assertTrue($category->products->contains($product));
        }
    }

    public function test_category_products_relationship_returns_collection()
    {
        $category = Category::factory()->create();
        Product::factory()->count(2)->create(['category_id' => $category->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $category->products);
        $this->assertCount(2, $category->products);
    }

    public function test_category_can_exist_without_products()
    {
        $category = Category::factory()->create();

        $this->assertCount(0, $category->products);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $category->products);
    }

    public function test_deleting_category_sets_product_category_to_null()
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $this->assertEquals($category->id, $product->category_id);

        $category->delete();
        $product->refresh();

        $this->assertNull($product->category_id);
    }

    public function test_product_category_relationship_eager_loading()
    {
        $category = Category::factory()->create(['name' => 'Test Category']);
        Product::factory()->count(3)->create(['category_id' => $category->id]);

        $products = Product::with('category')->get();

        foreach ($products as $product) {
            $this->assertEquals('Test Category', $product->category->name);
            // Verify no additional queries are made for category
            $this->assertTrue($product->relationLoaded('category'));
        }
    }

    public function test_category_products_relationship_eager_loading()
    {
        $category = Category::factory()->create();
        Product::factory()->count(3)->create(['category_id' => $category->id]);

        $categoryWithProducts = Category::with('products')->find($category->id);

        $this->assertCount(3, $categoryWithProducts->products);
        $this->assertTrue($categoryWithProducts->relationLoaded('products'));
    }

    public function test_user_model_has_admin_attribute()
    {
        $adminUser = User::factory()->create(['is_admin' => true]);
        $regularUser = User::factory()->create(['is_admin' => false]);

        $this->assertTrue($adminUser->is_admin);
        $this->assertFalse($regularUser->is_admin);
    }

    public function test_user_model_fillable_attributes()
    {
        $user = new User();
        
        $expectedFillable = [
            'name',
            'email',
            'password',
            'email_verified_at',
            'is_admin',
        ];

        $this->assertEquals($expectedFillable, $user->getFillable());
    }

    public function test_user_model_hidden_attributes()
    {
        $user = new User();
        
        $expectedHidden = [
            'password',
            'remember_token',
        ];

        $this->assertEquals($expectedHidden, $user->getHidden());
    }

    public function test_user_model_casts()
    {
        $user = new User();
        $casts = $user->getCasts();

        $this->assertArrayHasKey('email_verified_at', $casts);
        $this->assertArrayHasKey('password', $casts);
        $this->assertArrayHasKey('is_admin', $casts);
        $this->assertEquals('datetime', $casts['email_verified_at']);
        $this->assertEquals('hashed', $casts['password']);
        $this->assertEquals('boolean', $casts['is_admin']);
    }

    public function test_product_model_casts_price_to_decimal()
    {
        $product = Product::factory()->create(['price' => 1999.99]);

        $this->assertIsString($product->price);
        $this->assertEquals('1999.99', $product->price);
        
        // Test that it can be used in calculations
        $this->assertEquals(1999.99, (float) $product->price);
    }

    public function test_category_model_slug_generation()
    {
        $category = Category::factory()->create([
            'name' => 'Cardio Equipment',
            'slug' => 'cardio-equipment',
        ]);

        $this->assertEquals('cardio-equipment', $category->slug);
    }

    public function test_product_model_slug_generation()
    {
        $product = Product::factory()->create([
            'name' => 'Professional Treadmill',
            'slug' => 'professional-treadmill',
        ]);

        $this->assertEquals('professional-treadmill', $product->slug);
    }

    public function test_model_timestamps()
    {
        $product = Product::factory()->create();
        $category = Category::factory()->create();
        $user = User::factory()->create();

        $this->assertNotNull($product->created_at);
        $this->assertNotNull($product->updated_at);
        $this->assertNotNull($category->created_at);
        $this->assertNotNull($category->updated_at);
        $this->assertNotNull($user->created_at);
        $this->assertNotNull($user->updated_at);
    }

    public function test_product_model_route_key_name()
    {
        $product = new Product();
        
        // Test if using slug for route model binding
        if (method_exists($product, 'getRouteKeyName')) {
            $routeKey = $product->getRouteKeyName();
            $this->assertContains($routeKey, ['id', 'slug']);
        }
    }

    public function test_category_model_route_key_name()
    {
        $category = new Category();
        
        // Test if using slug for route model binding
        if (method_exists($category, 'getRouteKeyName')) {
            $routeKey = $category->getRouteKeyName();
            $this->assertContains($routeKey, ['id', 'slug']);
        }
    }
}