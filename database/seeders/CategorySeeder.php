<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Cardio Equipment',
                'description' => 'Machines designed to improve cardiovascular health and endurance through aerobic exercise. Perfect for burning calories and building stamina.',
            ],
            [
                'name' => 'Strength Training',
                'description' => 'Equipment focused on building muscle strength and power through resistance training. Ideal for muscle development and toning.',
            ],
            [
                'name' => 'Free Weights',
                'description' => 'Traditional weights and accessories for versatile strength and conditioning workouts. Essential for functional strength building.',
            ],
            [
                'name' => 'Functional Training',
                'description' => 'Equipment designed for functional movement patterns and athletic performance. Great for improving daily movement quality.',
            ],
            [
                'name' => 'Recovery & Wellness',
                'description' => 'Tools and machines to aid in muscle recovery and overall wellness. Important for injury prevention and rehabilitation.',
            ],
            [
                'name' => 'Commercial Grade',
                'description' => 'Heavy-duty equipment designed for high-traffic commercial gym environments. Built to withstand intensive daily use.',
            ],
        ];

        foreach ($categories as $categoryData) {
            Category::firstOrCreate(
                ['name' => $categoryData['name']],
                $categoryData
            );
        }

        $this->command->info('Categories seeded successfully!');
    }
}