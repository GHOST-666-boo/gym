<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SettingsHelperTest extends TestCase
{
    use RefreshDatabase;

    public function test_setting_helper_function_works()
    {
        // Create a test setting
        SiteSetting::create([
            'key' => 'test_helper',
            'value' => 'helper_value',
            'type' => 'string',
            'group' => 'general'
        ]);

        $value = setting('test_helper');
        
        $this->assertEquals('helper_value', $value);
    }

    public function test_site_name_helper_function()
    {
        SiteSetting::create([
            'key' => 'site_name',
            'value' => 'Test Gym Site',
            'type' => 'string',
            'group' => 'general'
        ]);

        $siteName = site_name();
        
        $this->assertEquals('Test Gym Site', $siteName);
    }

    public function test_site_name_helper_returns_default()
    {
        $siteName = site_name();
        
        // Should return the default from config (Laravel) or our fallback
        $this->assertContains($siteName, ['Laravel', 'Gym Machines']);
    }

    public function test_business_phone_helper_function()
    {
        SiteSetting::create([
            'key' => 'business_phone',
            'value' => '123-456-7890',
            'type' => 'string',
            'group' => 'contact'
        ]);

        $phone = business_phone();
        
        $this->assertEquals('123-456-7890', $phone);
    }

    public function test_social_media_helper_function()
    {
        SiteSetting::create([
            'key' => 'facebook_url',
            'value' => 'https://facebook.com/test',
            'type' => 'string',
            'group' => 'social'
        ]);

        SiteSetting::create([
            'key' => 'instagram_url',
            'value' => 'https://instagram.com/test',
            'type' => 'string',
            'group' => 'social'
        ]);

        $socialMedia = social_media();
        
        $this->assertIsArray($socialMedia);
        $this->assertEquals('https://facebook.com/test', $socialMedia['facebook']);
        $this->assertEquals('https://instagram.com/test', $socialMedia['instagram']);
    }

    public function test_active_social_media_helper_function()
    {
        SiteSetting::create([
            'key' => 'facebook_url',
            'value' => 'https://facebook.com/test',
            'type' => 'string',
            'group' => 'social'
        ]);

        // Don't create instagram_url, so it should be null/empty

        $activeSocial = active_social_media();
        
        $this->assertIsArray($activeSocial);
        $this->assertArrayHasKey('facebook', $activeSocial);
        $this->assertArrayNotHasKey('instagram', $activeSocial);
        $this->assertEquals('https://facebook.com/test', $activeSocial['facebook']);
    }

    public function test_format_price_helper_function()
    {
        SiteSetting::create([
            'key' => 'currency_symbol',
            'value' => '$',
            'type' => 'string',
            'group' => 'advanced'
        ]);

        SiteSetting::create([
            'key' => 'currency_position',
            'value' => 'before',
            'type' => 'string',
            'group' => 'advanced'
        ]);

        $formattedPrice = format_price(99.99);
        
        $this->assertEquals('$99.99', $formattedPrice);
    }

    public function test_is_maintenance_mode_helper_function()
    {
        SiteSetting::create([
            'key' => 'maintenance_mode',
            'value' => '1',
            'type' => 'boolean',
            'group' => 'advanced'
        ]);

        $isMaintenanceMode = is_maintenance_mode();
        
        $this->assertTrue($isMaintenanceMode);
    }

    public function test_contact_info_helper_function()
    {
        SiteSetting::create([
            'key' => 'business_phone',
            'value' => '123-456-7890',
            'type' => 'string',
            'group' => 'contact'
        ]);

        SiteSetting::create([
            'key' => 'business_email',
            'value' => 'test@example.com',
            'type' => 'string',
            'group' => 'contact'
        ]);

        $contactInfo = contact_info();
        
        $this->assertIsArray($contactInfo);
        $this->assertEquals('123-456-7890', $contactInfo['phone']);
        $this->assertEquals('test@example.com', $contactInfo['email']);
    }
}