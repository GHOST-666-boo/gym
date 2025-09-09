<?php

namespace Tests\Unit;

use App\Services\ImageProtectionService;
use App\Services\SettingsService;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Mockery;

class ImageProtectionErrorHandlingTest extends TestCase
{

    protected ImageProtectionService $protectionService;
    protected $settingsServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->settingsServiceMock = Mockery::mock(SettingsService::class);
        $this->protectionService = new ImageProtectionService($this->settingsServiceMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_generates_noscript_fallback_styles()
    {
        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('image_protection_enabled', false)
            ->andReturn(true);

        $styles = $this->protectionService->getNoScriptFallbackStyles();

        $this->assertStringContainsString('<noscript>', $styles);
        $this->assertStringContainsString('user-select: none', $styles);
        $this->assertStringContainsString('pointer-events: none', $styles);
        $this->assertStringContainsString('</noscript>', $styles);
    }

    /** @test */
    public function it_returns_empty_noscript_when_protection_disabled()
    {
        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('image_protection_enabled', false)
            ->andReturn(false);

        $styles = $this->protectionService->getNoScriptFallbackStyles();

        $this->assertEmpty($styles);
    }

    /** @test */
    public function it_generates_enhanced_protection_script_with_error_handling()
    {
        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('image_protection_enabled', false)
            ->andReturn(true);

        $this->settingsServiceMock
            ->shouldReceive('get')
            ->andReturn(true); // For other protection settings

        $script = $this->protectionService->getEnhancedProtectionScript();

        $this->assertStringContainsString('error', $script);
        $this->assertStringContainsString('try', $script);
        $this->assertStringContainsString('catch', $script);
        $this->assertStringContainsString('checkBrowserSupport', $script);
    }

    /** @test */
    public function it_validates_image_tokens_correctly()
    {
        // Test invalid token
        $result = $this->protectionService->validateImageToken('invalid-token');
        
        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('error', $result);
    }

    /** @test */
    public function it_generates_secure_image_tokens()
    {
        $request = Request::create('/', 'GET');
        $request->headers->set('User-Agent', 'Test Browser');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        
        $this->app->instance('request', $request);

        $reflection = new \ReflectionClass($this->protectionService);
        $method = $reflection->getMethod('generateSecureImageToken');
        $method->setAccessible(true);

        $token = $method->invoke($this->protectionService, 'test-image.jpg', []);

        $this->assertIsString($token);
        $this->assertNotEmpty($token);
        
        // Token should be base64 encoded
        $decoded = base64_decode($token);
        $this->assertNotFalse($decoded);
    }

    /** @test */
    public function it_handles_token_validation_errors_gracefully()
    {
        Log::shouldReceive('error')->once();

        // Test with malformed token
        $result = $this->protectionService->validateImageToken('malformed.token.data');

        $this->assertFalse($result['valid']);
        $this->assertEquals('Token validation error', $result['error']);
    }

    /** @test */
    public function it_detects_javascript_disabled_scenarios()
    {
        $request = Request::create('/', 'GET');
        $request->headers->set('User-Agent', 'Lynx/2.8.9');
        
        $this->app->instance('request', $request);

        $result = $this->protectionService->detectJavaScriptDisabled();

        $this->assertTrue($result);
    }

    /** @test */
    public function it_generates_protection_status_report()
    {
        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('image_protection_enabled', false)
            ->andReturn(true);

        $this->settingsServiceMock
            ->shouldReceive('get')
            ->andReturn(true); // For other settings

        $status = $this->protectionService->getProtectionStatus();

        $this->assertIsArray($status);
        $this->assertArrayHasKey('enabled', $status);
        $this->assertArrayHasKey('features', $status);
        $this->assertArrayHasKey('fallbacks', $status);
        $this->assertArrayHasKey('browser_compatibility', $status);
    }

    /** @test */
    public function it_generates_obfuscated_urls_safely()
    {
        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('image_protection_enabled', false)
            ->andReturn(true);

        $request = Request::create('/', 'GET');
        $request->headers->set('User-Agent', 'Test Browser');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        
        $this->app->instance('request', $request);

        Log::shouldReceive('warning')->never();

        $originalUrl = 'https://example.com/storage/products/test-image.jpg';
        $result = $this->protectionService->generateObfuscatedImageUrl($originalUrl);

        // Should either return obfuscated URL or original URL (if extraction fails)
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    /** @test */
    public function it_handles_url_obfuscation_errors()
    {
        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('image_protection_enabled', false)
            ->andReturn(true);

        Log::shouldReceive('warning')->once();

        // Test with invalid URL that will cause extraction to fail
        $result = $this->protectionService->generateObfuscatedImageUrl('invalid-url');

        // Should return original URL on error
        $this->assertEquals('invalid-url', $result);
    }

    /** @test */
    public function it_provides_protection_recommendations()
    {
        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('image_protection_enabled', false)
            ->andReturn(true);

        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('right_click_protection', true)
            ->andReturn(false);

        $this->settingsServiceMock
            ->shouldReceive('get')
            ->andReturn(true); // For other settings

        $reflection = new \ReflectionClass($this->protectionService);
        $method = $reflection->getMethod('getProtectionRecommendations');
        $method->setAccessible(true);

        $recommendations = $method->invoke($this->protectionService);

        $this->assertIsArray($recommendations);
        $this->assertNotEmpty($recommendations);
        $this->assertStringContainsString('right-click protection', $recommendations[0]);
    }

    /** @test */
    public function it_logs_protection_attempts()
    {
        Log::shouldReceive('info')->once()->with(
            'Image protection triggered',
            Mockery::type('array')
        );

        $request = Request::create('/', 'GET');
        $request->headers->set('User-Agent', 'Test Browser');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        
        $this->app->instance('request', $request);

        $this->protectionService->logProtectionAttempt('test_protection', [
            'additional_context' => 'test'
        ]);
    }
}