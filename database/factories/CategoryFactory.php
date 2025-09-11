<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Category::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = [
            'Cardio Equipment',
            'Strength Training', 
            'Free Weights',
            'Functional Training',
            'Recovery & Wellness',
            'Commercial Grade',
            'Resistance Training',
            'Flexibility & Mobility',
            'Cross Training',
            'Rehabilitation Equipment',
            'Sports Performance',
            'Home Fitness',
        ];

        $descriptors = [
            'Professional', 'Commercial', 'Elite', 'Premium', 'Advanced', 'Basic', 'Standard', 'Deluxe', 'Pro', 'Max'
        ];

        $baseCategory = $this->faker->randomElement($categories);
        $descriptor = $this->faker->randomElement($descriptors);
        $categoryName = $descriptor . ' ' . $baseCategory;
        
        return [
            'name' => $categoryName,
            'slug' => Str::slug($categoryName),
            'description' => $this->faker->sentence(12),
        ];
    }

    /**
     * Create a specific category by name.
     */
    public function withName(string $name, string $description = null): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $description ?? $this->faker->sentence(10),
        ]);
    }
}