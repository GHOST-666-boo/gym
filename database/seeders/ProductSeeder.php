<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure categories exist first
        $cardioCategory = Category::where('name', 'Cardio Equipment')->first();
        $strengthCategory = Category::where('name', 'Strength Training')->first();
        $freeWeightsCategory = Category::where('name', 'Free Weights')->first();
        $functionalCategory = Category::where('name', 'Functional Training')->first();
        $recoveryCategory = Category::where('name', 'Recovery & Wellness')->first();
        $commercialCategory = Category::where('name', 'Commercial Grade')->first();

        $products = [
            // Cardio Equipment
            [
                'name' => 'ProFit Elite Treadmill X1',
                'price' => 2999.99,
                'short_description' => 'Professional-grade treadmill with 4.0 HP motor, 22" touchscreen, and advanced cushioning system.',
                'long_description' => 'Experience the ultimate in cardio training with the ProFit Elite Treadmill X1. Featuring a powerful 4.0 HP continuous duty motor, this treadmill delivers smooth, quiet operation even during intense workouts. The 22" HD touchscreen provides access to thousands of on-demand classes and scenic routes. Advanced FlexDeck cushioning reduces impact by up to 40% compared to running on asphalt, while the spacious 22" x 60" running surface accommodates users of all sizes. With speeds up to 12 mph and inclines up to 15%, this treadmill offers unlimited training possibilities.',
                'category_id' => $cardioCategory?->id,
            ],
            [
                'name' => 'GymTech Elliptical Pro 500',
                'price' => 1899.99,
                'short_description' => 'Commercial-quality elliptical with 20" stride length and upper body moving handlebars.',
                'long_description' => 'The GymTech Elliptical Pro 500 delivers a complete full-body workout with its natural 20" stride length and synchronized upper body movement. The precision-engineered flywheel provides smooth, consistent resistance across 25 levels, while the ergonomic design ensures proper body alignment throughout your workout. Features include heart rate monitoring, 12 preset programs, and a clear LCD display tracking time, distance, calories, and resistance level.',
                'category_id' => $cardioCategory?->id,
            ],
            [
                'name' => 'FlexFit Rowing Machine R2000',
                'price' => 1299.99,
                'short_description' => 'Air and magnetic resistance rowing machine with performance monitor and foldable design.',
                'long_description' => 'Transform your fitness with the FlexFit Rowing Machine R2000, combining air and magnetic resistance for the most realistic rowing experience. The dual resistance system provides smooth, consistent feel while the performance monitor tracks stroke rate, distance, time, and calories burned. The ergonomic seat and handle design ensure comfort during long sessions, while the space-saving foldable frame makes storage convenient.',
                'category_id' => $cardioCategory?->id,
            ],

            // Strength Training
            [
                'name' => 'PowerMax Leg Press Station',
                'price' => 3499.99,
                'short_description' => 'Heavy-duty 45-degree leg press with 1000 lb weight capacity and safety features.',
                'long_description' => 'Build powerful leg muscles safely with the PowerMax Leg Press Station. This 45-degree angled machine features a smooth linear bearing system that supports up to 1000 lbs of weight plates. The extra-large foot plate accommodates various foot positions for targeting different muscle groups, while the adjustable back pad ensures proper positioning. Safety handles and stops provide confidence during heavy lifting sessions.',
                'category_id' => $strengthCategory?->id,
            ],
            [
                'name' => 'EliteGym Cable Crossover System',
                'price' => 4999.99,
                'short_description' => 'Dual-stack cable system with 200 lb weight stacks and multiple attachment points.',
                'long_description' => 'Maximize training versatility with the EliteGym Cable Crossover System. Featuring dual 200 lb weight stacks with 10 lb increments, this system offers unlimited exercise possibilities. The adjustable pulleys move smoothly on precision linear bearings, while multiple attachment points allow for functional training, strength building, and rehabilitation exercises. Includes lat pulldown bar, low row handle, and tricep rope.',
                'category_id' => $strengthCategory?->id,
            ],
            [
                'name' => 'FitnessPro Smith Machine Deluxe',
                'price' => 2799.99,
                'short_description' => 'Multi-station Smith machine with safety stops, pull-up bar, and adjustable bench.',
                'long_description' => 'Train with confidence using the FitnessPro Smith Machine Deluxe. The guided barbell system with linear bearings provides smooth vertical movement while safety stops every 2 inches ensure maximum security. The integrated pull-up bar, dip handles, and included adjustable bench create a complete training station. Perfect for squats, bench press, shoulder press, and countless other exercises.',
                'category_id' => $strengthCategory?->id,
            ],

            // Free Weights
            [
                'name' => 'ProFit Olympic Barbell Set',
                'price' => 899.99,
                'short_description' => '45 lb Olympic barbell with 300 lb rubber-coated weight plate set and collars.',
                'long_description' => 'Complete your home gym with the ProFit Olympic Barbell Set. The 7-foot, 45 lb barbell features knurled grips and rotating sleeves for smooth lifting. Includes 300 lbs of rubber-coated weight plates (2x45, 2x35, 2x25, 2x10, 4x5 lb plates) that protect floors and reduce noise. Heavy-duty spring collars ensure plates stay secure during intense workouts.',
                'category_id' => $freeWeightsCategory?->id,
            ],
            [
                'name' => 'GymTech Adjustable Dumbbell Set',
                'price' => 599.99,
                'short_description' => 'Space-saving adjustable dumbbells with quick-change system, 5-50 lbs per hand.',
                'long_description' => 'Revolutionize your strength training with the GymTech Adjustable Dumbbell Set. Each dumbbell adjusts from 5 to 50 lbs in 5 lb increments using the innovative dial system. Simply turn the dial to select your weight and lift - the remaining plates stay in the tray. Compact design replaces 15 sets of traditional dumbbells, perfect for home gyms with limited space.',
                'category_id' => $freeWeightsCategory?->id,
            ],

            // Functional Training
            [
                'name' => 'FlexFit Suspension Trainer Pro',
                'price' => 199.99,
                'short_description' => 'Professional suspension training system with door anchor and exercise guide.',
                'long_description' => 'Take your functional training anywhere with the FlexFit Suspension Trainer Pro. This versatile system uses body weight and gravity to provide a complete workout targeting strength, balance, flexibility, and core stability. Includes door anchor, suspension anchor, and comprehensive exercise guide with over 100 exercises. Suitable for all fitness levels from beginner to elite athlete.',
                'category_id' => $functionalCategory?->id,
            ],
            [
                'name' => 'PowerMax Kettlebell Set',
                'price' => 449.99,
                'short_description' => 'Cast iron kettlebell set with wide handles and flat bottoms, 15-50 lb range.',
                'long_description' => 'Enhance your functional strength with the PowerMax Kettlebell Set. This 5-piece set includes 15, 20, 25, 35, and 50 lb kettlebells made from solid cast iron with wide, comfortable handles. The flat bottom design prevents rolling and allows for push-ups and other floor exercises. Perfect for swings, snatches, Turkish get-ups, and countless other dynamic movements.',
                'category_id' => $functionalCategory?->id,
            ],

            // Recovery & Wellness
            [
                'name' => 'EliteGym Massage Gun Pro',
                'price' => 299.99,
                'short_description' => 'Percussive therapy device with 6 speed settings and 4 interchangeable heads.',
                'long_description' => 'Accelerate recovery with the EliteGym Massage Gun Pro. This powerful percussive therapy device delivers up to 3200 percussions per minute across 6 speed settings. Four interchangeable massage heads target different muscle groups and treatment needs. The quiet motor and ergonomic design make it perfect for pre-workout activation and post-workout recovery.',
                'category_id' => $recoveryCategory?->id,
            ],

            // Commercial Grade
            [
                'name' => 'CommercialFit Treadmill C7000',
                'price' => 7999.99,
                'short_description' => 'Commercial-grade treadmill with 5.0 HP motor, built for 24/7 gym operation.',
                'long_description' => 'The CommercialFit Treadmill C7000 is engineered for the demands of commercial fitness facilities. Featuring a robust 5.0 HP AC motor, this treadmill can handle continuous operation in high-traffic environments. The extra-wide 22" x 62" running surface accommodates users up to 400 lbs, while the advanced suspension system provides superior shock absorption. Built-in entertainment options and multiple user profiles make it perfect for any commercial setting.',
                'category_id' => $commercialCategory?->id,
            ],
        ];

        foreach ($products as $productData) {
            Product::firstOrCreate(
                ['name' => $productData['name']],
                $productData
            );
        }

        // Create additional random products using factories
        if ($cardioCategory) {
            Product::factory(5)->withCategory()->create(['category_id' => $cardioCategory->id]);
        }
        if ($strengthCategory) {
            Product::factory(5)->withCategory()->create(['category_id' => $strengthCategory->id]);
        }
        if ($freeWeightsCategory) {
            Product::factory(3)->withCategory()->create(['category_id' => $freeWeightsCategory->id]);
        }

        $this->command->info('Products seeded successfully!');
        $this->command->info('Created ' . Product::count() . ' total products across ' . Category::count() . ' categories.');
    }
}