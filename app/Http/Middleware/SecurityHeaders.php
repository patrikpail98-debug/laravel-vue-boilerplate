<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Adds baseline security response headers on every request. These reduce
     * the blast radius of an XSS (which would otherwise be able to read the
     * bearer token from localStorage), clickjacking and MIME-sniffing.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), camera=(), microphone=(), payment=(), usb=()');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        // HSTS is only honoured (and only makes sense) over HTTPS.
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // CSP is enforced in production only: the Vite dev server needs inline
        // scripts, eval and a websocket for HMR, which a strict policy breaks.
        // The allow-list covers the app's only third-party origins - OpenStreetMap
        // map tiles and Google Analytics. Note: no 'unsafe-inline' in script-src,
        // which is what preserves the anti-token-theft value. Re-check the map and
        // analytics after any change to those integrations.
        if (app()->isProduction()) {
            $response->headers->set('Content-Security-Policy', implode('; ', [
                "default-src 'self'",
                "base-uri 'self'",
                "object-src 'none'",
                "frame-ancestors 'self'",
                "form-action 'self'",
                "script-src 'self' https://www.googletagmanager.com",
                "style-src 'self' 'unsafe-inline'",
                "img-src 'self' data: https://*.tile.openstreetmap.org https://www.google-analytics.com",
                "font-src 'self' data:",
                "connect-src 'self' https://www.google-analytics.com https://*.analytics.google.com",
            ]));
        }

        return $response;
    }
}
