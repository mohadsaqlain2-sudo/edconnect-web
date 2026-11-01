<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline security headers for every web response (SRS 5.2).
 * The Jitsi room needs a different policy because it embeds meet.jit.si.
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

        $isJitsiRoom = $request->routeIs('classes.room');
        $jitsiDomain = config('services.jitsi.domain', 'meet.jit.si');

        if ($isJitsiRoom) {
            // The video room is embedded from another origin, so the page must
            // not forbid framing and must allow camera + microphone there.
            $response->headers->remove('X-Frame-Options');
            $response->headers->set(
                'Content-Security-Policy',
                "frame-src 'self' https://{$jitsiDomain};"
            );
            $response->headers->set(
                'Permissions-Policy',
                "camera=(self \"https://{$jitsiDomain}\"), ".
                "microphone=(self \"https://{$jitsiDomain}\"), ".
                "display-capture=(self \"https://{$jitsiDomain}\")"
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
