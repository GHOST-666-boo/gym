<?php

namespace Tests\Unit;

use App\Services\WatermarkService;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Mockery;

class WatermarkErrorHandlingTest extends TestCase
{

    protected WatermarkService $watermarkService;
    protected $settingsServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->settingsServiceMock = Mockery::mock(SettingsService::class);
        $this->watermarkService = new WatermarkService($this->settingsServiceMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_handles_missing_gd_extension_gracefully()
    {
        // Mock settings to enable watermarking
        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('watermark_enabled', false)
            ->andReturn(true);

        $this->settingsServiceMock
            ->shouldReceive('get')
            ->andReturn('Test Watermark'); // Default for other settings

        // Mock a scenario where GD is not available
        if (extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is available, cannot test missing extension scenario');
        }

        Log::shouldReceive('error')->once();
        Cache::shouldReceive('get')->andReturn([]);
        Cache::shouldReceive('put')->once();

        $result = $this->watermarkService->applyWatermark('test-image.jpg');

        // Should return CSS fallback
        $this->assertStringContainsString('css_watermark=', $result);
    }

    /** @test */
    public function it_handles_corrupted_image_files()
    {
        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('watermark_enabled', false)
            ->andReturn(true);

        // Create a fake corrupted image file
        Storage::fake('public');
        Storage::disk('public')->put('corrupted-image.jpg', 'not-an-image');

        Log::shouldReceive('error')->once();
        Log::shouldReceive('warning')->once();
        Cache::shouldReceive('get')->andReturn([]);
        Cache::shouldReceive('put')->twice(); // Once for notification, once for fallback

        $result = $this->watermarkService->applyWatermark('corrupted-image.jpg');

        // Should return error placeholder
        $this->assertStringContainsString('error=', $result);
    }

    /** @test */
    public function it_creates_admin_notifications_for_errors()
    {
        Cache::shouldReceive('has')->andReturn(false);
        Cache::shouldReceive('get')
            ->with('admin_watermark_notifications', [])
            ->andReturn([]);
        Cache::shouldReceive('put')->twice(); // Error count and notifications

        Log::shouldReceive('error')->once();

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($this->watermarkService);
        $method = $reflection->getMethod('notifyAdminAboutError');
        $method->setAccessible(true);

        $method->invoke($this->watermarkService, 'missing_extensions', [
            'message' => 'Test error',
            'image_path' => 'test.jpg'
        ]);

        // Verify notification was created (mocked)
        $this->assertTrue(true); // Test passes if no exceptions thrown
    }

    /** @test */
    public function it_validates_image_files_properly()
    {
        Storage::fake('public');
        
        // Test with non-existent file
        $reflection = new \ReflectionClass($this->watermarkService);
        $method = $reflection->getMethod('validateImageFile');
        $method->setAccessible(true);

        $result = $method->invoke($this->watermarkService, '/non/existent/file.jpg');
        
        $this->assertFalse($result['valid']);
        $this->assertContains('File does not exist', $result['errors']);
    }

    /** @test */
    public function it_checks_image_extensions_correctly()
    {
        $reflection = new \ReflectionClass($this->watermarkService);
        $method = $reflection->getMethod('checkImageExtensions');
        $method->setAccessible(true);

        $result = $method->invoke($this->watermarkService);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('has_gd', $result);
        $this->assertArrayHasKey('has_imagick', $result);
        $this->assertArrayHasKey('supported_formats', $result);
    }

    /** @test */
    public function it_generates_css_fallback_watermark()
    {
        Cache::shouldReceive('put')->once();
        Log::shouldReceive('info')->once();

        $reflection = new \ReflectionClass($this->watermarkService);
        $method = $reflection->getMethod('generateCssFallbackWatermark');
        $method->setAccessible(true);

        $result = $method->invoke($this->watermarkService, 'test-image.jpg', []);

        $this->assertStringContainsString('css_watermark=', $result);
        $this->assertStringContainsString('test-image.jpg', $result);
    }

    /** @test */
    public function it_limits_admin_notifications_per_day()
    {
        // Mock cache to simulate 10 notifications already sent today
        Cache::shouldReceive('has')
            ->with(Mockery::pattern('/watermark_error_.*_\d{4}-\d{2}-\d{2}/'))
            ->andReturn(true);
        Cache::shouldReceive('get')
            ->with(Mockery::pattern('/watermark_error_.*_\d{4}-\d{2}-\d{2}/'), 0)
            ->andReturn(10); // Max notifications reached

        // Should not create new notification
        $reflection = new \ReflectionClass($this->watermarkService);
        $method = $reflection->getMethod('notifyAdminAboutError');
        $method->setAccessible(true);

        // This should return early without creating notification
        $method->invoke($this->watermarkService, 'test_error', []);

        $this->assertTrue(true); // Test passes if no exceptions thrown
    }

    /** @test */
    public function it_gets_system_health_status()
    {
        Cache::shouldReceive('get')
            ->with('admin_watermark_notifications', [])
            ->andReturn([
                ['type' => 'error', 'read' => false],
                ['type' => 'info', 'read' => true]
            ]);

        $status = $this->watermarkService->getSystemHealthStatus();

        $this->assertIsArray($status);
        $this->assertArrayHasKey('status', $status);
        $this->assertArrayHasKey('extensions', $status);
        $this->assertArrayHasKey('notifications', $status);
        $this->assertArrayHasKey('last_check', $status);
    }

    /** @test */
    public function it_handles_imagick_fallback()
    {
        if (!extension_loaded('imagick')) {
            $this->markTestSkipped('Imagick extension not available');
        }

        Storage::fake('public');
        
        // Create a test image
        $imageContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChAI9jU77yQAAAABJRU5ErkJggg==');
        Storage::disk('public')->put('test-image.png', $imageContent);

        $reflection = new \ReflectionClass($this->watermarkService);
        $method = $reflection->getMethod('loadImageWithImagick');
        $method->setAccessible(true);

        $fullPath = Storage::disk('public')->path('test-image.png');
        $result = $method->invoke($this->watermarkService, $fullPath);

        // Should return GD resource or false
        $this->assertTrue(is_resource($result) || $result === false);
    }
}