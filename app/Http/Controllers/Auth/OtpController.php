<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OtpController extends Controller
{
    public function show(Request $request)
    {
        if ($request->session()->get('otp_verified', false)) {
            return redirect('/');
        }
        return view('auth.otp-verify');
    }

    public function verify(Request $request)
    {
        $request->validate(['otp_code' => 'required|string']);

        $user = auth()->user();

        if (\Illuminate\Support\Facades\Hash::check($request->otp_code, $user->otp_code) && now()->lessThanOrEqualTo($user->otp_expires_at)) {
            $request->session()->put('otp_verified', true);
            $user->otp_code = null;
            $user->otp_expires_at = null;
            $user->save();

            $intended = $request->session()->pull('url.intended');
            
            if ($user->requires_password_change) {
                return redirect()->route('password.change.view');
            }

            if ($intended) {
                return redirect()->to($intended);
            }

            return redirect($user->role === 'owner' ? route('dashboard') : route('customer.dashboard'));
        }

        return back()->withErrors(['otp_code' => 'The OTP code is invalid or has expired.']);
    }

    public function resend(Request $request)
    {
        $user = auth()->user();
        $otp = sprintf("%06d", random_int(100000, 999999));
        $user->otp_code = \Illuminate\Support\Facades\Hash::make($otp);
        $user->otp_expires_at = now()->addMinutes(10);
        $user->save();

        try {
            \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\OtpMail($otp));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('OTP Email failed: ' . $e->getMessage());
        }

        return back()->with('status', 'A new OTP has been sent to your email.');
    }

    public function updateEmail(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
        ]);

        $user = auth()->user();
        $user->email = $request->email;

        $otp = sprintf("%06d", random_int(100000, 999999));
        $user->otp_code = \Illuminate\Support\Facades\Hash::make($otp);
        $user->otp_expires_at = now()->addMinutes(10);
        $user->save();

        try {
            \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\OtpMail($otp));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('OTP Email failed: ' . $e->getMessage());
        }

        return back()->with('status', 'Email updated to ' . $user->email . '! A new OTP has been sent.');
    }
}
