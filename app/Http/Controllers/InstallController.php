<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

final class InstallController extends Controller
{
    /**
     * Show the install wizard.
     */
    public function index(): Response|RedirectResponse
    {
        // Redirect away if setup is already complete
        if (User::where('is_admin', true)->exists()) {
            return redirect()->route('login');
        }

        return Inertia::render('Install/Index', [
            'settings' => [
                'appName' => Setting::get('app_name', config('branding.name')),
                'primaryColor' => Setting::get('primary_color', config('branding.primary_color')),
                'logoUrl' => Setting::get('logo_url', config('branding.logo_url')),
            ],
        ]);
    }

    /**
     * Store branding settings.
     */
    public function storeSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'appName' => 'required|string|max:255',
            'primaryColor' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        Setting::set('app_name', $validated['appName']);
        Setting::set('primary_color', $validated['primaryColor']);

        return redirect()->route('install.index');
    }

    /**
     * Store logo.
     */
    public function storeLogo(Request $request): RedirectResponse
    {
        $request->validate([
            'logo' => 'required|image|mimes:png,jpg,jpeg,svg,webp|max:2048',
        ]);

        // Delete old logo if exists
        $currentLogo = Setting::get('logo_url');
        if ($currentLogo && str_starts_with((string) $currentLogo, '/storage/uploads/')) {
            $path = str_replace('/storage/', '', $currentLogo);
            Storage::disk('public')->delete($path);
        }

        $path = $request->file('logo')->store('uploads', 'public');
        Setting::set('logo_url', '/storage/'.$path);

        return redirect()->route('install.index');
    }

    /**
     * Remove logo.
     */
    public function removeLogo(): RedirectResponse
    {
        $currentLogo = Setting::get('logo_url');
        if ($currentLogo && str_starts_with((string) $currentLogo, '/storage/uploads/')) {
            $path = str_replace('/storage/', '', $currentLogo);
            Storage::disk('public')->delete($path);
        }

        Setting::set('logo_url', null);

        return redirect()->route('install.index');
    }

    /**
     * Create the first admin user.
     */
    public function storeAdmin(Request $request): RedirectResponse
    {
        // Prevent creating admin if one already exists
        if (User::where('is_admin', true)->exists()) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        return redirect()->route('login')->with('success', 'Setup complete! Please log in.');
    }
}
