<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'ends_with:@gmail.com', 'unique:'.User::class],
            'phone' => ['required', 'string', 'regex:/^(09|\+639)\d{9}$/'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'my_extra_field' => 'prohibited',
            'g-recaptcha-response' => [
                (app()->runningUnitTests() || request()->ip() === config('services.recaptcha.skip_ip')) ? 'nullable' : 'required',
                function ($attribute, $value, $fail) {
                    if (app()->runningUnitTests() || request()->ip() === config('services.recaptcha.skip_ip')) {
                        return;
                    }
                    $response = \Illuminate\Support\Facades\Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                        'secret' => config('services.recaptcha.secret_key'),
                        'response' => $value,
                        'remoteip' => request()->ip(),
                    ]);

                    if (!$response->json('success')) {
                        $fail('The reCAPTCHA validation failed.');
                    }
                }
            ],
        ], [
            'email.ends_with' => 'Only @gmail.com email addresses are allowed to register.',
            'phone.regex' => 'The phone number must start with 09 or +639 followed by 9 digits.',
            'g-recaptcha-response.required' => 'Please complete the CAPTCHA.',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'customer',
        ]);

        // Create the linked Customer profile
        \App\Models\Customer::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $request->phone,
        ]);

        event(new Registered($user));

        Auth::login($user);

        if ($user->role === 'owner') {
            return redirect(route('dashboard', absolute: false));
        }

        return redirect(route('customer.dashboard', absolute: false));
    }
}
