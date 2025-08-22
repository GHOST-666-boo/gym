<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_user_can_register_without_admin(): void
    {
        $response = $this->get('/register');
        $response->assertStatus(200);

        $response = $this->post('/register', [
            'name' => 'First User',
            'email' => 'first@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertDatabaseHas('users', [
            'email' => 'first@example.com',
            'is_admin' => false,
        ]);
    }

    public function test_non_admin_cannot_access_registration_when_users_exist(): void
    {
        User::factory()->create(['is_admin' => false]);

        $response = $this->get('/register');
        $response->assertStatus(403);
    }

    public function test_admin_can_access_registration_to_create_users(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/admin/users/create');
        $response->assertStatus(200);
    }

    public function test_admin_can_create_admin_users(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/admin/users', [
            'name' => 'New Admin',
            'email' => 'newadmin@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'is_admin' => '1',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertDatabaseHas('users', [
            'email' => 'newadmin@example.com',
            'is_admin' => true,
        ]);
    }
}
