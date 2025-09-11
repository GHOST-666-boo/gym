<?php

namespace Tests\Feature\Admin;

use App\Models\NewsletterSubscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class NewsletterManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    public function test_admin_can_view_newsletter_subscribers_index(): void
    {
        NewsletterSubscriber::factory()->count(5)->create();

        $response = $this->actingAs($this->admin)
                        ->get(route('admin.newsletter.index'));

        $response->assertStatus(200)
                ->assertViewIs('admin.newsletter.index')
                ->assertViewHas('subscribers')
                ->assertViewHas('stats');
    }

    public function test_non_admin_cannot_access_newsletter_management(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user)
                        ->get(route('admin.newsletter.index'));

        $response->assertStatus(403);
    }

    public function test_admin_can_create_new_subscriber(): void
    {
        $email = $this->faker->safeEmail();
        $name = $this->faker->name();

        $response = $this->actingAs($this->admin)
                        ->post(route('admin.newsletter.store'), [
                            'email' => $email,
                            'name' => $name,
                        ]);

        $response->assertRedirect(route('admin.newsletter.index'))
                ->assertSessionHas('success');

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => $email,
            'name' => $name,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_view_subscriber_details(): void
    {
        $subscriber = NewsletterSubscriber::factory()->create();

        $response = $this->actingAs($this->admin)
                        ->get(route('admin.newsletter.show', $subscriber));

        $response->assertStatus(200)
                ->assertViewIs('admin.newsletter.show')
                ->assertViewHas('subscriber', $subscriber);
    }

    public function test_admin_can_edit_subscriber(): void
    {
        $subscriber = NewsletterSubscriber::factory()->create();
        $newEmail = $this->faker->safeEmail();
        $newName = $this->faker->name();

        $response = $this->actingAs($this->admin)
                        ->put(route('admin.newsletter.update', $subscriber), [
                            'email' => $newEmail,
                            'name' => $newName,
                            'is_active' => false,
                        ]);

        $response->assertRedirect(route('admin.newsletter.index'))
                ->assertSessionHas('success');

        $subscriber->refresh();
        $this->assertEquals($newEmail, $subscriber->email);
        $this->assertEquals($newName, $subscriber->name);
        $this->assertFalse($subscriber->is_active);
    }

    public function test_admin_can_delete_subscriber(): void
    {
        $subscriber = NewsletterSubscriber::factory()->create();

        $response = $this->actingAs($this->admin)
                        ->delete(route('admin.newsletter.destroy', $subscriber));

        $response->assertRedirect(route('admin.newsletter.index'))
                ->assertSessionHas('success');

        $this->assertDatabaseMissing('newsletter_subscribers', [
            'id' => $subscriber->id,
        ]);
    }

    public function test_admin_can_perform_bulk_activate(): void
    {
        $subscribers = NewsletterSubscriber::factory()->count(3)->inactive()->create();
        $subscriberIds = $subscribers->pluck('id')->toArray();

        $response = $this->actingAs($this->admin)
                        ->post(route('admin.newsletter.bulk-action'), [
                            'action' => 'activate',
                            'subscribers' => $subscriberIds,
                        ]);

        $response->assertRedirect(route('admin.newsletter.index'))
                ->assertSessionHas('success');

        foreach ($subscribers as $subscriber) {
            $subscriber->refresh();
            $this->assertTrue($subscriber->is_active);
        }
    }

    public function test_admin_can_perform_bulk_deactivate(): void
    {
        $subscribers = NewsletterSubscriber::factory()->count(3)->active()->create();
        $subscriberIds = $subscribers->pluck('id')->toArray();

        $response = $this->actingAs($this->admin)
                        ->post(route('admin.newsletter.bulk-action'), [
                            'action' => 'deactivate',
                            'subscribers' => $subscriberIds,
                        ]);

        $response->assertRedirect(route('admin.newsletter.index'))
                ->assertSessionHas('success');

        foreach ($subscribers as $subscriber) {
            $subscriber->refresh();
            $this->assertFalse($subscriber->is_active);
        }
    }

    public function test_admin_can_perform_bulk_delete(): void
    {
        $subscribers = NewsletterSubscriber::factory()->count(3)->create();
        $subscriberIds = $subscribers->pluck('id')->toArray();

        $response = $this->actingAs($this->admin)
                        ->post(route('admin.newsletter.bulk-action'), [
                            'action' => 'delete',
                            'subscribers' => $subscriberIds,
                        ]);

        $response->assertRedirect(route('admin.newsletter.index'))
                ->assertSessionHas('success');

        foreach ($subscriberIds as $id) {
            $this->assertDatabaseMissing('newsletter_subscribers', ['id' => $id]);
        }
    }

    public function test_admin_can_export_subscribers_csv(): void
    {
        NewsletterSubscriber::factory()->count(5)->create();

        $response = $this->actingAs($this->admin)
                        ->get(route('admin.newsletter.export'));

        $response->assertStatus(200)
                ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_admin_can_filter_subscribers_by_status(): void
    {
        NewsletterSubscriber::factory()->count(3)->active()->create();
        NewsletterSubscriber::factory()->count(2)->inactive()->create();

        $response = $this->actingAs($this->admin)
                        ->get(route('admin.newsletter.index', ['status' => 'active']));

        $response->assertStatus(200);
        $subscribers = $response->viewData('subscribers');
        $this->assertEquals(3, $subscribers->total());
    }

    public function test_admin_can_search_subscribers(): void
    {
        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => 'test@example.com',
            'name' => 'John Doe'
        ]);
        NewsletterSubscriber::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)
                        ->get(route('admin.newsletter.index', ['search' => 'test@example.com']));

        $response->assertStatus(200);
        $subscribers = $response->viewData('subscribers');
        $this->assertEquals(1, $subscribers->total());
        $this->assertEquals($subscriber->id, $subscribers->first()->id);
    }
}
