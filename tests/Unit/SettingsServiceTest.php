<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\SettingsService;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SettingsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SettingsService $settingsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->settingsService = new SettingsService();
    }

    public function test_can_get_setting_value()
    {
        // Create a test setting
        SiteSetting::create([
            'key' => 'test_setting',
            'value' => 'test_value',
            'type' => 'string',
            'group' => 'general'
        ]);

        $value = $this->settingsService->get('test_setting');
        
        $this->assertEquals('test_value', $value);
    }

    public function test_can_set_setting_value()
    {
        $result = $this->settingsService->set('new_setting', 'new_value', 'string', 'general');
        
        $this->assertInstanceOf(SiteSetting::class, $result);
        $this->assertEquals('new_setting', $result->key);
        $this->assertEquals('new_value', $result->value);
    }

    public function test_can_get_settings_by_group()
    {
        // Create test settings
        SiteSetting::create([
            'key' => 'setting1',
            'value' => 'value1',
            'type' => 'string',
            'group' => 'test_group'
        ]);

        SiteSetting::create([
            'key' => 'setting2',
            'value' => 'value2',
            'type' => 'string',
            'group' => 'test_group'
        ]);

        $settings = $this->settingsService->getByGroup('test_group');
        
        $this->assertCount(2, $settings);
        $this->assertEquals('value1', $settings['setting1']);
        $this->assertEquals('value2', $settings['setting2']);
    }

    public function test_can_update_multiple_settings()
    {
        $settings = [
            'setting1' => ['value' => 'value1', 'type' => 'string', 'group' => 'general'],
            'setting2' => ['value' => 'value2', 'type' => 'string', 'group' => 'general'],
        ];

        $results = $this->settingsService->updateMultiple($settings);
        
        $this->assertCount(2, $results);
        $this->assertInstanceOf(SiteSetting::class, $results['setting1']);
        $this->assertInstanceOf(SiteSetting::class, $results['setting2']);
    }

    public function test_get_logo_url_returns_default_when_no_logo()
    {
        $logoUrl = $this->settingsService->getLogoUrl();
        
        $this->assertEquals(asset('images/default-logo.png'), $logoUrl);
    }

    public function test_get_favicon_url_returns_default_when_no_favicon()
    {
        $faviconUrl = $this->settingsService->getFaviconUrl();
        
        $this->assertEquals(asset('favicon.ico'), $faviconUrl);
    }

    public function test_can_upload_logo()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('logo.png', 200, 200);
        
        $result = $this->settingsService->uploadLogo($file);
        
        $this->assertTrue($result['success']);
        $this->assertNotNull($result['path']);
        $this->assertStringContainsString('logos/', $result['path']);
        
        // Verify setting was updated
        $logoPath = $this->settingsService->get('logo_path');
        $this->assertEquals($result['path'], $logoPath);
        
        // Verify file was stored
        Storage::disk('public')->assertExists($result['path']);
    }

    public function test_can_upload_favicon()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('favicon.png', 32, 32);
        
        $result = $this->settingsService->uploadFavicon($file);
        
        $this->assertTrue($result['success']);
        $this->assertNotNull($result['path']);
        $this->assertStringContainsString('favicons/', $result['path']);
        
        // Verify setting was updated
        $faviconPath = $this->settingsService->get('favicon_path');
        $this->assertEquals($result['path'], $faviconPath);
        
        // Verify file was stored
        Storage::disk('public')->assertExists($result['path']);
    }

    public function test_logo_upload_validates_file_size()
    {
        Storage::fake('public');

        // Create a file that's too large (over 2MB)
        $file = UploadedFile::fake()->create('large_logo.png', 3000); // 3MB
        
        $result = $this->settingsService->uploadLogo($file);
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('size exceeds', $result['message']);
    }

    public function test_favicon_upload_validates_file_size()
    {
        Storage::fake('public');

        // Create a file that's too large (over 1MB)
        $file = UploadedFile::fake()->create('large_favicon.png', 2000); // 2MB
        
        $result = $this->settingsService->uploadFavicon($file);
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('size exceeds', $result['message']);
    }

    public function test_can_get_all_grouped_settings()
    {
        // Create test settings in different groups
        SiteSetting::create(['key' => 'site_name', 'value' => 'Test Site', 'type' => 'string', 'group' => 'general']);
        SiteSetting::create(['key' => 'business_phone', 'value' => '123-456-7890', 'type' => 'string', 'group' => 'contact']);
        SiteSetting::create(['key' => 'facebook_url', 'value' => 'https://facebook.com/test', 'type' => 'string', 'group' => 'social']);

        $grouped = $this->settingsService->getAllGrouped();
        
        $this->assertIsArray($grouped);
        $this->assertArrayHasKey('general', $grouped);
        $this->assertArrayHasKey('contact', $grouped);
        $this->assertArrayHasKey('social', $grouped);
        $this->assertArrayHasKey('seo', $grouped);
        $this->assertArrayHasKey('advanced', $grouped);
    }
}