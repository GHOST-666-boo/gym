<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\SiteSetting;
use App\Services\SettingsService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class SettingsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected SettingsService $settingsService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user
        $this->adminUser = User::factory()->create([
            'email' => 'admin@test.com',
            'is_admin' => true,
        ]);
        
        $this->settingsService = app(SettingsService::class);
    }

    /** @test */
    public function test_settings_update_and_retrieval_functionality()
    {
        $this->actingAs($this->adminUser);

        // Test updating multiple settings
        $settingsData = [
            'site_name' => 'Test Gym Site',
            'site_tagline' => 'Your fitness destination',
            'business_phone' => '123-456-7890',
            'business_email' => 'test@gym.com',
            'business_address' => '123 Fitness St, Gym City',
            'business_hours' => 'Mon-Fri: 6AM-10PM',
            'facebook_url' => 'https://facebook.com/testgym',
            'instagram_url' => 'https://instagram.com/testgym',
            'twitter_url' => 'https://twitter.com/testgym',
            'youtube_url' => 'https://youtube.com/testgym',
            'default_meta_title' => 'Test Gym - Fitness Equipment',
            'default_meta_description' => 'Professional fitness equipment for your gym',
            'meta_keywords' => 'gym, fitness, equipment',
            'maintenance_mode' => '0',
            'allow_registration' => '1',
            'currency_symbol' => '$',
            'currency_position' => 'before',
        ];

        $response = $this->patch(route('admin.settings.update'), $settingsData);

        $response->assertRedirect(route('admin.settings.index'));
        $response->assertSessionHas('success');

        // Verify settings were saved correctly
        foreach ($settingsData as $key => $value) {
            $this->assertEquals($value, $this->settingsService->get($key));
        }

        // Test settings retrieval through helper functions
        $this->assertEquals('Test Gym Site', site_name());
        $this->assertEquals('Your fitness destination', site_tagline());
        $this->assertEquals('123-456-7890', business_phone());
        $this->assertEquals('test@gym.com', business_email());
        $this->assertEquals('123 Fitness St, Gym City', business_address());
        $this->assertEquals('Mon-Fri: 6AM-10PM', business_hours());
        $this->assertEquals('https://facebook.com/testgym', facebook_url());
        $this->assertEquals('https://instagram.com/testgym', instagram_url());
        $this->assertEquals('https://twitter.com/testgym', twitter_url());
        $this->assertEquals('https://youtube.com/testgym', youtube_url());
        $this->assertEquals('Test Gym - Fitness Equipment', meta_title());
        $this->assertEquals('Professional fitness equipment for your gym', meta_description());
        $this->assertEquals('gym, fitness, equipment', meta_keywords());
        $this->assertFalse(is_maintenance_mode());
        $this->assertTrue(allow_registration());
        $this->assertEquals('$', currency_symbol());
        $this->assertEquals('$99.99', format_price(99.99));
    }

    /** @test */
    public function test_settings_reflection_across_website_pages()
    {
        // Set up test settings
        $this->settingsService->updateMultiple([
            'site_name' => ['value' => 'Test Gym Website', 'type' => 'string', 'group' => 'general'],
            'site_tagline' => ['value' => 'Premium Fitness Equipment', 'type' => 'string', 'group' => 'general'],
            'business_phone' => ['value' => '555-123-4567', 'type' => 'string', 'group' => 'contact'],
            'business_email' => ['value' => 'info@testgym.com', 'type' => 'string', 'group' => 'contact'],
            'business_address' => ['value' => '456 Gym Avenue, Fitness City', 'type' => 'string', 'group' => 'contact'],
            'facebook_url' => ['value' => 'https://facebook.com/testgym', 'type' => 'string', 'group' => 'social'],
            'instagram_url' => ['value' => 'https://instagram.com/testgym', 'type' => 'string', 'group' => 'social'],
            'default_meta_title' => ['value' => 'Test Gym - Professional Equipment', 'type' => 'string', 'group' => 'seo'],
            'default_meta_description' => ['value' => 'Find the best gym equipment at Test Gym', 'type' => 'string', 'group' => 'seo'],
        ]);

        // Test home page
        $response = $this->get(route('home'));
        $response->assertStatus(200);
        $response->assertSee('Test Gym Website');
        $response->assertSee('Premium Fitness Equipment');
        $response->assertSee('555-123-4567');
        $response->assertSee('info@testgym.com');
        $response->assertSee('456 Gym Avenue, Fitness City');
        $response->assertSee('https://facebook.com/testgym');
        $response->assertSee('https://instagram.com/testgym');

        // Test products page
        $response = $this->get(route('products.index'));
        $response->assertStatus(200);
        $response->assertSee('Test Gym Website');

        // Test contact page
        $response = $this->get(route('contact'));
        $response->assertStatus(200);
        $response->assertSee('Test Gym Website');
        $response->assertSee('555-123-4567');
        $response->assertSee('info@testgym.com');

        // Verify meta tags in page source
        $response = $this->get(route('home'));
        $content = $response->getContent();
        $this->assertStringContainsString('<title>Test Gym - Professional Equipment</title>', $content);
        $this->assertStringContainsString('content="Find the best gym equipment at Test Gym"', $content);
    }

    /** @test */
    public function test_file_upload_and_display_for_logos_and_favicons()
    {
        Storage::fake('public');
        $this->actingAs($this->adminUser);

        // Test logo upload
        $logoFile = UploadedFile::fake()->image('test-logo.png', 200, 100);
        
        $response = $this->postJson(route('admin.settings.upload-logo'), [
            'logo' => $logoFile
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        
        $responseData = $response->json();
        $this->assertNotNull($responseData['path']);
        $this->assertStringContainsString('logos/', $responseData['path']);
        
        // Verify file was stored
        Storage::disk('public')->assertExists($responseData['path']);
        
        // Verify setting was updated
        $logoPath = $this->settingsService->get('logo_path');
        $this->assertEquals($responseData['path'], $logoPath);
        
        // Test logo URL helper
        $logoUrl = site_logo();
        $this->assertStringContainsString($responseData['path'], $logoUrl);

        // Test favicon upload
        $faviconFile = UploadedFile::fake()->image('test-favicon.png', 32, 32);
        
        $response = $this->postJson(route('admin.settings.upload-favicon'), [
            'favicon' => $faviconFile
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        
        $responseData = $response->json();
        $this->assertNotNull($responseData['path']);
        $this->assertStringContainsString('favicons/', $responseData['path']);
        
        // Verify file was stored
        Storage::disk('public')->assertExists($responseData['path']);
        
        // Verify setting was updated
        $faviconPath = $this->settingsService->get('favicon_path');
        $this->assertEquals($responseData['path'], $faviconPath);
        
        // Test favicon URL helper
        $faviconUrl = site_favicon();
        $this->assertStringContainsString($responseData['path'], $faviconUrl);

        // Test that uploaded files appear in the admin settings page
        $response = $this->get(route('admin.settings.index'));
        $response->assertStatus(200);
        
        // The view should show the current logo and favicon
        $content = $response->getContent();
        $this->assertStringContainsString('storage/' . $logoPath, $content);
        $this->assertStringContainsString('storage/' . $faviconPath, $content);
    }

    /** @test */
    public function test_maintenance_mode_functionality()
    {
        // Enable maintenance mode
        $this->settingsService->set('maintenance_mode', true, 'boolean', 'general');
        
        // Verify maintenance mode is enabled
        $this->assertTrue(is_maintenance_mode());
        
        // Test that regular users see maintenance page (if implemented)
        // Note: This would require a maintenance mode middleware to be implemented
        // For now, we'll just test the setting functionality
        
        // Disable maintenance mode
        $this->settingsService->set('maintenance_mode', false, 'boolean', 'general');
        
        // Verify maintenance mode is disabled
        $this->assertFalse(is_maintenance_mode());
        
        // Test through admin interface
        $this->actingAs($this->adminUser);
        
        $response = $this->patch(route('admin.settings.update'), [
            'maintenance_mode' => '1'
        ]);
        
        $response->assertRedirect();
        $this->assertTrue(is_maintenance_mode());
        
        $response = $this->patch(route('admin.settings.update'), [
            'maintenance_mode' => '0'
        ]);
        
        $response->assertRedirect();
        $this->assertFalse(is_maintenance_mode());
    }

    /** @test */
    public function test_social_media_links_display_in_footer()
    {
        // Set up social media settings
        $this->settingsService->updateMultiple([
            'facebook_url' => ['value' => 'https://facebook.com/testgym', 'type' => 'string', 'group' => 'social'],
            'instagram_url' => ['value' => 'https://instagram.com/testgym', 'type' => 'string', 'group' => 'social'],
            'twitter_url' => ['value' => 'https://twitter.com/testgym', 'type' => 'string', 'group' => 'social'],
            'youtube_url' => ['value' => 'https://youtube.com/testgym', 'type' => 'string', 'group' => 'social'],
        ]);

        // Test that social media links appear on pages
        $response = $this->get(route('home'));
        $response->assertStatus(200);
        $response->assertSee('https://facebook.com/testgym');
        $response->assertSee('https://instagram.com/testgym');
        $response->assertSee('https://twitter.com/testgym');
        $response->assertSee('https://youtube.com/testgym');

        // Test social media helper functions
        $socialMedia = social_media();
        $this->assertEquals('https://facebook.com/testgym', $socialMedia['facebook']);
        $this->assertEquals('https://instagram.com/testgym', $socialMedia['instagram']);
        $this->assertEquals('https://twitter.com/testgym', $socialMedia['twitter']);
        $this->assertEquals('https://youtube.com/testgym', $socialMedia['youtube']);

        // Test active social media (non-empty URLs)
        $activeSocial = active_social_media();
        $this->assertCount(4, $activeSocial);
        $this->assertArrayHasKey('facebook', $activeSocial);
        $this->assertArrayHasKey('instagram', $activeSocial);
        $this->assertArrayHasKey('twitter', $activeSocial);
        $this->assertArrayHasKey('youtube', $activeSocial);

        // Test with some empty social media URLs
        $this->settingsService->set('twitter_url', '', 'string', 'social');
        $this->settingsService->set('youtube_url', '', 'string', 'social');

        $activeSocial = active_social_media();
        $this->assertCount(2, $activeSocial);
        $this->assertArrayHasKey('facebook', $activeSocial);
        $this->assertArrayHasKey('instagram', $activeSocial);
        $this->assertArrayNotHasKey('twitter', $activeSocial);
        $this->assertArrayNotHasKey('youtube', $activeSocial);
    }

    /** @test */
    public function test_seo_meta_tags_on_all_pages()
    {
        // Set up SEO settings
        $this->settingsService->updateMultiple([
            'default_meta_title' => ['value' => 'Test Gym - Premium Fitness Equipment', 'type' => 'string', 'group' => 'seo'],
            'default_meta_description' => ['value' => 'Discover our premium collection of gym machines and fitness equipment for commercial and home gyms.', 'type' => 'string', 'group' => 'seo'],
            'meta_keywords' => ['value' => 'gym equipment, fitness machines, commercial gym, home gym, exercise equipment', 'type' => 'string', 'group' => 'seo'],
        ]);

        // Test home page meta tags
        $response = $this->get(route('home'));
        $response->assertStatus(200);
        $content = $response->getContent();
        
        $this->assertStringContainsString('<title>Test Gym - Premium Fitness Equipment</title>', $content);
        $this->assertStringContainsString('content="Discover our premium collection of gym machines and fitness equipment for commercial and home gyms."', $content);
        $this->assertStringContainsString('content="gym equipment, fitness machines, commercial gym, home gym, exercise equipment"', $content);

        // Test products page meta tags
        $response = $this->get(route('products.index'));
        $response->assertStatus(200);
        $content = $response->getContent();
        
        $this->assertStringContainsString('Test Gym - Premium Fitness Equipment', $content);

        // Test contact page meta tags
        $response = $this->get(route('contact'));
        $response->assertStatus(200);
        $content = $response->getContent();
        
        $this->assertStringContainsString('Test Gym - Premium Fitness Equipment', $content);

        // Test SEO helper functions
        $seoSettings = seo_settings();
        $this->assertEquals('Test Gym - Premium Fitness Equipment', $seoSettings['title']);
        $this->assertEquals('Discover our premium collection of gym machines and fitness equipment for commercial and home gyms.', $seoSettings['description']);
        $this->assertEquals('gym equipment, fitness machines, commercial gym, home gym, exercise equipment', $seoSettings['keywords']);
    }

    /** @test */
    public function test_settings_caching_functionality()
    {
        // Clear cache first
        $this->settingsService->clearCache();
        
        // Create test settings
        SiteSetting::create(['key' => 'cache_test_1', 'value' => 'value1', 'type' => 'string', 'group' => 'general']);
        SiteSetting::create(['key' => 'cache_test_2', 'value' => 'value2', 'type' => 'string', 'group' => 'contact']);

        // Verify cache is not warm initially
        $this->assertFalse($this->settingsService->isCacheWarm());

        // Warm the cache
        $warmedSettings = $this->settingsService->warmCache();
        
        // Verify cache is now warm
        $this->assertTrue($this->settingsService->isCacheWarm());
        $this->assertArrayHasKey('cache_test_1', $warmedSettings);
        $this->assertArrayHasKey('cache_test_2', $warmedSettings);

        // Verify individual settings are cached
        $this->assertTrue(Cache::has('site_setting_cache_test_1'));
        $this->assertTrue(Cache::has('site_setting_cache_test_2'));

        // Test cache invalidation on update
        $this->settingsService->set('cache_test_1', 'updated_value', 'string', 'general');
        
        // Verify cache was invalidated and new value is returned
        $this->assertEquals('updated_value', $this->settingsService->get('cache_test_1'));

        // Test cache statistics
        $stats = $this->settingsService->getCacheStats();
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('is_warm', $stats);
        $this->assertArrayHasKey('cached_settings', $stats);
        $this->assertArrayHasKey('cached_groups', $stats);
        $this->assertArrayHasKey('cache_driver', $stats);
    }

    /** @test */
    public function test_admin_settings_interface_accessibility()
    {
        $this->actingAs($this->adminUser);

        // Test settings index page loads
        $response = $this->get(route('admin.settings.index'));
        $response->assertStatus(200);
        $response->assertSee('Site Settings');
        $response->assertSee('General');
        $response->assertSee('Contact');
        $response->assertSee('Social Media');
        $response->assertSee('SEO');
        $response->assertSee('Advanced');

        // Test that non-admin users cannot access settings
        $regularUser = User::factory()->create(['is_admin' => false]);
        $this->actingAs($regularUser);

        $response = $this->get(route('admin.settings.index'));
        $response->assertStatus(403);
    }

    /** @test */
    public function test_settings_validation_rules()
    {
        $this->actingAs($this->adminUser);

        // Test invalid email validation
        $response = $this->patch(route('admin.settings.update'), [
            'business_email' => 'invalid-email'
        ]);
        $response->assertSessionHasErrors('business_email');

        // Test invalid URL validation
        $response = $this->patch(route('admin.settings.update'), [
            'facebook_url' => 'not-a-url'
        ]);
        $response->assertSessionHasErrors('facebook_url');

        // Test valid data passes validation
        $response = $this->patch(route('admin.settings.update'), [
            'site_name' => 'Valid Site Name',
            'business_email' => 'valid@email.com',
            'facebook_url' => 'https://facebook.com/valid'
        ]);
        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    }

    /** @test */
    public function test_file_upload_validation()
    {
        Storage::fake('public');
        $this->actingAs($this->adminUser);

        // Test invalid file type for logo
        $invalidFile = UploadedFile::fake()->create('document.pdf', 100);
        
        $response = $this->postJson(route('admin.settings.upload-logo'), [
            'logo' => $invalidFile
        ]);
        $response->assertStatus(422);

        // Test file too large for logo
        $largeFile = UploadedFile::fake()->create('large-logo.png', 6000); // 6MB
        
        $response = $this->postJson(route('admin.settings.upload-logo'), [
            'logo' => $largeFile
        ]);
        $response->assertStatus(422);

        // Test valid logo upload
        $validFile = UploadedFile::fake()->image('valid-logo.png', 200, 100);
        
        $response = $this->postJson(route('admin.settings.upload-logo'), [
            'logo' => $validFile
        ]);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    /** @test */
    public function test_contact_information_display()
    {
        // Set up contact information
        $this->settingsService->updateMultiple([
            'business_phone' => ['value' => '555-CONTACT', 'type' => 'string', 'group' => 'contact'],
            'business_email' => ['value' => 'contact@testgym.com', 'type' => 'string', 'group' => 'contact'],
            'business_address' => ['value' => '789 Contact Ave\nSuite 100\nContact City, CC 12345', 'type' => 'string', 'group' => 'contact'],
            'business_hours' => ['value' => 'Monday - Friday: 6:00 AM - 10:00 PM\nSaturday - Sunday: 8:00 AM - 8:00 PM', 'type' => 'string', 'group' => 'contact'],
        ]);

        // Test contact info helper
        $contactInfo = contact_info();
        $this->assertEquals('555-CONTACT', $contactInfo['phone']);
        $this->assertEquals('contact@testgym.com', $contactInfo['email']);
        $this->assertStringContainsString('789 Contact Ave', $contactInfo['address']);
        $this->assertStringContainsString('Monday - Friday', $contactInfo['hours']);

        // Test contact information appears on pages
        $response = $this->get(route('home'));
        $response->assertStatus(200);
        $response->assertSee('555-CONTACT');
        $response->assertSee('contact@testgym.com');
        $response->assertSee('789 Contact Ave');

        $response = $this->get(route('contact'));
        $response->assertStatus(200);
        $response->assertSee('555-CONTACT');
        $response->assertSee('contact@testgym.com');
    }

    /** @test */
    public function test_currency_and_pricing_settings()
    {
        // Set up currency settings
        $this->settingsService->updateMultiple([
            'currency_symbol' => ['value' => '€', 'type' => 'string', 'group' => 'advanced'],
            'currency_position' => ['value' => 'after', 'type' => 'string', 'group' => 'advanced'],
        ]);

        // Test currency formatting
        $this->assertEquals('€', currency_symbol());
        $this->assertEquals('99.99€', format_price(99.99));

        // Test with currency before
        $this->settingsService->set('currency_position', 'before', 'string', 'advanced');
        $this->assertEquals('€99.99', format_price(99.99));
    }
}