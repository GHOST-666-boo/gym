<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\QuoteRequest;
use App\Models\Product;
use Carbon\Carbon;

class FakeQuoteRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some products for fake quotes
        $products = Product::take(10)->get();
        
        if ($products->isEmpty()) {
            $this->command->info('No products found. Please seed products first.');
            return;
        }

        $statuses = ['pending', 'processing', 'quoted', 'completed'];
        $companies = [
            'Fitness First Gym',
            'PowerHouse Fitness',
            'Elite Training Center',
            'Iron Paradise Gym',
            'Muscle Factory',
            'Strength & Conditioning Co.',
            'FitZone Wellness',
            'Athletic Performance Center',
            'Body Building Academy',
            'CrossFit Revolution'
        ];

        $customers = [
            ['name' => 'John Smith', 'email' => 'john.smith@fitnessfirst.com', 'phone' => '+1-555-0101'],
            ['name' => 'Sarah Johnson', 'email' => 'sarah.j@powerhouse.com', 'phone' => '+1-555-0102'],
            ['name' => 'Mike Wilson', 'email' => 'mike.wilson@elitetraining.com', 'phone' => '+1-555-0103'],
            ['name' => 'Emily Davis', 'email' => 'emily.davis@ironparadise.com', 'phone' => '+1-555-0104'],
            ['name' => 'David Brown', 'email' => 'david.brown@musclefactory.com', 'phone' => '+1-555-0105'],
            ['name' => 'Lisa Anderson', 'email' => 'lisa.anderson@strengthco.com', 'phone' => '+1-555-0106'],
            ['name' => 'Robert Taylor', 'email' => 'robert.taylor@fitzone.com', 'phone' => '+1-555-0107'],
            ['name' => 'Jennifer Martinez', 'email' => 'jennifer.m@athleticperf.com', 'phone' => '+1-555-0108'],
            ['name' => 'Chris Lee', 'email' => 'chris.lee@bodybuilding.com', 'phone' => '+1-555-0109'],
            ['name' => 'Amanda White', 'email' => 'amanda.white@crossfitrev.com', 'phone' => '+1-555-0110']
        ];

        $messages = [
            'Looking to upgrade our gym equipment. Need bulk pricing for multiple units.',
            'Interested in commercial-grade equipment for our new fitness center.',
            'Please provide installation and warranty details with the quote.',
            'Need equipment for our corporate wellness program. Budget is flexible.',
            'Expanding our gym facility. Looking for durable, professional equipment.',
            'Interested in financing options. Please include in your quote.',
            'Need delivery to multiple locations. Can you accommodate?',
            'Looking for equipment package deals for complete gym setup.',
            'Require maintenance contract along with equipment purchase.',
            'Need quote for replacement of existing equipment. Urgent requirement.'
        ];

        // Create 25 fake quote requests
        for ($i = 0; $i < 25; $i++) {
            $customer = $customers[array_rand($customers)];
            $company = $companies[array_rand($companies)];
            $status = $statuses[array_rand($statuses)];
            $message = $messages[array_rand($messages)];
            
            // Random number of products (1-4)
            $numProducts = rand(1, 4);
            $selectedProducts = $products->random($numProducts);
            
            $productData = [];
            $totalAmount = 0;
            
            foreach ($selectedProducts as $product) {
                $quantity = rand(1, 10);
                $total = $product->price * $quantity;
                $totalAmount += $total;
                
                $productData[] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'quantity' => $quantity,
                    'price' => $product->price,
                    'total' => $total,
                    'category' => $product->category?->name,
                    'slug' => $product->slug,
                ];
            }
            
            // Random date within last 30 days
            $createdAt = Carbon::now()->subDays(rand(0, 30))->subHours(rand(0, 23))->subMinutes(rand(0, 59));
            
            $quoteRequest = QuoteRequest::create([
                'name' => $customer['name'],
                'email' => $customer['email'],
                'phone' => $customer['phone'],
                'company' => $company,
                'message' => $message,
                'products' => $productData,
                'total_amount' => $totalAmount,
                'status' => $status,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
            
            // Add quoted_at and expires_at for quoted/completed status
            if (in_array($status, ['quoted', 'completed'])) {
                $quotedAt = $createdAt->copy()->addHours(rand(2, 48));
                $expiresAt = $quotedAt->copy()->addDays(rand(7, 30));
                
                $quoteRequest->update([
                    'quoted_at' => $quotedAt,
                    'expires_at' => $expiresAt,
                ]);
            }
        }

        $this->command->info('Created 25 fake quote requests successfully!');
    }
}