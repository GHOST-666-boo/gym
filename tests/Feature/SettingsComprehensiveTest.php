<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\SiteSetting;
use App\Services\SettingsService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SettingsComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected SettingsService $settingsService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adminUser = User::factory()->create(['is_admin' => true]);
        $this->settingsService = app(SettingsService::class);
    }

    /** @test */
    public function test_complete_settings_functionality_validation()
    {
        $this->actingAs($this->adminUser);

        // 1. Test settings update and retrieval functionality
        $settingsData = [
            'site_name' => 'Comprehensive Test Gym',
            'site_tagline' => 'Complete fitness solution',
            'business_phone' => '555-TEST-GYM',
            'business_email' => 'test@comprehensivegym.com',
            'business_address' => '123 Test Street, Test City, TC 12345',
            'business_hours' => 'Mon-Fri: 5AM-11PM, Sat-Sun: 6AM-10PM',
            'facebook_url' => 'https://facebook.com/comprehensivegym',
            'instagram_url' => 'https://instagram.com/comprehensivegym',
            'twitter_url' => 'https://twitter.com/comprehensivegym',
            'youtube_url' => 'https://youtube.com/comprehensivegym',
            'default_meta_title' => 'Comprehensive Test Gym - Complete Fitness',
            'default_meta_description' => 'Your complete fitness solution with professional equipment and expert guidance.',
            'meta_keywords' => 'comprehensive gym, fitness, equipment, training',
            'maintenance_mode' => '0',
            'allow_registration' => '1',
            'currency_symbol' => '$',
            'currency_position' => 'before',
        ];

        $response = $this->patch(route('admin.settings.update'), $settingsData);
        $response->assertRedirect(route('admin.settings.index'));
        $response->assertSessionHas('success');

        // Verify all settings were saved
        foreach ($settingsData as $key => $value) {
            if ($key === 'maintenance_mode') {
                $this->assertFalse(is_maintenance_mode());
            } elseif ($key === 'allow_registration') {
                $this->assertTrue(allow_registration());
            } else {
                $this->assertEquals($value, $this->settingsService->get($key));
            }
        }

        // 2. Test settings reflection across website pages
        $response = $this->get(route('home'));
        $response->assertStatus(200);
        $response->assertSee('Comprehensive Test Gym');
        $response->assertSee('Complete fitness solution');
        $response->assertSee('555-TEST-GYM');
        $response->assertSee('test@comprehensivegym.com');
        $response->assertSee('https://facebook.com/comprehensivegym');

        // 3. Test file upload functionality
        Storage::fake('public');
        
        $logoFile = UploadedFile::fake()->image('test-logo.png', 200, 100);
        $logoResponse = $this->postJson(route('admin.settings.upload-logo'), [
            'logo' => $logoFile
        ]);
        $logoResponse->assertStatus(200);
        $logoResponse->assertJson(['success' => true]);
        
        $faviconFile = UploadedFile::fake()->image('test-favicon.png', 32, 32);
        $faviconResponse = $this->postJson(route('admin.settings.upload-favicon'), [
            'favicon' => $faviconFile
        ]);
        $faviconResponse->assertStatus(200);
        $faviconResponse->assertJson(['success' => true]);

        // Verify files were stored
        $logoPath = $logoResponse->json('path');
        $faviconPath = $faviconResponse->json('path');
        $this->assertTrue(Storage::disk('public')->exists($logoPath));
        $this->assertTrue(Storage::disk('public')->exists($faviconPath));

        // 4. Test maintenance mode functionality
        $this->settingsService->set('maintenance_mode', true, 'boolean', 'general');
        $this->assertTrue(is_maintenance_mode());
        
        $this->settingsService->set('maintenance_mode', false, 'boolean', 'general');
        $this->assertFalse(is_maintenance_mode());

        // 5. Test social media links display
        $socialMedia = social_media();
        $this->assertEquals('https://facebook.com/comprehensivegym', $socialMedia['facebook']);
        $this->assertEquals('https://instagram.com/comprehensivegym', $socialMedia['instagram']);
        $this->assertEquals('https://twitter.com/comprehensivegym', $socialMedia['twitter']);
        $this->assertEquals('https://youtube.com/comprehensivegym', $socialMedia['youtube']);

        $activeSocial = active_social_media();
        $this->assertCount(4, $activeSocial);

        // 6. Test SEO meta tags
        $response = $this->get(route('home'));
        $content = $response->getContent();
        $this->assertStringContainsString('<title>Comprehensive Test Gym - Complete Fitness</title>', $content);
        $this->assertStringContainsString('content="Your complete fitness solution with professional equipment and expert guidance."', $content);
        $this->assertStringContainsString('content="comprehensive gym, fitness, equipment, training"', $content);

        // 7. Test settings caching
        $this->settingsService->clearCache();
        $this->assertFalse($this->settingsService->isCacheWarm());
        
        $warmedSettings = $this->settingsService->warmCache();
        $this->assertTrue($this->settingsService->isCacheWarm());
        $this->assertArrayHasKey('site_name', $warmedSettings);

        // 8. Test contact information display
        $contactInfo = contact_info();
        $this->assertEquals('555-TEST-GYM', $contactInfo['phone']);
        $this->assertEquals('test@comprehensivegym.com', $contactInfo['email']);
        $this->assertStringContainsString('123 Test Street', $contactInfo['address']);

        // 9. Test currency and pricing settings
        $this->assertEquals('$', currency_symbol());
        $this->assertEquals('$99.99', format_price(99.99));

        // 10. Test admin settings interface accessibility
        $response = $this->get(route('admin.settings.index'));
        $response->assertStatus(200);
        $response->assertSee('Site Settings');
        $response->assertSee('General');
        $response->assertSee('Contact');
        $response->assertSee('Social Media');
        $response->assertSee('SEO');
        $response->assertSee('Advanced');

        // Test non-admin cannot access
        $regularUser = User::factory()->create(['is_admin' => false]);
        $this->actingAs($regularUser);
        $response = $this->get(route('admin.settings.index'));
        $response->assertStatus(403);

        $this->assertTrue(true); // All tests passed
    }

    /** @test */
    public function test_settings_validation_and_error_handling()
    {
        $this->actingAs($this->adminUser);

        // Test invalid email validation
        $response = $this->patch(route('admin.settings.update'), [
            'business_email' => 'invalid-email-format'
        ]);
        $response->assertSessionHasErrors('business_email');

        // Test invalid URL validation
        $response = $this->patch(route('admin.settings.update'), [
            'facebook_url' => 'not-a-valid-url'
        ]);
        $response->assertSessionHasErrors('facebook_url');

        // Test file upload validation
        Storage::fake('public');
        
        $invalidFile = UploadedFile::fake()->create('document.pdf', 100);
        $response = $this->postJson(route('admin.settings.upload-logo'), [
            'logo' => $invalidFile
        ]);
        $response->assertStatus(422);

        $largeFile = UploadedFile::fake()->create('large-logo.png', 6000);
        $response = $this->postJson(route('admin.settings.upload-logo'), [
            'logo' => $largeFile
        ]);
        $response->assertStatus(422);

        $this->assertTrue(true); // All validation tests passed
    }

    /** @test */
    public function test_helper_functions_comprehensive()
    {
        // Set up comprehensive test data
        $this->settingsService->updateMultiple([
            'site_name' => ['value' => 'Helper Test Gym', 'type' => 'string', 'group' => 'general'],
            'site_tagline' => ['value' => 'Testing all helpers', 'type' => 'string', 'group' => 'general'],
            'business_phone' => ['value' => '555-HELPER', 'type' => 'string', 'group' => 'contact'],
            'business_email' => ['value' => 'helper@test.com', 'type' => 'string', 'group' => 'contact'],
            'business_address' => ['value' => '456 Helper Ave', 'type' => 'string', 'group' => 'contact'],
            'business_hours' => ['value' => '24/7 Testing', 'type' => 'string', 'group' => 'contact'],
            'facebook_url' => ['value' => 'https://facebook.com/helper', 'type' => 'string', 'group' => 'social'],
            'instagram_url' => ['value' => 'https://instagram.com/helper', 'type' => 'string', 'group' => 'social'],
            'default_meta_title' => ['value' => 'Helper Test Title', 'type' => 'string', 'group' => 'seo'],
            'default_meta_description' => ['value' => 'Helper test description', 'type' => 'string', 'group' => 'seo'],
            'meta_keywords' => ['value' => 'helper, test, keywords', 'type' => 'string', 'group' => 'seo'],
            'maintenance_mode' => ['value' => false, 'type' => 'boolean', 'group' => 'general'],
            'allow_registration' => ['value' => true, 'type' => 'boolean', 'group' => 'general'],
            'currency_symbol' => ['value' => '€', 'type' => 'string', 'group' => 'advanced'],
            'currency_position' => ['value' => 'after', 'type' => 'string', 'group' => 'advanced'],
        ]);

        // Test all helper functions
        $this->assertEquals('Helper Test Gym', site_name());
        $this->assertEquals('Testing all helpers', site_tagline());
        $this->assertEquals('555-HELPER', business_phone());
        $this->assertEquals('helper@test.com', business_email());
        $this->assertEquals('456 Helper Ave', business_address());
        $this->assertEquals('24/7 Testing', business_hours());
        $this->assertEquals('https://facebook.com/helper', facebook_url());
        $this->assertEquals('https://instagram.com/helper', instagram_url());
        $this->assertEquals('Helper Test Title', meta_title());
        $this->assertEquals('Helper test description', meta_description());
        $this->assertEquals('helper, test, keywords', meta_keywords());
        $this->assertFalse(is_maintenance_mode());
        $this->assertTrue(allow_registration());
        $this->assertEquals('€', currency_symbol());
        $this->assertEquals('99.99€', format_price(99.99));

        // Test social media helpers
        $socialMedia = social_media();
        $this->assertIsArray($socialMedia);
        $this->assertEquals('https://facebook.com/helper', $socialMedia['facebook']);
        $this->assertEquals('https://instagram.com/helper', $socialMedia['instagram']);

        $activeSocial = active_social_media();
        $this->assertCount(2, $activeSocial); // Only facebook and instagram are set

        // Test contact info helper
        $contactInfo = contact_info();
        $this->assertIsArray($contactInfo);
        $this->assertEquals('555-HELPER', $contactInfo['phone']);
        $this->assertEquals('helper@test.com', $contactInfo['email']);

        // Test SEO settings helper
        $seoSettings = seo_settings();
        $this->assertIsArray($seoSettings);
        $this->assertEquals('Helper Test Title', $seoSettings['title']);
        $this->assertEquals('Helper test description', $seoSettings['description']);

        $this->assertTrue(true); // All helper function tests passed
    }
}