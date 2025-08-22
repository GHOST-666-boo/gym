<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default admin user if it doesn't exist
        User::firstOrCreate(
            ['email' => 'admin@gymachines.com'],
            [
                'name' => 'Admin User',
                'email' => 'admin@gymachines.com',
                'password' => Hash::make('password'),
                'is_admin' => true,
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Admin user created successfully!');
        $this->command->info('Email: admin@gymachines.com');
        $this->command->info('Password: password');
    }
}
