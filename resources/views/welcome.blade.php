<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" id="html-root" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

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

    <title>MotoTrack — Edmar Motorcycle Parts & Accessories</title>
    <meta name="description" content="Your trusted motorcycle parts shop in Pandi, Bulacan since 2009. Browse parts, check compatibility, and pre-order online.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vanilla-tilt/1.8.0/vanilla-tilt.min.js"></script>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-950 selection:bg-indigo-500 selection:text-white overflow-x-hidden">

    {{-- NAVBAR --}}
    <nav class="fixed w-full z-50 transition-all duration-300 bg-white/80 dark:bg-gray-950/80 backdrop-blur-xl border-b border-gray-200 dark:border-gray-800" id="navbar">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20 md:h-28 lg:h-32">
                <!-- Brand -->
                <a href="/" class="flex items-center gap-3 group">
                    <img src="{{ asset('images/logo.png') . '?v=' . filemtime(public_path('images/logo.png')) }}" alt="EM Parts Logo" class="h-14 md:h-20 lg:h-24 w-auto group-hover:scale-105 transition-transform">
                    <div>
                        <h1 class="text-xl font-black tracking-tighter leading-none text-gray-900 dark:text-white">EDMAR MOTORCYCLE</h1>
                        <p class="text-[10px] text-indigo-600 dark:text-indigo-400 font-bold uppercase tracking-widest mt-0.5">Parts and Accessories</p>
                    </div>
                </a>

                <!-- Desktop Links -->
                <div class="hidden md:flex items-center gap-8">
                    <a href="#home" class="text-sm font-bold text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Home</a>
                    <a href="#about" class="text-sm font-bold text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">About</a>
                    <a href="#services" class="text-sm font-bold text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Services</a>
                    <a href="#compat" class="text-sm font-bold text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Compatibility</a>
                    <a href="#preorder" class="text-sm font-bold text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Pre-Order</a>
                    <a href="#contact" class="text-sm font-bold text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Contact</a>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-4">
                    <button onclick="toggleTheme()" class="p-2 text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-white transition-colors rounded-full hover:bg-gray-100 dark:hover:bg-gray-800">
                        <i data-lucide="moon" class="w-5 h-5 hidden dark:block"></i>
                        <i data-lucide="sun" class="w-5 h-5 block dark:hidden"></i>
                    </button>
                    @guest
                        <a href="{{ route('login') }}" class="hidden lg:block text-sm font-bold text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Sign In</a>
                        <a href="{{ route('register') }}" class="bg-gray-900 dark:bg-white text-white dark:text-gray-900 px-5 py-2.5 rounded-xl text-sm font-black hover:scale-105 active:scale-95 transition-all shadow-lg hover:shadow-gray-900/20 dark:hover:shadow-white/20">Get Started</a>
                    @endguest
                    @auth
                        <a href="/dashboard" class="bg-gradient-to-r from-indigo-600 to-indigo-700 text-white px-5 py-2.5 rounded-xl text-sm font-black hover:scale-105 active:scale-95 transition-all shadow-lg shadow-indigo-500/30 flex items-center gap-2">
                            <i data-lucide="layout-dashboard" class="w-4 h-4"></i> Dashboard
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    {{-- HERO --}}
    <section id="home" class="relative pt-32 pb-16 lg:pt-36 lg:pb-20 overflow-hidden">
        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-5"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 flex flex-col lg:flex-row items-center gap-12">
            
            <div class="flex-1 text-center lg:text-left">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 font-bold text-xs uppercase tracking-widest mb-8 border border-indigo-100 dark:border-indigo-800">
                    <span class="w-2 h-2 rounded-full bg-indigo-600 dark:bg-indigo-400 animate-pulse"></span>
                    Serving Pandi, Bulacan since 2009
                </div>
                
                <h1 class="text-5xl md:text-6xl lg:text-7xl font-black tracking-tighter mb-6 text-gray-900 dark:text-white leading-[1.1]">
                    Your <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-blue-500">Trusted Partner</span> <br class="hidden lg:block"/>for Motorcycle Parts
                </h1>
                
                <p class="text-lg md:text-xl text-gray-600 dark:text-gray-400 font-medium mb-10 max-w-2xl mx-auto lg:mx-0 leading-relaxed">
                    Find genuine parts, check compatibility instantly, and get expert repair services — all in one unified platform.
                </p>
                
                <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4">
                    <a href="/browse" class="w-full sm:w-auto bg-gray-900 dark:bg-white text-white dark:text-gray-900 px-8 py-4 rounded-2xl text-sm font-black uppercase tracking-widest hover:scale-105 active:scale-95 transition-all shadow-xl hover:shadow-gray-900/20 flex items-center justify-center gap-2">
                        <i data-lucide="shopping-bag" class="w-5 h-5"></i> Browse Products
                    </a>
                    <a href="#compat" class="w-full sm:w-auto bg-white dark:bg-gray-900 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-800 px-8 py-4 rounded-2xl text-sm font-black uppercase tracking-widest hover:bg-gray-50 dark:hover:bg-gray-800 transition-all flex items-center justify-center gap-2 shadow-sm">
                        <i data-lucide="search" class="w-5 h-5"></i> Check Compatibility
                    </a>
                </div>
            </div>

            <div class="flex-1 relative mt-12 lg:mt-0 w-full max-w-lg lg:max-w-none mx-auto" data-tilt data-tilt-max="10" data-tilt-speed="400" data-tilt-glare="true" data-tilt-max-glare="0.2">
                <div class="absolute inset-0 bg-indigo-600/10 dark:bg-indigo-900/20 rounded-full blur-[100px] pointer-events-none -z-10"></div>
                <img src="{{ asset('images/hero.png') }}" alt="Motorcycle Parts Shop" class="w-full h-auto drop-shadow-2xl relative z-10 hover:scale-[1.02] transition-transform duration-500" fetchpriority="high">
                
                <!-- Floating badge -->
                <div class="absolute -bottom-6 -left-6 bg-white dark:bg-gray-800 p-4 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-700 z-20 flex items-center gap-4 animate-bounce" style="animation-duration: 3s;">
                    <div class="w-12 h-12 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 rounded-xl flex items-center justify-center">
                        <i data-lucide="check" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Open Daily</p>
                        <strong class="text-sm font-black text-gray-900 dark:text-white">6:00 AM — 6:00 PM</strong>
                    </div>
                </div>
            </div>
            
        </div>
    </section>

    {{-- SERVICES --}}
    <section id="services" class="py-16 bg-white dark:bg-gray-900 border-y border-gray-100 dark:border-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <div class="inline-flex items-center gap-2 text-indigo-600 dark:text-indigo-400 font-bold text-sm uppercase tracking-widest mb-4">
                    <i data-lucide="zap" class="w-4 h-4"></i> Our Services
                </div>
                <h2 class="text-4xl font-black tracking-tighter mb-4 text-gray-900 dark:text-white">Everything You Need</h2>
                <p class="text-gray-500 dark:text-gray-400 max-w-2xl mx-auto font-medium text-lg">From parts to repairs, we've got your motorcycle covered with professional service and premium quality products.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Card 1 -->
                <div class="bg-gray-50 dark:bg-gray-950 p-8 rounded-[2rem] border border-gray-100 dark:border-gray-800 transition-all duration-300 group" data-tilt data-tilt-max="15" data-tilt-speed="400" data-tilt-glare="true" data-tilt-max-glare="0.1">
                    <div class="w-14 h-14 bg-blue-100 dark:bg-blue-900/30 text-blue-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <i data-lucide="box" class="w-7 h-7"></i>
                    </div>
                    <h3 class="text-xl font-black text-gray-900 dark:text-white mb-3">Quality Parts</h3>
                    <p class="text-gray-500 dark:text-gray-400 font-medium text-sm leading-relaxed">Wide selection of genuine motorcycle parts and accessories for all major brands.</p>
                </div>
                <!-- Card 2 -->
                <div class="bg-gray-50 dark:bg-gray-950 p-8 rounded-[2rem] border border-gray-100 dark:border-gray-800 transition-all duration-300 group" data-tilt data-tilt-max="15" data-tilt-speed="400" data-tilt-glare="true" data-tilt-max-glare="0.1">
                    <div class="w-14 h-14 bg-purple-100 dark:bg-purple-900/30 text-purple-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <i data-lucide="search" class="w-7 h-7"></i>
                    </div>
                    <h3 class="text-xl font-black text-gray-900 dark:text-white mb-3">Smart Search</h3>
                    <p class="text-gray-500 dark:text-gray-400 font-medium text-sm leading-relaxed">Instantly check which parts fit your motorcycle model with our compatibility tool.</p>
                </div>
                <!-- Card 3 -->
                <div class="bg-gray-50 dark:bg-gray-950 p-8 rounded-[2rem] border border-gray-100 dark:border-gray-800 transition-all duration-300 group" data-tilt data-tilt-max="15" data-tilt-speed="400" data-tilt-glare="true" data-tilt-max-glare="0.1">
                    <div class="w-14 h-14 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <i data-lucide="wrench" class="w-7 h-7"></i>
                    </div>
                    <h3 class="text-xl font-black text-gray-900 dark:text-white mb-3">Expert Repairs</h3>
                    <p class="text-gray-500 dark:text-gray-400 font-medium text-sm leading-relaxed">Professional repair services by experienced mechanics with fast turnaround times.</p>
                </div>
                <!-- Card 4 -->
                <div class="bg-gray-50 dark:bg-gray-950 p-8 rounded-[2rem] border border-gray-100 dark:border-gray-800 transition-all duration-300 group" data-tilt data-tilt-max="15" data-tilt-speed="400" data-tilt-glare="true" data-tilt-max-glare="0.1">
                    <div class="w-14 h-14 bg-amber-100 dark:bg-amber-900/30 text-amber-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <i data-lucide="clipboard-check" class="w-7 h-7"></i>
                    </div>
                    <h3 class="text-xl font-black text-gray-900 dark:text-white mb-3">Pre-Orders</h3>
                    <p class="text-gray-500 dark:text-gray-400 font-medium text-sm leading-relaxed">Can't find what you need? Pre-order any custom part with a secure 50% down payment.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ABOUT --}}
    <section id="about" class="py-16 relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row items-center gap-16">
                <div class="flex-1 w-full relative">
                    <div class="absolute inset-0 bg-blue-600/10 rounded-[3rem] -rotate-6 transform scale-105 pointer-events-none"></div>
                    <img src="{{ asset('images/about.png') }}" alt="Expert Mechanics" class="w-full h-auto rounded-[3rem] shadow-2xl relative z-10 border border-gray-100 dark:border-gray-800" loading="lazy">
                </div>
                
                <div class="flex-1">
                    <div class="inline-flex items-center gap-2 text-indigo-600 dark:text-indigo-400 font-bold text-sm uppercase tracking-widest mb-4">
                        <i data-lucide="info" class="w-4 h-4"></i> About Us
                    </div>
                    <h2 class="text-4xl md:text-5xl font-black tracking-tighter mb-6 text-gray-900 dark:text-white">Trusted by Riders Across Bulacan</h2>
                    <p class="text-lg text-gray-600 dark:text-gray-400 font-medium mb-10 leading-relaxed">
                        Since 2009, Edmar Motorcycle Parts has been the go-to shop for riders in Pandi and beyond. We combine quality products with expert service to keep you safely on the road.
                    </p>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="flex items-center gap-3"><i data-lucide="check-circle" class="w-5 h-5 text-indigo-600"></i><span class="font-bold text-gray-700 dark:text-gray-300">Genuine OEM Parts</span></div>
                        <div class="flex items-center gap-3"><i data-lucide="check-circle" class="w-5 h-5 text-indigo-600"></i><span class="font-bold text-gray-700 dark:text-gray-300">Expert Mechanics</span></div>
                        <div class="flex items-center gap-3"><i data-lucide="check-circle" class="w-5 h-5 text-indigo-600"></i><span class="font-bold text-gray-700 dark:text-gray-300">Fast Turnaround</span></div>
                        <div class="flex items-center gap-3"><i data-lucide="check-circle" class="w-5 h-5 text-indigo-600"></i><span class="font-bold text-gray-700 dark:text-gray-300">Fair Pricing</span></div>
                        <div class="flex items-center gap-3"><i data-lucide="check-circle" class="w-5 h-5 text-indigo-600"></i><span class="font-bold text-gray-700 dark:text-gray-300">Gcash Payments</span></div>
                        <div class="flex items-center gap-3"><i data-lucide="check-circle" class="w-5 h-5 text-indigo-600"></i><span class="font-bold text-gray-700 dark:text-gray-300">15+ Years Experience</span></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- COMPATIBILITY --}}
    <section id="compat" class="py-16 relative overflow-hidden bg-white dark:bg-gray-900 border-y border-gray-100 dark:border-gray-800">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center mb-10">
                <div class="inline-flex items-center gap-2 text-indigo-600 dark:text-indigo-400 font-bold text-sm uppercase tracking-widest mb-4">
                    <i data-lucide="search" class="w-4 h-4"></i> Compatibility Checker
                </div>
                <h2 class="text-4xl font-black tracking-tighter mb-4 text-gray-900 dark:text-white">Find the Right Part</h2>
                <p class="text-gray-500 dark:text-gray-400 font-medium text-lg">Enter your motorcycle details and we'll instantly find the exact parts that fit your ride.</p>
            </div>

            <div class="bg-gradient-to-br from-indigo-600 to-indigo-900 rounded-[3rem] p-8 md:p-12 shadow-2xl shadow-indigo-500/30 overflow-hidden relative border border-indigo-500/20">
                <div class="absolute -top-24 -right-24 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
                
                <div class="relative z-10">
                    <form method="GET" action="/compatibility" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                        <input type="text" name="make" placeholder="Make (e.g. Yamaha)" class="flex-1 bg-white/10 border border-white/20 text-white placeholder-white/50 rounded-2xl px-6 py-4 focus:outline-none focus:ring-2 focus:ring-white/50 font-bold">
                        <input type="text" name="model" placeholder="Model (e.g. R6)" class="flex-1 bg-white/10 border border-white/20 text-white placeholder-white/50 rounded-2xl px-6 py-4 focus:outline-none focus:ring-2 focus:ring-white/50 font-bold">
                        <input type="text" name="part" placeholder="Part name" class="flex-1 bg-white/10 border border-white/20 text-white placeholder-white/50 rounded-2xl px-6 py-4 focus:outline-none focus:ring-2 focus:ring-white/50 font-bold">
                        <button type="submit" class="bg-white text-indigo-900 font-black px-8 py-4 rounded-2xl hover:bg-indigo-50 transition-colors shadow-lg shadow-black/10 flex items-center justify-center gap-2">
                            <i data-lucide="search" class="w-5 h-5"></i> Search
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    {{-- PREORDER --}}
    <section id="preorder" class="py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <div class="inline-flex items-center gap-2 text-indigo-600 dark:text-indigo-400 font-bold text-sm uppercase tracking-widest mb-4">
                    <i data-lucide="package" class="w-4 h-4"></i> Pre-Order System
                </div>
                <h2 class="text-4xl font-black tracking-tighter mb-4 text-gray-900 dark:text-white">Can't Find Your Part?</h2>
                <p class="text-gray-500 dark:text-gray-400 max-w-2xl mx-auto font-medium text-lg">We'll source it for you. Just place a pre-order with 50% down payment and we'll handle the rest.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">
                <!-- Steps -->
                <div class="space-y-6">
                    <div class="flex gap-6 items-start bg-white dark:bg-gray-900 p-6 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm">
                        <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 rounded-2xl flex items-center justify-center font-black text-xl shrink-0">1</div>
                        <div>
                            <h4 class="text-lg font-black text-gray-900 dark:text-white mb-1">Submit Pre-Order</h4>
                            <p class="text-gray-500 dark:text-gray-400 font-medium text-sm">Fill out the form with the specific part details you need.</p>
                        </div>
                    </div>
                    <div class="flex gap-6 items-start bg-white dark:bg-gray-900 p-6 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm">
                        <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 rounded-2xl flex items-center justify-center font-black text-xl shrink-0">2</div>
                        <div>
                            <h4 class="text-lg font-black text-gray-900 dark:text-white mb-1">Pay 50% Down Payment</h4>
                            <p class="text-gray-500 dark:text-gray-400 font-medium text-sm">We'll send a secure Gcash payment invoice directly to you.</p>
                        </div>
                    </div>
                    <div class="flex gap-6 items-start bg-white dark:bg-gray-900 p-6 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm">
                        <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 rounded-2xl flex items-center justify-center font-black text-xl shrink-0">3</div>
                        <div>
                            <h4 class="text-lg font-black text-gray-900 dark:text-white mb-1">We Process Your Order</h4>
                            <p class="text-gray-500 dark:text-gray-400 font-medium text-sm">Your genuine part will be specially ordered and reserved.</p>
                        </div>
                    </div>
                    <div class="flex gap-6 items-start bg-white dark:bg-gray-900 p-6 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm">
                        <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 rounded-2xl flex items-center justify-center font-black text-xl shrink-0">4</div>
                        <div>
                            <h4 class="text-lg font-black text-gray-900 dark:text-white mb-1">Pick Up & Pay Balance</h4>
                            <p class="text-gray-500 dark:text-gray-400 font-medium text-sm">Pay the remaining 50% when you pick up at our shop.</p>
                        </div>
                    </div>
                </div>

                <!-- Info Box -->
                <div class="bg-gray-900 dark:bg-gray-800 rounded-[3rem] p-10 text-white shadow-2xl relative overflow-hidden">
                    <div class="absolute -top-20 -right-20 w-64 h-64 bg-indigo-500/20 rounded-full blur-3xl pointer-events-none"></div>
                    <h3 class="text-2xl font-black mb-6 relative z-10">Payment & Pickup Info</h3>
                    <ul class="space-y-4 mb-10 relative z-10">
                        <li class="flex items-start gap-3">
                            <i data-lucide="check" class="w-5 h-5 text-indigo-400 shrink-0 mt-0.5"></i>
                            <span class="text-gray-300 text-sm"><strong class="text-white">Pre-Order:</strong> 50% down payment required via Gcash</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i data-lucide="check" class="w-5 h-5 text-indigo-400 shrink-0 mt-0.5"></i>
                            <span class="text-gray-300 text-sm"><strong class="text-white">Available Products:</strong> 100% payment required for checkout</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i data-lucide="check" class="w-5 h-5 text-indigo-400 shrink-0 mt-0.5"></i>
                            <span class="text-gray-300 text-sm"><strong class="text-white">Payment:</strong> Gcash Payment only accepted</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i data-lucide="check" class="w-5 h-5 text-indigo-400 shrink-0 mt-0.5"></i>
                            <span class="text-gray-300 text-sm"><strong class="text-white">Delivery:</strong> Pick up only at our shop in Pandi, Bulacan</span>
                        </li>
                    </ul>

                    <div class="relative z-10">
                        @guest
                            <a href="{{ route('register') }}" class="block w-full text-center bg-white text-gray-900 px-6 py-4 rounded-2xl font-black hover:bg-gray-100 transition-colors">Create Account to Pre-Order</a>
                        @endguest
                        @auth
                            <a href="/preorder-custom" class="block w-full text-center bg-indigo-600 text-white px-6 py-4 rounded-2xl font-black hover:bg-indigo-500 transition-colors shadow-lg shadow-indigo-500/30">Submit a Pre-Order</a>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- CONTACT --}}
    <section id="contact" class="py-16 bg-white dark:bg-gray-900 border-t border-gray-100 dark:border-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <div class="inline-flex items-center gap-2 text-indigo-600 dark:text-indigo-400 font-bold text-sm uppercase tracking-widest mb-4">
                    <i data-lucide="mail" class="w-4 h-4"></i> Get In Touch
                </div>
                <h2 class="text-4xl font-black tracking-tighter mb-4 text-gray-900 dark:text-white">Visit Our Shop</h2>
                <p class="text-gray-500 dark:text-gray-400 font-medium text-lg">We'd love to help you find the right parts. Visit us or get in touch!</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Info Cards -->
                <div class="lg:col-span-1 space-y-4">
                    <div class="bg-gray-50 dark:bg-gray-950 p-6 rounded-3xl border border-gray-100 dark:border-gray-800 flex items-center gap-4">
                        <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 rounded-2xl flex items-center justify-center shrink-0"><i data-lucide="map-pin" class="w-6 h-6"></i></div>
                        <div><h4 class="font-black text-gray-900 dark:text-white">Location</h4><p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pandi, Bulacan, Philippines</p></div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-950 p-6 rounded-3xl border border-gray-100 dark:border-gray-800 flex items-center gap-4">
                        <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 rounded-2xl flex items-center justify-center shrink-0"><i data-lucide="phone" class="w-6 h-6"></i></div>
                        <div><h4 class="font-black text-gray-900 dark:text-white">Phone</h4><p class="text-sm font-medium text-gray-500 dark:text-gray-400">+63 XXX XXX XXXX</p></div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-950 p-6 rounded-3xl border border-gray-100 dark:border-gray-800 flex items-center gap-4">
                        <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 rounded-2xl flex items-center justify-center shrink-0"><i data-lucide="mail" class="w-6 h-6"></i></div>
                        <div><h4 class="font-black text-gray-900 dark:text-white">Email</h4><p class="text-sm font-medium text-gray-500 dark:text-gray-400">contact@edmarmoto.ph</p></div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-950 p-6 rounded-3xl border border-gray-100 dark:border-gray-800 flex items-center gap-4">
                        <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 rounded-2xl flex items-center justify-center shrink-0"><i data-lucide="clock" class="w-6 h-6"></i></div>
                        <div><h4 class="font-black text-gray-900 dark:text-white">Shop Hours</h4><p class="text-sm font-medium text-gray-500 dark:text-gray-400">Open Daily: 6:00 AM — 6:00 PM</p></div>
                    </div>
                </div>

                <!-- Google Map -->
                <div class="lg:col-span-2 bg-gray-50 dark:bg-gray-950 rounded-[2rem] border border-gray-100 dark:border-gray-800 overflow-hidden h-80 lg:h-auto shadow-sm relative">
                    @if(config('services.google_maps.key'))
                        <iframe width="100%" height="100%" style="border:0; position:absolute; top:0; left:0; width:100%; height:100%;" loading="lazy" allowfullscreen 
                            src="https://www.google.com/maps/embed/v1/place?key={{ config('services.google_maps.key') }}&q=Edmar+Motorcycle+Parts,+Pandi,+Bulacan,+Philippines"></iframe>
                    @else
                        <div class="absolute inset-0 flex items-center justify-center">
                            <p class="text-gray-400 font-bold italic">Map will appear once API key is configured.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- FOOTER --}}
    <footer class="bg-white dark:bg-gray-900 border-t border-gray-100 dark:border-gray-800 pt-16 pb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
                <div class="md:col-span-2">
                    <div class="flex items-center gap-3 mb-6">
                        <img src="{{ asset('images/logo.png') . '?v=' . filemtime(public_path('images/logo.png')) }}" alt="EM Parts" class="h-10 w-auto grayscale opacity-80">
                        <h2 class="text-xl font-black tracking-tighter text-gray-900 dark:text-white">MOTOTRACK</h2>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400 font-medium max-w-sm">Your trusted partner for quality motorcycle parts and expert repair services in Pandi, Bulacan since 2009.</p>
                </div>
                <div>
                    <h4 class="font-black text-gray-900 dark:text-white uppercase tracking-widest text-sm mb-6">Quick Links</h4>
                    <ul class="space-y-4">
                        <li><a href="#home" class="text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 font-medium transition-colors">Home</a></li>
                        <li><a href="#about" class="text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 font-medium transition-colors">About Us</a></li>
                        <li><a href="#services" class="text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 font-medium transition-colors">Services</a></li>
                        <li><a href="/browse" class="text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 font-medium transition-colors">Shop Parts</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-black text-gray-900 dark:text-white uppercase tracking-widest text-sm mb-6">Contact</h4>
                    <ul class="space-y-4">
                        <li class="flex items-center gap-3 text-gray-500 dark:text-gray-400 font-medium"><i data-lucide="map-pin" class="w-4 h-4 text-gray-400"></i> Pandi, Bulacan</li>
                        <li class="flex items-center gap-3 text-gray-500 dark:text-gray-400 font-medium"><i data-lucide="phone" class="w-4 h-4 text-gray-400"></i> +63 912 345 6789</li>
                        <li class="flex items-center gap-3 text-gray-500 dark:text-gray-400 font-medium"><i data-lucide="clock" class="w-4 h-4 text-gray-400"></i> Daily: 6AM — 6PM</li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-100 dark:border-gray-800 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-gray-400 text-sm font-medium">&copy; 2026 Edmar Motorcycle Parts. All rights reserved.</p>
                <div class="flex items-center gap-2 text-sm font-bold text-gray-300 dark:text-gray-700">
                    <i data-lucide="shield-check" class="w-4 h-4"></i> Secured by MotoTrack System
                </div>
            </div>
        </div>
    </footer>

    <script>
        lucide.createIcons();
        
        function toggleTheme() {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            }
        }
    </script>
</body>
</html>
