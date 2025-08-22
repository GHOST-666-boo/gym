<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductFileUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user for authentication
        $this->admin = User::factory()->create(['is_admin' => true]);
        
        // Create a test category
        $this->category = Category::factory()->create();
        
        // Fake the storage disk for testing
        Storage::fake('public');
    }

    /** @test */
    public function admin_can_create_product_with_image_upload()
    {
        $this->actingAs($this->admin);

        // Create a fake image file
        $image = UploadedFile::fake()->image('test-product.jpg', 800, 600);

        $productData = [
            'name' => 'Test Gym Machine',
            'price' => 1299.99,
            'short_description' => 'A great gym machine for testing',
            'long_description' => 'This is a detailed description of the test gym machine with all its features and benefits.',
            'category_id' => $this->category->id,
            'image' => $image,
        ];

        $response = $this->post(route('admin.products.store'), $productData);

        $response->assertRedirect(route('admin.products.index'));
        $response->assertSessionHas('success', 'Product created successfully.');

        // Assert product was created in database
        $this->assertDatabaseHas('products', [
            'name' => 'Test Gym Machine',
            'price' => 1299.99,
            'short_description' => 'A great gym machine for testing',
            'category_id' => $this->category->id,
        ]);

        // Assert image was stored
        $product = Product::where('name', 'Test Gym Machine')->first();
        $this->assertNotNull($product->image_path);
        Storage::disk('public')->assertExists($product->image_path);
    }

    /** @test */
    public function admin_can_update_product_with_new_image()
    {
        $this->actingAs($this->admin);

        // Create a product with an existing image
        $oldImage = UploadedFile::fake()->image('old-image.jpg');
        $product = Product::factory()->create([
            'image_path' => 'products/old-image.jpg',
            'category_id' => $this->category->id,
        ]);

        // Store the old image in fake storage
        Storage::disk('public')->put('products/old-image.jpg', 'old image content');

        // Create a new image for update
        $newImage = UploadedFile::fake()->image('new-image.jpg', 800, 600);

        $updateData = [
            'name' => $product->name,
            'price' => $product->price,
            'short_description' => $product->short_description,
            'long_description' => $product->long_description,
            'category_id' => $product->category_id,
            'image' => $newImage,
        ];

        $response = $this->put(route('admin.products.update', $product), $updateData);

        $response->assertRedirect(route('admin.products.index'));
        $response->assertSessionHas('success', 'Product updated successfully.');

        // Refresh the product from database
        $product->refresh();

        // Assert new image was stored and path changed
        $this->assertNotNull($product->image_path);
        $this->assertNotEquals('products/old-image.jpg', $product->image_path);
        Storage::disk('public')->assertExists($product->image_path);

        // Assert old image was deleted
        Storage::disk('public')->assertMissing('products/old-image.jpg');
    }

    /** @test */
    public function admin_can_update_product_without_changing_image()
    {
        $this->actingAs($this->admin);

        // Create a product with an existing image
        $product = Product::factory()->create([
            'image_path' => 'products/existing-image.jpg',
            'category_id' => $this->category->id,
        ]);

        // Store the existing image in fake storage
        Storage::disk('public')->put('products/existing-image.jpg', 'existing image content');

        $updateData = [
            'name' => 'Updated Product Name',
            'price' => $product->price,
            'short_description' => $product->short_description,
            'long_description' => $product->long_description,
            'category_id' => $product->category_id,
            // No image field - should keep existing image
        ];

        $response = $this->put(route('admin.products.update', $product), $updateData);

        $response->assertRedirect(route('admin.products.index'));
        $response->assertSessionHas('success', 'Product updated successfully.');

        // Refresh the product from database
        $product->refresh();

        // Assert product name was updated
        $this->assertEquals('Updated Product Name', $product->name);

        // Assert existing image was preserved
        $this->assertEquals('products/existing-image.jpg', $product->image_path);
        Storage::disk('public')->assertExists($product->image_path);
    }

    /** @test */
    public function product_deletion_removes_associated_image()
    {
        $this->actingAs($this->admin);

        // Create a product with an image
        $product = Product::factory()->create([
            'image_path' => 'products/delete-test.jpg',
            'category_id' => $this->category->id,
        ]);

        // Store the image in fake storage
        Storage::disk('public')->put('products/delete-test.jpg', 'test image content');

        $response = $this->delete(route('admin.products.destroy', $product));

        $response->assertRedirect(route('admin.products.index'));
        $response->assertSessionHas('success', 'Product deleted successfully.');

        // Assert product was deleted from database
        $this->assertDatabaseMissing('products', ['id' => $product->id]);

        // Assert image was deleted from storage
        Storage::disk('public')->assertMissing('products/delete-test.jpg');
    }

    /** @test */
    public function image_validation_rejects_invalid_file_types()
    {
        $this->actingAs($this->admin);

        // Create a fake non-image file
        $invalidFile = UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf');

        $productData = [
            'name' => 'Test Product',
            'price' => 999.99,
            'short_description' => 'Test description',
            'long_description' => 'Test long description',
            'category_id' => $this->category->id,
            'image' => $invalidFile,
        ];

        $response = $this->post(route('admin.products.store'), $productData);

        $response->assertSessionHasErrors('image');
        $this->assertDatabaseMissing('products', ['name' => 'Test Product']);
    }

    /** @test */
    public function image_validation_rejects_oversized_files()
    {
        $this->actingAs($this->admin);

        // Create a fake image that's too large (3MB)
        $oversizedImage = UploadedFile::fake()->image('large-image.jpg')->size(3072);

        $productData = [
            'name' => 'Test Product',
            'price' => 999.99,
            'short_description' => 'Test description',
            'long_description' => 'Test long description',
            'category_id' => $this->category->id,
            'image' => $oversizedImage,
        ];

        $response = $this->post(route('admin.products.store'), $productData);

        $response->assertSessionHasErrors('image');
        $this->assertDatabaseMissing('products', ['name' => 'Test Product']);
    }

    /** @test */
    public function image_validation_rejects_images_too_small()
    {
        $this->actingAs($this->admin);

        // Create a fake image that's too small (200x200)
        $smallImage = UploadedFile::fake()->image('small-image.jpg', 200, 200);

        $productData = [
            'name' => 'Test Product',
            'price' => 999.99,
            'short_description' => 'Test description',
            'long_description' => 'Test long description',
            'category_id' => $this->category->id,
            'image' => $smallImage,
        ];

        $response = $this->post(route('admin.products.store'), $productData);

        $response->assertSessionHasErrors('image');
        $this->assertDatabaseMissing('products', ['name' => 'Test Product']);
    }

    /** @test */
    public function product_can_be_created_without_image()
    {
        $this->actingAs($this->admin);

        $productData = [
            'name' => 'Test Product Without Image',
            'price' => 799.99,
            'short_description' => 'Test description',
            'long_description' => 'Test long description',
            'category_id' => $this->category->id,
            // No image field
        ];

        $response = $this->post(route('admin.products.store'), $productData);

        $response->assertRedirect(route('admin.products.index'));
        $response->assertSessionHas('success', 'Product created successfully.');

        // Assert product was created in database
        $this->assertDatabaseHas('products', [
            'name' => 'Test Product Without Image',
            'price' => 799.99,
            'image_path' => null,
        ]);
    }
}