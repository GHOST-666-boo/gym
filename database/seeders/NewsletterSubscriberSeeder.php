<?php

namespace Database\Seeders;

use App\Models\NewsletterSubscriber;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NewsletterSubscriberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create active subscribers with names
        NewsletterSubscriber::factory()
            ->count(25)
            ->active()
            ->withName()
            ->create();

        // Create active subscribers without names
        NewsletterSubscriber::factory()
            ->count(15)
            ->active()
            ->withoutName()
            ->create();

        // Create inactive subscribers
        NewsletterSubscriber::factory()
            ->count(8)
            ->inactive()
            ->withName()
            ->create();

        // Create some recent subscribers (last 7 days)
        NewsletterSubscriber::factory()
            ->count(5)
            ->active()
            ->withName()
            ->state([
                'subscribed_at' => now()->subDays(rand(1, 7)),
            ])
            ->create();

        $this->command->info('Newsletter subscribers seeded successfully!');
        $this->command->info('Total active subscribers: ' . NewsletterSubscriber::active()->count());
        $this->command->info('Total inactive subscribers: ' . NewsletterSubscriber::inactive()->count());
    }
}
