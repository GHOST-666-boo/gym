<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_user_seeder_creates_admin_user(): void
    {
        $this->seed(AdminUserSeeder::class);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@gymachines.com',
            'name' => 'Admin User',
            'is_admin' => true,
        ]);

        $admin = User::where('email', 'admin@gymachines.com')->first();
        $this->assertNotNull($admin);
        $this->assertTrue($admin->is_admin);
        $this->assertNotNull($admin->email_verified_at);
    }

    public function test_category_seeder_creates_expected_categories(): void
    {
        $this->seed(CategorySeeder::class);

        $expectedCategories = [
            'Cardio Equipment',
            'Strength Training',
            'Free Weights',
            'Functional Training',
            'Recovery & Wellness',
            'Commercial Grade',
        ];

        $this->assertEquals(6, Category::count());

        foreach ($expectedCategories as $categoryName) {
            $this->assertDatabaseHas('categories', ['name' => $categoryName]);
            
            $category = Category::where('name', $categoryName)->first();
            $this->assertNotNull($category);
            $this->assertNotEmpty($category->slug);
            $this->assertNotEmpty($category->description);
        }
    }

    public function test_category_seeder_creates_unique_slugs(): void
    {
        $this->seed(CategorySeeder::class);

        $categories = Category::all();
        $slugs = $categories->pluck('slug')->toArray();
        
        $this->assertEquals(count($slugs), count(array_unique($slugs)), 'All category slugs should be unique');
    }

    public function test_product_seeder_creates_products_with_categories(): void
    {
        $this->seed([CategorySeeder::class, ProductSeeder::class]);

        // Should have at least the predefined products plus factory-generated ones
        $this->assertGreaterThanOrEqual(12, Product::count());

        // Check that some products have categories assigned
        $productsWithCategories = Product::whereNotNull('category_id')->count();
        $this->assertGreaterThan(0, $productsWithCategories);

        // Verify specific predefined products exist
        $this->assertDatabaseHas('products', ['name' => 'ProFit Elite Treadmill X1']);
        $this->assertDatabaseHas('products', ['name' => 'GymTech Elliptical Pro 500']);
        $this->assertDatabaseHas('products', ['name' => 'PowerMax Leg Press Station']);
    }

    public function test_product_seeder_creates_products_with_valid_data(): void
    {
        $this->seed([CategorySeeder::class, ProductSeeder::class]);

        $products = Product::all();

        foreach ($products as $product) {
            $this->assertNotEmpty($product->name);
            $this->assertNotEmpty($product->slug);
            $this->assertIsNumeric($product->price);
            $this->assertGreaterThan(0, $product->price);
            $this->assertNotEmpty($product->short_description);
            $this->assertNotEmpty($product->long_description);
        }
    }

    public function test_product_seeder_creates_products_in_correct_categories(): void
    {
        $this->seed([CategorySeeder::class, ProductSeeder::class]);

        // Check specific product-category relationships
        $cardioCategory = Category::where('name', 'Cardio Equipment')->first();
        $strengthCategory = Category::where('name', 'Strength Training')->first();

        $this->assertNotNull($cardioCategory);
        $this->assertNotNull($strengthCategory);

        // Verify treadmill is in cardio category
        $treadmill = Product::where('name', 'ProFit Elite Treadmill X1')->first();
        $this->assertNotNull($treadmill);
        $this->assertEquals($cardioCategory->id, $treadmill->category_id);

        // Verify leg press is in strength category
        $legPress = Product::where('name', 'PowerMax Leg Press Station')->first();
        $this->assertNotNull($legPress);
        $this->assertEquals($strengthCategory->id, $legPress->category_id);
    }

    public function test_product_seeder_creates_realistic_prices(): void
    {
        $this->seed([CategorySeeder::class, ProductSeeder::class]);

        $products = Product::all();

        foreach ($products as $product) {
            // All gym equipment should be reasonably priced
            $this->assertGreaterThanOrEqual(100, $product->price, "Product {$product->name} price too low");
            $this->assertLessThanOrEqual(10000, $product->price, "Product {$product->name} price too high");
        }
    }

    public function test_seeded_products_have_unique_names(): void
    {
        $this->seed([CategorySeeder::class, ProductSeeder::class]);

        $products = Product::all();
        $names = $products->pluck('name')->toArray();
        
        $this->assertEquals(count($names), count(array_unique($names)), 'All seeded product names should be unique');
    }

    public function test_seeded_products_have_unique_slugs(): void
    {
        $this->seed([CategorySeeder::class, ProductSeeder::class]);

        $products = Product::all();
        $slugs = $products->pluck('slug')->toArray();
        
        $this->assertEquals(count($slugs), count(array_unique($slugs)), 'All seeded product slugs should be unique');
    }

    public function test_category_product_relationships_work_correctly(): void
    {
        $this->seed([CategorySeeder::class, ProductSeeder::class]);

        $categories = Category::with('products')->get();

        foreach ($categories as $category) {
            if ($category->products->count() > 0) {
                foreach ($category->products as $product) {
                    $this->assertEquals($category->id, $product->category_id);
                    $this->assertEquals($category->name, $product->category->name);
                }
            }
        }
    }

    public function test_full_database_seeding_works(): void
    {
        // Test running all seeders together
        $this->seed();

        // Verify all expected data exists
        $this->assertGreaterThan(0, User::count());
        $this->assertGreaterThan(0, Category::count());
        $this->assertGreaterThan(0, Product::count());

        // Verify admin user exists
        $this->assertDatabaseHas('users', [
            'email' => 'admin@gymachines.com',
            'is_admin' => true,
        ]);

        // Verify categories exist
        $this->assertEquals(6, Category::count());

        // Verify products exist with relationships
        $productsWithCategories = Product::whereNotNull('category_id')->count();
        $this->assertGreaterThan(0, $productsWithCategories);
    }
}