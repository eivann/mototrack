<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $intended = $request->session()->pull('url.intended');
        $default = $request->user()->role === 'owner'
            ? route('dashboard', absolute: false)
            : route('customer.dashboard', absolute: false);

        if ($intended && $safeIntendedPath = $this->safeIntendedPath($intended)) {
            return redirect()->to($safeIntendedPath);
        }

        $request->session()->forget('url.intended');

        if ($request->user()->role === 'owner') {
            return redirect($default);
        }

        return redirect($default);
    }

    private function safeIntendedPath(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: $url;
        $path = '/'.ltrim($path, '/');
        $query = parse_url($url, PHP_URL_QUERY);

        if (! str_starts_with($path, '/checkout')) {
            return null;
        }

        return $query ? "{$path}?{$query}" : $path;
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
