<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

final class CreateAdminCommand extends Command
{
    protected $signature = 'admin:create
                            {--email= : Admin email address}
                            {--password= : Admin password}
                            {--name= : Admin name}';

    protected $description = 'Create or reset an admin user';

    public function handle(): int
    {
        $email = $this->option('email') ?? $this->ask('Email address');
        $name = $this->option('name') ?? $this->ask('Name', 'Admin');
        $password = $this->option('password') ?? $this->secret('Password');

        if (! $email || ! $password) {
            $this->error('Email and password are required.');

            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            if (! $this->confirm("User {$email} already exists. Reset password and make admin?")) {
                $this->info('Cancelled.');

                return self::SUCCESS;
            }

            $user->update([
                'password' => Hash::make($password),
                'is_admin' => true,
                'is_disabled' => false,
            ]);

            $this->info("User {$email} updated as admin.");
        } else {
            User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'is_admin' => true,
                'email_verified_at' => now(),
            ]);

            $this->info("Admin user {$email} created.");
        }

        return self::SUCCESS;
    }
}
