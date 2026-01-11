<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Symfony\Component\HttpFoundation\Cookie;

final class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        //
    ];

    /**
     * Create a new "XSRF-TOKEN" cookie that contains the CSRF token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $config
     * @return Cookie
     */
    protected function newCookie($request, $config)
    {
        return new Cookie(
            'XSRF-TOKEN',
            $request->session()->token(),
            $this->availableAt(60 * $config['lifetime']),
            $config['path'],
            $config['domain'], // Use the same domain as session
            $config['secure'],
            false, // Not HTTP-only so JavaScript can read it
            false,
            $config['same_site'] ?? 'lax'
        );
    }
}
