<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign in - {{ config('app.name', 'MotoTrack') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

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
        body { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none !important; }
        .shake { animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both; }
        @keyframes shake {
            10%, 90% { transform: translate3d(-1px, 0, 0); }
            20%, 80% { transform: translate3d(2px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
            40%, 60% { transform: translate3d(4px, 0, 0); }
        }
        @media (max-width: 360px) {
            .g-recaptcha {
                transform: scale(0.85);
                transform-origin: center;
            }
        }
    </style>
</head>
<body class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 flex flex-col antialiased">

    <div class="min-h-screen flex flex-col lg:flex-row flex-1">

        <!-- LEFT PANEL: Dark Branded Hero -->
        <div class="lg:w-[460px] xl:w-[500px] 2xl:w-[540px] shrink-0 bg-[#0b1320] text-white p-7 sm:p-10 lg:p-12 xl:p-16 flex flex-col justify-between relative overflow-hidden">

            <!-- Subtle background glows -->
            <div class="absolute -top-24 -left-24 w-96 h-96 bg-blue-600/15 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-indigo-600/15 rounded-full blur-3xl pointer-events-none"></div>

            <!-- Top: Brand Header -->
            <div class="relative z-10">
                <a href="/" class="inline-flex items-center gap-3 group focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-xl p-1 -m-1 transition-transform hover:translate-x-0.5">
                    <div class="w-11 h-11 rounded-xl bg-blue-600 group-hover:bg-blue-500 transition-colors flex items-center justify-center text-white font-extrabold text-base tracking-wider shadow-lg shadow-blue-600/30">
                        EM
                    </div>
                    <div>
                        <span class="block text-white font-extrabold text-lg tracking-wider leading-none">MOTOTRACK</span>
                        <span class="block text-slate-400 text-xs font-medium tracking-normal mt-1">Edmar Motorcycle Parts</span>
                    </div>
                </a>
            </div>

            <!-- Middle: Headlines & Feature Highlights -->
            <div class="my-8 lg:my-auto relative z-10">
                <h1 class="text-3xl sm:text-4xl lg:text-[40px] font-extrabold text-white tracking-tight leading-[1.15]">
                    Welcome back.<br>
                    <span class="text-slate-100">Let's get you riding.</span>
                </h1>
                <p class="mt-4 text-slate-300 text-sm sm:text-base leading-relaxed max-w-sm">
                    Sign in to manage your pre-orders and follow your repairs.
                </p>

                <!-- Value propositions -->
                <div class="mt-8 sm:mt-10 space-y-5">
                    <!-- Feature 1: Pre-orders -->
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-xl bg-slate-800/80 border border-slate-700/60 flex items-center justify-center text-blue-400 shrink-0 mt-0.5">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10" />
                                <polyline points="12 6 12 12 16 14" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-white font-bold text-sm sm:text-base">Your pre-orders</h2>
                            <p class="text-slate-300 text-xs sm:text-sm mt-0.5 leading-normal">
                                Check status and your 24-hour pickup deadline.
                            </p>
                        </div>
                    </div>

                    <!-- Feature 2: Repairs -->
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-xl bg-slate-800/80 border border-slate-700/60 flex items-center justify-center text-blue-400 shrink-0 mt-0.5">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-white font-bold text-sm sm:text-base">Your repairs</h2>
                            <p class="text-slate-300 text-xs sm:text-sm mt-0.5 leading-normal">
                                Follow the progress of your service.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom: Location & Store Hours -->
            <div class="pt-6 border-t border-slate-800/80 relative z-10 text-xs text-slate-300 flex items-center justify-between">
                <span>Pandi, Bulacan &bull; Open daily 6:00 AM &ndash; 6:00 PM</span>
                <a href="/" class="text-slate-400 hover:text-white transition-colors underline font-medium">Home</a>
            </div>
        </div>

        <!-- RIGHT PANEL: Authentication Form -->
        <div class="flex-1 bg-white dark:bg-slate-900 flex flex-col justify-center items-center p-6 sm:p-10 lg:p-12 xl:p-16">
            <div class="w-full max-w-[440px]">

                <!-- Form Header -->
                <div class="mb-7">
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        Sign in
                    </h2>
                    <p class="mt-1.5 text-sm text-slate-600 dark:text-slate-400">
                        Enter your details to manage your account and orders.
                    </p>
                </div>

                <!-- Session status -->
                <x-auth-session-status class="mb-5 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 text-sm" :status="session('status')" />

                <!-- Error alert -->
                @if($errors->any() || session('error'))
                    <div role="alert" class="mb-6 p-4 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 rounded-xl flex items-start gap-3 text-red-700 dark:text-red-300 shake">
                        <svg class="w-5 h-5 shrink-0 mt-0.5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="8" x2="12" y2="12" />
                            <line x1="12" y1="16" x2="12.01" y2="16" />
                        </svg>
                        <div class="text-sm">
                            <p class="font-bold">Authentication Failed</p>
                            <p class="text-xs opacity-90 mt-0.5">
                                {{ session('error') ?: ($errors->first('email') ?: 'Please check your email and password.') }}
                            </p>
                        </div>
                    </div>
                @endif

                <!-- Google Sign-in -->
                <a href="{{ route('auth.google') }}" class="w-full min-h-[44px] inline-flex justify-center items-center gap-3 py-2.5 px-4 border border-slate-300 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-800 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                    </svg>
                    <span>Sign in with Google</span>
                </a>

                <!-- Divider -->
                <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-slate-200 dark:border-slate-800"></div>
                    </div>
                    <div class="relative flex justify-center text-xs">
                        <span class="px-3 bg-white dark:bg-slate-900 text-slate-500 dark:text-slate-400 font-medium">or sign in with email</span>
                    </div>
                </div>

                <!-- Form -->
                <form method="POST" action="{{ route('login') }}" x-data="{ showPassword: false, isSubmitting: false }" @submit="isSubmitting = true" class="space-y-4">
                    @csrf

                    <!-- Email Field -->
                    <div>
                        <label for="email" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                            Email address
                        </label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                            placeholder="you@example.com"
                            class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-4 focus:ring-blue-100 dark:focus:ring-blue-900/40 focus:border-blue-600 transition-all {{ $errors->has('email') ? 'border-red-400 focus:border-red-500 focus:ring-red-100' : '' }}">
                        @if ($errors->has('email'))
                            <p class="mt-1.5 text-xs text-red-500 font-semibold">{{ $errors->first('email') }}</p>
                        @endif
                    </div>

                    <!-- Password Field -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="password" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                                Password
                            </label>
                            <a href="{{ route('password.request') }}" class="text-xs font-semibold text-blue-600 dark:text-blue-400 hover:text-blue-700 hover:underline">
                                Forgot password?
                            </a>
                        </div>
                        <div class="relative">
                            <input id="password" :type="showPassword ? 'text' : 'password'" name="password" required autocomplete="current-password"
                                placeholder="Enter your password"
                                class="w-full px-4 py-2.5 pr-12 text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-4 focus:ring-blue-100 dark:focus:ring-blue-900/40 focus:border-blue-600 transition-all {{ $errors->has('password') ? 'border-red-400 focus:border-red-500 focus:ring-red-100' : '' }}">
                            <button type="button" @click="showPassword = !showPassword"
                                :aria-label="showPassword ? 'Hide password' : 'Show password'"
                                class="absolute right-1 top-1/2 -translate-y-1/2 w-11 h-11 flex items-center justify-center text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-lg">
                                <svg x-show="!showPassword" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                                <svg x-show="showPassword" x-cloak class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" />
                                    <line x1="1" y1="1" x2="23" y2="23" />
                                </svg>
                            </button>
                        </div>
                        @if ($errors->has('password'))
                            <p class="mt-1.5 text-xs text-red-500 font-semibold">{{ $errors->first('password') }}</p>
                        @endif
                    </div>

                    <!-- Keep me signed in -->
                    <div class="flex items-start gap-2.5 pt-1">
                        <input id="remember_me" type="checkbox" name="remember" class="w-4 h-4 mt-0.5 text-blue-600 bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-700 rounded focus:ring-blue-500 focus:ring-offset-0">
                        <label for="remember_me" class="text-sm cursor-pointer select-none">
                            <span class="font-semibold text-slate-800 dark:text-slate-200">Keep me signed in</span>
                            <span class="block text-xs text-slate-500 dark:text-slate-400">Only on your own device.</span>
                        </label>
                    </div>

                    <!-- Google reCAPTCHA -->
                    <div class="pt-2 flex flex-col items-center">
                        <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
                        @if ($errors->has('g-recaptcha-response'))
                            <p class="mt-2 text-red-500 text-xs font-semibold">{{ $errors->first('g-recaptcha-response') }}</p>
                        @endif
                    </div>

                    <!-- Honeypot -->
                    <div class="hidden">
                        <input type="text" name="my_extra_field" value="" tabindex="-1" autocomplete="off">
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" :disabled="isSubmitting"
                        class="w-full min-h-[46px] py-3 px-4 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 disabled:opacity-75 disabled:cursor-not-allowed text-white text-sm font-bold rounded-xl shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2 focus:outline-none focus:ring-4 focus:ring-blue-200 dark:focus:ring-blue-900/40">
                        <span x-show="!isSubmitting">Sign in</span>
                        <span x-show="isSubmitting" x-cloak class="inline-flex items-center gap-2">
                            <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span>Signing in...</span>
                        </span>
                    </button>
                </form>

                <!-- Footer Navigation -->
                <p class="mt-8 text-center text-sm text-slate-600 dark:text-slate-400">
                    New to MotoTrack?
                    <a href="{{ route('register') }}" class="font-bold text-blue-600 dark:text-blue-400 hover:text-blue-700 hover:underline">
                        Create an account
                    </a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
