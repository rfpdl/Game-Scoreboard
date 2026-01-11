<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds test users for e2e testing with Cypress.
 *
 * Run with: php artisan db:seed --class=TestUserSeeder
 */
final class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'is_admin' => true,
                'email_verified_at' => now(),
            ]
        );

        // Create Player A for e2e tests
        User::firstOrCreate(
            ['email' => 'player.a@example.com'],
            [
                'name' => 'Player A',
                'password' => Hash::make('password'),
                'is_admin' => false,
                'email_verified_at' => now(),
            ]
        );

        // Create Player B for e2e tests
        User::firstOrCreate(
            ['email' => 'player.b@example.com'],
            [
                'name' => 'Player B',
                'password' => Hash::make('password'),
                'is_admin' => false,
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Test users seeded successfully!');
        $this->command->info('- admin@example.com (password: password)');
        $this->command->info('- player.a@example.com (password: password)');
        $this->command->info('- player.b@example.com (password: password)');
    }
}
