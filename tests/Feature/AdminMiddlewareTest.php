<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        // Create a test route that uses admin middleware
        $this->app['router']->get('/admin/test', function () {
            return 'Admin area';
        })->middleware('admin');

        $response = $this->get('/admin/test');

        $response->assertRedirect('/login');
    }

    public function test_non_admin_user_gets_403(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        // Create a test route that uses admin middleware
        $this->app['router']->get('/admin/test', function () {
            return 'Admin area';
        })->middleware('admin');

        $response = $this->actingAs($user)->get('/admin/test');

        $response->assertStatus(403);
    }

    public function test_admin_user_can_access_admin_routes(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        // Create a test route that uses admin middleware
        $this->app['router']->get('/admin/test', function () {
            return 'Admin area';
        })->middleware('admin');

        $response = $this->actingAs($admin)->get('/admin/test');

        $response->assertStatus(200);
        $response->assertSeeText('Admin area');
    }
}
