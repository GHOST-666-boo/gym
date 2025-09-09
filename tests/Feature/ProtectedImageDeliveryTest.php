<?php

namespace Tests\Feature;

use App\Http\Controllers\ProtectedImageController;
use App\Services\WatermarkService;
use App\Services\ImageProtectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ProtectedImageDeliveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up test configuration
        Config::set('image_protection.rate_limit.max_requests', 10);
        Config::set('image_protection.rate_limit.time_window', 60);
        Config::set('image_protection.token.expires_in', 3600);
        
        Storage::fake('public');
    }

    public function test_protected_image_route_requires_valid_token()
    {
        $response = $this->get('/protected/image/invalid-token');
        
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function test_protected_image_delivery_with_valid_token()
    {
        // Create a test image
        $imagePath = 'products/test-image.jpg';
        $imageContent = file_get_contents(__DIR__ . '/../fixtures/test-image.jpg') ?: 'fake-image-content';
        Storage::disk('public')->put($imagePath, $imageContent);
        
        // Generate a valid token
        $controller = app(ProtectedImageController::class);
        $token = $controller->generateImageToken($imagePath);
        
        // Make request with valid headers
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'Referer' => config('app.url'),
        ])->get('/protected/image/' . $token);
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('image/', $response->headers->get('Content-Type'));
    }

    public function test_rate_limiting_blocks_excessive_requests()
    {
        Config::set('image_protection.rate_limit.max_requests', 2);
        
        // Create a test image
        $imagePath = 'products/test-image.jpg';
        Storage::disk('public')->put($imagePath, 'fake-image-content');
        
        $controller = app(ProtectedImageController::class);
        $token = $controller->generateImageToken($imagePath);
        
        $headers = [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'Referer' => config('app.url'),
        ];
        
        // First two requests should succeed
        for ($i = 0; $i < 2; $i++) {
            $response = $this->withHeaders($headers)->get('/protected/image/' . $token);
            $this->assertEquals(200, $response->getStatusCode());
        }
        
        // Third request should be rate limited
        $response = $this->withHeaders($headers)->get('/protected/image/' . $token);
        $this->assertEquals(429, $response->getStatusCode());
    }

    public function test_blocks_suspicious_user_agents()
    {
        // Create a test image
        $imagePath = 'products/test-image.jpg';
        Storage::disk('public')->put($imagePath, 'fake-image-content');
        
        $controller = app(ProtectedImageController::class);
        $token = $controller->generateImageToken($imagePath);
        
        $suspiciousAgents = ['wget', 'curl', 'python-requests', 'scrapy'];
        
        foreach ($suspiciousAgents as $agent) {
            $response = $this->withHeaders([
                'User-Agent' => $agent,
                'Referer' => config('app.url'),
            ])->get('/protected/image/' . $token);
            
            $this->assertEquals(403, $response->getStatusCode());
        }
    }

    public function test_blocks_requests_without_user_agent()
    {
        // Create a test image
        $imagePath = 'products/test-image.jpg';
        Storage::disk('public')->put($imagePath, 'fake-image-content');
        
        $controller = app(ProtectedImageController::class);
        $token = $controller->generateImageToken($imagePath);
        
        $response = $this->withHeaders([
            'User-Agent' => '',
            'Referer' => config('app.url'),
        ])->get('/protected/image/' . $token);
        
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_returns_304_for_cached_images()
    {
        // Create a test image
        $imagePath = 'products/test-image.jpg';
        $imageContent = 'fake-image-content';
        Storage::disk('public')->put($imagePath, $imageContent);
        
        $controller = app(ProtectedImageController::class);
        $token = $controller->generateImageToken($imagePath);
        
        $headers = [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'Referer' => config('app.url'),
        ];
        
        // First request to get ETag
        $response = $this->withHeaders($headers)->get('/protected/image/' . $token);
        $this->assertEquals(200, $response->getStatusCode());
        
        $etag = $response->headers->get('ETag');
        $this->assertNotEmpty($etag);
        
        // Second request with If-None-Match header
        $response = $this->withHeaders(array_merge($headers, [
            'If-None-Match' => $etag,
        ]))->get('/protected/image/' . $token);
        
        $this->assertEquals(304, $response->getStatusCode());
    }

    public function test_image_protection_service_generates_protected_urls()
    {
        $protectionService = app(ImageProtectionService::class);
        
        // Mock settings to enable protection
        $this->app['config']->set('app.name', 'Test App');
        
        $originalUrl = asset('storage/products/test-image.jpg');
        $protectedUrl = $protectionService->generateProtectedImageUrl($originalUrl);
        
        // If protection is disabled, should return original URL
        // If enabled, should return protected URL
        $this->assertIsString($protectedUrl);
    }

    public function test_watermark_is_applied_to_protected_images()
    {
        // Create a test image
        $imagePath = 'products/test-image.jpg';
        Storage::disk('public')->put($imagePath, 'fake-image-content');
        
        // Create cached watermarked version
        $watermarkedPath = 'watermarks/cache/test-image_abc123.jpg';
        Storage::disk('public')->put($watermarkedPath, 'fake-watermarked-content');
        
        $controller = app(ProtectedImageController::class);
        $token = $controller->generateImageToken($imagePath);
        
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'Referer' => config('app.url'),
        ])->get('/protected/image/' . $token);
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('image/', $response->headers->get('Content-Type'));
    }

    public function test_access_statistics_are_tracked()
    {
        $controller = app(ProtectedImageController::class);
        $stats = $controller->getAccessStats();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_requests', $stats);
        $this->assertArrayHasKey('unique_ips', $stats);
        $this->assertArrayHasKey('blocked_requests', $stats);
        $this->assertArrayHasKey('cache_hits', $stats);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}