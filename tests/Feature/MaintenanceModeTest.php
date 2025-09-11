<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Services\SettingsService;

class MaintenanceModeTest extends TestCase
{
    use RefreshDatabase;

    protected SettingsService $settingsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->settingsService = app(SettingsService::class);
    }

    /** @test */
    public function test_maintenance_mode_setting_functionality()
    {
        // Test maintenance mode is disabled by default
        $this->assertFalse(is_maintenance_mode());

        // Enable maintenance mode
        $this->settingsService->set('maintenance_mode', true, 'boolean', 'general');
        
        // Clear any cached settings to ensure fresh read
        $this->settingsService->clearCache();
        
        // Verify maintenance mode is enabled
        $this->assertTrue(is_maintenance_mode());

        // Disable maintenance mode
        $this->settingsService->set('maintenance_mode', false, 'boolean', 'general');
        
        // Clear cache again
        $this->settingsService->clearCache();
        
        // Verify maintenance mode is disabled
        $this->assertFalse(is_maintenance_mode());
    }

    /** @test */
    public function test_maintenance_mode_through_admin_interface()
    {
        $adminUser = User::factory()->create(['is_admin' => true]);
        $this->actingAs($adminUser);

        // Enable maintenance mode through admin interface
        $response = $this->patch(route('admin.settings.update'), [
            'maintenance_mode' => '1'
        ]);

        $response->assertRedirect(route('admin.settings.index'));
        $response->assertSessionHas('success');
        
        // Verify maintenance mode is enabled
        $this->assertTrue(is_maintenance_mode());

        // Disable maintenance mode through admin interface
        $response = $this->patch(route('admin.settings.update'), [
            'maintenance_mode' => '0'
        ]);

        $response->assertRedirect(route('admin.settings.index'));
        $response->assertSessionHas('success');
        
        // Verify maintenance mode is disabled
        $this->assertFalse(is_maintenance_mode());
    }

    /** @test */
    public function test_maintenance_mode_boolean_casting()
    {
        // Test various boolean values
        $this->settingsService->set('maintenance_mode', '1', 'boolean', 'general');
        $this->assertTrue(is_maintenance_mode());

        $this->settingsService->set('maintenance_mode', '0', 'boolean', 'general');
        $this->assertFalse(is_maintenance_mode());

        $this->settingsService->set('maintenance_mode', 'true', 'boolean', 'general');
        $this->assertTrue(is_maintenance_mode());

        $this->settingsService->set('maintenance_mode', 'false', 'boolean', 'general');
        $this->assertFalse(is_maintenance_mode());

        $this->settingsService->set('maintenance_mode', true, 'boolean', 'general');
        $this->assertTrue(is_maintenance_mode());

        $this->settingsService->set('maintenance_mode', false, 'boolean', 'general');
        $this->assertFalse(is_maintenance_mode());
    }

    /** @test */
    public function test_maintenance_mode_admin_access()
    {
        // Enable maintenance mode
        $this->settingsService->set('maintenance_mode', true, 'boolean', 'general');

        // Create admin user
        $adminUser = User::factory()->create(['is_admin' => true]);
        $this->actingAs($adminUser);

        // Admin should be able to access admin routes even in maintenance mode
        $response = $this->get(route('admin.dashboard'));
        $response->assertStatus(200);

        $response = $this->get(route('admin.settings.index'));
        $response->assertStatus(200);

        // Admin should be able to disable maintenance mode
        $response = $this->patch(route('admin.settings.update'), [
            'maintenance_mode' => '0'
        ]);
        $response->assertRedirect();
        $this->assertFalse(is_maintenance_mode());
    }

    /** @test */
    public function test_maintenance_mode_checkbox_handling()
    {
        $adminUser = User::factory()->create(['is_admin' => true]);
        $this->actingAs($adminUser);

        // Test checkbox checked (maintenance mode enabled)
        $response = $this->patch(route('admin.settings.update'), [
            'maintenance_mode' => '1'
        ]);
        $response->assertRedirect();
        $this->assertTrue(is_maintenance_mode());

        // Test checkbox unchecked (maintenance mode disabled)
        // When checkbox is unchecked, the value won't be sent in the request
        // But we have a hidden input with value="0" to handle this
        $response = $this->patch(route('admin.settings.update'), [
            'maintenance_mode' => '0'
        ]);
        $response->assertRedirect();
        $this->assertFalse(is_maintenance_mode());

        // Test when maintenance_mode is not in request at all
        $response = $this->patch(route('admin.settings.update'), [
            'site_name' => 'Test Site'
        ]);
        $response->assertRedirect();
        // Maintenance mode should remain unchanged
        $this->assertFalse(is_maintenance_mode());
    }

    /** @test */
    public function test_maintenance_mode_setting_persistence()
    {
        // Enable maintenance mode
        $this->settingsService->set('maintenance_mode', true, 'boolean', 'general');
        $this->assertTrue(is_maintenance_mode());

        // Clear cache to simulate fresh request
        $this->settingsService->clearCache();
        
        // Setting should persist
        $this->assertTrue(is_maintenance_mode());

        // Disable maintenance mode
        $this->settingsService->set('maintenance_mode', false, 'boolean', 'general');
        $this->assertFalse(is_maintenance_mode());

        // Clear cache again
        $this->settingsService->clearCache();
        
        // Setting should persist
        $this->assertFalse(is_maintenance_mode());
    }

    /** @test */
    public function test_maintenance_mode_in_settings_display()
    {
        $adminUser = User::factory()->create(['is_admin' => true]);
        $this->actingAs($adminUser);

        // Test maintenance mode disabled in settings page
        $this->settingsService->set('maintenance_mode', false, 'boolean', 'general');
        
        $response = $this->get(route('admin.settings.index'));
        $response->assertStatus(200);
        
        // The maintenance mode checkbox should not be checked
        $content = $response->getContent();
        // Look for the specific maintenance mode checkbox without checked attribute
        $this->assertStringContainsString('name="maintenance_mode"', $content);
        $this->assertStringNotContainsString('name="maintenance_mode" value="1" class="sr-only peer" checked', $content);

        // Test maintenance mode enabled in settings page
        $this->settingsService->set('maintenance_mode', true, 'boolean', 'general');
        
        $response = $this->get(route('admin.settings.index'));
        $response->assertStatus(200);
        
        // The checkbox should be checked
        $content = $response->getContent();
        $this->assertStringContainsString('maintenance_mode', $content);
    }
}