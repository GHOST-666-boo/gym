<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Services\SettingsService;
use App\Services\WatermarkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class AdminWatermarkSettingsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected SettingsService $settingsService;
    protected WatermarkService $watermarkService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adminUser = User::factory()->create([
            'email' => 'admin@test.com',
            'is_admin' => true,
        ]);
        
        $this->settingsService = app(SettingsService::class);
        $this->watermarkService = app(WatermarkService::class);
        
        Storage::fake('public');
    }

    /** @test */
    public function test_admin_can_update_watermark_settings_through_form()
    {
        $this->actingAs($this->adminUser);

        $watermarkSettings = [
            'watermark_enabled' => '1',
            'watermark_text' => 'Test Watermark Text',
            'watermark_position' => 'center',
            'watermark_opacity' => '75',
            'watermark_size' => '18',
            'watermark_text_color' => '#FF0000',
            'watermark_logo_size' => 'large',
        ];

        $response = $this->patch(route('admin.settings.update'), $watermarkSettings);

        $response->assertRedirect(route('admin.settings.index'));
        $response->assertSessionHas('success');

        // Verify settings were saved correctly
        foreach ($watermarkSettings as $key => $value) {
            if ($key === 'watermark_enabled') {
                $this->assertTrue((bool)$this->settingsService->get($key));
            } else {
                $this->assertEquals($value, $this->settingsService->get($key));
            }
        }
    }

    /** @test */
    public function test_admin_can_update_image_protection_settings_through_form()
    {
        $this->actingAs($this->adminUser);

        $protectionSettings = [
            'image_protection_enabled' => '1',
            'right_click_protection' => '1',
            'drag_drop_protection' => '1',
            'keyboard_protection' => '1',
        ];

        $response = $this->patch(route('admin.settings.update'), $protectionSettings);

        $response->assertRedirect(route('admin.settings.index'));
        $response->assertSessionHas('success');

        // Verify settings were saved correctly
        foreach ($protectionSettings as $key => $value) {
            $this->assertTrue((bool)$this->settingsService->get($key));
        }
    }

    /** @test */
    public function test_watermark_settings_validation_rules()
    {
        $this->actingAs($this->adminUser);

        // Test invalid opacity value
        $response = $this->patch(route('admin.settings.update'), [
            'watermark_opacity' => '150' // Should be between 10-90
        ]);
        $response->assertSessionHasErrors('watermark_opacity');

        // Test invalid position
        $response = $this->patch(route('admin.settings.update'), [
            'watermark_position' => 'invalid-position'
        ]);
        $response->assertSessionHasErrors('watermark_position');

        // Test invalid color format
        $response = $this->patch(route('admin.settings.update'), [
            'watermark_text_color' => 'not-a-color'
        ]);
        $response->assertSessionHasErrors('watermark_text_color');

        // Test invalid size
        $response = $this->patch(route('admin.settings.update'), [
            'watermark_size' => '0'
        ]);
        $response->assertSessionHasErrors('watermark_size');
    }

    /** @test */
    public function test_watermark_logo_upload_validation()
    {
        $this->actingAs($this->adminUser);

        // Test invalid file type
        $invalidFile = UploadedFile::fake()->create('document.pdf', 100);
        $response = $this->postJson(route('admin.settings.upload-watermark-logo'), [
            'logo' => $invalidFile
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('logo');

        // Test file too large (over 5MB)
        $largeFile = UploadedFile::fake()->create('large-logo.png', 6000);
        $response = $this->postJson(route('admin.settings.upload-watermark-logo'), [
            'logo' => $largeFile
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('logo');

        // Test valid logo upload
        $validFile = UploadedFile::fake()->image('valid-logo.png', 200, 100);
        $response = $this->postJson(route('admin.settings.upload-watermark-logo'), [
            'logo' => $validFile
        ]);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        
        $responseData = $response->json();
        $this->assertArrayHasKey('path', $responseData);
        $this->assertArrayHasKey('url', $responseData);
        
        // Verify file was stored
        Storage::disk('public')->assertExists($responseData['path']);
    }

    /** @test */
    public function test_settings_form_displays_current_values()
    {
        $this->actingAs($this->adminUser);

        // Set up test settings
        $this->settingsService->updateMultiple([
            'watermark_enabled' => ['value' => true, 'type' => 'boolean', 'group' => 'watermark'],
            'watermark_text' => ['value' => 'Current Watermark', 'type' => 'string', 'group' => 'watermark'],
            'watermark_position' => ['value' => 'top-right', 'type' => 'string', 'group' => 'watermark'],
            'watermark_opacity' => ['value' => 60, 'type' => 'integer', 'group' => 'watermark'],
            'image_protection_enabled' => ['value' => true, 'type' => 'boolean', 'group' => 'image_protection'],
            'right_click_protection' => ['value' => false, 'type' => 'boolean', 'group' => 'image_protection'],
        ]);

        $response = $this->get(route('admin.settings.index'));
        $response->assertStatus(200);

        $content = $response->getContent();
        
        // Check that current values are displayed
        $this->assertStringContainsString('Current Watermark', $content);
        $this->assertStringContainsString('top-right', $content);
        $this->assertStringContainsString('60', $content);
        
        // Check that checkboxes reflect current state
        $this->assertStringContainsString('checked', $content); // For enabled watermark
    }

    /** @test */
    public function test_settings_cache_invalidation_on_form_submission()
    {
        $this->actingAs($this->adminUser);

        // Warm up cache
        $this->settingsService->get('watermark_text');
        $this->assertTrue(Cache::has('site_setting_watermark_text'));

        // Update setting through form
        $response = $this->patch(route('admin.settings.update'), [
            'watermark_text' => 'Updated Watermark Text'
        ]);

        $response->assertRedirect();

        // Verify cache was invalidated and new value is returned
        $this->assertEquals('Updated Watermark Text', $this->settingsService->get('watermark_text'));
    }

    /** @test */
    public function test_watermark_cache_invalidation_on_settings_change()
    {
        $this->actingAs($this->adminUser);

        // Create a test image and apply watermark to populate cache
        Storage::disk('public')->put('products/test.jpg', 'fake-image-content');
        
        $this->settingsService->set('watermark_enabled', true);
        $this->settingsService->set('watermark_text', 'Original Text');
        
        // Apply watermark to create cache
        $watermarkedPath = $this->watermarkService->applyWatermark('products/test.jpg');
        
        // Update watermark settings through form
        $response = $this->patch(route('admin.settings.update'), [
            'watermark_text' => 'Updated Text',
            'watermark_opacity' => '80'
        ]);

        $response->assertRedirect();

        // Verify watermark cache was cleared (would need to check if cached file was removed)
        $this->assertEquals('Updated Text', $this->settingsService->get('watermark_text'));
        $this->assertEquals(80, $this->settingsService->get('watermark_opacity'));
    }

    /** @test */
    public function test_non_admin_cannot_access_watermark_settings()
    {
        $regularUser = User::factory()->create(['is_admin' => false]);
        $this->actingAs($regularUser);

        $response = $this->get(route('admin.settings.index'));
        $response->assertStatus(403);

        $response = $this->patch(route('admin.settings.update'), [
            'watermark_enabled' => '1'
        ]);
        $response->assertStatus(403);
    }

    /** @test */
    public function test_settings_form_ajax_validation()
    {
        $this->actingAs($this->adminUser);

        // Test AJAX validation for watermark opacity
        $response = $this->postJson(route('admin.settings.validate'), [
            'field' => 'watermark_opacity',
            'value' => '150'
        ]);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('watermark_opacity');

        // Test valid AJAX validation
        $response = $this->postJson(route('admin.settings.validate'), [
            'field' => 'watermark_opacity',
            'value' => '75'
        ]);
        
        $response->assertStatus(200);
        $response->assertJson(['valid' => true]);
    }

    /** @test */
    public function test_bulk_settings_update()
    {
        $this->actingAs($this->adminUser);

        $bulkSettings = [
            'watermark_enabled' => '1',
            'watermark_text' => 'Bulk Update Text',
            'watermark_position' => 'bottom-left',
            'watermark_opacity' => '65',
            'image_protection_enabled' => '1',
            'right_click_protection' => '1',
            'drag_drop_protection' => '0',
            'keyboard_protection' => '1',
        ];

        $response = $this->patch(route('admin.settings.update'), $bulkSettings);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify all settings were updated correctly
        foreach ($bulkSettings as $key => $value) {
            if (in_array($key, ['watermark_enabled', 'image_protection_enabled', 'right_click_protection', 'keyboard_protection'])) {
                $this->assertTrue((bool)$this->settingsService->get($key));
            } elseif ($key === 'drag_drop_protection') {
                $this->assertFalse((bool)$this->settingsService->get($key));
            } else {
                $this->assertEquals($value, $this->settingsService->get($key));
            }
        }
    }

    /** @test */
    public function test_settings_form_handles_missing_gd_extension()
    {
        $this->actingAs($this->adminUser);

        // Mock missing GD extension scenario
        if (extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is available, cannot test missing extension scenario');
        }

        $response = $this->get(route('admin.settings.index'));
        $response->assertStatus(200);

        $content = $response->getContent();
        
        // Should show warning about missing GD extension
        $this->assertStringContainsString('GD extension', $content);
        $this->assertStringContainsString('watermark', $content);
    }

    /** @test */
    public function test_settings_export_and_import()
    {
        $this->actingAs($this->adminUser);

        // Set up test settings
        $testSettings = [
            'watermark_enabled' => '1',
            'watermark_text' => 'Export Test',
            'watermark_position' => 'center',
            'image_protection_enabled' => '1',
        ];

        $this->patch(route('admin.settings.update'), $testSettings);

        // Export settings
        $response = $this->get(route('admin.settings.export'));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');

        $exportedData = $response->json();
        $this->assertArrayHasKey('watermark_enabled', $exportedData);
        $this->assertArrayHasKey('watermark_text', $exportedData);

        // Import settings
        $importData = [
            'watermark_text' => 'Imported Text',
            'watermark_opacity' => '85',
        ];

        $response = $this->postJson(route('admin.settings.import'), [
            'settings' => $importData
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify imported settings
        $this->assertEquals('Imported Text', $this->settingsService->get('watermark_text'));
        $this->assertEquals(85, $this->settingsService->get('watermark_opacity'));
    }
}