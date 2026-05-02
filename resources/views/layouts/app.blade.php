<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white dark:bg-gray-800 shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
            
            <!-- Global Flash Messages / Mock SMS -->
            @if(session('mock_sms'))
                <div class="fixed bottom-5 right-5 max-w-sm w-full bg-white dark:bg-gray-800 border-l-4 border-amber-500 shadow-2xl rounded-xl p-4 z-50 animate-bounce transition-all">
                    <div class="flex items-start gap-4">
                        <div class="bg-amber-100 p-2 rounded-lg">
                            <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 dark:text-white text-sm uppercase tracking-wider">SMS Alert Sent</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">{{ session('mock_sms') }}</p>
                            <p class="text-xs text-gray-400 mt-2 italic">This is a mock notification. To send real SMS, add your API key to .env</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </body>
</html>
