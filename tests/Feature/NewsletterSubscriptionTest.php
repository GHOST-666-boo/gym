<?php

namespace Tests\Feature;

use App\Models\NewsletterSubscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class NewsletterSubscriptionTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_user_can_subscribe_to_newsletter(): void
    {
        $email = $this->faker->safeEmail();
        $name = $this->faker->name();

        $response = $this->postJson(route('newsletter.subscribe'), [
            'email' => $email,
            'name' => $name,
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Thank you for subscribing to our newsletter!'
                ]);

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => $email,
            'name' => $name,
            'is_active' => true,
        ]);
    }

    public function test_user_can_subscribe_without_name(): void
    {
        $email = $this->faker->safeEmail();

        $response = $this->postJson(route('newsletter.subscribe'), [
            'email' => $email,
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Thank you for subscribing to our newsletter!'
                ]);

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => $email,
            'name' => null,
            'is_active' => true,
        ]);
    }

    public function test_duplicate_subscription_returns_error(): void
    {
        $subscriber = NewsletterSubscriber::factory()->active()->create();

        $response = $this->postJson(route('newsletter.subscribe'), [
            'email' => $subscriber->email,
        ]);

        $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'You are already subscribed to our newsletter.'
                ]);
    }

    public function test_reactivating_inactive_subscription(): void
    {
        $subscriber = NewsletterSubscriber::factory()->inactive()->create();

        $response = $this->postJson(route('newsletter.subscribe'), [
            'email' => $subscriber->email,
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Welcome back! Your newsletter subscription has been reactivated.'
                ]);

        $subscriber->refresh();
        $this->assertTrue($subscriber->is_active);
        $this->assertNull($subscriber->unsubscribed_at);
    }

    public function test_invalid_email_returns_validation_error(): void
    {
        $response = $this->postJson(route('newsletter.subscribe'), [
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    public function test_user_can_unsubscribe_with_valid_token(): void
    {
        $subscriber = NewsletterSubscriber::factory()->active()->create();

        $response = $this->get(route('newsletter.unsubscribe', $subscriber->unsubscribe_token));

        $response->assertStatus(200)
                ->assertViewIs('newsletter.unsubscribe')
                ->assertViewHas('success', true);

        $subscriber->refresh();
        $this->assertFalse($subscriber->is_active);
        $this->assertNotNull($subscriber->unsubscribed_at);
    }

    public function test_unsubscribe_with_invalid_token_shows_error(): void
    {
        $response = $this->get(route('newsletter.unsubscribe', 'invalid-token'));

        $response->assertStatus(200)
                ->assertViewIs('newsletter.unsubscribe')
                ->assertViewHas('success', false);
    }

    public function test_unsubscribe_already_inactive_subscriber(): void
    {
        $subscriber = NewsletterSubscriber::factory()->inactive()->create();

        $response = $this->get(route('newsletter.unsubscribe', $subscriber->unsubscribe_token));

        $response->assertStatus(200)
                ->assertViewIs('newsletter.unsubscribe')
                ->assertViewHas('success', true)
                ->assertViewHas('already_unsubscribed', true);
    }

    public function test_newsletter_preferences_page_loads(): void
    {
        $subscriber = NewsletterSubscriber::factory()->active()->create();

        $response = $this->get(route('newsletter.preferences', $subscriber->unsubscribe_token));

        $response->assertStatus(200)
                ->assertViewIs('newsletter.preferences')
                ->assertViewHas('subscriber', $subscriber);
    }

    public function test_newsletter_preferences_with_invalid_token_returns_404(): void
    {
        $response = $this->get(route('newsletter.preferences', 'invalid-token'));

        $response->assertStatus(404);
    }

    public function test_newsletter_form_appears_in_footer(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200)
                ->assertSee('newsletter-form', false)
                ->assertSee('newsletter-email', false)
                ->assertSee('Stay Updated');
    }
}
