<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Services\SettingsService;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

class SettingsCacheTest extends TestCase
{
    use RefreshDatabase;

    protected SettingsService $settingsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->settingsService = app(SettingsService::class);
    }

    public function test_cache_warming_preloads_all_settings(): void
    {
        // Create some test settings
        SiteSetting::create(['key' => 'test_setting_1', 'value' => 'value1', 'type' => 'string', 'group' => 'general']);
        SiteSetting::create(['key' => 'test_setting_2', 'value' => 'value2', 'type' => 'string', 'group' => 'contact']);
        SiteSetting::create(['key' => 'test_setting_3', 'value' => 'true', 'type' => 'boolean', 'group' => 'advanced']);

        // Clear cache first
        $this->settingsService->clearCache();
        $this->assertFalse($this->settingsService->isCacheWarm());

        // Warm the cache
        $warmedSettings = $this->settingsService->warmCache();

        // Verify cache is warm
        $this->assertTrue($this->settingsService->isCacheWarm());
        $this->assertCount(3, $warmedSettings);
        $this->assertEquals('value1', $warmedSettings['test_setting_1']);
        $this->assertEquals('value2', $warmedSettings['test_setting_2']);
        $this->assertTrue($warmedSettings['test_setting_3']); // Should be cast to boolean

        // Verify individual settings are cached
        $this->assertTrue(Cache::has('site_setting_test_setting_1'));
        $this->assertTrue(Cache::has('site_setting_test_setting_2'));
        $this->assertTrue(Cache::has('site_setting_test_setting_3'));
    }

    public function test_cache_invalidation_on_setting_update(): void
    {
        // Create a test setting
        $setting = SiteSetting::create(['key' => 'test_setting', 'value' => 'original', 'type' => 'string', 'group' => 'general']);

        // Warm cache
        $this->settingsService->warmCache();
        $this->assertTrue(Cache::has('site_setting_test_setting'));
        $this->assertTrue(Cache::has('site_settings_group_general'));

        // Update the setting
        $this->settingsService->set('test_setting', 'updated', 'string', 'general');

        // Verify caches are invalidated
        $this->assertFalse(Cache::has('site_settings_group_general'));
        $this->assertFalse(Cache::has('site_settings_all'));

        // Verify new value is returned
        $this->assertEquals('updated', $this->settingsService->get('test_setting'));
    }

    public function test_get_multiple_settings_optimized(): void
    {
        // Create test settings
        SiteSetting::create(['key' => 'setting_1', 'value' => 'value1', 'type' => 'string', 'group' => 'general']);
        SiteSetting::create(['key' => 'setting_2', 'value' => 'value2', 'type' => 'string', 'group' => 'general']);
        SiteSetting::create(['key' => 'setting_3', 'value' => 'value3', 'type' => 'string', 'group' => 'general']);

        // Clear cache
        $this->settingsService->clearCache();

        // Get multiple settings
        $keys = ['setting_1', 'setting_2', 'setting_3', 'nonexistent'];
        $results = $this->settingsService->getMultiple($keys, 'default');

        // Verify results
        $this->assertEquals('value1', $results['setting_1']);
        $this->assertEquals('value2', $results['setting_2']);
        $this->assertEquals('value3', $results['setting_3']);
        $this->assertEquals('default', $results['nonexistent']);

        // Verify settings are now cached
        $this->assertTrue(Cache::has('site_setting_setting_1'));
        $this->assertTrue(Cache::has('site_setting_setting_2'));
        $this->assertTrue(Cache::has('site_setting_setting_3'));
    }

    public function test_update_multiple_settings_with_transaction(): void
    {
        // Create initial settings
        SiteSetting::create(['key' => 'setting_1', 'value' => 'old1', 'type' => 'string', 'group' => 'general']);
        SiteSetting::create(['key' => 'setting_2', 'value' => 'old2', 'type' => 'string', 'group' => 'contact']);

        // Warm cache
        $this->settingsService->warmCache();

        // Update multiple settings
        $updates = [
            'setting_1' => ['value' => 'new1', 'type' => 'string', 'group' => 'general'],
            'setting_2' => ['value' => 'new2', 'type' => 'string', 'group' => 'contact'],
            'setting_3' => ['value' => 'new3', 'type' => 'string', 'group' => 'advanced'],
        ];

        $results = $this->settingsService->updateMultiple($updates);

        // Verify all updates were successful
        $this->assertCount(3, $results);
        $this->assertEquals('new1', $this->settingsService->get('setting_1'));
        $this->assertEquals('new2', $this->settingsService->get('setting_2'));
        $this->assertEquals('new3', $this->settingsService->get('setting_3'));

        // Verify affected group caches were cleared
        $this->assertFalse(Cache::has('site_settings_all'));
    }

    public function test_cache_statistics(): void
    {
        // Create test settings
        SiteSetting::create(['key' => 'stat_test_1', 'value' => 'value1', 'type' => 'string', 'group' => 'general']);
        SiteSetting::create(['key' => 'stat_test_2', 'value' => 'value2', 'type' => 'string', 'group' => 'contact']);

        // Clear cache
        $this->settingsService->clearCache();

        // Get initial stats
        $stats = $this->settingsService->getCacheStats();
        $this->assertFalse($stats['is_warm']);
        $this->assertEquals(0, $stats['cached_settings']);

        // Warm cache
        $this->settingsService->warmCache();

        // Get updated stats
        $stats = $this->settingsService->getCacheStats();
        $this->assertTrue($stats['is_warm']);
        $this->assertEquals(2, $stats['cached_settings']);
        $this->assertGreaterThan(0, $stats['cached_groups']);
        $this->assertNotEmpty($stats['cache_driver']);
    }

    public function test_get_all_settings_cached(): void
    {
        // Create test settings
        SiteSetting::create(['key' => 'all_test_1', 'value' => 'value1', 'type' => 'string', 'group' => 'general']);
        SiteSetting::create(['key' => 'all_test_2', 'value' => 'true', 'type' => 'boolean', 'group' => 'advanced']);

        // Clear cache
        $this->settingsService->clearCache();

        // Get all settings (should cache them)
        $allSettings = $this->settingsService->getAll();

        // Verify results
        $this->assertArrayHasKey('all_test_1', $allSettings);
        $this->assertArrayHasKey('all_test_2', $allSettings);
        $this->assertEquals('value1', $allSettings['all_test_1']);
        $this->assertTrue($allSettings['all_test_2']); // Should be cast to boolean

        // Verify cache was created
        $this->assertTrue(Cache::has('site_settings_all'));
    }
}
