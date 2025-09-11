<?php

namespace Tests\Unit;

use App\Http\Requests\ProductRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ProductRequestTest extends TestCase
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
        $request = new ProductRequest();
        $validator = Validator::make([
            'name' => 'Test Product',
            'price' => '99.99',
            'short_description' => 'A short description',
            'long_description' => 'A longer description with more details',
            'category_id' => null,
        ], $request->rules());

        $this->assertTrue($validator->passes());
    }

    public function test_product_request_validation_fails_with_invalid_data()
    {
        $request = new ProductRequest();
        $validator = Validator::make([
            'name' => '', // Required field empty
            'price' => 'invalid', // Invalid price
            'short_description' => '', // Required field empty
            'long_description' => '', // Required field empty
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
        $this->assertArrayHasKey('price', $validator->errors()->toArray());
        $this->assertArrayHasKey('short_description', $validator->errors()->toArray());
        $this->assertArrayHasKey('long_description', $validator->errors()->toArray());
    }

    public function test_product_request_authorization_for_admin_user()
    {
        $adminUser = User::factory()->create(['is_admin' => true]);
        $this->actingAs($adminUser);

        $request = new ProductRequest();
        $this->assertTrue($request->authorize());
    }

    public function test_product_request_authorization_for_non_admin_user()
    {
        $regularUser = User::factory()->create(['is_admin' => false]);
        $this->actingAs($regularUser);

        $request = new ProductRequest();
        $this->assertFalse($request->authorize());
    }

    public function test_product_request_custom_messages()
    {
        $request = new ProductRequest();
        $messages = $request->messages();

        $this->assertArrayHasKey('name.required', $messages);
        $this->assertArrayHasKey('price.required', $messages);
        $this->assertArrayHasKey('short_description.required', $messages);
        $this->assertArrayHasKey('long_description.required', $messages);
    }
}