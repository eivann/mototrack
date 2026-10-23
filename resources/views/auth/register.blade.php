@php
    $rawOldPhone = (string) old('phone', '');
    $digitsOnly = preg_replace('/[^0-9]/', '', $rawOldPhone);
    if (str_starts_with($digitsOnly, '639') && strlen($digitsOnly) === 12) {
        $initialPhoneDisplay = substr($digitsOnly, 2);
    } elseif (str_starts_with($digitsOnly, '09') && strlen($digitsOnly) === 11) {
        $initialPhoneDisplay = substr($digitsOnly, 1);
    } elseif (str_starts_with($digitsOnly, '9') && strlen($digitsOnly) === 10) {
        $initialPhoneDisplay = $digitsOnly;
    } else {
        $initialPhoneDisplay = $rawOldPhone;
    }
@endphp
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Create your account - {{ config('app.name', 'MotoTrack') }}</title>

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
                    <img src="{{ asset('brand/mototrack-em.png') }}" alt="MotoTrack Logo" width="44" height="44" class="w-11 h-11 rounded-xl object-contain shadow-lg shadow-blue-600/30 shrink-0">
                    <div>
                        <span class="block text-white font-extrabold text-lg tracking-wider leading-none">MOTOTRACK</span>
                        <span class="block text-slate-400 text-xs font-medium tracking-normal mt-1">Edmar Motorcycle Parts</span>
                    </div>
                </a>
            </div>

            <!-- Middle: Headlines & Feature Highlights -->
            <div class="my-8 lg:my-auto relative z-10">
                <h1 class="text-3xl sm:text-4xl lg:text-[40px] font-extrabold text-white tracking-tight leading-[1.15]">
                    Find the right part.<br>
                    <span class="text-slate-100">Reserve it today.</span>
                </h1>
                <p class="mt-4 text-slate-300 text-sm sm:text-base leading-relaxed max-w-sm">
                    One account to pre-order parts, check what fits your bike, and follow your repairs.
                </p>

                <!-- Value propositions -->
                <div class="mt-8 sm:mt-10 space-y-5">
                    <!-- Feature 1: Compatibility Checker -->
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-xl bg-slate-800/80 border border-slate-700/60 flex items-center justify-center text-blue-400 shrink-0 mt-0.5">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8" />
                                <line x1="21" y1="21" x2="16.65" y2="16.65" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-white font-bold text-sm sm:text-base">Compatibility checker</h2>
                            <p class="text-slate-300 text-xs sm:text-sm mt-0.5 leading-normal">
                                See which parts fit your motorcycle model.
                            </p>
                        </div>
                    </div>

                    <!-- Feature 2: Pre-order reservations -->
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-xl bg-slate-800/80 border border-slate-700/60 flex items-center justify-center text-blue-400 shrink-0 mt-0.5">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10" />
                                <polyline points="12 6 12 12 16 14" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-white font-bold text-sm sm:text-base">Pre-order reservations</h2>
                            <p class="text-slate-300 text-xs sm:text-sm mt-0.5 leading-normal">
                                Reserve now, pay when you pick up within 24 hours.
                            </p>
                        </div>
                    </div>

                    <!-- Feature 3: Service tracking -->
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-xl bg-slate-800/80 border border-slate-700/60 flex items-center justify-center text-blue-400 shrink-0 mt-0.5">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-white font-bold text-sm sm:text-base">Service tracking</h2>
                            <p class="text-slate-300 text-xs sm:text-sm mt-0.5 leading-normal">
                                Follow the status of your repair.
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

        <!-- RIGHT PANEL: Registration Form -->
        <div class="flex-1 bg-white dark:bg-slate-900 flex flex-col justify-center items-center p-6 sm:p-10 lg:p-12 xl:p-16">
            <div class="w-full max-w-[480px]">

                <!-- Form Header -->
                <div class="mb-7">
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        Create your account
                    </h2>
                    <p class="mt-1.5 text-sm text-slate-600 dark:text-slate-400">
                        Pre-order parts and track your repairs in one place.
                    </p>
                </div>

                <!-- Error alert -->
                @if($errors->any() || session('error'))
                    <div role="alert" class="mb-6 p-4 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 rounded-xl flex items-start gap-3 text-red-700 dark:text-red-300 shake">
                        <svg class="w-5 h-5 shrink-0 mt-0.5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="8" x2="12" y2="12" />
                            <line x1="12" y1="16" x2="12.01" y2="16" />
                        </svg>
                        <div class="text-sm">
                            <p class="font-bold">Registration Failed</p>
                            <p class="text-xs opacity-90 mt-0.5">
                                {{ session('error') ?: 'Please correct the indicated fields below.' }}
                            </p>
                        </div>
                    </div>
                @endif

                <!-- Google Sign-up -->
                <a href="{{ route('auth.google') }}" class="w-full min-h-[44px] inline-flex justify-center items-center gap-3 py-2.5 px-4 border border-slate-300 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-800 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                    </svg>
                    <span>Sign up with Google</span>
                </a>

                <!-- Divider -->
                <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-slate-200 dark:border-slate-800"></div>
                    </div>
                    <div class="relative flex justify-center text-xs">
                        <span class="px-3 bg-white dark:bg-slate-900 text-slate-500 dark:text-slate-400 font-medium">or sign up with email</span>
                    </div>
                </div>

                <!-- Form -->
                <form method="POST" action="{{ route('register') }}"
                    x-data="{
                        phoneDisplay: @js($initialPhoneDisplay),
                        password: '',
                        showPassword: false,
                        showConfirmPassword: false,
                        isSubmitting: false,
                        onPhoneInput(e) {
                            let val = e.target.value;
                            let clean = val.replace(/[^0-9+]/g, '');
                            if (clean.startsWith('+63')) clean = clean.substring(3);
                            else if (clean.startsWith('63') && clean.length > 10) clean = clean.substring(2);
                            if (clean.startsWith('0')) clean = clean.substring(1);
                            this.phoneDisplay = clean;
                        },
                        get rawPhone() {
                            let digits = (this.phoneDisplay || '').replace(/[^0-9]/g, '');
                            if (digits.startsWith('639') && digits.length === 12) {
                                return '0' + digits.substring(2);
                            }
                            if (digits.startsWith('09') && digits.length === 11) {
                                return digits;
                            }
                            if (digits.startsWith('9') && digits.length === 10) {
                                return '0' + digits;
                            }
                            return (this.phoneDisplay || '').trim();
                        },
                        prepareSubmit() {
                            this.isSubmitting = true;
                            if (this.$refs.phoneInput) {
                                this.$refs.phoneInput.value = this.rawPhone;
                            }
                        },
                        get hasLength() { return this.password.length >= 8; },
                        get hasUpperLower() { return /[a-z]/.test(this.password) && /[A-Z]/.test(this.password); },
                        get hasNumber() { return /[0-9]/.test(this.password); },
                        get hasSymbol() { return /[^a-zA-Z0-9]/.test(this.password); },
                        get strengthScore() {
                            let score = 0;
                            if (this.hasLength) score++;
                            if (this.hasUpperLower) score++;
                            if (this.hasNumber) score++;
                            if (this.hasSymbol) score++;
                            return score;
                        },
                        get strengthLabel() {
                            if (!this.password) return '';
                            if (this.strengthScore === 4) return 'Strong password';
                            if (this.strengthScore === 3) {
                                if (!this.hasSymbol) return 'Almost there - add a symbol';
                                if (!this.hasNumber) return 'Almost there - add a number';
                                if (!this.hasUpperLower) return 'Almost there - mix upper & lower';
                                return 'Good password';
                            }
                            if (this.strengthScore === 2) return 'Fair - make it stronger';
                            return 'Too short / weak';
                        }
                    }"
                    @submit="prepareSubmit()"
                    class="space-y-4">
                    @csrf

                    <!-- Full Name -->
                    <div>
                        <label for="name" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                            Full name
                        </label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"
                            placeholder="e.g. Juan Dela Cruz"
                            class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-4 focus:ring-blue-100 dark:focus:ring-blue-900/40 focus:border-blue-600 transition-all {{ $errors->has('name') ? 'border-red-400 focus:border-red-500 focus:ring-red-100' : '' }}">
                        @if ($errors->has('name'))
                            <p class="mt-1.5 text-xs text-red-500 font-semibold">{{ $errors->first('name') }}</p>
                        @endif
                    </div>

                    <!-- Email Address -->
                    <div>
                        <label for="email" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                            Email address
                        </label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username"
                            placeholder="you@gmail.com"
                            class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-4 focus:ring-blue-100 dark:focus:ring-blue-900/40 focus:border-blue-600 transition-all {{ $errors->has('email') ? 'border-red-400 focus:border-red-500 focus:ring-red-100' : '' }}">
                        <p class="mt-1.5 text-xs text-slate-600 dark:text-slate-400 font-medium">Only @gmail.com email addresses are allowed to register.</p>
                        @if ($errors->has('email'))
                            <p class="mt-1 text-xs text-red-500 font-semibold">{{ $errors->first('email') }}</p>
                        @endif
                    </div>

                    <!-- Phone Number -->
                    <div>
                        <label for="phone" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                            Phone number
                        </label>
                        <div class="flex rounded-xl border border-slate-300 dark:border-slate-700 overflow-hidden focus-within:ring-4 focus-within:ring-blue-100 dark:focus-within:ring-blue-900/40 focus-within:border-blue-600 transition-all {{ $errors->has('phone') ? 'border-red-400' : '' }}">
                            <span class="inline-flex items-center px-3.5 bg-slate-100 dark:bg-slate-800 border-r border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-sm font-bold select-none">
                                +63
                            </span>
                            <input id="phone" type="tel" name="phone" x-ref="phoneInput"
                                :value="phoneDisplay"
                                @input="onPhoneInput($event)"
                                value="{{ old('phone') ?: $initialPhoneDisplay }}"
                                required
                                autocomplete="tel" maxlength="13"
                                placeholder="912 345 6789"
                                class="w-full px-3.5 py-2.5 text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 border-0 focus:ring-0 focus:outline-none">
                        </div>
                        <p class="mt-1.5 text-xs text-slate-600 dark:text-slate-400">
                            We'll text you when your order is ready. Format: 912 345 6789 or 0912 345 6789.
                        </p>
                        <noscript>
                            <p class="mt-1.5 text-xs text-amber-600 dark:text-amber-400 font-medium">
                                Note: When JavaScript is disabled, enter your full 11-digit mobile number starting with 09 (e.g. 09123456789) without spaces to satisfy server validation.
                            </p>
                        </noscript>
                        @if ($errors->has('phone'))
                            <p class="mt-1 text-xs text-red-500 font-semibold">{{ $errors->first('phone') }}</p>
                        @endif
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                            Password
                        </label>
                        <div class="relative">
                            <input id="password" :type="showPassword ? 'text' : 'password'" name="password" x-model="password" required autocomplete="new-password"
                                placeholder="Create your password"
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

                        <!-- Real-time Strength Meter & Criteria Checklist -->
                        <div class="mt-2.5">
                            <!-- 4-segment progress bar -->
                            <div class="grid grid-cols-4 gap-1.5 h-1.5 w-full">
                                <div class="rounded-full transition-all duration-300"
                                    :class="strengthScore >= 1 ? (strengthScore === 1 ? 'bg-amber-500' : 'bg-emerald-500') : 'bg-slate-200 dark:bg-slate-700'"></div>
                                <div class="rounded-full transition-all duration-300"
                                    :class="strengthScore >= 2 ? (strengthScore === 2 ? 'bg-amber-500' : 'bg-emerald-500') : 'bg-slate-200 dark:bg-slate-700'"></div>
                                <div class="rounded-full transition-all duration-300"
                                    :class="strengthScore >= 3 ? 'bg-emerald-500' : 'bg-slate-200 dark:bg-slate-700'"></div>
                                <div class="rounded-full transition-all duration-300"
                                    :class="strengthScore >= 4 ? 'bg-emerald-500' : 'bg-slate-200 dark:bg-slate-700'"></div>
                            </div>

                            <!-- Status text -->
                            <div class="flex items-center justify-between mt-2">
                                <span class="text-xs font-semibold text-slate-700 dark:text-slate-300" x-text="strengthLabel"></span>
                            </div>

                            <!-- 2-column checklist matching mockups -->
                            <div class="grid grid-cols-2 gap-x-4 gap-y-1.5 mt-2.5 text-xs">
                                <!-- 8+ characters -->
                                <div class="flex items-center gap-1.5" :class="hasLength ? 'text-emerald-700 dark:text-emerald-400 font-semibold' : 'text-slate-500 dark:text-slate-400'">
                                    <svg x-show="hasLength" class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <polyline points="20 6 9 17 4 12" />
                                    </svg>
                                    <svg x-show="!hasLength" class="w-3.5 h-3.5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="9" />
                                    </svg>
                                    <span>8+ characters</span>
                                </div>

                                <!-- Upper & lower case -->
                                <div class="flex items-center gap-1.5" :class="hasUpperLower ? 'text-emerald-700 dark:text-emerald-400 font-semibold' : 'text-slate-500 dark:text-slate-400'">
                                    <svg x-show="hasUpperLower" class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <polyline points="20 6 9 17 4 12" />
                                    </svg>
                                    <svg x-show="!hasUpperLower" class="w-3.5 h-3.5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="9" />
                                    </svg>
                                    <span>Upper & lower case</span>
                                </div>

                                <!-- A number -->
                                <div class="flex items-center gap-1.5" :class="hasNumber ? 'text-emerald-700 dark:text-emerald-400 font-semibold' : 'text-slate-500 dark:text-slate-400'">
                                    <svg x-show="hasNumber" class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <polyline points="20 6 9 17 4 12" />
                                    </svg>
                                    <svg x-show="!hasNumber" class="w-3.5 h-3.5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="9" />
                                    </svg>
                                    <span>A number</span>
                                </div>

                                <!-- A symbol (!@#$) -->
                                <div class="flex items-center gap-1.5" :class="hasSymbol ? 'text-emerald-700 dark:text-emerald-400 font-semibold' : 'text-slate-500 dark:text-slate-400'">
                                    <svg x-show="hasSymbol" class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <polyline points="20 6 9 17 4 12" />
                                    </svg>
                                    <svg x-show="!hasSymbol" class="w-3.5 h-3.5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="9" />
                                    </svg>
                                    <span>A symbol (!@#$)</span>
                                </div>
                            </div>
                        </div>

                        @if ($errors->has('password'))
                            <p class="mt-1.5 text-xs text-red-500 font-semibold">{{ $errors->first('password') }}</p>
                        @endif
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label for="password_confirmation" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                            Confirm password
                        </label>
                        <div class="relative">
                            <input id="password_confirmation" :type="showConfirmPassword ? 'text' : 'password'" name="password_confirmation" required autocomplete="new-password"
                                placeholder="Re-enter your password"
                                class="w-full px-4 py-2.5 pr-12 text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-4 focus:ring-blue-100 dark:focus:ring-blue-900/40 focus:border-blue-600 transition-all {{ $errors->has('password_confirmation') ? 'border-red-400 focus:border-red-500 focus:ring-red-100' : '' }}">
                            <button type="button" @click="showConfirmPassword = !showConfirmPassword"
                                :aria-label="showConfirmPassword ? 'Hide password' : 'Show password'"
                                class="absolute right-1 top-1/2 -translate-y-1/2 w-11 h-11 flex items-center justify-center text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-lg">
                                <svg x-show="!showConfirmPassword" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                                <svg x-show="showConfirmPassword" x-cloak class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" />
                                    <line x1="1" y1="1" x2="23" y2="23" />
                                </svg>
                            </button>
                        </div>
                        @if ($errors->has('password_confirmation'))
                            <p class="mt-1.5 text-xs text-red-500 font-semibold">{{ $errors->first('password_confirmation') }}</p>
                        @endif
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
                        <span x-show="!isSubmitting">Create account</span>
                        <span x-show="isSubmitting" x-cloak class="inline-flex items-center gap-2">
                            <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span>Creating account...</span>
                        </span>
                    </button>
                </form>

                <!-- Footer Navigation -->
                <p class="mt-8 text-center text-sm text-slate-600 dark:text-slate-400">
                    Already have an account?
                    <a href="{{ route('login') }}" class="font-bold text-blue-600 dark:text-blue-400 hover:text-blue-700 hover:underline">
                        Sign in
                    </a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
