<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Prevent destructive commands in production
        // This blocks: migrate:fresh, migrate:refresh, migrate:reset, db:wipe
        DB::prohibitDestructiveCommands($this->app->isProduction());

        Vite::prefetch(concurrency: 3);

        // Force URL root when APP_URL is set (for Cloudflare tunnel)
        $appUrl = config('app.url');
        if ($appUrl && str_starts_with((string) $appUrl, 'https://')) {
            URL::forceRootUrl($appUrl);
            URL::forceScheme('https');
        } elseif (request()->header('X-Forwarded-Proto') === 'https') {
            URL::forceScheme('https');
        }

        // Define admin gate
        Gate::define('admin', fn ($user): bool => $user->is_admin === true);
    }
}
