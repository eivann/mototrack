<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'MotoTrack') }} - Login</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: radial-gradient(circle at top, #eef2ff, #f8fafc);
            min-height: 100vh;
        }
        
        .card {
            background: white;
            border-radius: 24px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .btn {
            border-radius: 14px;
            padding: 12px 22px;
            font-weight: 500;
            transition: 0.25s;
            display: inline-flex;
            justify-content: center;
            align-items: center;
        }
        
        .btn-primary {
            background: linear-gradient(135deg,#2563eb,#4f46e5);
            color: white;
            border: none;
        }
        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(37,99,235,0.3);
        }
        .btn-primary:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .input-field {
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
            padding: 12px 16px;
            padding-left: 44px;
            width: 100%;
            background: #f8fafc;
        }
        .input-field:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
            outline: none;
            background: white;
        }
        
        .input-group:focus-within i {
            color: #4f46e5;
        }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="flex items-center justify-center p-6 relative overflow-hidden">
    
    <!-- Background accents -->
    <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-blue-200/40 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-indigo-200/40 rounded-full blur-3xl pointer-events-none"></div>

    <div class="w-full max-w-md relative z-10">
        
        <div class="text-center mb-8">
            <a href="/" class="inline-flex flex-col items-center gap-2 mb-6 hover:opacity-80 transition-opacity">
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white w-12 h-12 flex items-center justify-center rounded-xl font-bold text-xl shadow-lg shadow-blue-500/30">M</div>
                <div class="text-center">
                    <h1 class="font-bold text-xl text-gray-900 tracking-tight">MOTOTRACK</h1>
                    <p class="text-xs text-gray-500 font-medium">Edmar Motorcycle Parts</p>
                </div>
            </a>
            <h2 class="text-3xl font-extrabold text-gray-900">Welcome Back</h2>
            <p class="text-gray-500 mt-2">Sign in to manage your account and orders</p>
        </div>

        <div class="card p-8 sm:p-10">
            
            <x-auth-session-status class="mb-4 bg-green-50 text-green-600 p-3 rounded-xl text-sm border border-green-100" :status="session('status')" />

            <form method="POST" action="{{ route('login') }}" x-data="{ showPassword: false, isSubmitting: false }" @submit="isSubmitting = true">
                @csrf

                <!-- Email Address -->
                <div class="mb-5 relative input-group">
                    <label for="email" class="block text-sm font-semibold text-gray-700 mb-1.5">Email Address</label>
                    <div class="relative">
                        <i data-lucide="mail" class="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400 transition-colors"></i>
                        <input id="email" type="email" name="email" value="{{ old('email', 'macapagal.eivan@gmail.com') }}" required autofocus autocomplete="username" 
                            class="input-field" placeholder="you@example.com">
                    </div>
                    <x-input-error :messages="$errors->get('email')" class="mt-2 text-red-500 text-sm" />
                </div>

                <!-- Password -->
                <div class="mb-5 relative input-group">
                    <div class="flex justify-between items-center mb-1.5">
                        <label for="password" class="block text-sm font-semibold text-gray-700">Password</label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium transition-colors">
                                Forgot password?
                            </a>
                        @endif
                    </div>
                    <div class="relative">
                        <i data-lucide="lock" class="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400 transition-colors"></i>
                        <input id="password" :type="showPassword ? 'text' : 'password'" name="password" required autocomplete="current-password" 
                            class="input-field pr-12" placeholder="••••••••">
                        
                        <button type="button" @click="showPassword = !showPassword" class="absolute right-3 top-1/2 transform -translate-y-1/2 p-1 text-gray-400 hover:text-gray-600 focus:outline-none">
                            <i x-show="!showPassword" data-lucide="eye" class="w-5 h-5"></i>
                            <i x-show="showPassword" x-cloak data-lucide="eye-off" class="w-5 h-5"></i>
                        </button>
                    </div>
                    <x-input-error :messages="$errors->get('password')" class="mt-2 text-red-500 text-sm" />
                </div>

                <!-- Remember Me -->
                <div class="mb-4 flex items-center">
                    <input id="remember_me" type="checkbox" name="remember" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                    <label for="remember_me" class="ml-2 text-sm text-gray-600 font-medium cursor-pointer">Keep me signed in</label>
                </div>

                <!-- Google reCAPTCHA -->
                <div class="mb-6 flex flex-col items-center">
                    <div class="g-recaptcha" data-sitekey="{{ env('RECAPTCHA_SITE_KEY') }}"></div>
                    @if ($errors->has('g-recaptcha-response'))
                        <p class="mt-2 text-red-500 text-xs font-semibold">{{ $errors->first('g-recaptcha-response') }}</p>
                    @endif
                </div>

                <!-- Honeypot (Invisible Trap) -->
                <div class="hidden">
                    <input type="text" name="my_extra_field" value="">
                </div>

                <!-- Submit Button -->
                <button type="submit" :disabled="isSubmitting" class="btn btn-primary w-full h-12 text-base shadow-md">
                    <span x-show="!isSubmitting">Sign In</span>
                    <span x-show="isSubmitting" x-cloak class="flex items-center gap-2">
                        <i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i>
                        Signing in...
                    </span>
                </button>
            </form>
        </div>
        
        <p class="text-center mt-8 text-gray-600 text-sm">
            Don't have an account? 
            <a href="{{ route('register') }}" class="text-blue-600 font-semibold hover:underline">Sign up</a>
        </p>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
