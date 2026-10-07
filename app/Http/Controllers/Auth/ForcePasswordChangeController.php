<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ForcePasswordChangeController extends Controller
{
    public function show(Request $request)
    {
        if (!$request->user()->requires_password_change) {
            return redirect('/');
        }
        return view('auth.force-password-change');
    }

    public function update(Request $request)
    {
        $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $request->user();
        $user->password = Hash::make($request->password);
        $user->requires_password_change = false;
        $user->save();

        $intended = $request->session()->pull('url.intended');

        if ($intended) {
            return redirect()->to($intended);
        }

        return redirect($user->role === 'owner' ? route('dashboard') : route('customer.dashboard'));
    }
}
