<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds development users for local testing.
 */
final class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin',
                'email' => 'admin@test.com',
                'password' => 'admin@test.com',
                'is_admin' => true,
            ],
            [
                'name' => 'Roy',
                'email' => 'roy@test.com',
                'password' => 'roy@test.com',
                'is_admin' => false,
            ],
            [
                'name' => 'Ben',
                'email' => 'ben@test.com',
                'password' => 'ben@test.com',
                'is_admin' => false,
            ],
            [
                'name' => 'Alex',
                'email' => 'alex@test.com',
                'password' => 'alex@test.com',
                'is_admin' => false,
            ],
        ];

        foreach ($users as $user) {
            User::firstOrCreate(
                ['email' => $user['email']],
                $user
            );
        }
    }
}
