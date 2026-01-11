<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Use custom CSRF verification middleware
        $middleware->web(replace: [
            Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class => App\Http\Middleware\VerifyCsrfToken::class,
        ]);

        $middleware->web(append: [
            App\Http\Middleware\HandleInertiaRequests::class,
            Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            App\Http\Middleware\EnsureSetupComplete::class,
            App\Http\Middleware\EnsureUserNotDisabled::class,
        ]);

        // Alias for admin middleware
        $middleware->alias([
            'admin' => App\Http\Middleware\AdminMiddleware::class,
        ]);

        // Trust all proxies (Cloudflare tunnel)
        $middleware->trustProxies(at: '*', headers: Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
            Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
            Illuminate\Http\Request::HEADER_X_FORWARDED_PORT |
            Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO |
            Illuminate\Http\Request::HEADER_X_FORWARDED_AWS_ELB
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
