<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

final class SettingsController extends Controller
{
    /**
     * Show the settings page.
     */
    public function index(): Response
    {
        return Inertia::render('Admin/Settings', [
            'settings' => [
                'appName' => Setting::get('app_name', config('branding.name')),
                'primaryColor' => Setting::get('primary_color', config('branding.primary_color')),
                'logoUrl' => Setting::get('logo_url', config('branding.logo_url')),
                'registrationEnabled' => Setting::get('registration_enabled', true),
                'colorMode' => Setting::get('color_mode', 'light'),
                'streakMultiplierEnabled' => Setting::get('streak_multiplier_enabled', false),
            ],
        ]);
    }

    /**
     * Update settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'appName' => 'required|string|max:255',
            'primaryColor' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'registrationEnabled' => 'required|boolean',
            'colorMode' => 'required|string|in:light,dark,system',
            'streakMultiplierEnabled' => 'required|boolean',
        ]);

        $oldValues = [
            'app_name' => Setting::get('app_name'),
            'primary_color' => Setting::get('primary_color'),
            'registration_enabled' => Setting::get('registration_enabled'),
            'color_mode' => Setting::get('color_mode'),
            'streak_multiplier_enabled' => Setting::get('streak_multiplier_enabled'),
        ];

        Setting::set('app_name', $validated['appName']);
        Setting::set('primary_color', $validated['primaryColor']);
        Setting::set('registration_enabled', $validated['registrationEnabled']);
        Setting::set('color_mode', $validated['colorMode']);
        Setting::set('streak_multiplier_enabled', $validated['streakMultiplierEnabled']);

        // Clear the computed CSS cache
        Cache::forget('setting.primary_color_css');

        ActivityLog::log(
            userId: $request->user()->id,
            action: 'updated',
            subjectType: 'settings',
            subjectName: 'App Settings',
            changes: [
                'old' => $oldValues,
                'new' => [
                    'app_name' => $validated['appName'],
                    'primary_color' => $validated['primaryColor'],
                    'registration_enabled' => $validated['registrationEnabled'],
                    'color_mode' => $validated['colorMode'],
                    'streak_multiplier_enabled' => $validated['streakMultiplierEnabled'],
                ],
            ],
            ipAddress: $request->ip(),
        );

        return redirect()->back()->with('success', 'Settings updated.');
    }

    /**
     * Upload logo.
     */
    public function uploadLogo(Request $request): RedirectResponse
    {
        $request->validate([
            'logo' => 'required|file|mimes:png,jpg,jpeg,svg,webp|max:2048',
        ]);

        // Delete old logo
        $currentLogo = Setting::get('logo_url');
        if ($currentLogo && str_starts_with((string) $currentLogo, '/storage/uploads/')) {
            $path = str_replace('/storage/', '', $currentLogo);
            Storage::disk('public')->delete($path);
        }

        $path = $request->file('logo')->store('uploads', 'public');
        Setting::set('logo_url', '/storage/'.$path);

        ActivityLog::log(
            userId: $request->user()->id,
            action: 'uploaded',
            subjectType: 'settings',
            subjectName: 'Logo',
            ipAddress: $request->ip(),
        );

        return redirect()->back()->with('success', 'Logo updated.');
    }

    /**
     * Remove logo.
     */
    public function removeLogo(Request $request): RedirectResponse
    {
        $currentLogo = Setting::get('logo_url');
        if ($currentLogo && str_starts_with((string) $currentLogo, '/storage/uploads/')) {
            $path = str_replace('/storage/', '', $currentLogo);
            Storage::disk('public')->delete($path);
        }

        Setting::set('logo_url', null);

        ActivityLog::log(
            userId: $request->user()->id,
            action: 'removed',
            subjectType: 'settings',
            subjectName: 'Logo',
            ipAddress: $request->ip(),
        );

        return redirect()->back()->with('success', 'Logo removed.');
    }
}
