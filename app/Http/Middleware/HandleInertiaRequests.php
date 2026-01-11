<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Middleware;

final class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'branding' => [
                'name' => Setting::get('app_name', config('branding.name')),
                'logoUrl' => Setting::get('logo_url', config('branding.logo_url')),
                'primaryColor' => Setting::get('primary_color', config('branding.primary_color')),
            ],
            'registrationEnabled' => Setting::get('registration_enabled', true),
            'colorMode' => Setting::get('color_mode', 'light'),
        ];
    }
}
