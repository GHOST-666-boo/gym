<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingsFileUploadTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user
        $this->adminUser = User::factory()->create([
            'is_admin' => true,
        ]);
        
        // Fake the storage disk
        Storage::fake('public');
    }

    /** @test */
    public function admin_can_upload_logo()
    {
        $this->actingAs($this->adminUser);
        
        // Create a fake image file
        $file = UploadedFile::fake()->image('logo.png', 200, 200);
        
        $response = $this->postJson(route('admin.settings.upload-logo'), [
            'logo' => $file
        ]);
        
        // Debug the response if it fails
        if ($response->status() !== 200) {
            dump($response->json());
        }
        
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'path',
                    'url'
                ]);
        
        // Check that the setting was created
        $this->assertDatabaseHas('site_settings', [
            'key' => 'logo_path',
            'type' => 'string',
            'group' => 'general'
        ]);
        
        // Check that file was stored
        $logoPath = SiteSetting::get('logo_path');
        $this->assertNotNull($logoPath);
        Storage::disk('public')->assertExists($logoPath);
    }

    /** @test */
    public function admin_can_upload_favicon()
    {
        $this->actingAs($this->adminUser);
        
        // Create a fake image file
        $file = UploadedFile::fake()->image('favicon.png', 32, 32);
        
        $response = $this->postJson(route('admin.settings.upload-favicon'), [
            'favicon' => $file
        ]);
        
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Favicon uploaded and optimized successfully.'
                ]);
        
        // Check that the setting was created
        $this->assertDatabaseHas('site_settings', [
            'key' => 'favicon_path',
            'type' => 'string',
            'group' => 'seo'
        ]);
        
        // Check that file was stored
        $faviconPath = SiteSetting::get('favicon_path');
        Storage::disk('public')->assertExists($faviconPath);
    }

    /** @test */
    public function logo_upload_validates_file_size()
    {
        $this->actingAs($this->adminUser);
        
        // Create a file that's too large (6MB)
        $file = UploadedFile::fake()->create('large-logo.png', 6144); // 6MB
        
        $response = $this->postJson(route('admin.settings.upload-logo'), [
            'logo' => $file
        ]);
        
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['logo']);
    }

    /** @test */
    public function favicon_upload_validates_file_size()
    {
        $this->actingAs($this->adminUser);
        
        // Create a file that's too large (3MB)
        $file = UploadedFile::fake()->create('large-favicon.ico', 3072); // 3MB
        
        $response = $this->postJson(route('admin.settings.upload-favicon'), [
            'favicon' => $file
        ]);
        
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['favicon']);
    }

    /** @test */
    public function logo_upload_validates_file_type()
    {
        $this->actingAs($this->adminUser);
        
        // Create a non-image file
        $file = UploadedFile::fake()->create('document.pdf', 100);
        
        $response = $this->postJson(route('admin.settings.upload-logo'), [
            'logo' => $file
        ]);
        
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['logo']);
    }

    /** @test */
    public function favicon_upload_validates_file_type()
    {
        $this->actingAs($this->adminUser);
        
        // Create a non-image file
        $file = UploadedFile::fake()->create('document.txt', 100);
        
        $response = $this->postJson(route('admin.settings.upload-favicon'), [
            'favicon' => $file
        ]);
        
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['favicon']);
    }

    /** @test */
    public function uploading_new_logo_deletes_old_logo()
    {
        $this->actingAs($this->adminUser);
        
        // Upload first logo
        $firstFile = UploadedFile::fake()->image('logo1.png', 200, 200);
        $firstResponse = $this->postJson(route('admin.settings.upload-logo'), ['logo' => $firstFile]);
        $firstResponse->assertStatus(200);
        
        $firstLogoPath = SiteSetting::get('logo_path');
        $this->assertNotNull($firstLogoPath);
        Storage::disk('public')->assertExists($firstLogoPath);
        
        // Wait a moment to ensure different timestamps
        sleep(1);
        
        // Upload second logo
        $secondFile = UploadedFile::fake()->image('logo2.png', 200, 200);
        $secondResponse = $this->postJson(route('admin.settings.upload-logo'), ['logo' => $secondFile]);
        $secondResponse->assertStatus(200);
        
        $secondLogoPath = SiteSetting::get('logo_path');
        $this->assertNotNull($secondLogoPath);
        
        // First logo should be deleted, second should exist
        Storage::disk('public')->assertMissing($firstLogoPath);
        Storage::disk('public')->assertExists($secondLogoPath);
        
        // Paths should be different
        $this->assertNotEquals($firstLogoPath, $secondLogoPath);
    }

    /** @test */
    public function uploading_new_favicon_deletes_old_favicon()
    {
        $this->actingAs($this->adminUser);
        
        // Upload first favicon
        $firstFile = UploadedFile::fake()->image('favicon1.png', 32, 32);
        $firstResponse = $this->postJson(route('admin.settings.upload-favicon'), ['favicon' => $firstFile]);
        $firstResponse->assertStatus(200);
        
        $firstFaviconPath = SiteSetting::get('favicon_path');
        $this->assertNotNull($firstFaviconPath);
        Storage::disk('public')->assertExists($firstFaviconPath);
        
        // Wait a moment to ensure different timestamps
        sleep(1);
        
        // Upload second favicon
        $secondFile = UploadedFile::fake()->image('favicon2.png', 32, 32);
        $secondResponse = $this->postJson(route('admin.settings.upload-favicon'), ['favicon' => $secondFile]);
        $secondResponse->assertStatus(200);
        
        $secondFaviconPath = SiteSetting::get('favicon_path');
        $this->assertNotNull($secondFaviconPath);
        
        // First favicon should be deleted, second should exist
        Storage::disk('public')->assertMissing($firstFaviconPath);
        Storage::disk('public')->assertExists($secondFaviconPath);
        
        // Paths should be different
        $this->assertNotEquals($firstFaviconPath, $secondFaviconPath);
    }

    /** @test */
    public function non_admin_cannot_upload_files()
    {
        $regularUser = User::factory()->create(['is_admin' => false]);
        $this->actingAs($regularUser);
        
        $file = UploadedFile::fake()->image('logo.png', 200, 200);
        
        $response = $this->postJson(route('admin.settings.upload-logo'), [
            'logo' => $file
        ]);
        
        $response->assertStatus(403);
    }

    /** @test */
    public function guest_cannot_upload_files()
    {
        $file = UploadedFile::fake()->image('logo.png', 200, 200);
        
        $response = $this->postJson(route('admin.settings.upload-logo'), [
            'logo' => $file
        ]);
        
        $response->assertStatus(401);
    }
}