<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Anti-framing, sniffing and Content-Security-Policy headers on every
     * response. The CSP mode comes from config('app.csp_mode') so it can go
     * report-only -> enforce (or back) by changing an env var, no deploy.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Must run before the view renders: Vite and Ziggy read this nonce.
        $nonce = Vite::useCspNonce();

        $response = $next($request);

        $response->headers->set('Content-Security-Policy', "frame-ancestors 'none'");
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        // The QR reader (check-in) needs the camera on our own origin.
        $response->headers->set('Permissions-Policy', 'camera=(self), microphone=(), geolocation=()');

        // HTTPS only: browsers ignore HSTS over plain HTTP. No includeSubDomains
        // or preload: the railway.app parent domain is not ours.
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        match (config('app.csp_mode')) {
            'enforce' => $response->headers->set('Content-Security-Policy', $this->policy($nonce)),
            'report-only' => $response->headers->set('Content-Security-Policy-Report-Only', $this->policy($nonce)),
            default => null,
        };

        return $response;
    }

    /**
     * Scripts only from us (+ nonce for the inline Ziggy routes). Styles keep
     * 'unsafe-inline' because React/Radix set style attributes; that is a
     * far smaller hole than inline scripts.
     */
    private function policy(string $nonce): string
    {
        return implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}'",
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net",
            "font-src 'self' https://fonts.bunny.net data:",
            "img-src 'self' data: blob: https://*.googleusercontent.com",
            "media-src 'self' blob:",
            "connect-src 'self'",
            "worker-src 'self' blob:",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
        ]);
    }
}
