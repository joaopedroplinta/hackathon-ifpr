<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Anti-framing and sniffing headers on every web response.
     *
     * A full CSP is deliberately not set here: it needs a report-only run
     * against the real Vite/Inertia assets first, or it breaks the app.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Content-Security-Policy', "frame-ancestors 'none'");
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        // The QR reader (check-in) needs the camera on our own origin.
        $response->headers->set('Permissions-Policy', 'camera=(self), microphone=(), geolocation=()');

        return $response;
    }
}
