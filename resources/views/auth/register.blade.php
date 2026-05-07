<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'MotoTrack') }} - Register</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    
    <script>
        (function() {
            var theme = localStorage.getItem('theme');
            if (theme === 'dark' || (!theme)) {
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
            min-height: 100vh;
            transition: background 0.3s ease;
        }
        
        .dark body {
            background: radial-gradient(circle at top, #1e1b4b, #0f172a);
            color: white;
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
        
        .input-group:focus-within i {
            color: #4f46e5;
        }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="flex items-center justify-center p-6 py-12 relative overflow-hidden">
    
    <!-- Background accents -->
    <div class="absolute top-[-10%] right-[-10%] w-[40%] h-[40%] bg-indigo-200/40 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-[-10%] left-[-10%] w-[40%] h-[40%] bg-blue-200/40 rounded-full blur-3xl pointer-events-none"></div>

    <div class="w-full max-w-md relative z-10">
        
        <div class="text-center mb-8">
            <a href="/" class="inline-flex flex-col items-center gap-2 mb-6 hover:opacity-80 transition-opacity">
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white w-12 h-12 flex items-center justify-center rounded-xl font-bold text-xl shadow-lg shadow-blue-500/30">M</div>
                <div class="text-center">
                    <h1 class="font-bold text-xl text-gray-900 dark:text-white tracking-tight">MOTOTRACK</h1>
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Edmar Motorcycle Parts</p>
                </div>
            </a>
            <h2 class="text-3xl font-extrabold text-gray-900 dark:text-white">Create an Account</h2>
            <p class="text-gray-500 dark:text-gray-400 mt-2">Sign up to pre-order parts and track services</p>
        </div>

        <div class="card p-8 sm:p-10">
            
            <form method="POST" action="{{ route('register') }}" x-data="{ showPassword: false, isSubmitting: false }" @submit="isSubmitting = true">
                @csrf

                <!-- Name -->
                <div class="mb-4 relative input-group">
                    <label for="name" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Full Name</label>
                    <div class="relative">
                        <i data-lucide="user" class="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400 transition-colors"></i>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" 
                            class="input-field" placeholder="John Doe">
                    </div>
                    <x-input-error :messages="$errors->get('name')" class="mt-2 text-red-500 text-sm" />
                </div>

                <!-- Email Address -->
                <div class="mb-4 relative input-group">
                    <label for="email" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Email Address</label>
                    <div class="relative">
                        <i data-lucide="mail" class="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400 transition-colors"></i>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" 
                            class="input-field" placeholder="example@gmail.com">
                    </div>
                    <x-input-error :messages="$errors->get('email')" class="mt-2 text-red-500 text-sm" />
                </div>

                <!-- Phone Number -->
                <div class="mb-4 relative input-group">
                    <label for="phone" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Phone Number</label>
                    <div class="relative">
                        <i data-lucide="phone" class="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400 transition-colors"></i>
                        <input id="phone" type="text" name="phone" value="{{ old('phone') }}" required autocomplete="tel" 
                            class="input-field" placeholder="09123456789">
                    </div>
                    <x-input-error :messages="$errors->get('phone')" class="mt-2 text-red-500 text-sm" />
                </div>

                <!-- Password -->
                <div class="mb-4 relative input-group">
                    <label for="password" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Password</label>
                    <div class="relative">
                        <i data-lucide="lock" class="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400 transition-colors"></i>
                        <input id="password" :type="showPassword ? 'text' : 'password'" name="password" required autocomplete="new-password" 
                            class="input-field pr-12" placeholder="••••••••">
                        
                        <button type="button" @click="showPassword = !showPassword" class="absolute right-3 top-1/2 transform -translate-y-1/2 p-1 text-gray-400 hover:text-gray-600 focus:outline-none">
                            <i x-show="!showPassword" data-lucide="eye" class="w-5 h-5"></i>
                            <i x-show="showPassword" x-cloak data-lucide="eye-off" class="w-5 h-5"></i>
                        </button>
                    </div>
                    <x-input-error :messages="$errors->get('password')" class="mt-2 text-red-500 text-sm" />
                </div>

                <!-- Confirm Password -->
                <div class="mb-6 relative input-group">
                    <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Confirm Password</label>
                    <div class="relative">
                        <i data-lucide="shield-check" class="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400 transition-colors"></i>
                        <input id="password_confirmation" :type="showPassword ? 'text' : 'password'" name="password_confirmation" required autocomplete="new-password" 
                            class="input-field pr-12" placeholder="••••••••">
                    </div>
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2 text-red-500 text-sm" />
                </div>
                
                <!-- Google reCAPTCHA -->
                <div class="mb-6 flex flex-col items-center">
                    <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
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
                    <span x-show="!isSubmitting">Create Account</span>
                    <span x-show="isSubmitting" x-cloak class="flex items-center gap-2">
                        <i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i>
                        Registering...
                    </span>
                </button>
            </form>
        </div>
        
        <p class="text-center mt-8 text-gray-600 dark:text-gray-400 text-sm">
            Already have an account? 
            <a href="{{ route('login') }}" class="text-blue-600 dark:text-blue-400 font-semibold hover:underline">Sign in</a>
        </p>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
