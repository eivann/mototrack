<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'MotoTrack') }} - Forgot Password</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
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
<body class="flex items-center justify-center p-6 py-12 relative overflow-hidden">
    
    <!-- Background accents -->
    <div class="absolute top-[-10%] right-[-10%] w-[40%] h-[40%] bg-indigo-200/40 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-[-10%] left-[-10%] w-[40%] h-[40%] bg-blue-200/40 rounded-full blur-3xl pointer-events-none"></div>

    <div class="w-full max-w-md relative z-10">
        
        <div class="text-center mb-8">
            <a href="/" class="inline-flex items-center gap-3 mb-6 hover:opacity-80 transition-opacity">
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white w-12 h-12 flex items-center justify-center rounded-xl font-bold text-xl shadow-lg shadow-blue-500/30">M</div>
                <div class="text-left">
                    <h1 class="font-bold text-xl text-gray-900 tracking-tight">MOTOTRACK</h1>
                    <p class="text-xs text-gray-500 font-medium">Edmar Motorcycle Parts</p>
                </div>
            </a>
            <h2 class="text-3xl font-extrabold text-gray-900">Reset Password</h2>
            <p class="text-gray-500 mt-2">Enter your email to get a password reset link</p>
        </div>

        <div class="card p-8 sm:p-10">
            
            <div class="mb-6 text-sm text-gray-600 bg-blue-50 p-4 rounded-xl border border-blue-100 flex items-start gap-3">
                <i data-lucide="info" class="w-5 h-5 text-blue-500 mt-0.5 shrink-0"></i>
                <p>Forgot your password? No problem. Just let us know your email address and we will email you a password reset link.</p>
            </div>

            <x-auth-session-status class="mb-4 bg-green-50 text-green-600 p-3 rounded-xl text-sm border border-green-100" :status="session('status')" />

            <form method="POST" action="{{ route('password.email') }}" x-data="{ isSubmitting: false }" @submit="isSubmitting = true">
                @csrf

                <!-- Email Address -->
                <div class="mb-6 relative input-group">
                    <label for="email" class="block text-sm font-semibold text-gray-700 mb-1.5">Email Address</label>
                    <div class="relative">
                        <i data-lucide="mail" class="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400 transition-colors"></i>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" 
                            class="input-field" placeholder="you@example.com">
                    </div>
                    <x-input-error :messages="$errors->get('email')" class="mt-2 text-red-500 text-sm" />
                </div>

                <!-- Submit Button -->
                <button type="submit" :disabled="isSubmitting" class="btn btn-primary w-full h-12 text-base shadow-md">
                    <span x-show="!isSubmitting">Email Reset Link</span>
                    <span x-show="isSubmitting" x-cloak class="flex items-center gap-2">
                        <i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i>
                        Sending...
                    </span>
                </button>
            </form>
        </div>
        
        <p class="text-center mt-8 text-gray-600 text-sm">
            Remember your password? 
            <a href="{{ route('login') }}" class="text-blue-600 font-semibold hover:underline">Sign in</a>
        </p>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
