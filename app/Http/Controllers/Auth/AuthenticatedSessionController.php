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

        // RD2-R4: the password was just verified above. A valid remembered device for THIS user skips the
        // emailed OTP; anything else (no/unknown/expired/revoked/malformed token) falls through to the OTP.
        $trusted = app(\App\Services\TrustedDeviceService::class);
        $token = $request->cookie($trusted->cookieName());
        if ($trusted->consume($user, is_string($token) ? $token : null)) {
            $request->session()->put('otp_verified', true);
            $user->otp_code = null;
            $user->otp_expires_at = null;
            $user->save();

            $intended = $request->session()->pull('url.intended');
            if ($user->requires_password_change) {
                return redirect()->route('password.change.view');
            }

            return $intended ? redirect()->to($intended)
                : redirect($user->role === 'owner' ? route('dashboard') : route('customer.dashboard'));
        }
        if ($token !== null && $token !== '') {
            \Illuminate\Support\Facades\Cookie::queue($trusted->forgetCookie($request));
        }

        $otp = sprintf("%06d", random_int(100000, 999999));
        $user->otp_code = \Illuminate\Support\Facades\Hash::make($otp);
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
