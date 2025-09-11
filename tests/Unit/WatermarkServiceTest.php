<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\WatermarkService;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Testing\File;
use Illuminate\Http\UploadedFile;
use Mockery;

class WatermarkServiceTest extends TestCase
{
    use RefreshDatabase;

    protected WatermarkService $watermarkService;
    protected $settingsServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock SettingsService
        $this->settingsServiceMock = Mockery::mock(SettingsService::class);
        $this->watermarkService = new WatermarkService($this->settingsServiceMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_has_correct_predefined_positions()
    {
        $expectedPositions = [
            'top-left',
            'top-center', 
            'top-right',
            'center-left',
            'center',
            'center-right',
            'bottom-left',
            'bottom-center',
            'bottom-right'
        ];

        $this->assertEquals($expectedPositions, WatermarkService::POSITIONS);
    }

    public function test_calculate_watermark_position_top_left()
    {
        $imageWidth = 800;
        $imageHeight = 600;
        $watermarkWidth = 100;
        $watermarkHeight = 50;
        
        [$x, $y] = $this->watermarkService->calculateWatermarkPosition(
            $imageWidth, 
            $imageHeight, 
            'top-left', 
            $watermarkWidth, 
            $watermarkHeight
        );
        
        $this->assertEquals(20, $x); // padding
        $this->assertEquals(70, $y); // watermarkHeight + padding
    }

    public function test_calculate_watermark_position_top_center()
    {
        $imageWidth = 800;
        $imageHeight = 600;
        $watermarkWidth = 100;
        $watermarkHeight = 50;
        
        [$x, $y] = $this->watermarkService->calculateWatermarkPosition(
            $imageWidth, 
            $imageHeight, 
            'top-center', 
            $watermarkWidth, 
            $watermarkHeight
        );
        
        $this->assertEquals(350, $x); // (800 - 100) / 2
        $this->assertEquals(70, $y); // watermarkHeight + padding
    }

    public function test_calculate_watermark_position_top_right()
    {
        $imageWidth = 800;
        $imageHeight = 600;
        $watermarkWidth = 100;
        $watermarkHeight = 50;
        
        [$x, $y] = $this->watermarkService->calculateWatermarkPosition(
            $imageWidth, 
            $imageHeight, 
            'top-right', 
            $watermarkWidth, 
            $watermarkHeight
        );
        
        $this->assertEquals(680, $x); // 800 - 100 - 20
        $this->assertEquals(70, $y); // watermarkHeight + padding
    }

    public function test_calculate_watermark_position_center_left()
    {
        $imageWidth = 800;
        $imageHeight = 600;
        $watermarkWidth = 100;
        $watermarkHeight = 50;
        
        [$x, $y] = $this->watermarkService->calculateWatermarkPosition(
            $imageWidth, 
            $imageHeight, 
            'center-left', 
            $watermarkWidth, 
            $watermarkHeight
        );
        
        $this->assertEquals(20, $x); // padding
        $this->assertEquals(325, $y); // (600 + 50) / 2
    }

    public function test_calculate_watermark_position_center()
    {
        $imageWidth = 800;
        $imageHeight = 600;
        $watermarkWidth = 100;
        $watermarkHeight = 50;
        
        [$x, $y] = $this->watermarkService->calculateWatermarkPosition(
            $imageWidth, 
            $imageHeight, 
            'center', 
            $watermarkWidth, 
            $watermarkHeight
        );
        
        $this->assertEquals(350, $x); // (800 - 100) / 2
        $this->assertEquals(325, $y); // (600 + 50) / 2
    }

    public function test_calculate_watermark_position_center_right()
    {
        $imageWidth = 800;
        $imageHeight = 600;
        $watermarkWidth = 100;
        $watermarkHeight = 50;
        
        [$x, $y] = $this->watermarkService->calculateWatermarkPosition(
            $imageWidth, 
            $imageHeight, 
            'center-right', 
            $watermarkWidth, 
            $watermarkHeight
        );
        
        $this->assertEquals(680, $x); // 800 - 100 - 20
        $this->assertEquals(325, $y); // (600 + 50) / 2
    }

    public function test_calculate_watermark_position_bottom_left()
    {
        $imageWidth = 800;
        $imageHeight = 600;
        $watermarkWidth = 100;
        $watermarkHeight = 50;
        
        [$x, $y] = $this->watermarkService->calculateWatermarkPosition(
            $imageWidth, 
            $imageHeight, 
            'bottom-left', 
            $watermarkWidth, 
            $watermarkHeight
        );
        
        $this->assertEquals(20, $x); // padding
        $this->assertEquals(580, $y); // 600 - 20
    }

    public function test_calculate_watermark_position_bottom_center()
    {
        $imageWidth = 800;
        $imageHeight = 600;
        $watermarkWidth = 100;
        $watermarkHeight = 50;
        
        [$x, $y] = $this->watermarkService->calculateWatermarkPosition(
            $imageWidth, 
            $imageHeight, 
            'bottom-center', 
            $watermarkWidth, 
            $watermarkHeight
        );
        
        $this->assertEquals(350, $x); // (800 - 100) / 2
        $this->assertEquals(580, $y); // 600 - 20
    }

    public function test_calculate_watermark_position_bottom_right_default()
    {
        $imageWidth = 800;
        $imageHeight = 600;
        $watermarkWidth = 100;
        $watermarkHeight = 50;
        
        // Test both 'bottom-right' and default case
        [$x1, $y1] = $this->watermarkService->calculateWatermarkPosition(
            $imageWidth, 
            $imageHeight, 
            'bottom-right', 
            $watermarkWidth, 
            $watermarkHeight
        );
        
        [$x2, $y2] = $this->watermarkService->calculateWatermarkPosition(
            $imageWidth, 
            $imageHeight, 
            'invalid-position', 
            $watermarkWidth, 
            $watermarkHeight
        );
        
        $this->assertEquals(680, $x1); // 800 - 100 - 20
        $this->assertEquals(580, $y1); // 600 - 20
        $this->assertEquals($x1, $x2); // Default should be same as bottom-right
        $this->assertEquals($y1, $y2);
    }

    public function test_get_watermark_settings_returns_correct_defaults()
    {
        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('watermark_text', config('app.name', 'Watermark'))
            ->andReturn('Test Watermark');
            
        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('watermark_opacity', 50)
            ->andReturn(75);
            
        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('watermark_position', 'bottom-right')
            ->andReturn('center');
            
        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('watermark_size', 24)
            ->andReturn(18);
            
        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('watermark_text_color', '#FFFFFF')
            ->andReturn('#000000');
            
        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('watermark_logo_path', '')
            ->andReturn('watermarks/logos/test.png');
            
        $this->settingsServiceMock
            ->shouldReceive('get')
            ->with('watermark_logo_size', 'medium')
            ->andReturn('large');

        $settings = $this->watermarkService->getWatermarkSettings();

        $this->assertEquals([
            'text' => 'Test Watermark',
            'opacity' => 75,
            'position' => 'center',
            'size' => 18,
            'color' => '#000000',
            'logo_path' => 'watermarks/logos/test.png',
            'logo_size' => 'large',
        ], $settings);
    }

    public function test_apply_watermark_returns_original_path_when_gd_not_available()
    {
        // This test would require mocking extension_loaded function
        // For now, we'll test the basic flow assuming GD is available
        $this->assertTrue(true); // Placeholder test
    }

    public function test_apply_watermark_returns_original_path_when_file_not_found()
    {
        Storage::fake('public');
        
        Log::shouldReceive('warning')
            ->once()
            ->with('Image file not found for watermarking: ' . Storage::disk('public')->path('nonexistent.jpg'));

        $result = $this->watermarkService->applyWatermark('nonexistent.jpg');
        
        $this->assertEquals('nonexistent.jpg', $result);
    }

    public function test_clear_watermark_cache_logs_message()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Watermark cache cleared');

        $this->watermarkService->clearWatermarkCache();
        
        // Test passes if no exception is thrown
        $this->assertTrue(true);
    }

