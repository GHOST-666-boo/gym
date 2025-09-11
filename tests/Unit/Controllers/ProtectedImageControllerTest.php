<?php

namespace Tests\Unit\Controllers;

use App\Http\Controllers\ProtectedImageController;
use App\Services\WatermarkService;
use App\Services\ImageProtectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
use Mockery;

class ProtectedImageControllerTest extends TestCase
{
    protected ProtectedImageController $controller;
    protected $watermarkService;
    protected $protectionService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->watermarkService = Mockery::mock(WatermarkService::class);
        $this->protectionService = Mockery::mock(ImageProtectionService::class);
        
        $this->controller = new ProtectedImageController(
            $this->watermarkService,
            $this->protectionService
        );
    }

    public function test_generates_valid_image_token()
    {
        $imagePath = 'products/test-image.jpg';
        $token = $this->controller->generateImageToken($imagePath);
        
        $this->assertNotEmpty($token);
        $this->assertIsString($token);
        
        // Token should be base64 encoded
        $decoded = base64_decode($token);
        $this->assertNotFalse($decoded);
    }

    public function test_decodes_valid_image_token()
    {
        $imagePath = 'products/test-image.jpg';
        $token = $this->controller->generateImageToken($imagePath);
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('decodeImageToken');
        $method->setAccessible(true);
        
        $decodedPath = $method->invoke($this->controller, $token);
        
        $this->assertEquals($imagePath, $decodedPath);
    }

    public function test_rejects_invalid_token()
    {
        $invalidToken = base64_encode('invalid.token.data');
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('decodeImageToken');
        $method->setAccessible(true);
        
        $decodedPath = $method->invoke($this->controller, $invalidToken);
        
        $this->assertNull($decodedPath);
    }

    public function test_rejects_expired_token()
    {
        // Create a token that's already expired
        $imagePath = 'products/test-image.jpg';
        $data = [
            'path' => $imagePath,
            'timestamp' => time() - 3600,
            'expires' => time() - 1800, // Expired 30 minutes ago
        ];
        
        $payload = base64_encode(json_encode($data));
        $signature = hash_hmac('sha256', $payload, config('app.key'));
        $token = base64_encode($payload . '.' . $signature);
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('decodeImageToken');
        $method->setAccessible(true);
        
        $decodedPath = $method->invoke($this->controller, $token);
        
        $this->assertNull($decodedPath);
    }

    public function test_generates_protected_url()
    {
        $imagePath = 'products/test-image.jpg';
        $url = $this->controller->generateProtectedUrl($imagePath);
        
        $this->assertStringContainsString('/protected/image/', $url);
        // The token is in the URL path, not as a query parameter
        $this->assertNotEquals('http://localhost/protected/image/', $url);
    }

    public function test_serve_returns_404_for_invalid_token()
    {
        $request = Request::create('/protected/image/invalid-token');
        $response = $this->controller->serve($request, 'invalid-token');
        
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('Not Found', $response->getContent());
    }

    public function test_serve_returns_404_for_nonexistent_image()
    {
        Storage::fake('public');
        
        $imagePath = 'products/nonexistent.jpg';
        $token = $this->controller->generateImageToken($imagePath);
        
        $request = Request::create('/protected/image/' . $token);
        $response = $this->controller->serve($request, $token);
        
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('Not Found', $response->getContent());
    }

    public function test_serve_applies_watermark_when_enabled()
    {
        Storage::fake('public');
        
        // Create a test image file
        $imagePath = 'products/test-image.jpg';
        Storage::disk('public')->put($imagePath, 'fake-image-content');
        
        $token = $this->controller->generateImageToken($imagePath);
        
        // Mock watermark service to return watermarked path
        $watermarkedPath = 'watermarks/cache/test-image_abc123.jpg';
        Storage::disk('public')->put($watermarkedPath, 'fake-watermarked-content');
        
        $this->watermarkService
            ->shouldReceive('applyWatermark')
            ->with($imagePath)
            ->once()
            ->andReturn($watermarkedPath);
        
        $request = Request::create('/protected/image/' . $token);
        $response = $this->controller->serve($request, $token);
        
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_is_not_modified_returns_true_for_matching_etag()
    {
        Storage::fake('public');
        
        $imagePath = 'products/test-image.jpg';
        $content = 'fake-image-content';
        Storage::disk('public')->put($imagePath, $content);
        
        $fullPath = Storage::disk('public')->path($imagePath);
        $etag = md5($content);
        
        $request = Request::create('/protected/image/test');
        $request->headers->set('If-None-Match', '"' . $etag . '"');
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('isNotModified');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->controller, $request, $fullPath);
        
        $this->assertTrue($result);
    }

    public function test_get_access_stats_returns_array()
    {
        $stats = $this->controller->getAccessStats();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_requests', $stats);
        $this->assertArrayHasKey('unique_ips', $stats);
        $this->assertArrayHasKey('blocked_requests', $stats);
        $this->assertArrayHasKey('cache_hits', $stats);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}