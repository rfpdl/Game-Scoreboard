<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureSetupComplete
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip if we're already on the install route
        if ($request->routeIs('install.*')) {
            return $next($request);
        }

        // Redirect to install if no admin exists
        if (! User::where('is_admin', true)->exists()) {
            return redirect()->route('install.index');
        }

        return $next($request);
    }
}
