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
                $user->update(['google_id' => $googleUser->getId()]);
            }
        } else {
            // Create a new user
            $user = User::create([
                'name'              => $googleUser->getName(),
                'email'             => $googleUser->getEmail(),
                'google_id'         => $googleUser->getId(),
                'password'          => bcrypt(Str::random(32)), // random unusable password
                'role'              => 'customer',
                'email_verified_at' => now(),
            ]);
        }

        Auth::login($user, true);

        // Redirect based on role
        if ($user->role === 'owner') {
            return redirect()->route('dashboard');
        }

        return redirect()->route('customer.dashboard');
    }
}