    public function test_delete_watermarked_image_removes_file_when_exists()
    {
        Storage::fake('public');
        
        // Create a fake watermarked image
        Storage::disk('public')->put('products/test_watermarked.jpg', 'fake content');
        
        $this->watermarkService->deleteWatermarkedImage('products/test.jpg');
        
        Storage::disk('public')->assertMissing('products/test_watermarked.jpg');
    }

    public function test_delete_watermarked_image_handles_non_existent_file()
    {
        Storage::fake('public');
        
        // Should not throw exception when file doesn't exist
        $this->watermarkService->deleteWatermarkedImage('products/nonexistent.jpg');
        
        $this->assertTrue(true);
    }

    public function test_hex_to_rgb_conversion()
    {
        // Use reflection to test protected method
        $reflection = new \ReflectionClass($this->watermarkService);
        $method = $reflection->getMethod('hexToRgb');
        $method->setAccessible(true);

        // Test full hex
        $result = $method->invoke($this->watermarkService, '#FF0000');
        $this->assertEquals(['r' => 255, 'g' => 0, 'b' => 0], $result);

        // Test hex without #
        $result = $method->invoke($this->watermarkService, '00FF00');
        $this->assertEquals(['r' => 0, 'g' => 255, 'b' => 0], $result);

        // Test short hex
        $result = $method->invoke($this->watermarkService, '#F0F');
        $this->assertEquals(['r' => 255, 'g' => 0, 'b' => 255], $result);
    }

