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
        if ($intended && $safeIntendedPath = $this->safeIntendedPath($intended)) {
            $request->session()->put('url.intended', $safeIntendedPath);
        }

        $user = $request->user();
        $otp = sprintf("%06d", mt_rand(100000, 999999));
        $user->otp_code = $otp;
        $user->otp_expires_at = now()->addMinutes(10);
        $user->save();

        try {
            \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\OtpMail($otp));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('OTP Email failed: ' . $e->getMessage());
        }

        $request->session()->put('otp_verified', false);

        return redirect()->route('otp.verify');
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
