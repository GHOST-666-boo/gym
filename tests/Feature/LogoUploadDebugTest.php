<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class LogoUploadDebugTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user
        $this->adminUser = User::factory()->create(['is_admin' => true]);
        
        // Setup storage
        Storage::fake('public');
    }

    /** @test */
    public function admin_can_upload_logo_successfully()
    {
        $file = UploadedFile::fake()->image('logo.png', 200, 60);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.settings.upload-logo'), [
                'logo' => $file
            ]);

        $response->assertStatus(200);
        
        $responseData = $response->json();
        
        // Debug output
        dump('Response Status: ' . $response->getStatusCode());
        dump('Response Data: ', $responseData);
        
        $this->assertTrue($responseData['success'] ?? false);
        $this->assertArrayHasKey('url', $responseData);
        $this->assertArrayHasKey('path', $responseData);
    }

    /** @test */
    public function logo_upload_fails_with_invalid_file()
    {
        $file = UploadedFile::fake()->create('document.pdf', 1000);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.settings.upload-logo'), [
                'logo' => $file
            ]);

        $response->assertStatus(422);
        
        $responseData = $response->json();
        
        // Debug output
        dump('Response Status: ' . $response->getStatusCode());
        dump('Response Data: ', $responseData);
        
        $this->assertFalse($responseData['success'] ?? true);
        $this->assertArrayHasKey('message', $responseData);
    }

    /** @test */
    public function logo_upload_requires_authentication()
    {
        $file = UploadedFile::fake()->image('logo.png', 200, 60);

        $response = $this->post(route('admin.settings.upload-logo'), [
            'logo' => $file
        ]);

        // Should redirect to login
        $response->assertRedirect();
    }

    /** @test */
    public function logo_upload_requires_admin_role()
    {
        $regularUser = User::factory()->create(['is_admin' => false]);
        $file = UploadedFile::fake()->image('logo.png', 200, 60);

        $response = $this->actingAs($regularUser)
            ->post(route('admin.settings.upload-logo'), [
                'logo' => $file
            ]);

        // Should be forbidden or redirect
        $this->assertTrue(in_array($response->getStatusCode(), [403, 302]));
    }
}