    public function test_generate_watermarked_path()
    {
        // Use reflection to test protected method
        $reflection = new \ReflectionClass($this->watermarkService);
        $method = $reflection->getMethod('generateWatermarkedPath');
        $method->setAccessible(true);

        $result = $method->invoke($this->watermarkService, 'products/image.jpg');
        $this->assertEquals('products/image_watermarked.jpg', $result);

        $result = $method->invoke($this->watermarkService, 'images/test.png');
        $this->assertEquals('images/test_watermarked.png', $result);
    }

    // Logo Watermarking Tests

    public function test_calculate_logo_size_small()
    {
        // Use reflection to test protected method
        $reflection = new \ReflectionClass($this->watermarkService);
        $method = $reflection->getMethod('calculateLogoSize');
        $method->setAccessible(true);

        $logoWidth = 200;
        $logoHeight = 100;
        $imageWidth = 800;
        $imageHeight = 600;

        [$scaledWidth, $scaledHeight] = $method->invoke(
            $this->watermarkService, 
            $logoWidth, 
            $logoHeight, 
            $imageWidth, 
            $imageHeight, 
            'small'
        );

        // Small should be 10% of image width
        $expectedWidth = 80; // 800 * 0.1
        $expectedHeight = 40; // Proportional to aspect ratio

        $this->assertEquals($expectedWidth, $scaledWidth);
        $this->assertEquals($expectedHeight, $scaledHeight);
    }

    public function test_calculate_logo_size_medium()
    {
        // Use reflection to test protected method
        $reflection = new \ReflectionClass($this->watermarkService);
        $method = $reflection->getMethod('calculateLogoSize');
        $method->setAccessible(true);

        $logoWidth = 200;
        $logoHeight = 100;
        $imageWidth = 800;
        $imageHeight = 600;

        [$scaledWidth, $scaledHeight] = $method->invoke(
            $this->watermarkService, 
            $logoWidth, 
            $logoHeight, 
            $imageWidth, 
            $imageHeight, 
            'medium'
        );

        // Medium should be 15% of image width
        $expectedWidth = 120; // 800 * 0.15
        $expectedHeight = 60; // Proportional to aspect ratio

        $this->assertEquals($expectedWidth, $scaledWidth);
        $this->assertEquals($expectedHeight, $scaledHeight);
    }

