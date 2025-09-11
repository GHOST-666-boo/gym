<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create([
            'is_admin' => true,
        ]);
    }

    public function test_admin_can_view_categories_index()
    {
        $categories = Category::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.categories.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.categories.index');
        $response->assertViewHas('categories');
        
        foreach ($categories as $category) {
            $response->assertSee($category->name);
            $response->assertSee($category->slug);
        }
    }

    public function test_admin_can_view_create_category_form()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.categories.create'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.categories.create');
        $response->assertSee('Add New Category');
    }

    public function test_admin_can_create_category_with_valid_data()
    {
        $categoryData = [
            'name' => 'Cardio Equipment',
            'description' => 'Equipment for cardiovascular training',
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.categories.store'), $categoryData);

        $response->assertRedirect(route('admin.categories.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('categories', [
            'name' => 'Cardio Equipment',
            'slug' => 'cardio-equipment',
            'description' => 'Equipment for cardiovascular training',
        ]);
    }

    public function test_admin_can_create_category_with_custom_slug()
    {
        $categoryData = [
            'name' => 'Strength Training',
            'slug' => 'strength-gear',
            'description' => 'Equipment for strength building',
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.categories.store'), $categoryData);

        $response->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', [
            'name' => 'Strength Training',
            'slug' => 'strength-gear',
            'description' => 'Equipment for strength building',
        ]);
    }

    public function test_category_creation_requires_name()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.categories.store'), [
                'description' => 'Some description',
            ]);

        $response->assertSessionHasErrors(['name']);
        $this->assertDatabaseCount('categories', 0);
    }

    public function test_category_name_must_be_unique()
    {
        Category::factory()->create(['name' => 'Existing Category']);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.categories.store'), [
                'name' => 'Existing Category',
            ]);

        $response->assertSessionHasErrors(['name']);
        $this->assertDatabaseCount('categories', 1);
    }

    public function test_category_slug_must_be_unique()
    {
        Category::factory()->create(['slug' => 'existing-slug']);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.categories.store'), [
                'name' => 'New Category',
                'slug' => 'existing-slug',
            ]);

        $response->assertSessionHasErrors(['slug']);
    }

    public function test_admin_can_view_category_details()
    {
        $category = Category::factory()->create();
        $products = Product::factory()->count(3)->create(['category_id' => $category->id]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.categories.show', $category));

        $response->assertStatus(200);
        $response->assertViewIs('admin.categories.show');
        $response->assertViewHas(['category', 'products']);
        $response->assertSee($category->name);
        
        foreach ($products as $product) {
            $response->assertSee($product->name);
        }
    }

    public function test_admin_can_view_edit_category_form()
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.categories.edit', $category));

        $response->assertStatus(200);
        $response->assertViewIs('admin.categories.edit');
        $response->assertViewHas('category');
        $response->assertSee($category->name);
        $response->assertSee($category->slug);
    }

    public function test_admin_can_update_category()
    {
        $category = Category::factory()->create([
            'name' => 'Old Name',
            'slug' => 'old-slug',
            'description' => 'Old description',
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'slug' => 'updated-slug',
            'description' => 'Updated description',
        ];

        $response = $this->actingAs($this->admin)
            ->put(route('admin.categories.update', $category), $updateData);

        $response->assertRedirect(route('admin.categories.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Updated Name',
            'slug' => 'updated-slug',
            'description' => 'Updated description',
        ]);
    }

    public function test_admin_can_delete_empty_category()
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.categories.destroy', $category));

        $response->assertRedirect(route('admin.categories.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_admin_can_delete_category_with_products()
    {
        $category = Category::factory()->create();
        $products = Product::factory()->count(3)->create(['category_id' => $category->id]);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.categories.destroy', $category));

        $response->assertRedirect(route('admin.categories.index'));
        $response->assertSessionHas('success');
        
        // Category should be deleted
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
        
        // Products should be moved to uncategorized (category_id = null)
        foreach ($products as $product) {
            $this->assertDatabaseHas('products', [
                'id' => $product->id,
                'category_id' => null,
            ]);
        }
    }

    public function test_admin_can_view_confirm_delete_page_for_category_with_products()
    {
        $category = Category::factory()->create();
        $products = Product::factory()->count(5)->create(['category_id' => $category->id]);
        $otherCategory = Category::factory()->create();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.categories.confirm-delete', $category));

        $response->assertStatus(200);
        $response->assertViewIs('admin.categories.confirm-delete');
        $response->assertViewHas(['category', 'products', 'otherCategories']);
        $response->assertSee($category->name);
        $response->assertSee($otherCategory->name);
    }

    public function test_admin_can_reassign_products_to_another_category_before_deletion()
    {
        $category = Category::factory()->create();
        $targetCategory = Category::factory()->create();
        $products = Product::factory()->count(3)->create(['category_id' => $category->id]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.categories.reassign-delete', $category), [
                'action' => 'reassign',
                'reassign_to' => $targetCategory->id,
            ]);

        $response->assertRedirect(route('admin.categories.index'));
        $response->assertSessionHas('success');
        
        // Category should be deleted
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
        
        // Products should be moved to target category
        foreach ($products as $product) {
            $this->assertDatabaseHas('products', [
                'id' => $product->id,
                'category_id' => $targetCategory->id,
            ]);
        }
    }

    public function test_admin_can_move_products_to_uncategorized_before_deletion()
    {
        $category = Category::factory()->create();
        $products = Product::factory()->count(3)->create(['category_id' => $category->id]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.categories.reassign-delete', $category), [
                'action' => 'uncategorize',
            ]);

        $response->assertRedirect(route('admin.categories.index'));
        $response->assertSessionHas('success');
        
        // Category should be deleted
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
        
        // Products should be moved to uncategorized
        foreach ($products as $product) {
            $this->assertDatabaseHas('products', [
                'id' => $product->id,
                'category_id' => null,
            ]);
        }
    }

    public function test_categories_index_shows_product_counts()
    {
        $categoryWithProducts = Category::factory()->create();
        $categoryWithoutProducts = Category::factory()->create();
        
        Product::factory()->count(5)->create(['category_id' => $categoryWithProducts->id]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.categories.index'));

        $response->assertStatus(200);
        $response->assertSee('5 products'); // Category with products
        $response->assertSee('0 products'); // Category without products
    }

    public function test_non_admin_cannot_access_category_management()
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user)
            ->get(route('admin.categories.index'));

        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_category_management()
    {
        $response = $this->get(route('admin.categories.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_category_slug_validation_rejects_invalid_format()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.categories.store'), [
                'name' => 'Test Category',
                'slug' => 'Invalid Slug With Spaces',
            ]);

        $response->assertSessionHasErrors(['slug']);
    }

    public function test_category_description_has_maximum_length()
    {
        $longDescription = str_repeat('a', 1001); // 1001 characters

        $response = $this->actingAs($this->admin)
            ->post(route('admin.categories.store'), [
                'name' => 'Test Category',
                'description' => $longDescription,
            ]);

        $response->assertSessionHasErrors(['description']);
    }

    public function test_category_auto_generates_slug_when_empty()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.categories.store'), [
                'name' => 'Test Category Name',
                // No slug provided
            ]);

        $response->assertRedirect(route('admin.categories.index'));
        
        $this->assertDatabaseHas('categories', [
            'name' => 'Test Category Name',
            'slug' => 'test-category-name',
        ]);
    }
}