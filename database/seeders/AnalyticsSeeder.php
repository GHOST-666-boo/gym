<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AnalyticsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = \App\Models\Product::all();
        
        if ($products->isEmpty()) {
            $this->command->info('No products found. Please run ProductSeeder first.');
            return;
        }

        // Generate product views for the last 30 days
        $this->generateProductViews($products);
        
        // Generate contact submissions for the last 30 days
        $this->generateContactSubmissions();
        
        $this->command->info('Analytics test data created successfully!');
    }

    private function generateProductViews($products)
    {
        $ips = [
            '192.168.1.1', '10.0.0.1', '172.16.0.1', '203.0.113.1', '198.51.100.1',
            '192.0.2.1', '203.0.113.2', '198.51.100.2', '192.168.1.2', '10.0.0.2'
        ];
        
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1',
            'Mozilla/5.0 (iPad; CPU OS 14_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1'
        ];

        for ($day = 30; $day >= 0; $day--) {
            $date = \Carbon\Carbon::now()->subDays($day);
            
            // Generate 10-50 views per day with some randomness
            $viewsCount = rand(10, 50);
            
            for ($i = 0; $i < $viewsCount; $i++) {
                $product = $products->random();
                $ip = $ips[array_rand($ips)];
                $userAgent = $userAgents[array_rand($userAgents)];
                
                // Random time during the day
                $viewTime = $date->copy()->addHours(rand(0, 23))->addMinutes(rand(0, 59));
                
                \App\Models\ProductView::create([
                    'product_id' => $product->id,
                    'ip_address' => $ip,
                    'user_agent' => $userAgent,
                    'referrer' => rand(0, 1) ? 'https://google.com' : null,
                    'session_id' => 'session_' . uniqid(),
                    'viewed_at' => $viewTime,
                ]);
            }
        }
    }

    private function generateContactSubmissions()
    {
        $names = ['John Doe', 'Jane Smith', 'Mike Johnson', 'Sarah Wilson', 'David Brown', 'Lisa Davis', 'Tom Miller', 'Amy Garcia'];
        $domains = ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'example.com'];
        $messages = [
            'I am interested in learning more about your gym equipment.',
            'Could you provide more information about pricing and availability?',
            'I would like to schedule a demonstration of your machines.',
            'Do you offer installation services for your equipment?',
            'What warranty do you provide with your gym machines?',
            'I am opening a new gym and need equipment recommendations.',
            'Can you provide bulk pricing for multiple machines?',
            'I need technical support for one of your machines.'
        ];

        for ($day = 30; $day >= 0; $day--) {
            $date = \Carbon\Carbon::now()->subDays($day);
            
            // Generate 0-5 contact submissions per day
            $contactsCount = rand(0, 5);
            
            for ($i = 0; $i < $contactsCount; $i++) {
                $name = $names[array_rand($names)];
                $email = strtolower(str_replace(' ', '.', $name)) . '@' . $domains[array_rand($domains)];
                $message = $messages[array_rand($messages)];
                
                // Random time during the day
                $submitTime = $date->copy()->addHours(rand(8, 18))->addMinutes(rand(0, 59));
                
                // 90% success rate for email sending
                $emailSent = rand(1, 10) <= 9;
                
                \App\Models\ContactSubmission::create([
                    'name' => $name,
                    'email' => $email,
                    'message' => $message,
                    'ip_address' => '192.168.1.' . rand(1, 255),
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'referrer' => rand(0, 1) ? 'https://google.com' : null,
                    'email_sent' => $emailSent,
                    'submitted_at' => $submitTime,
                ]);
            }
        }
    }
}
