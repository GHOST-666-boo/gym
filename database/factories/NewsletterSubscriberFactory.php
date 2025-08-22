<?php

namespace Database\Factories;

use App\Models\NewsletterSubscriber;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NewsletterSubscriber>
 */
class NewsletterSubscriberFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = NewsletterSubscriber::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subscribedAt = $this->faker->dateTimeBetween('-1 year', 'now');
        
        return [
            'email' => $this->faker->unique()->safeEmail(),
            'name' => $this->faker->optional(0.7)->name(),
            'is_active' => $this->faker->boolean(85), // 85% chance of being active
            'subscribed_at' => $subscribedAt,
            'unsubscribed_at' => null,
            'unsubscribe_token' => Str::random(32),
        ];
    }

    /**
     * Indicate that the subscriber is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'unsubscribed_at' => null,
        ]);
    }

    /**
     * Indicate that the subscriber is inactive.
     */
    public function inactive(): static
    {
        return $this->state(function (array $attributes) {
            $unsubscribedAt = $this->faker->dateTimeBetween($attributes['subscribed_at'], 'now');
            
            return [
                'is_active' => false,
                'unsubscribed_at' => $unsubscribedAt,
            ];
        });
    }

    /**
     * Indicate that the subscriber has a name.
     */
    public function withName(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $this->faker->name(),
        ]);
    }

    /**
     * Indicate that the subscriber has no name.
     */
    public function withoutName(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => null,
        ]);
    }
}
