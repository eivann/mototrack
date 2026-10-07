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

        if ($user->otp_code === $request->otp_code && now()->lessThanOrEqualTo($user->otp_expires_at)) {
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
        $otp = sprintf("%06d", mt_rand(100000, 999999));
        $user->otp_code = $otp;
        $user->otp_expires_at = now()->addMinutes(10);
        $user->save();

        try {
            \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\OtpMail($otp));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('OTP Email failed: ' . $e->getMessage());
        }

        return back()->with('status', 'A new OTP has been sent to your email.');
    }
}
