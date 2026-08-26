<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'MotoTrack') }} - Register</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@0.460.0"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    
    <script>
        (function() {
            var theme = localStorage.getItem('theme');
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: radial-gradient(circle at top, #eef2ff, #f8fafc);
            color: #0f172a;
            min-height: 100svh;
            display: flex;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
            margin: 0;
            padding: 0.75rem;
            overflow-x: hidden;
            transition: background 0.3s ease;
            color-scheme: light;
        }

        *,
        *::before,
        *::after {
            box-sizing: inherit;
        }
        
        .dark body {
            background: radial-gradient(circle at top, #1e1b4b, #0f172a);
            color: white;
            color-scheme: dark;
        }
        
        .card {
            background: white;
            border-radius: 24px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .dark .card {
            background: #1e293b;
            border-color: #334155;
            box-shadow: none;
        }
        
        .btn {
            border-radius: 14px;
            padding: 10px 20px;
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
        .btn-primary:active {
            transform: translateY(0);
        }

        .input-field {
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
            padding: 10px 14px;
            padding-left: 44px;
            width: 100%;
            background: #f8fafc;
        }
        
        .dark .input-field {
            background: #334155;
            border-color: #475569;
            color: white;
        }
        
        .input-field:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
            outline: none;
            background: white;
        }
        
        .dark .input-field:focus {
            background: #1e293b;
        }

        .input-field.error {
            border-color: #ef4444;
            background: #fef2f2;
        }

        .dark .input-field.error {
            background: #451a1a;
            border-color: #991b1b;
        }
        
        .shake {
            animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both;
        }

        @keyframes shake {
            10%, 90% { transform: translate3d(-1px, 0, 0); }
            20%, 80% { transform: translate3d(2px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
            40%, 60% { transform: translate3d(4px, 0, 0); }
        }

        .error-banner {
            animation: slideDown 0.4s ease-out forwards;
        }

        .hidden {
            display: none !important;
        }

        .register-wrapper {
            width: 100%;
            max-width: 42rem;
            position: relative;
            z-index: 10;
        }

        .register-header {
            text-align: center;
            margin-bottom: 1rem;
        }

        .register-header a {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
            margin-bottom: 0.75rem;
            color: inherit;
            text-decoration: none;
        }

        .register-header img {
            height: 3rem;
            width: auto;
        }

        .register-header h1 {
            margin: 0;
            font-size: 1.125rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .register-header h2 {
            margin: 0;
            font-size: clamp(1.5rem, 3vw, 1.875rem);
            line-height: 1.15;
            font-weight: 800;
        }

        .register-header p {
            margin: 0.25rem 0 0;
            font-size: 0.875rem;
        }

        .register-card {
            padding: 1.25rem;
        }

        .register-form {
            display: grid;
            grid-template-columns: 1fr;
            column-gap: 1rem;
            row-gap: 0.75rem;
        }

        .register-form .input-group {
            margin: 0;
        }

        .register-form label {
            display: block;
            margin-bottom: 0.375rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: #374151;
        }

        .dark .register-form label {
            color: #d1d5db;
        }

        .register-form .field-shell {
            position: relative;
        }

        .register-form .field-shell > i[data-lucide] {
            position: absolute;
            left: 1rem;
            top: 50%;
            width: 1.25rem;
            height: 1.25rem;
            transform: translateY(-50%);
            color: #9ca3af;
            pointer-events: none;
        }

        .register-form .field-shell > button[type="button"] {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            border: 0;
            background: transparent;
            color: #9ca3af;
            cursor: pointer;
        }

        .register-form .span-all,
        .register-form .google-section {
            grid-column: 1 / -1;
        }

        .register-form .captcha-field {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .register-form .google-section {
            margin-top: 0.25rem;
        }

        .register-form button[type="submit"],
        .register-form .google-section a {
            width: 100%;
            min-height: 44px;
        }

        .register-form .google-section a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.625rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 0.75rem;
            background: #ffffff;
            font-size: 0.875rem;
            font-weight: 600;
            color: inherit;
            text-decoration: none;
        }

        .dark .register-form .google-section a {
            background: #334155;
            border-color: #475569;
            color: #e5e7eb;
        }

        .register-form .google-section svg {
            width: 1.25rem;
            height: 1.25rem;
            flex: 0 0 auto;
            margin-right: 0;
        }

        .register-footer {
            margin-top: 1rem;
            text-align: center;
            font-size: 0.875rem;
            color: #4b5563;
        }

        .dark .register-footer {
            color: #9ca3af;
        }

        .register-footer a {
            color: #2563eb;
            font-weight: 600;
            text-decoration: none;
        }

        .register-footer a:hover {
            text-decoration: underline;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        [x-cloak] { display: none !important; }

        @media (max-width: 360px) {
            .g-recaptcha {
                transform: scale(0.85);
                transform-origin: center;
            }
        }

        @media (min-width: 640px) {
            .register-header img {
                height: 3.5rem;
            }

            .register-card {
                padding: 1.5rem;
            }

            .register-form {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
    </style>
</head>
<body class="min-h-[100svh] flex items-center justify-center p-3 sm:p-4 relative overflow-x-hidden">
    
    <!-- Background accents -->
    <div class="absolute top-[-10%] right-[-10%] w-[40%] h-[40%] bg-indigo-200/40 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-[-10%] left-[-10%] w-[40%] h-[40%] bg-blue-200/40 rounded-full blur-3xl pointer-events-none"></div>

    <div class="register-wrapper w-full max-w-2xl relative z-10">
        
        <div class="register-header text-center mb-3">
            <a href="/" class="inline-flex flex-col items-center gap-1 mb-2 hover:opacity-80 transition-opacity">
                <img src="{{ asset('images/logo.png') }}" alt="EM Parts" class="h-10 sm:h-12 w-auto drop-shadow-xl hover:scale-105 transition-transform duration-300">
                <div class="text-center">
                    <h1 class="font-bold text-base text-gray-900 dark:text-white tracking-tight">MOTOTRACK</h1>
                    <p class="text-[10px] text-gray-500 dark:text-gray-400 font-medium">Edmar Motorcycle Parts</p>
                </div>
            </a>
            <h2 class="text-xl sm:text-2xl font-extrabold text-gray-900 dark:text-white">Create an Account</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Sign up to pre-order parts and track services</p>
        </div>

        <div class="register-card card p-4 sm:p-5 {{ $errors->any() || session('error') ? 'shake' : '' }}">
            
            @if($errors->any() || session('error'))
                <div role="alert" class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-800 rounded-2xl flex items-center gap-3 text-red-600 dark:text-red-400 error-banner">
                    <div class="bg-red-100 dark:bg-red-900/40 p-2 rounded-lg shrink-0">
                        <i data-lucide="alert-circle" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <p class="text-sm font-bold">Registration Failed</p>
                        <p class="text-xs opacity-80">
                            {{ session('error') ?: 'Please correct the errors below.' }}
                        </p>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}" x-data="{ showPassword: false, isSubmitting: false }" @submit="isSubmitting = true" class="register-form grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-3">
                @csrf

                <!-- Name -->
                <div class="relative input-group">
                    <label for="name" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Full Name</label>
                    <div class="field-shell relative">
                        <i data-lucide="user" class="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400 transition-colors"></i>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" 
                            class="input-field {{ $errors->has('name') ? 'error' : '' }}" placeholder="John Doe">
                    </div>
                    <x-input-error :messages="$errors->get('name')" class="mt-2 text-red-500 text-xs italic" />
                </div>

                <!-- Email Address -->
                <div class="relative input-group">
                    <label for="email" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Email Address</label>
                    <div class="field-shell relative">
                        <i data-lucide="mail" class="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400 transition-colors"></i>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" 
                            class="input-field {{ $errors->has('email') ? 'error' : '' }}" placeholder="example@gmail.com">
                    </div>
                    <x-input-error :messages="$errors->get('email')" class="mt-2 text-red-500 text-xs italic" />
                </div>

                <!-- Phone Number -->
                <div class="relative input-group">
                    <label for="phone" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Phone Number</label>
                    <div class="field-shell relative">
                        <i data-lucide="phone" class="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400 transition-colors"></i>
                        <input id="phone" type="text" name="phone" value="{{ old('phone') }}" required autocomplete="tel" 
                            class="input-field {{ $errors->has('phone') ? 'error' : '' }}" 
                            placeholder="09123456789 or +639123456789"
                            maxlength="13"
                            oninput="this.value = this.value.replace(/[^0-9+]/g, '')"
                            title="Format: 09123456789 or +639123456789">
                    </div>
                    <x-input-error :messages="$errors->get('phone')" class="mt-2 text-red-500 text-xs italic" />
                </div>

                <!-- Password -->
                <div class="relative input-group">
                    <label for="password" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Password</label>
                    <div class="field-shell relative">
                        <i data-lucide="lock" class="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400 transition-colors"></i>
                        <input id="password" :type="showPassword ? 'text' : 'password'" name="password" required autocomplete="new-password" 
                            class="input-field pr-12 {{ $errors->has('password') ? 'error' : '' }}" placeholder="••••••••">
                        
                        <button type="button" @click="showPassword = !showPassword"
                                :aria-label="showPassword ? 'Hide password' : 'Show password'"
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 p-1 text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-400 rounded">
                            <i x-show="!showPassword" data-lucide="eye" class="w-5 h-5"></i>
                            <i x-show="showPassword" x-cloak data-lucide="eye-off" class="w-5 h-5"></i>
                        </button>
                    </div>
                    <x-input-error :messages="$errors->get('password')" class="mt-2 text-red-500 text-xs italic" />
                    <p class="mt-1.5 text-[10px] text-gray-400 dark:text-gray-500 font-medium">
                        Must be at least 8 characters with upper/lower case, numbers, and symbols.
                    </p>
                </div>

                <div class="relative input-group">
                    <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Confirm Password</label>
                    <div class="field-shell relative">
                        <i data-lucide="shield-check" class="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400 transition-colors"></i>
                        <input id="password_confirmation" :type="showPassword ? 'text' : 'password'" name="password_confirmation" required autocomplete="new-password" 
                            class="input-field pr-12 {{ $errors->has('password_confirmation') ? 'error' : '' }}" placeholder="••••••••">
                        
                        <button type="button" @click="showPassword = !showPassword"
                                :aria-label="showPassword ? 'Hide password' : 'Show password'"
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 p-1 text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-400 rounded">
                            <i x-show="!showPassword" data-lucide="eye" class="w-5 h-5"></i>
                            <i x-show="showPassword" x-cloak data-lucide="eye-off" class="w-5 h-5"></i>
                        </button>
                    </div>
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2 text-red-500 text-xs italic" />
                </div>
                
                <!-- Google reCAPTCHA -->
                <div class="captcha-field flex flex-col items-center justify-center">
                    <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
                    @if ($errors->has('g-recaptcha-response'))
                        <p class="mt-2 text-red-500 text-xs font-semibold">{{ $errors->first('g-recaptcha-response') }}</p>
                    @endif
                </div>

                <!-- Honeypot (Invisible Trap) -->
                <div class="hidden span-all sm:col-span-2">
                    <input type="text" name="my_extra_field" value="">
                </div>

                <!-- Submit Button -->
                <button type="submit" :disabled="isSubmitting" class="span-all btn btn-primary w-full min-h-[44px] text-base shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:col-span-2">
                    <span x-show="!isSubmitting">Create Account</span>
                    <span x-show="isSubmitting" x-cloak class="flex items-center gap-2">
                        <i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i>
                        Registering...
                    </span>
                </button>
                
                <div class="google-section mt-0 sm:col-span-2">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-200 dark:border-gray-700"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 bg-white dark:bg-[#1e293b] text-gray-500 font-medium">Or continue with</span>
                        </div>
                    </div>

                    <div class="mt-3">
                        <a href="{{ route('auth.google') }}" class="w-full inline-flex justify-center items-center py-2.5 px-4 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm bg-white dark:bg-[#334155] text-sm font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                            <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                            </svg>
                            Sign up with Google
                        </a>
                    </div>
                </div>
            </form>
        </div>
        
        <p class="register-footer text-center mt-3 text-gray-600 dark:text-gray-400 text-sm">
            Already have an account? 
            <a href="{{ route('login') }}" class="text-blue-600 dark:text-blue-400 font-semibold hover:underline">Sign in</a>
        </p>
    </div>

    <script>
        if (window.lucide) {
            lucide.createIcons();
        }
    </script>
</body>
</html>
