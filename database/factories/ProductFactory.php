<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gymMachines = [
            'Treadmill', 'Elliptical Trainer', 'Rowing Machine', 'Leg Press Machine', 
            'Cable Crossover System', 'Smith Machine', 'Lat Pulldown Machine', 'Chest Press Machine',
            'Shoulder Press Machine', 'Leg Curl Machine', 'Leg Extension Machine', 'Seated Row Machine',
            'Pull-up Station', 'Dip Station', 'Multi-Station Gym', 'Power Rack', 'Squat Rack',
            'Bench Press', 'Incline Bench', 'Decline Bench', 'Adjustable Bench', 'Preacher Curl Bench',
            'Cable Machine', 'Functional Trainer', 'Suspension Trainer', 'Battle Ropes Station',
            'Kettlebell Set', 'Dumbbell Set', 'Barbell Set', 'Weight Plates Set', 'Medicine Ball Set',
            'Resistance Bands Set', 'Foam Roller', 'Massage Gun', 'Stretching Mat', 'Balance Ball',
            'Stationary Bike', 'Recumbent Bike', 'Spin Bike', 'Air Bike', 'Stair Climber',
            'Cross Trainer', 'Vibration Platform', 'Inversion Table', 'Roman Chair', 'Hyperextension Bench'
        ];

        $brands = ['ProFit', 'GymTech', 'FitnessPro', 'EliteGym', 'PowerMax', 'FlexFit', 'IronCore', 'FitMax', 'GymPro', 'StrengthTech'];
        $models = ['Pro', 'Elite', 'Max', 'Plus', 'Deluxe', 'Premium', 'Advanced', 'Commercial', 'X1', 'X2', 'X3', '2000', '3000', '5000'];
        
        $machineName = $this->faker->randomElement($gymMachines);
        $brand = $this->faker->randomElement($brands);
        $model = $this->faker->randomElement($models);
        
        $fullName = $brand . ' ' . $machineName . ' ' . $model;

        return [
            'name' => $fullName,
            'price' => $this->faker->randomFloat(2, 299.99, 4999.99),
            'short_description' => $this->faker->sentence(8),
            'long_description' => $this->faker->paragraphs(3, true),
            'image_path' => null, // Default to no image
            'category_id' => null, // Default to no category
            'stock_quantity' => $this->faker->numberBetween(0, 100),
            'low_stock_threshold' => $this->faker->numberBetween(5, 15),
            'track_inventory' => $this->faker->boolean(80), // 80% chance of tracking inventory
        ];
    }

    /**
     * Indicate that the product should have a category.
     */
    public function withCategory(): static
    {
        return $this->state(fn (array $attributes) => [
            'category_id' => Category::factory(),
        ]);
    }

    /**
     * Indicate that the product should have an image.
     */
    public function withImage(): static
    {
        return $this->state(fn (array $attributes) => [
            'image_path' => 'products/' . $this->faker->uuid() . '.jpg',
        ]);
    }
}