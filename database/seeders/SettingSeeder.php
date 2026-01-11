<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

final class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'app_name' => config('branding.name', 'Game Scoreboard'),
            'primary_color' => '#f97316', // Orange
            // 'logo_url' => null,
        ];

        foreach ($settings as $key => $value) {
            Setting::set($key, $value);
        }
    }
}