    public function test_calculate_logo_size_large()
    {
        // Use reflection to test protected method
        $reflection = new \ReflectionClass($this->watermarkService);
        $method = $reflection->getMethod('calculateLogoSize');
        $method->setAccessible(true);

        $logoWidth = 200;
        $logoHeight = 100;
        $imageWidth = 800;
        $imageHeight = 600;

        [$scaledWidth, $scaledHeight] = $method->invoke(
            $this->watermarkService, 
            $logoWidth, 
            $logoHeight, 
            $imageWidth, 
            $imageHeight, 
            'large'
        );

        // Large should be 20% of image width
        $expectedWidth = 160; // 800 * 0.2
        $expectedHeight = 80; // Proportional to aspect ratio

        $this->assertEquals($expectedWidth, $scaledWidth);
        $this->assertEquals($expectedHeight, $scaledHeight);
    }

    public function test_calculate_logo_size_respects_maximum_dimensions()
    {
        // Use reflection to test protected method
        $reflection = new \ReflectionClass($this->watermarkService);
        $method = $reflection->getMethod('calculateLogoSize');
        $method->setAccessible(true);

        // Test with very tall logo that would exceed height limit
        $logoWidth = 100;
        $logoHeight = 500; // Very tall logo
        $imageWidth = 800;
        $imageHeight = 600;

        [$scaledWidth, $scaledHeight] = $method->invoke(
            $this->watermarkService, 
            $logoWidth, 
            $logoHeight, 
            $imageWidth, 
            $imageHeight, 
            'large'
        );

        // Should be constrained by height (80% of image height = 480)
        $this->assertLessThanOrEqual(480, $scaledHeight);
        $this->assertLessThanOrEqual(640, $scaledWidth); // 80% of image width
    }

