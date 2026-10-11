<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOtpVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && !$request->session()->get('otp_verified', false)) {
            if (!$request->is('otp*') && !$request->routeIs('otp.*') && !$request->routeIs('logout')) {
                return redirect()->route('otp.verify');
            }
        }

        return $next($request);
    }
}
