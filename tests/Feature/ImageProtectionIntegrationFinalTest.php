<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\SiteSetting;
use App\Services\WatermarkSettingsService;
use App\Services\ImageProtectionValidationService;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageProtectionIntegrationFinalTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected WatermarkSettingsService $watermarkService;
    protected ImageProtectionValidationService $validationService;
    protected SettingsService $settingsService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user
        $this->adminUser = User::factory()->create(['is_admin' => true]);
        
        // Initialize services
        $this->watermarkService = app(WatermarkSettingsService::class);
        $this->validationService = app(ImageProtectionValidationService::class);
        $this->settingsService = app(SettingsService::class);
        
        // Setup storage
        Storage::fake('public');
    }

    /** @test */
    public function admin_can_access_settings_page_with_watermark_status()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.settings.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.settings.index');
        $response->assertViewHas('settings');
    }

    /** @test */
    public function admin_dashboard_shows_image_protection_status()
    {
        // Enable some protection features
        SiteSetting::set('image_protection_enabled', '1', 'boolean', 'image_protection');
        SiteSetting::set('watermark_enabled', '1', 'boolean', 'image_protection');

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('imageProtectionStatus');
        
        $imageProtectionStatus = $response->viewData('imageProtectionStatus');
        $this->assertTrue($imageProtectionStatus['protection_enabled']);
        $this->assertTrue($imageProtectionStatus['watermark_enabled']);
    }

    /** @test */
    public function comprehensive_settings_validation_works()
    {
        $validSettings = [
            'image_protection_enabled' => true,
            'watermark_enabled' => true,
            'right_click_protection' => true,
            'drag_drop_protection' => true,
            'keyboard_protection' => false,
            'watermark_text' => 'Test Watermark',
            'watermark_position' => 'bottom-right',
            'watermark_opacity' => 50,
            'watermark_size' => 'medium',
            'watermark_text_color' => '#ffffff',
        ];

        $validated = $this->validationService->validateSettings($validSettings);
        
        $this->assertArrayHasKey('image_protection_enabled', $validated);
        $this->assertArrayHasKey('watermark_text', $validated);
        $this->assertEquals('Test Watermark', $validated['watermark_text']);
    }

    /** @test */
    public function validation_fails_when_protection_enabled_without_methods()
    {
        $invalidSettings = [
            'image_protection_enabled' => true,
            'right_click_protection' => false,
            'drag_drop_protection' => false,
            'keyboard_protection' => false,
        ];

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->validationService->validateSettings($invalidSettings);
    }

    /** @test */
    public function validation_fails_when_watermark_enabled_without_content()
    {
        $invalidSettings = [
            'watermark_enabled' => true,
            'watermark_text' => '',
            'watermark_logo_path' => '',
        ];

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->validationService->validateSettings($invalidSettings);
    }

    /** @test */
    public function admin_can_update_settings_with_comprehensive_validation()
    {
        $settingsData = [
            'site_name' => 'Test Gym Site',
            'image_protection_enabled' => '1',
            'watermark_enabled' => '1',
            'right_click_protection' => '1',
            'drag_drop_protection' => '1',
            'keyboard_protection' => '0',
            'watermark_text' => 'Test Watermark',
            'watermark_position' => 'bottom-right',
            'watermark_opacity' => '60',
            'watermark_size' => 'medium',
            'watermark_text_color' => '#ffffff',
        ];

        $response = $this->actingAs($this->adminUser)
            ->patch(route('admin.settings.update'), $settingsData);

        $response->assertRedirect(route('admin.settings.index'));
        $response->assertSessionHas('success');

        // Verify settings were saved
        $this->assertEquals('1', SiteSetting::get('image_protection_enabled'));
        $this->assertEquals('1', SiteSetting::get('watermark_enabled'));
        $this->assertEquals('Test Watermark', SiteSetting::get('watermark_text'));
    }

    /** @test */
    public function admin_cannot_update_settings_with_invalid_data()
    {
        $invalidData = [
            'site_name' => '', // Required field empty
            'image_protection_enabled' => '1',
            'right_click_protection' => '0',
            'drag_drop_protection' => '0',
            'keyboard_protection' => '0', // No protection methods enabled
            'watermark_opacity' => '5', // Below minimum
        ];

        $response = $this->actingAs($this->adminUser)
            ->patch(route('admin.settings.update'), $invalidData);

        $response->assertRedirect(route('admin.settings.index'));
        $response->assertSessionHasErrors();
    }

    /** @test */
    public function watermark_logo_upload_with_validation_works()
    {
        $file = UploadedFile::fake()->image('watermark.png', 100, 100);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.settings.upload-watermark-logo'), [
                'watermark_logo' => $file
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        
        $responseData = $response->json();
        $this->assertArrayHasKey('url', $responseData);
        $this->assertArrayHasKey('path', $responseData);
    }

    /** @test */
    public function watermark_logo_upload_fails_with_invalid_file()
    {
        $file = UploadedFile::fake()->create('document.pdf', 1000);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.settings.upload-watermark-logo'), [
                'watermark_logo' => $file
            ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
    }

    /** @test */
    public function validation_summary_endpoint_works()
    {
        // Set up some settings
        SiteSetting::set('image_protection_enabled', '1', 'boolean', 'image_protection');
        SiteSetting::set('watermark_enabled', '1', 'boolean', 'image_protection');
        SiteSetting::set('watermark_text', 'Test', 'string', 'watermark');

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.settings.validation-summary'));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        
        $responseData = $response->json();
        $this->assertArrayHasKey('summary', $responseData);
        $this->assertArrayHasKey('is_valid', $responseData['summary']);
    }

    /** @test */
    public function protection_features_test_endpoint_works()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.settings.test-protection'));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        
        $responseData = $response->json();
        $this->assertArrayHasKey('tests', $responseData);
        $this->assertArrayHasKey('all_tests_passed', $responseData);
        $this->assertArrayHasKey('recommendations', $responseData);
        
        // Check that basic tests are present
        $tests = $responseData['tests'];
        $this->assertArrayHasKey('storage_writable', $tests);
        $this->assertArrayHasKey('protection_enabled', $tests);
        $this->assertArrayHasKey('watermark_enabled', $tests);
    }

    /** @test */
    public function watermark_settings_service_integration_works()
    {
        // Set up watermark settings
        SiteSetting::set('watermark_enabled', '1', 'boolean', 'image_protection');
        SiteSetting::set('watermark_text', 'Test Watermark', 'string', 'watermark');
        SiteSetting::set('watermark_position', 'center', 'string', 'watermark');
        SiteSetting::set('watermark_opacity', '75', 'integer', 'watermark');

        $this->assertTrue($this->watermarkService->isWatermarkEnabled());
        $this->assertEquals('Test Watermark', $this->watermarkService->getWatermarkText());
        $this->assertEquals('center', $this->watermarkService->getWatermarkPosition());
        $this->assertEquals(75, $this->watermarkService->getWatermarkOpacity());

        $config = $this->watermarkService->getWatermarkConfig();
        $this->assertArrayHasKey('enabled', $config);
        $this->assertArrayHasKey('text', $config);
        $this->assertArrayHasKey('position', $config);
        $this->assertArrayHasKey('opacity', $config);
    }

    /** @test */
    public function image_protection_settings_service_integration_works()
    {
        // Set up protection settings
        SiteSetting::set('image_protection_enabled', '1', 'boolean', 'image_protection');
        SiteSetting::set('right_click_protection', '1', 'boolean', 'image_protection');
        SiteSetting::set('drag_drop_protection', '0', 'boolean', 'image_protection');
        SiteSetting::set('keyboard_protection', '1', 'boolean', 'image_protection');

        $this->assertTrue($this->watermarkService->isImageProtectionEnabled());
        $this->assertTrue($this->watermarkService->hasAnyProtectionEnabled());

        $config = $this->watermarkService->getProtectionConfig();
        $this->assertTrue($config['enabled']);
        $this->assertTrue($config['right_click']);
        $this->assertFalse($config['drag_drop']);
        $this->assertTrue($config['keyboard']);
    }

    /** @test */
    public function validation_service_provides_comprehensive_summary()
    {
        $settings = [
            'image_protection_enabled' => true,
            'watermark_enabled' => false, // This should generate a warning
            'right_click_protection' => true,
            'watermark_text' => 'Test',
            'watermark_opacity' => 80, // This should generate a recommendation
        ];

        $summary = $this->validationService->getValidationSummary($settings);

        $this->assertArrayHasKey('is_valid', $summary);
        $this->assertArrayHasKey('warnings', $summary);
        $this->assertArrayHasKey('errors', $summary);
        $this->assertArrayHasKey('recommendations', $summary);

        // Should have warnings about watermarking being disabled
        $this->assertNotEmpty($summary['warnings']);
        
        // Should have recommendations about opacity
        $this->assertNotEmpty($summary['recommendations']);
    }

    /** @test */
    public function all_components_work_together_in_complete_workflow()
    {
        // 1. Start with default settings
        $this->assertFalse($this->watermarkService->isImageProtectionEnabled());
        $this->assertFalse($this->watermarkService->isWatermarkEnabled());

        // 2. Update settings through the controller
        $settingsData = [
            'site_name' => 'Integration Test Site',
            'image_protection_enabled' => '1',
            'watermark_enabled' => '1',
            'right_click_protection' => '1',
            'drag_drop_protection' => '1',
            'keyboard_protection' => '0',
            'watermark_text' => 'Integration Test Watermark',
            'watermark_position' => 'bottom-center',
            'watermark_opacity' => '45',
            'watermark_size' => 'large',
            'watermark_text_color' => '#ff0000',
        ];

        $response = $this->actingAs($this->adminUser)
            ->patch(route('admin.settings.update'), $settingsData);

        $response->assertRedirect(route('admin.settings.index'));
        $response->assertSessionHas('success');

        // Clear cache to ensure fresh data
        $this->watermarkService->clearCache();

        // 3. Verify settings are accessible through service
        $this->assertTrue($this->watermarkService->isImageProtectionEnabled());
        $this->assertTrue($this->watermarkService->isWatermarkEnabled());
        $this->assertEquals('Integration Test Watermark', $this->watermarkService->getWatermarkText());

        // 4. Test validation summary
        $validationResponse = $this->actingAs($this->adminUser)
            ->get(route('admin.settings.validation-summary'));

        $validationResponse->assertStatus(200);
        $summary = $validationResponse->json()['summary'];
        $this->assertTrue($summary['is_valid']);

        // 5. Test system status
        $statusResponse = $this->actingAs($this->adminUser)
            ->get(route('admin.settings.test-protection'));

        $statusResponse->assertStatus(200);
        $statusData = $statusResponse->json();
        $this->assertTrue($statusData['tests']['protection_enabled']);
        $this->assertTrue($statusData['tests']['watermark_enabled']);

        // 6. Verify dashboard shows correct status
        $dashboardResponse = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard'));

        $dashboardResponse->assertStatus(200);
        $imageProtectionStatus = $dashboardResponse->viewData('imageProtectionStatus');
        $this->assertTrue($imageProtectionStatus['protection_enabled']);
        $this->assertTrue($imageProtectionStatus['watermark_enabled']);
        $this->assertTrue($imageProtectionStatus['has_watermark_text']);
    }
}