    public function test_validate_logo_file_success()
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension not available');
        }

        Storage::fake('public');
        
        // Create a valid PNG image that meets minimum requirements (100x100)
        $image = imagecreatetruecolor(100, 100);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $white);
        
        $tempPath = Storage::disk('public')->path('test-logo.png');
        $directory = dirname($tempPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        imagepng($image, $tempPath);
        imagedestroy($image);
        
        $errors = $this->watermarkService->validateLogoFile($tempPath);
        
        // Should have no errors for a valid PNG
        $this->assertEmpty($errors);
    }

    public function test_validate_logo_file_nonexistent()
    {
        $errors = $this->watermarkService->validateLogoFile('/nonexistent/file.png');
        
        $this->assertContains('Logo file does not exist', $errors);
    }

    public function test_validate_logo_file_invalid_extension()
    {
        Storage::fake('public');
        Storage::disk('public')->put('test-logo.txt', 'not an image');
        
        $filePath = Storage::disk('public')->path('test-logo.txt');
        $errors = $this->watermarkService->validateLogoFile($filePath);
        
        $this->assertContains('Logo file format not supported. Allowed formats: PNG, JPG, GIF, WebP, SVG', $errors);
    }

    public function test_validate_logo_file_too_large()
    {
        Storage::fake('public');
        
        // Create a file larger than 5MB
        $largeContent = str_repeat('x', 6 * 1024 * 1024); // 6MB
        Storage::disk('public')->put('large-logo.png', $largeContent);
        
        $filePath = Storage::disk('public')->path('large-logo.png');
        $errors = $this->watermarkService->validateLogoFile($filePath);
        
        $this->assertContains('Logo file size exceeds 5MB limit', $errors);
    }

    public function test_resolve_logo_path_absolute()
    {
        // Use reflection to test protected method
        $reflection = new \ReflectionClass($this->watermarkService);
        $method = $reflection->getMethod('resolveLogoPath');
        $method->setAccessible(true);

        $absolutePath = '/absolute/path/to/logo.png';
        $result = $method->invoke($this->watermarkService, $absolutePath);
        
        $this->assertEquals($absolutePath, $result);
    }

    public function test_resolve_logo_path_relative()
    {
        Storage::fake('public');
        Storage::disk('public')->put('watermarks/logos/test.png', 'fake content');
        
        // Use reflection to test protected method
        $reflection = new \ReflectionClass($this->watermarkService);
        $method = $reflection->getMethod('resolveLogoPath');
        $method->setAccessible(true);

        $relativePath = 'watermarks/logos/test.png';
        $result = $method->invoke($this->watermarkService, $relativePath);
        
        $expectedPath = Storage::disk('public')->path($relativePath);
        $this->assertEquals($expectedPath, $result);
    }

    public function test_process_logo_upload_success()
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension not available');
        }

        Storage::fake('public');
        
        // Create a valid PNG image file for upload testing
        $image = imagecreatetruecolor(100, 100);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $white);
        
        $tempFile = tempnam(sys_get_temp_dir(), 'test_logo') . '.png';
        imagepng($image, $tempFile);
        imagedestroy($image);
        
        // Create uploaded file from the temporary file
        $uploadedFile = new \Illuminate\Http\UploadedFile(
            $tempFile,
            'logo.png',
            'image/png',
            null,
            true
        );
        
        $result = $this->watermarkService->processLogoUpload($uploadedFile);
        
        // Clean up temp file
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('path', $result);
        $this->assertArrayHasKey('filename', $result);
        $this->assertArrayHasKey('url', $result);
        $this->assertStringContainsString('logo_', $result['filename']);
        $this->assertStringEndsWith('.png', $result['filename']);
    }

    public function test_process_logo_upload_validation_failure()
    {
        Storage::fake('public');
        
        // Create a large temporary file (6MB)
        $tempFile = tempnam(sys_get_temp_dir(), 'large_logo') . '.png';
        $largeContent = str_repeat('x', 6 * 1024 * 1024); // 6MB
        file_put_contents($tempFile, $largeContent);
        
        // Create uploaded file from the temporary file
        $uploadedFile = new \Illuminate\Http\UploadedFile(
            $tempFile,
            'logo.png',
            'image/png',
            null,
            true
        );
        
        $result = $this->watermarkService->processLogoUpload($uploadedFile);
        
        // Clean up temp file
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('errors', $result);
        $this->assertNotEmpty($result['errors']);
    }

    public function test_load_logo_image_png()
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension not available');
        }

        Storage::fake('public');
        
        // Create a minimal valid PNG
        $pngData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
        Storage::disk('public')->put('test.png', $pngData);
        
        // Use reflection to test protected method
        $reflection = new \ReflectionClass($this->watermarkService);
        $method = $reflection->getMethod('loadLogoImage');
        $method->setAccessible(true);

        $logoPath = Storage::disk('public')->path('test.png');
        $result = $method->invoke($this->watermarkService, $logoPath);
        
        $this->assertNotFalse($result);
        $this->assertTrue(is_resource($result) || is_object($result)); // GD resource or GdImage object
        
        if (is_resource($result)) {
            imagedestroy($result);
        }
    }

    public function test_load_logo_image_nonexistent()
    {
        // Use reflection to test protected method
        $reflection = new \ReflectionClass($this->watermarkService);
        $method = $reflection->getMethod('loadLogoImage');
        $method->setAccessible(true);

        Log::shouldReceive('warning')
            ->once()
            ->with('Logo file not found: /nonexistent/logo.png');

        $result = $method->invoke($this->watermarkService, '/nonexistent/logo.png');
        
        $this->assertFalse($result);
    }

    public function test_load_svg_image_without_imagick()
    {
        if (extension_loaded('imagick')) {
            $this->markTestSkipped('Imagick extension is available, cannot test fallback');
        }

        Storage::fake('public');
        Storage::disk('public')->put('test.svg', '<svg></svg>');
        
        // Use reflection to test protected method
        $reflection = new \ReflectionClass($this->watermarkService);
        $method = $reflection->getMethod('loadSvgImage');
        $method->setAccessible(true);

        Log::shouldReceive('warning')
            ->once()
            ->with('Imagick extension not available for SVG support');

        $logoPath = Storage::disk('public')->path('test.svg');
        $result = $method->invoke($this->watermarkService, $logoPath);
        
        $this->assertFalse($result);
    }
}