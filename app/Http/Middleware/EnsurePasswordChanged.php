<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->requires_password_change) {
            if (!$request->routeIs('password.change.*') && !$request->routeIs('logout')) {
                return redirect()->route('password.change.view');
            }
        }

        return $next($request);
    }
}
