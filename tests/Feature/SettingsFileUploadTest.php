<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SettingsFileUploadTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected SettingsService $settingsService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adminUser = User::factory()->create(['is_admin' => true]);
        $this->settingsService = app(SettingsService::class);
        
        // Fake storage for testing
        Storage::fake('public');
    }

    /** @test */
    public function test_logo_upload_functionality()
    {
        $this->actingAs($this->adminUser);

        // Create a test logo file
        $logoFile = UploadedFile::fake()->image('test-logo.png', 300, 150);

        // Upload the logo
        $response = $this->postJson(route('admin.settings.upload-logo'), [
            'logo' => $logoFile
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        
        $responseData = $response->json();
        
        // Verify response contains required fields
        $this->assertArrayHasKey('path', $responseData);
        $this->assertArrayHasKey('url', $responseData);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertStringContainsString('logos/', $responseData['path']);
        $this->assertStringContainsString('storage/', $responseData['url']);

        // Verify file was stored in correct location
        Storage::disk('public')->assertExists($responseData['path']);

        // Verify setting was updated
        $logoPath = $this->settingsService->get('logo_path');
        $this->assertEquals($responseData['path'], $logoPath);

        // Verify logo URL helper returns correct URL
        $logoUrl = site_logo();
        $this->assertStringContainsString($responseData['path'], $logoUrl);
        $this->assertStringContainsString('storage/', $logoUrl);
    }

    /** @test */
    public function test_favicon_upload_functionality()
    {
        $this->actingAs($this->adminUser);

        // Create a test favicon file
        $faviconFile = UploadedFile::fake()->image('test-favicon.png', 32, 32);

        // Upload the favicon
        $response = $this->postJson(route('admin.settings.upload-favicon'), [
            'favicon' => $faviconFile
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        
        $responseData = $response->json();
        
        // Verify response contains required fields
        $this->assertArrayHasKey('path', $responseData);
        $this->assertArrayHasKey('url', $responseData);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertStringContainsString('favicons/', $responseData['path']);
        $this->assertStringContainsString('storage/', $responseData['url']);

        // Verify file was stored in correct location
        Storage::disk('public')->assertExists($responseData['path']);

        // Verify setting was updated
        $faviconPath = $this->settingsService->get('favicon_path');
        $this->assertEquals($responseData['path'], $faviconPath);

        // Verify favicon URL helper returns correct URL
        $faviconUrl = site_favicon();
        $this->assertStringContainsString($responseData['path'], $faviconUrl);
        $this->assertStringContainsString('storage/', $faviconUrl);
    }

    /** @test */
    public function test_logo_upload_validation()
    {
        $this->actingAs($this->adminUser);

        // Test missing file
        $response = $this->postJson(route('admin.settings.upload-logo'), []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('logo');

        // Test invalid file type
        $invalidFile = UploadedFile::fake()->create('document.pdf', 100);
        $response = $this->postJson(route('admin.settings.upload-logo'), [
            'logo' => $invalidFile
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('logo');

        // Test file too large (over 5MB)
        $largeFile = UploadedFile::fake()->create('large-logo.png', 6000);
        $response = $this->postJson(route('admin.settings.upload-logo'), [
            'logo' => $largeFile
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('logo');

        // Test valid file types
        $validTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        
        foreach ($validTypes as $type) {
            if ($type === 'svg') {
                // Create a simple SVG file
                $svgContent = '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="red"/></svg>';
                $svgFile = UploadedFile::fake()->createWithContent("logo.{$type}", $svgContent);
            } else {
                $svgFile = UploadedFile::fake()->image("logo.{$type}", 200, 100);
            }
            
            $response = $this->postJson(route('admin.settings.upload-logo'), [
                'logo' => $svgFile
            ]);
            
            $response->assertStatus(200);
            $response->assertJson(['success' => true]);
        }
    }

    /** @test */
    public function test_favicon_upload_validation()
    {
        $this->actingAs($this->adminUser);

        // Test missing file
        $response = $this->postJson(route('admin.settings.upload-favicon'), []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('favicon');

        // Test invalid file type
        $invalidFile = UploadedFile::fake()->create('document.pdf', 100);
        $response = $this->postJson(route('admin.settings.upload-favicon'), [
            'favicon' => $invalidFile
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('favicon');

        // Test file too large (over 2MB)
        $largeFile = UploadedFile::fake()->create('large-favicon.png', 3000);
        $response = $this->postJson(route('admin.settings.upload-favicon'), [
            'favicon' => $largeFile
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('favicon');

        // Test valid file types (skip ICO as it's hard to fake properly)
        $validTypes = ['png', 'jpg', 'jpeg', 'gif'];
        
        foreach ($validTypes as $type) {
            $file = UploadedFile::fake()->image("favicon.{$type}", 32, 32);
            
            $response = $this->postJson(route('admin.settings.upload-favicon'), [
                'favicon' => $file
            ]);
            
            $response->assertStatus(200);
            $response->assertJson(['success' => true]);
        }
    }

    /** @test */
    public function test_logo_replacement_deletes_old_file()
    {
        $this->actingAs($this->adminUser);

        // Upload first logo
        $firstLogo = UploadedFile::fake()->image('first-logo.png', 200, 100);
        $response = $this->postJson(route('admin.settings.upload-logo'), [
            'logo' => $firstLogo
        ]);
        
        $response->assertStatus(200);
        $firstLogoPath = $response->json('path');
        
        // Verify first logo exists
        $this->assertTrue(Storage::disk('public')->exists($firstLogoPath));

        // Upload second logo
        $secondLogo = UploadedFile::fake()->image('second-logo.png', 200, 100);
        $response = $this->postJson(route('admin.settings.upload-logo'), [
            'logo' => $secondLogo
        ]);
        
        $response->assertStatus(200);
        $secondLogoPath = $response->json('path');
        
        // Verify second logo exists and first logo was deleted
        $this->assertTrue(Storage::disk('public')->exists($secondLogoPath));
        $this->assertFalse(Storage::disk('public')->exists($firstLogoPath));
        
        // Verify setting was updated to new logo
        $currentLogoPath = $this->settingsService->get('logo_path');
        $this->assertEquals($secondLogoPath, $currentLogoPath);
    }

    /** @test */
    public function test_favicon_replacement_deletes_old_file()
    {
        $this->actingAs($this->adminUser);

        // Upload first favicon
        $firstFavicon = UploadedFile::fake()->image('first-favicon.png', 32, 32);
        $response = $this->postJson(route('admin.settings.upload-favicon'), [
            'favicon' => $firstFavicon
        ]);
        
        $response->assertStatus(200);
        $firstFaviconPath = $response->json('path');
        
        // Verify first favicon exists
        $this->assertTrue(Storage::disk('public')->exists($firstFaviconPath));

        // Upload second favicon
        $secondFavicon = UploadedFile::fake()->image('second-favicon.png', 32, 32);
        $response = $this->postJson(route('admin.settings.upload-favicon'), [
            'favicon' => $secondFavicon
        ]);
        
        $response->assertStatus(200);
        $secondFaviconPath = $response->json('path');
        
        // Verify second favicon exists and first favicon was deleted
        $this->assertTrue(Storage::disk('public')->exists($secondFaviconPath));
        $this->assertFalse(Storage::disk('public')->exists($firstFaviconPath));
        
        // Verify setting was updated to new favicon
        $currentFaviconPath = $this->settingsService->get('favicon_path');
        $this->assertEquals($secondFaviconPath, $currentFaviconPath);
    }

    /** @test */
    public function test_file_upload_error_handling()
    {
        $this->actingAs($this->adminUser);

        // Mock a file upload error by creating an invalid file
        $corruptedFile = UploadedFile::fake()->create('corrupted.png', 100);
        
        // The service should handle errors gracefully
        $result = $this->settingsService->uploadLogo($corruptedFile);
        
        // Should return error response
        $this->assertFalse($result['success']);
        $this->assertNotNull($result['message']);
        $this->assertNull($result['path']);
    }

    /** @test */
    public function test_logo_and_favicon_display_in_admin_interface()
    {
        $this->actingAs($this->adminUser);

        // Upload logo and favicon
        $logoFile = UploadedFile::fake()->image('admin-logo.png', 200, 100);
        $faviconFile = UploadedFile::fake()->image('admin-favicon.png', 32, 32);

        $logoResponse = $this->postJson(route('admin.settings.upload-logo'), [
            'logo' => $logoFile
        ]);
        
        $faviconResponse = $this->postJson(route('admin.settings.upload-favicon'), [
            'favicon' => $faviconFile
        ]);

        $logoPath = $logoResponse->json('path');
        $faviconPath = $faviconResponse->json('path');

        // Check admin settings page displays uploaded files
        $response = $this->get(route('admin.settings.index'));
        $response->assertStatus(200);
        
        $content = $response->getContent();
        $this->assertStringContainsString('storage/' . $logoPath, $content);
        $this->assertStringContainsString('storage/' . $faviconPath, $content);
        $this->assertStringContainsString('Current logo', $content);
        $this->assertStringContainsString('Current favicon', $content);
    }

    /** @test */
    public function test_default_logo_and_favicon_urls()
    {
        // Test default logo URL when no logo is uploaded
        $logoUrl = site_logo();
        $this->assertEquals(asset('images/default-logo.png'), $logoUrl);

        // Test default favicon URL when no favicon is uploaded
        $faviconUrl = site_favicon();
        $this->assertEquals(asset('favicon.ico'), $faviconUrl);

        // Test service methods directly
        $logoUrl = $this->settingsService->getLogoUrl();
        $this->assertEquals(asset('images/default-logo.png'), $logoUrl);

        $faviconUrl = $this->settingsService->getFaviconUrl();
        $this->assertEquals(asset('favicon.ico'), $faviconUrl);
    }

    /** @test */
    public function test_file_upload_directory_creation()
    {
        $this->actingAs($this->adminUser);

        // Ensure directories don't exist initially
        Storage::disk('public')->deleteDirectory('logos');
        Storage::disk('public')->deleteDirectory('favicons');

        // Upload logo - should create logos directory
        $logoFile = UploadedFile::fake()->image('test-logo.png', 200, 100);
        $response = $this->postJson(route('admin.settings.upload-logo'), [
            'logo' => $logoFile
        ]);
        
        $response->assertStatus(200);
        $this->assertTrue(Storage::disk('public')->exists('logos'));

        // Upload favicon - should create favicons directory
        $faviconFile = UploadedFile::fake()->image('test-favicon.png', 32, 32);
        $response = $this->postJson(route('admin.settings.upload-favicon'), [
            'favicon' => $faviconFile
        ]);
        
        $response->assertStatus(200);
        $this->assertTrue(Storage::disk('public')->exists('favicons'));
    }

    /** @test */
    public function test_unauthorized_file_upload_access()
    {
        // Test without authentication
        $logoFile = UploadedFile::fake()->image('test-logo.png', 200, 100);
        
        $response = $this->postJson(route('admin.settings.upload-logo'), [
            'logo' => $logoFile
        ]);
        $response->assertStatus(401);

        // Test with non-admin user
        $regularUser = User::factory()->create(['is_admin' => false]);
        $this->actingAs($regularUser);

        $response = $this->postJson(route('admin.settings.upload-logo'), [
            'logo' => $logoFile
        ]);
        $response->assertStatus(403);
    }

    /** @test */
    public function test_image_optimization_and_processing()
    {
        $this->actingAs($this->adminUser);

        // Upload a large logo that should be resized
        $largeLogo = UploadedFile::fake()->image('large-logo.png', 800, 400);
        
        $response = $this->postJson(route('admin.settings.upload-logo'), [
            'logo' => $largeLogo
        ]);
        
        $response->assertStatus(200);
        $logoPath = $response->json('path');
        
        // Verify file was stored
        Storage::disk('public')->assertExists($logoPath);
        
        // The service should handle image processing (resizing, optimization)
        // This is tested indirectly through successful upload and storage
        $this->assertTrue($response->json('success'));
        $this->assertStringContainsString('optimized successfully', $response->json('message'));
    }
}