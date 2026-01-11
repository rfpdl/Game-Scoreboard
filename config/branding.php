<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | The name of the application, displayed in the header and title.
    |
    */

    'name' => env('APP_NAME', 'Game Scoreboard'),

    /*
    |--------------------------------------------------------------------------
    | Logo URL
    |--------------------------------------------------------------------------
    |
    | URL to a custom logo image. If empty, a text-based logo using the
    | app name will be displayed. Can be a full URL or path relative to public.
    | Examples:
    |   - "/images/logo.png" (file in public/images)
    |   - "https://example.com/logo.png" (external URL)
    |
    */

    'logo_url' => env('APP_LOGO_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | Primary Color
    |--------------------------------------------------------------------------
    |
    | The primary brand color used for buttons, accents, and highlights.
    | Must be a valid hex color code including the # symbol.
    |
    */

    'primary_color' => env('APP_PRIMARY_COLOR', '#f97316'),

];
