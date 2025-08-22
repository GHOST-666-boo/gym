<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Review>
 */
class ReviewFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Review::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $reviewTitles = [
            'Excellent quality!',
            'Great value for money',
            'Highly recommended',
            'Perfect for my home gym',
            'Outstanding build quality',
            'Exceeded my expectations',
            'Solid and reliable',
            'Worth every penny',
            'Professional grade equipment',
            'Amazing results',
            'Good product overall',
            'Decent quality',
            'Could be better',
            'Not what I expected',
            'Average product'
        ];

        $positiveComments = [
            'This gym machine has transformed my workout routine. The build quality is exceptional and it feels very sturdy during use.',
            'I\'ve been using this for several months now and it\'s holding up perfectly. Great investment for my home gym.',
            'The quality is outstanding and the design is both functional and aesthetically pleasing. Highly recommend!',
            'Excellent piece of equipment. Easy to assemble and the instructions were clear. Very satisfied with my purchase.',
            'This machine provides a great workout and the adjustability options are perfect for different exercises.',
            'Professional quality at a reasonable price. I\'ve used similar equipment at commercial gyms and this compares very well.',
            'Sturdy construction and smooth operation. The safety features give me confidence during my workouts.',
            'Great addition to my home gym. The compact design fits perfectly in my space without compromising functionality.',
            'The customer service was excellent and the delivery was prompt. The product exceeded my expectations.',
            'I\'ve recommended this to several friends already. It\'s a solid piece of equipment that delivers results.'
        ];

        $neutralComments = [
            'The product is decent for the price point. It does what it\'s supposed to do but nothing exceptional.',
            'Good quality overall, though I wish some features were better designed. Still satisfied with the purchase.',
            'It\'s a solid machine that gets the job done. Assembly took longer than expected but worth it in the end.',
            'The build quality is good and it\'s functional, though I\'ve seen better designs in this price range.',
            'Does what it promises. The instructions could be clearer but manageable with some patience.',
        ];

        $negativeComments = [
            'The quality is not what I expected for the price. Some parts feel flimsy and I\'m concerned about durability.',
            'Assembly was more difficult than anticipated and some parts didn\'t fit perfectly. Disappointed overall.',
            'It works but the design could be improved. Not as smooth in operation as I hoped.',
            'For the price, I expected better quality. It\'s functional but feels cheap in some areas.',
            'The product arrived with minor damage and customer service was slow to respond. Average experience.',
        ];

        $rating = $this->faker->numberBetween(1, 5);
        
        // Choose comment based on rating
        if ($rating >= 4) {
            $comment = $this->faker->randomElement($positiveComments);
            $title = $this->faker->randomElement(array_slice($reviewTitles, 0, 10));
        } elseif ($rating == 3) {
            $comment = $this->faker->randomElement($neutralComments);
            $title = $this->faker->randomElement(array_slice($reviewTitles, 10, 3));
        } else {
            $comment = $this->faker->randomElement($negativeComments);
            $title = $this->faker->randomElement(array_slice($reviewTitles, 13));
        }

        return [
            'product_id' => Product::factory(),
            'user_id' => $this->faker->boolean(70) ? User::factory() : null, // 70% chance of having a user
            'reviewer_name' => $this->faker->name(),
            'reviewer_email' => $this->faker->safeEmail(),
            'rating' => $rating,
            'title' => $title,
            'comment' => $comment,
            'is_approved' => $this->faker->boolean(80), // 80% chance of being approved
            'approved_at' => function (array $attributes) {
                return $attributes['is_approved'] ? $this->faker->dateTimeBetween('-1 month', 'now') : null;
            },
            'approved_by' => function (array $attributes) {
                return $attributes['is_approved'] ? User::factory()->create(['is_admin' => true])->id : null;
            },
        ];
    }

    /**
     * Indicate that the review is approved.
     */
    public function approved(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_approved' => true,
                'approved_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
                'approved_by' => User::factory()->create(['is_admin' => true])->id,
            ];
        });
    }

    /**
     * Indicate that the review is pending approval.
     */
    public function pending(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_approved' => false,
                'approved_at' => null,
                'approved_by' => null,
            ];
        });
    }

    /**
     * Create a review with a specific rating.
     */
    public function rating(int $rating): static
    {
        return $this->state(function (array $attributes) use ($rating) {
            // Adjust title and comment based on rating
            $reviewTitles = [
                1 => ['Poor quality', 'Not recommended', 'Disappointing'],
                2 => ['Below average', 'Could be better', 'Not satisfied'],
                3 => ['Average product', 'Decent quality', 'It\'s okay'],
                4 => ['Good quality', 'Recommended', 'Happy with purchase'],
                5 => ['Excellent!', 'Outstanding quality', 'Highly recommended', 'Perfect!']
            ];

            return [
                'rating' => $rating,
                'title' => $this->faker->randomElement($reviewTitles[$rating] ?? $reviewTitles[3]),
            ];
        });
    }

    /**
     * Create a review for a specific product.
     */
    public function forProduct(Product $product): static
    {
        return $this->state(function (array $attributes) use ($product) {
            return [
                'product_id' => $product->id,
            ];
        });
    }

    /**
     * Create a review by a specific user.
     */
    public function byUser(User $user): static
    {
        return $this->state(function (array $attributes) use ($user) {
            return [
                'user_id' => $user->id,
                'reviewer_name' => $user->name,
                'reviewer_email' => $user->email,
            ];
        });
    }

    /**
     * Create a guest review (no user account).
     */
    public function guest(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'user_id' => null,
            ];
        });
    }
}