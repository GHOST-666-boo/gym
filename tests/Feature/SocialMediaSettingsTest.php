<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SocialMediaSettingsTest extends TestCase
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

    public function test_it_can_save_social_media_settings()
    {
        // Test data
        $socialData = [
            'site_name' => 'Test Gym',
            'facebook_url' => 'https://facebook.com/testgym',
            'instagram_url' => 'https://instagram.com/testgym',
            'twitter_url' => 'https://twitter.com/testgym',
            'youtube_url' => 'https://youtube.com/testgym',
            'linkedin_url' => 'https://linkedin.com/company/testgym',
            'tiktok_url' => 'https://tiktok.com/@testgym',
            'show_social_footer' => '1',
            'show_social_contact' => '1',
            'social_links_new_tab' => '1',
            'maintenance_mode' => '0',
            'allow_registration' => '1'
        ];

        // Act as admin and submit form
        $response = $this->actingAs($this->admin)
            ->patch(route('admin.settings.update'), $socialData);

        // Check response
        $response->assertRedirect(route('admin.settings.index'));
        $response->assertSessionHas('success');

        // Verify settings were saved in database
        $this->assertDatabaseHas('site_settings', [
            'key' => 'facebook_url',
            'value' => 'https://facebook.com/testgym'
        ]);

        $this->assertDatabaseHas('site_settings', [
            'key' => 'instagram_url',
            'value' => 'https://instagram.com/testgym'
        ]);

        $this->assertDatabaseHas('site_settings', [
            'key' => 'show_social_footer',
            'value' => '1'
        ]);

        // Test retrieval through model
        $this->assertEquals('https://facebook.com/testgym', SiteSetting::get('facebook_url'));
        $this->assertEquals('https://instagram.com/testgym', SiteSetting::get('instagram_url'));
        $this->assertEquals(true, SiteSetting::get('show_social_footer'));
    }

    public function test_it_validates_social_media_urls()
    {
        // Invalid data
        $invalidData = [
            'site_name' => 'Test Gym',
            'facebook_url' => 'invalid-url',
            'instagram_url' => 'not-a-url',
            'maintenance_mode' => '0',
            'allow_registration' => '1'
        ];

        // Submit invalid data
        $response = $this->actingAs($this->admin)
            ->patch(route('admin.settings.update'), $invalidData);

        // Should redirect back with errors
        $response->assertRedirect(route('admin.settings.index'));
        $response->assertSessionHasErrors(['facebook_url', 'instagram_url']);
    }

    public function test_it_handles_empty_social_media_fields()
    {
        // Data with empty social fields
        $dataWithEmptyFields = [
            'site_name' => 'Test Gym',
            'facebook_url' => '',
            'instagram_url' => '',
            'twitter_url' => '',
            'youtube_url' => '',
            'linkedin_url' => '',
            'tiktok_url' => '',
            'show_social_footer' => '0',
            'show_social_contact' => '0',
            'social_links_new_tab' => '0',
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
        $this->assertEquals('', SiteSetting::get('facebook_url'));
        $this->assertEquals('', SiteSetting::get('instagram_url'));
        $this->assertEquals(false, SiteSetting::get('show_social_footer'));
    }
}