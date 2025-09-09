<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ContactSettingsDebugTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user
        $this->admin = User::factory()->create([
            'email' => 'admin@test.com',
            'is_admin' => true
        ]);
    }

    /** @test */
    public function it_can_save_contact_information()
    {
        // Test data
        $contactData = [
            'site_name' => 'Test Gym',
            'business_phone' => '+1-555-123-4567',
            'business_email' => 'contact@testgym.com',
            'business_address' => '123 Test Street, Test City, TC 12345',
            'business_hours' => 'Mon-Fri: 9AM-6PM',
            'contact_form_email' => 'admin@testgym.com',
            'contact_auto_reply' => '1',
            'maintenance_mode' => '0',
            'allow_registration' => '1'
        ];

        // Act as admin and submit form
        $response = $this->actingAs($this->admin)
            ->patch(route('admin.settings.update'), $contactData);

        // Check response
        $response->assertRedirect(route('admin.settings.index'));
        $response->assertSessionHas('success');

        // Verify settings were saved in database
        $this->assertDatabaseHas('site_settings', [
            'key' => 'business_phone',
            'value' => '+1-555-123-4567'
        ]);

        $this->assertDatabaseHas('site_settings', [
            'key' => 'business_email',
            'value' => 'contact@testgym.com'
        ]);

        $this->assertDatabaseHas('site_settings', [
            'key' => 'business_address',
            'value' => '123 Test Street, Test City, TC 12345'
        ]);

        // Test retrieval through model
        $this->assertEquals('+1-555-123-4567', SiteSetting::get('business_phone'));
        $this->assertEquals('contact@testgym.com', SiteSetting::get('business_email'));
        $this->assertEquals('123 Test Street, Test City, TC 12345', SiteSetting::get('business_address'));
    }

    /** @test */
    public function it_can_update_existing_contact_information()
    {
        // Create initial settings
        SiteSetting::set('business_phone', '+1-555-000-0000', 'string', 'contact');
        SiteSetting::set('business_email', 'old@testgym.com', 'string', 'contact');

        // Updated data
        $updatedData = [
            'site_name' => 'Test Gym',
            'business_phone' => '+1-555-999-9999',
            'business_email' => 'new@testgym.com',
            'business_address' => '456 New Street, New City, NC 54321',
            'business_hours' => 'Mon-Sun: 24/7',
            'contact_form_email' => 'newadmin@testgym.com',
            'contact_auto_reply' => '0',
            'maintenance_mode' => '0',
            'allow_registration' => '1'
        ];

        // Submit update
        $response = $this->actingAs($this->admin)
            ->patch(route('admin.settings.update'), $updatedData);

        // Check response
        $response->assertRedirect(route('admin.settings.index'));

        // Verify updates
        $this->assertEquals('+1-555-999-9999', SiteSetting::get('business_phone'));
        $this->assertEquals('new@testgym.com', SiteSetting::get('business_email'));
        $this->assertEquals('456 New Street, New City, NC 54321', SiteSetting::get('business_address'));
        $this->assertEquals('Mon-Sun: 24/7', SiteSetting::get('business_hours'));
        $this->assertEquals('newadmin@testgym.com', SiteSetting::get('contact_form_email'));
        $this->assertEquals(false, SiteSetting::get('contact_auto_reply'));
    }

    /** @test */
    public function it_validates_contact_information()
    {
        // Invalid data
        $invalidData = [
            'site_name' => '', // Required field
            'business_email' => 'invalid-email', // Invalid email
            'contact_form_email' => 'another-invalid-email', // Invalid email
            'maintenance_mode' => '0',
            'allow_registration' => '1'
        ];

        // Submit invalid data
        $response = $this->actingAs($this->admin)
            ->patch(route('admin.settings.update'), $invalidData);

        // Should redirect back with errors
        $response->assertRedirect(route('admin.settings.index'));
        $response->assertSessionHasErrors(['site_name', 'business_email', 'contact_form_email']);
    }

    /** @test */
    public function it_handles_empty_optional_fields()
    {
        // Data with empty optional fields
        $dataWithEmptyFields = [
            'site_name' => 'Test Gym',
            'business_phone' => '',
            'business_email' => '',
            'business_address' => '',
            'business_hours' => '',
            'contact_form_email' => '',
            'contact_auto_reply' => '0',
            'maintenance_mode' => '0',
            'allow_registration' => '1'
        ];

        // Submit data
        $response = $this->actingAs($this->admin)
            ->patch(route('admin.settings.update'), $dataWithEmptyFields);

        // Should succeed
        $response->assertRedirect(route('admin.settings.index'));
        $response->assertSessionHas('success');

        // Verify empty values are stored
        $this->assertEquals('', SiteSetting::get('business_phone'));
        $this->assertEquals('', SiteSetting::get('business_email'));
        $this->assertEquals('', SiteSetting::get('business_address'));
    }
}