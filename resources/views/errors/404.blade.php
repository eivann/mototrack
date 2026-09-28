<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" id="html-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Page Not Found — MotoTrack</title>
    
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
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@0.460.0"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-950 flex flex-col min-h-screen">

    @include('layouts.navigation')

    <main class="flex-grow flex items-center justify-center p-4 sm:p-6 lg:p-8">
        <div class="max-w-2xl w-full text-center">
            <div class="relative inline-block">
                <div class="absolute inset-0 bg-indigo-500/20 rounded-full blur-3xl"></div>
                <h1 class="relative text-[8rem] sm:text-[12rem] font-black tracking-tighter text-transparent bg-clip-text bg-gradient-to-br from-gray-900 to-gray-500 dark:from-white dark:to-gray-600 leading-none">
                    404
                </h1>
            </div>
            
            <h2 class="mt-4 text-3xl font-extrabold tracking-tight sm:text-4xl">Page not found</h2>
            <p class="mt-4 text-lg text-gray-500 dark:text-gray-400">
                Sorry, we couldn't find the page you're looking for. It might have been moved or doesn't exist.
            </p>
            
            <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="/" class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-3.5 rounded-xl text-sm font-bold shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors flex items-center justify-center gap-2">
                    <i data-lucide="home" class="w-4 h-4"></i>
                    Back to Home
                </a>
                <a href="/browse" class="w-full sm:w-auto bg-white dark:bg-gray-800 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 px-8 py-3.5 rounded-xl text-sm font-bold shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors flex items-center justify-center gap-2">
                    <i data-lucide="shopping-bag" class="w-4 h-4"></i>
                    Browse Products
                </a>
            </div>
        </div>
    </main>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
