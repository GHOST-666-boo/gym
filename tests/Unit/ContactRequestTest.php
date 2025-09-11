<?php

namespace Tests\Unit;

use App\Http\Requests\ContactRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ContactRequestTest extends TestCase
{
    public function test_contact_request_validation_rules()
    {
        $request = new ContactRequest();
        $rules = $request->rules();

        // Test that all required fields are present
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayHasKey('message', $rules);
    }

    public function test_contact_request_validation_passes_with_valid_data()
    {
        $request = new ContactRequest();
        $validator = Validator::make([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'This is a test message with enough characters',
        ], $request->rules());

        $this->assertTrue($validator->passes());
    }

    public function test_contact_request_validation_fails_with_invalid_data()
    {
        $request = new ContactRequest();
        $validator = Validator::make([
            'name' => 'A', // Too short
            'email' => 'invalid-email', // Invalid email
            'message' => 'Short', // Too short
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
        $this->assertArrayHasKey('message', $validator->errors()->toArray());
    }

    public function test_contact_request_validation_fails_with_empty_data()
    {
        $request = new ContactRequest();
        $validator = Validator::make([
            'name' => '',
            'email' => '',
            'message' => '',
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
        $this->assertArrayHasKey('message', $validator->errors()->toArray());
    }

    public function test_contact_request_authorization_always_returns_true()
    {
        $request = new ContactRequest();
        $this->assertTrue($request->authorize());
    }

    public function test_contact_request_custom_messages()
    {
        $request = new ContactRequest();
        $messages = $request->messages();

        $this->assertArrayHasKey('name.required', $messages);
        $this->assertArrayHasKey('email.required', $messages);
        $this->assertArrayHasKey('message.required', $messages);
        $this->assertArrayHasKey('email.email', $messages);
    }

    public function test_contact_request_has_prepare_for_validation_method()
    {
        $request = new ContactRequest();
        
        // Test that the prepareForValidation method exists
        $this->assertTrue(method_exists($request, 'prepareForValidation'));
    }
}