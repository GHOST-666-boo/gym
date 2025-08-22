<?php

namespace Tests\Unit;

use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ProductRequestValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_request_validation_rules()
    {
        $request = new ProductRequest();
        $rules = $request->rules();

        // Test that all required fields are present
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('price', $rules);
        $this->assertArrayHasKey('short_description', $rules);
        $this->assertArrayHasKey('long_description', $rules);
        $this->assertArrayHasKey('category_id', $rules);
        $this->assertArrayHasKey('image', $rules);
    }

    public function test_product_request_validation_passes_with_valid_data()
    {
        $category = Category::factory()->create();
        
        $request = new ProductRequest();
        $validator = Validator::make([
            'name' => 'Professional Treadmill',
            'price' => 2999.99,
            'short_description' => 'High-quality treadmill for professional use',
            'long_description' => 'This is a comprehensive description of the professional treadmill with all its advanced features and specifications.',
            'category_id' => $category->id,
        ], $request->rules());

        $this->assertTrue($validator->passes());
    }

    public function test_product_request_validation_fails_with_missing_required_fields()
    {
        $request = new ProductRequest();
        $validator = Validator::make([], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
        $this->assertArrayHasKey('price', $validator->errors()->toArray());
        $this->assertArrayHasKey('short_description', $validator->errors()->toArray());
        $this->assertArrayHasKey('long_description', $validator->errors()->toArray());
    }

    public function test_product_request_name_validation()
    {
        $category = Category::factory()->create();
        $request = new ProductRequest();

        // Test name too short - but 'A' is actually valid since min length is not specified
        // Let's test empty name instead
        $validator = Validator::make([
            'name' => '',
            'price' => 2999.99,
            'short_description' => 'Valid description',
            'long_description' => 'Valid long description',
            'category_id' => $category->id,
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());

        // Test name too long
        $validator = Validator::make([
            'name' => str_repeat('A', 256), // Assuming max 255 characters
            'price' => 2999.99,
            'short_description' => 'Valid description',
            'long_description' => 'Valid long description',
            'category_id' => $category->id,
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_product_request_price_validation()
    {
        $category = Category::factory()->create();
        $request = new ProductRequest();

        // Test negative price
        $validator = Validator::make([
            'name' => 'Valid Product Name',
            'price' => -100,
            'short_description' => 'Valid description',
            'long_description' => 'Valid long description',
            'category_id' => $category->id,
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('price', $validator->errors()->toArray());

        // Test non-numeric price
        $validator = Validator::make([
            'name' => 'Valid Product Name',
            'price' => 'not-a-number',
            'short_description' => 'Valid description',
            'long_description' => 'Valid long description',
            'category_id' => $category->id,
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('price', $validator->errors()->toArray());

        // Test price exceeding maximum
        $validator = Validator::make([
            'name' => 'Valid Product Name',
            'price' => 1000000, // Exceeds max:999999.99
            'short_description' => 'Valid description',
            'long_description' => 'Valid long description',
            'category_id' => $category->id,
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('price', $validator->errors()->toArray());
    }

    public function test_product_request_description_validation()
    {
        $category = Category::factory()->create();
        $request = new ProductRequest();

        // Test short description empty (since there's no min length rule, test required)
        $validator = Validator::make([
            'name' => 'Valid Product Name',
            'price' => 2999.99,
            'short_description' => '',
            'long_description' => 'Valid long description with enough characters',
            'category_id' => $category->id,
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('short_description', $validator->errors()->toArray());

        // Test long description empty (since there's no min length rule, test required)
        $validator = Validator::make([
            'name' => 'Valid Product Name',
            'price' => 2999.99,
            'short_description' => 'Valid short description',
            'long_description' => '',
            'category_id' => $category->id,
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('long_description', $validator->errors()->toArray());
    }

    public function test_product_request_category_validation()
    {
        $request = new ProductRequest();

        // Test invalid category ID
        $validator = Validator::make([
            'name' => 'Valid Product Name',
            'price' => 2999.99,
            'short_description' => 'Valid short description',
            'long_description' => 'Valid long description with enough characters',
            'category_id' => 999, // Non-existent category
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('category_id', $validator->errors()->toArray());

        // Test non-numeric category ID
        $validator = Validator::make([
            'name' => 'Valid Product Name',
            'price' => 2999.99,
            'short_description' => 'Valid short description',
            'long_description' => 'Valid long description with enough characters',
            'category_id' => 'not-a-number',
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('category_id', $validator->errors()->toArray());
    }

    public function test_product_request_image_validation()
    {
        $category = Category::factory()->create();
        $request = new ProductRequest();

        // Test valid image
        $validImage = UploadedFile::fake()->image('product.jpg', 800, 600);
        $validator = Validator::make([
            'name' => 'Valid Product Name',
            'price' => 2999.99,
            'short_description' => 'Valid short description',
            'long_description' => 'Valid long description with enough characters',
            'category_id' => $category->id,
            'image' => $validImage,
        ], $request->rules());

        $this->assertTrue($validator->passes());

        // Test invalid file type
        $invalidFile = UploadedFile::fake()->create('document.pdf', 1000);
        $validator = Validator::make([
            'name' => 'Valid Product Name',
            'price' => 2999.99,
            'short_description' => 'Valid short description',
            'long_description' => 'Valid long description with enough characters',
            'category_id' => $category->id,
            'image' => $invalidFile,
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('image', $validator->errors()->toArray());

        // Test oversized image
        $oversizedImage = UploadedFile::fake()->image('large.jpg')->size(10000); // 10MB
        $validator = Validator::make([
            'name' => 'Valid Product Name',
            'price' => 2999.99,
            'short_description' => 'Valid short description',
            'long_description' => 'Valid long description with enough characters',
            'category_id' => $category->id,
            'image' => $oversizedImage,
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('image', $validator->errors()->toArray());
    }

    public function test_product_request_authorization_requires_admin()
    {
        $request = new ProductRequest();
        
        // Without authentication, should return false
        $this->assertFalse($request->authorize());
        
        // With non-admin user, should return false
        $user = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user);
        $this->assertFalse($request->authorize());
        
        // With admin user, should return true
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);
        $this->assertTrue($request->authorize());
    }

    public function test_product_request_custom_messages()
    {
        $request = new ProductRequest();
        $messages = $request->messages();

        $this->assertArrayHasKey('name.required', $messages);
        $this->assertArrayHasKey('price.required', $messages);
        $this->assertArrayHasKey('short_description.required', $messages);
        $this->assertArrayHasKey('long_description.required', $messages);
        $this->assertArrayHasKey('price.numeric', $messages);
        $this->assertArrayHasKey('price.min', $messages);
        $this->assertArrayHasKey('image.image', $messages);
        $this->assertArrayHasKey('image.max', $messages);
    }

    public function test_product_request_nullable_fields()
    {
        $request = new ProductRequest();
        
        // Test that category_id and image are nullable
        $validator = Validator::make([
            'name' => 'Valid Product Name',
            'price' => 2999.99,
            'short_description' => 'Valid short description',
            'long_description' => 'Valid long description with enough characters',
            'category_id' => null,
            'image' => null,
        ], $request->rules());

        $this->assertTrue($validator->passes());
    }
}