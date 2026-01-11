<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

final class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Get a setting value by key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember('setting.'.$key, 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            return $setting?->value ?? $default;
        });
    }

    /**
     * Set a setting value.
     */
    public static function set(string $key, mixed $value): void
    {
        self::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
        Cache::forget('setting.'.$key);
    }

    /**
     * Get multiple settings at once.
     */
    public static function getMany(array $keys): array
    {
        $settings = self::whereIn('key', $keys)->pluck('value', 'key')->toArray();

        return array_merge(array_fill_keys($keys, null), $settings);
    }

    /**
     * Set multiple settings at once.
     */
    public static function setMany(array $data): void
    {
        foreach ($data as $key => $value) {
            self::set($key, $value);
        }
    }

    /**
     * Check if initial setup is complete (admin user exists).
     */
    public static function isSetupComplete(): bool
    {
        return User::where('is_admin', true)->exists();
    }

    /**
     * Get CSS variables for primary color (cached).
     */
    public static function getPrimaryColorCss(): string
    {
        return Cache::remember('setting.primary_color_css', 3600, function (): string {
            $primaryColor = self::get('primary_color', config('branding.primary_color', '#6366f1'));
            $hex = mb_ltrim($primaryColor, '#');

            // Calculate darker variants for hover/active
            $r = max(0, hexdec(mb_substr($hex, 0, 2)) - 16);
            $g = max(0, hexdec(mb_substr($hex, 2, 2)) - 16);
            $b = max(0, hexdec(mb_substr($hex, 4, 2)) - 16);
            $primaryColorHover = sprintf('#%02x%02x%02x', $r, $g, $b);

            $r2 = max(0, hexdec(mb_substr($hex, 0, 2)) - 25);
            $g2 = max(0, hexdec(mb_substr($hex, 2, 2)) - 25);
            $b2 = max(0, hexdec(mb_substr($hex, 4, 2)) - 25);
            $primaryColorActive = sprintf('#%02x%02x%02x', $r2, $g2, $b2);

            return sprintf(':root{--color-primary:%s;--color-primary-hover:%s;--color-primary-active:%s;}', $primaryColor, $primaryColorHover, $primaryColorActive);
        });
    }

    /**
     * Clear all setting caches.
     */
    public static function clearCache(): void
    {
        Cache::forget('setting.primary_color');
        Cache::forget('setting.primary_color_css');
        Cache::forget('setting.app_name');
        Cache::forget('setting.logo_url');
    }
}
