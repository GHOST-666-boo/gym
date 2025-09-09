<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\WatermarkService;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

class LogoWatermarkingTest extends TestCase
{
    use RefreshDatabase;

    protected WatermarkService $watermarkService;
    protected SettingsService $settingsService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->settingsService = app(SettingsService::class);
        $this->watermarkService = app(WatermarkService::class);
    }

    public function test_logo_watermarking_integration()
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension not available');
        }

        Storage::fake('public');
        
        // Create a test product image
        $productImage = imagecreatetruecolor(400, 300);
        $blue = imagecolorallocate($productImage, 0, 100, 200);
        imagefill($productImage, 0, 0, $blue);
        
        $productImagePath = 'products/test-product.jpg';
        $fullProductPath = Storage::disk('public')->path($productImagePath);
        $directory = dirname($fullProductPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        imagejpeg($productImage, $fullProductPath, 90);
        imagedestroy($productImage);
        
        // Create a test logo
        $logo = imagecreatetruecolor(100, 50);
        $white = imagecolorallocate($logo, 255, 255, 255);
        $black = imagecolorallocate($logo, 0, 0, 0);
        imagefill($logo, 0, 0, $white);
        imagestring($logo, 5, 10, 15, 'LOGO', $black);
        
        $logoPath = 'watermarks/logos/test-logo.png';
        $fullLogoPath = Storage::disk('public')->path($logoPath);
        $logoDirectory = dirname($fullLogoPath);
        if (!is_dir($logoDirectory)) {
            mkdir($logoDirectory, 0755, true);
        }
        imagepng($logo, $fullLogoPath);
        imagedestroy($logo);
        
        // Configure watermark settings
        $this->settingsService->set('watermark_enabled', true);
        $this->settingsService->set('watermark_text', 'Test Watermark');
        $this->settingsService->set('watermark_logo_path', $logoPath);
        $this->settingsService->set('watermark_position', 'bottom-right');
        $this->settingsService->set('watermark_opacity', 70);
        $this->settingsService->set('watermark_logo_size', 'medium');
        
        // Apply watermark
        $watermarkedPath = $this->watermarkService->applyWatermark($productImagePath);
        
        // Verify watermarked image was created
        $this->assertNotEquals($productImagePath, $watermarkedPath);
        $this->assertTrue(Storage::disk('public')->exists($watermarkedPath));
        
        // Verify the watermarked image has different content than original
        $originalContent = Storage::disk('public')->get($productImagePath);
        $watermarkedContent = Storage::disk('public')->get($watermarkedPath);
        $this->assertNotEquals($originalContent, $watermarkedContent);
        
        // Verify watermarked image is a valid image
        $watermarkedFullPath = Storage::disk('public')->path($watermarkedPath);
        $imageInfo = getimagesize($watermarkedFullPath);
        $this->assertNotFalse($imageInfo);
        $this->assertEquals(400, $imageInfo[0]); // Width should remain the same
        $this->assertEquals(300, $imageInfo[1]); // Height should remain the same
    }

    public function test_logo_watermarking_with_text_and_logo()
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension not available');
        }

        Storage::fake('public');
        
        // Create a test product image
        $productImage = imagecreatetruecolor(600, 400);
        $green = imagecolorallocate($productImage, 0, 200, 100);
        imagefill($productImage, 0, 0, $green);
        
        $productImagePath = 'products/test-product-2.jpg';
        $fullProductPath = Storage::disk('public')->path($productImagePath);
        $directory = dirname($fullProductPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        imagejpeg($productImage, $fullProductPath, 90);
        imagedestroy($productImage);
        
        // Create a test logo
        $logo = imagecreatetruecolor(80, 40);
        $transparent = imagecolorallocatealpha($logo, 0, 0, 0, 127);
        imagefill($logo, 0, 0, $transparent);
        imagesavealpha($logo, true);
        $red = imagecolorallocate($logo, 255, 0, 0);
        imagefilledrectangle($logo, 10, 10, 70, 30, $red);
        
        $logoPath = 'watermarks/logos/test-logo-2.png';
        $fullLogoPath = Storage::disk('public')->path($logoPath);
        $logoDirectory = dirname($fullLogoPath);
        if (!is_dir($logoDirectory)) {
            mkdir($logoDirectory, 0755, true);
        }
        imagepng($logo, $fullLogoPath);
        imagedestroy($logo);
        
        // Configure watermark settings with both text and logo
        $this->settingsService->set('watermark_enabled', true);
        $this->settingsService->set('watermark_text', 'Brand Name');
        $this->settingsService->set('watermark_logo_path', $logoPath);
        $this->settingsService->set('watermark_position', 'center');
        $this->settingsService->set('watermark_opacity', 80);
        $this->settingsService->set('watermark_logo_size', 'large');
        $this->settingsService->set('watermark_text_color', '#FFFFFF');
        
        // Apply watermark
        $watermarkedPath = $this->watermarkService->applyWatermark($productImagePath);
        
        // Verify watermarked image was created and is different
        $this->assertNotEquals($productImagePath, $watermarkedPath);
        $this->assertTrue(Storage::disk('public')->exists($watermarkedPath));
        
        $originalSize = Storage::disk('public')->size($productImagePath);
        $watermarkedSize = Storage::disk('public')->size($watermarkedPath);
        
        // Watermarked image should exist and have reasonable size
        $this->assertGreaterThan(0, $watermarkedSize);
        
        // Clean up
        Storage::disk('public')->delete($productImagePath);
        Storage::disk('public')->delete($watermarkedPath);
        Storage::disk('public')->delete($logoPath);
    }

    public function test_logo_watermarking_different_positions()
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension not available');
        }

        Storage::fake('public');
        
        $positions = ['top-left', 'top-center', 'top-right', 'center-left', 'center', 'center-right', 'bottom-left', 'bottom-center', 'bottom-right'];
        
        foreach ($positions as $position) {
            // Create a test product image
            $productImage = imagecreatetruecolor(300, 200);
            $color = imagecolorallocate($productImage, rand(50, 200), rand(50, 200), rand(50, 200));
            imagefill($productImage, 0, 0, $color);
            
            $productImagePath = "products/test-{$position}.jpg";
            $fullProductPath = Storage::disk('public')->path($productImagePath);
            $directory = dirname($fullProductPath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            imagejpeg($productImage, $fullProductPath, 90);
            imagedestroy($productImage);
            
            // Create a small logo
            $logo = imagecreatetruecolor(50, 25);
            $logoColor = imagecolorallocate($logo, 255, 255, 255);
            imagefill($logo, 0, 0, $logoColor);
            
            $logoPath = 'watermarks/logos/position-test-logo.png';
            $fullLogoPath = Storage::disk('public')->path($logoPath);
            $logoDirectory = dirname($fullLogoPath);
            if (!is_dir($logoDirectory)) {
                mkdir($logoDirectory, 0755, true);
            }
            imagepng($logo, $fullLogoPath);
            imagedestroy($logo);
            
            // Configure watermark settings for this position
            $this->settingsService->set('watermark_enabled', true);
            $this->settingsService->set('watermark_logo_path', $logoPath);
            $this->settingsService->set('watermark_position', $position);
            $this->settingsService->set('watermark_opacity', 60);
            $this->settingsService->set('watermark_logo_size', 'small');
            
            // Apply watermark
            $watermarkedPath = $this->watermarkService->applyWatermark($productImagePath);
            
            // Verify watermarked image was created
            $this->assertTrue(Storage::disk('public')->exists($watermarkedPath), "Watermarked image not created for position: {$position}");
            
            // Clean up for this iteration
            Storage::disk('public')->delete($productImagePath);
            Storage::disk('public')->delete($watermarkedPath);
        }
        
        // Clean up logo
        Storage::disk('public')->delete('watermarks/logos/position-test-logo.png');
    }
}