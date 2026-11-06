<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline security headers for every web response (SRS 5.2).
 * The live room is our own page, so it only needs to allow the camera and the
 * microphone on this origin; framing stays locked down everywhere.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-XSS-Protection', '0');
        $response->headers->remove('X-Powered-By');

        // The room page and its own signaling API. Nothing is embedded from
        // another origin any more, so the framing rules stay as they are.
        $isLiveRoom = $request->routeIs('classes.room') || $request->routeIs('live.*');

        if ($isLiveRoom) {
            // WebRTC needs the camera, the microphone and screen capture, but
            // only on this origin: there is no external video server left.
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
            $response->headers->set(
                'Permissions-Policy',
                'camera=(self), microphone=(self), display-capture=(self)'
            );
        } else {
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
            $response->headers->set(
                'Permissions-Policy',
                'camera=(), microphone=(), geolocation=()'
            );
        }

        if (app()->isProduction() && $request->isSecure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        return $response;
    }
}
