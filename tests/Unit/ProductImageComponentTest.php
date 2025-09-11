<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\View\Components\ProductImage;
use App\Services\WatermarkService;
use App\Services\ImageProtectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductImageComponentTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_product_image_component_without_watermarking()
    {
        // Mock the services to return disabled states
        $this->mock(WatermarkService::class, function ($mock) {
            $mock->shouldReceive('getWatermarkSettings')->andReturn([
                'text' => '',
                'logo_path' => '',
                'opacity' => 50,
                'position' => 'bottom-right',
                'size' => 24,
                'color' => '#FFFFFF',
                'logo_size' => 'medium',
            ]);
        });

        $this->mock(ImageProtectionService::class, function ($mock) {
            $mock->shouldReceive('isProtectionEnabled')->andReturn(false);
            $mock->shouldReceive('getProtectionScript')->andReturn('');
            $mock->shouldReceive('getProtectionStyles')->andReturn('');
        });

        $component = new ProductImage(
            imagePath: 'test-image.jpg',
            alt: 'Test Image',
            class: 'test-class',
            width: 400,
            height: 300
        );

        $this->assertInstanceOf(ProductImage::class, $component);
        $this->assertEquals('Test Image', $component->alt);
        $this->assertFalse($component->isWatermarkEnabled());
        $this->assertFalse($component->isProtectionEnabled());
    }

    /** @test */
    public function it_can_create_product_image_component_with_watermarking()
    {
        // Mock the services to return enabled states
        $this->mock(WatermarkService::class, function ($mock) {
            $mock->shouldReceive('getWatermarkSettings')->andReturn([
                'text' => 'Test Watermark',
                'logo_path' => '',
                'opacity' => 50,
                'position' => 'bottom-right',
                'size' => 24,
                'color' => '#FFFFFF',
                'logo_size' => 'medium',
            ]);
            $mock->shouldReceive('applyWatermark')->with('test-image.jpg')->andReturn('watermarked-test-image.jpg');
        });

        $this->mock(ImageProtectionService::class, function ($mock) {
            $mock->shouldReceive('isProtectionEnabled')->andReturn(false);
            $mock->shouldReceive('getProtectionScript')->andReturn('');
            $mock->shouldReceive('getProtectionStyles')->andReturn('');
        });

        $component = new ProductImage(
            imagePath: 'test-image.jpg',
            alt: 'Test Image',
            class: 'test-class',
            width: 400,
            height: 300
        );

        $this->assertInstanceOf(ProductImage::class, $component);
        $this->assertTrue($component->isWatermarkEnabled());
        $this->assertFalse($component->isProtectionEnabled());
    }

    /** @test */
    public function it_can_create_product_image_component_with_protection()
    {
        // Mock the services
        $this->mock(WatermarkService::class, function ($mock) {
            $mock->shouldReceive('getWatermarkSettings')->andReturn([
                'text' => '',
                'logo_path' => '',
                'opacity' => 50,
                'position' => 'bottom-right',
                'size' => 24,
                'color' => '#FFFFFF',
                'logo_size' => 'medium',
            ]);
        });

        $this->mock(ImageProtectionService::class, function ($mock) {
            $mock->shouldReceive('isProtectionEnabled')->andReturn(true);
            $mock->shouldReceive('getProtectionScript')->andReturn('console.log("protection enabled");');
            $mock->shouldReceive('getProtectionStyles')->andReturn('<style>.product-image { user-select: none; }</style>');
        });

        $component = new ProductImage(
            imagePath: 'test-image.jpg',
            alt: 'Test Image',
            class: 'test-class',
            width: 400,
            height: 300
        );

        $this->assertInstanceOf(ProductImage::class, $component);
        $this->assertFalse($component->isWatermarkEnabled());
        $this->assertTrue($component->isProtectionEnabled());
        $this->assertStringContainsString('protection enabled', $component->getProtectionScript());
        $this->assertStringContainsString('user-select: none', $component->getProtectionStyles());
    }

    /** @test */
    public function it_handles_fallback_when_watermarking_fails()
    {
        // Mock the services to simulate watermarking failure
        $this->mock(WatermarkService::class, function ($mock) {
            $mock->shouldReceive('getWatermarkSettings')->andReturn([
                'text' => 'Test Watermark',
                'logo_path' => '',
                'opacity' => 50,
                'position' => 'bottom-right',
                'size' => 24,
                'color' => '#FFFFFF',
                'logo_size' => 'medium',
            ]);
            $mock->shouldReceive('applyWatermark')->with('test-image.jpg')->andThrow(new \Exception('Watermarking failed'));
        });

        $this->mock(ImageProtectionService::class, function ($mock) {
            $mock->shouldReceive('isProtectionEnabled')->andReturn(false);
            $mock->shouldReceive('getProtectionScript')->andReturn('');
            $mock->shouldReceive('getProtectionStyles')->andReturn('');
        });

        // Should not throw exception and should fallback gracefully
        $component = new ProductImage(
            imagePath: 'test-image.jpg',
            alt: 'Test Image',
            class: 'test-class',
            width: 400,
            height: 300
        );

        $this->assertInstanceOf(ProductImage::class, $component);
        $this->assertTrue($component->isWatermarkEnabled());
    }

    /** @test */
    public function it_can_override_protection_and_watermarking_settings()
    {
        // Mock the services
        $this->mock(WatermarkService::class, function ($mock) {
            $mock->shouldReceive('getWatermarkSettings')->andReturn([
                'text' => 'Test Watermark',
                'logo_path' => '',
                'opacity' => 50,
                'position' => 'bottom-right',
                'size' => 24,
                'color' => '#FFFFFF',
                'logo_size' => 'medium',
            ]);
        });

        $this->mock(ImageProtectionService::class, function ($mock) {
            $mock->shouldReceive('isProtectionEnabled')->andReturn(true);
            $mock->shouldReceive('getProtectionScript')->andReturn('');
            $mock->shouldReceive('getProtectionStyles')->andReturn('');
        });

        // Override both settings to false
        $component = new ProductImage(
            imagePath: 'test-image.jpg',
            alt: 'Test Image',
            class: 'test-class',
            width: 400,
            height: 300,
            lazy: true,
            protected: false,
            watermarked: false
        );

        $this->assertInstanceOf(ProductImage::class, $component);
        $this->assertFalse($component->isWatermarkEnabled());
        $this->assertFalse($component->isProtectionEnabled());
    }
}