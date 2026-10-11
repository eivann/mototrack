<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     */
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Obtain the user information from Google and log them in.
     */
    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', 'Google sign-in failed. Please try again.');
        }

        // Find or create the user
        $user = User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            // Update google_id if not already set
            if (!$user->google_id) {
                $user->google_id = $googleUser->getId();
                $user->save();
            }
        } else {
            // Create a new user
            $user = new User([
                'name'              => $googleUser->getName(),
                'email'             => $googleUser->getEmail(),
                'password'          => bcrypt(Str::random(32)), // random unusable password
            ]);
            $user->google_id = $googleUser->getId();
            $user->role = 'customer';
            $user->email_verified_at = now();
            $user->save();
        }

        // CR-1: Ensure a Customer profile exists for every authenticated user.
        // Covers both new Google signups and returning users who may lack a
        // profile (e.g., created before the customer system was introduced).
        \App\Models\Customer::firstOrCreate(
            ['user_id' => $user->id],
            ['name' => $user->name, 'email' => $user->email]
        );

        Auth::login($user, true);

        $otp = sprintf("%06d", random_int(100000, 999999));
        $user->otp_code = \Illuminate\Support\Facades\Hash::make($otp);
        $user->otp_expires_at = now()->addMinutes(10);
        $user->save();

        try {
            \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\OtpMail($otp));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('OTP Email failed: ' . $e->getMessage());
        }

        session()->put('otp_verified', false);

        return redirect()->route('otp.verify');
    }
}
