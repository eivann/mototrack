<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Don't apply to binary/file responses
        if (method_exists($response, 'header')) {
            $response->header('X-Frame-Options', 'SAMEORIGIN');
            $response->header('X-Content-Type-Options', 'nosniff');
            $response->header('Referrer-Policy', 'strict-origin-when-cross-origin');
            $response->header('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
            
            // Content Security Policy
            // Allows scripts from unpkg (lucide icons) and recaptcha
            $csp = "default-src 'self'; " .
                   "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://unpkg.com https://www.google.com https://www.gstatic.com https://cdn.jsdelivr.net; " .
                   "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
                   "font-src 'self' https://fonts.gstatic.com; " .
                   "img-src 'self' data: https:; " .
                   "frame-src https://www.google.com https://www.recaptcha.net; " .
                   "media-src 'self' https://assets.mixkit.co; " .
                   "connect-src 'self' wss: https:;";
            
            $response->header('Content-Security-Policy', $csp);

            // SYS4-07: HSTS, production only (browsers ignore it over HTTP, and local/Laragon/testing
            // must never pin a development host to HTTPS). No 'preload' directive on purpose.
            if (app()->environment('production')) {
                $response->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
            }
        }

        return $response;
    }
}
