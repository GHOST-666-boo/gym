<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_displays_correctly()
    {
        $featuredProducts = Product::factory()->count(3)->create();

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewIs('public.home');
        $response->assertSee('Gym Equipment');
        $response->assertSee('Featured Products');
        
        // Check that featured products are displayed
        foreach ($featuredProducts as $product) {
            $response->assertSee($product->name);
        }
    }

    public function test_home_page_displays_without_products()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewIs('public.home');
        $response->assertSee('Gym Equipment');
    }

    public function test_products_index_displays_all_products()
    {
        $category = Category::factory()->create();
        $products = Product::factory()->count(5)->create(['category_id' => $category->id]);

        $response = $this->get('/products');

        $response->assertStatus(200);
        $response->assertViewIs('public.products.index');
        $response->assertViewHas('products');
        
        foreach ($products as $product) {
            $response->assertSee($product->name);
            $response->assertSee($product->price);
            $response->assertSee($product->short_description);
        }
    }

    public function test_products_index_with_pagination()
    {
        Product::factory()->count(25)->create(); // More than typical page size

        $response = $this->get('/products');

        $response->assertStatus(200);
        $response->assertViewIs('public.products.index');
        $response->assertViewHas('products');
        
        // Check pagination links exist
        $response->assertSee('pagination', false);
    }

    public function test_products_index_displays_empty_state()
    {
        $response = $this->get('/products');

        $response->assertStatus(200);
        $response->assertViewIs('public.products.index');
        $response->assertSee('No products found');
    }

    public function test_product_show_displays_product_details()
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Professional Treadmill',
            'price' => 2999.99,
            'short_description' => 'High-end treadmill for professionals',
            'long_description' => 'This is a detailed description of the professional treadmill with all its advanced features.',
        ]);

        $response = $this->get("/products/{$product->id}");

        $response->assertStatus(200);
        $response->assertViewIs('public.products.show');
        $response->assertViewHas('product', $product);
        
        $response->assertSee('Professional Treadmill');
        $response->assertSee('2999.99');
        $response->assertSee('High-end treadmill for professionals');
        $response->assertSee('This is a detailed description of the professional treadmill');
        $response->assertSee($category->name);
    }

    public function test_product_show_with_slug_url()
    {
        $product = Product::factory()->create([
            'name' => 'Professional Treadmill',
            'slug' => 'professional-treadmill',
        ]);

        $response = $this->get("/products/{$product->slug}");

        $response->assertStatus(200);
        $response->assertViewIs('public.products.show');
        $response->assertViewHas('product', $product);
        $response->assertSee('Professional Treadmill');
    }

    public function test_product_show_returns_404_for_nonexistent_product()
    {
        $response = $this->get('/products/999');

        $response->assertStatus(404);
    }

    public function test_product_show_returns_404_for_invalid_slug()
    {
        $response = $this->get('/products/nonexistent-product');

        $response->assertStatus(404);
    }

    public function test_contact_page_displays_form()
    {
        $response = $this->get('/contact');

        $response->assertStatus(200);
        $response->assertViewIs('public.contact');
        $response->assertSee('Contact Us');
        $response->assertSee('Send Us a Message');
        
        // Check form fields are present
        $response->assertSee('name="name"', false);
        $response->assertSee('name="email"', false);
        $response->assertSee('name="message"', false);
        $response->assertSee('type="submit"', false);
    }

    public function test_contact_form_has_csrf_protection()
    {
        $response = $this->get('/contact');

        $response->assertStatus(200);
        $response->assertSee('name="_token"', false);
    }

    public function test_products_by_category_filtering()
    {
        $cardioCategory = Category::factory()->create(['name' => 'Cardio Equipment']);
        $strengthCategory = Category::factory()->create(['name' => 'Strength Equipment']);
        
        $cardioProducts = Product::factory()->count(3)->create(['category_id' => $cardioCategory->id]);
        $strengthProducts = Product::factory()->count(2)->create(['category_id' => $strengthCategory->id]);

        $response = $this->get("/products?category={$cardioCategory->id}");

        $response->assertStatus(200);
        
        // Should see cardio products
        foreach ($cardioProducts as $product) {
            $response->assertSee($product->name);
        }
        
        // Should not see strength products
        foreach ($strengthProducts as $product) {
            $response->assertDontSee($product->name);
        }
    }

    public function test_seo_meta_tags_on_pages()
    {
        $product = Product::factory()->create([
            'name' => 'Professional Treadmill',
            'short_description' => 'High-end treadmill for professionals',
        ]);

        // Test home page meta tags
        $homeResponse = $this->get('/');
        $homeResponse->assertSee('<title>', false);
        $homeResponse->assertSee('name="description"', false);

        // Test product page meta tags
        $productResponse = $this->get("/products/{$product->id}");
        $productResponse->assertSee('<title>', false);
        $productResponse->assertSee('Professional Treadmill', false);
        $productResponse->assertSee('name="description"', false);
        $productResponse->assertSee('High-end treadmill for professionals', false);
    }

    public function test_breadcrumb_navigation()
    {
        $category = Category::factory()->create(['name' => 'Cardio Equipment']);
        $product = Product::factory()->create([
            'name' => 'Professional Treadmill',
            'category_id' => $category->id,
        ]);

        $response = $this->get("/products/{$product->id}");

        $response->assertStatus(200);
        $response->assertSee('Home');
        $response->assertSee('Products');
        $response->assertSee('Professional Treadmill');
    }

    public function test_responsive_layout_classes()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        // Check for responsive CSS classes (Bootstrap/Tailwind)
        $response->assertSee('container', false);
        $response->assertSee('row', false);
    }

    public function test_navigation_menu_links()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('href="/"', false);
        $response->assertSee('href="/products"', false);
        $response->assertSee('href="/contact"', false);
    }

    public function test_footer_content()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('footer', false);
        $response->assertSee('© 2024', false); // Copyright notice
    }

    public function test_product_image_display()
    {
        $product = Product::factory()->create([
            'image_path' => 'products/treadmill.jpg',
        ]);

        $response = $this->get("/products/{$product->id}");

        $response->assertStatus(200);
        $response->assertSee('products/treadmill.jpg');
        $response->assertSee('alt=', false); // Alt text for accessibility
    }

    public function test_product_price_formatting()
    {
        $product = Product::factory()->create([
            'price' => 2999.99,
        ]);

        $response = $this->get("/products/{$product->id}");

        $response->assertStatus(200);
        $response->assertSee('$2,999.99'); // Formatted price display
    }
}