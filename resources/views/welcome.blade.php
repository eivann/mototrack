<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" id="html-root" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

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

    <title>MotoTrack — Edmar Motorcycle Parts & Accessories</title>
    <meta name="description" content="Your trusted motorcycle parts shop in Pandi, Bulacan since 2009. Browse parts, check compatibility, and pre-order online.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@0.460.0"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vanilla-tilt/1.8.0/vanilla-tilt.min.js"></script>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-950 selection:bg-indigo-500 selection:text-white overflow-x-hidden">

    @include('layouts.navigation')

    {{-- HERO --}}
    <section id="home" class="relative pt-12 pb-16 lg:pt-16 lg:pb-20 overflow-hidden">
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
                    <a href="/compatibility" class="w-full sm:w-auto bg-white dark:bg-gray-900 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-800 px-8 py-4 rounded-2xl text-sm font-black uppercase tracking-widest hover:bg-gray-50 dark:hover:bg-gray-800 transition-all flex items-center justify-center gap-2 shadow-sm">
                        <i data-lucide="search" class="w-5 h-5"></i> Check Compatibility
                    </a>
                </div>
            </div>

            <div class="flex-1 relative mt-12 lg:mt-0 w-full max-w-lg lg:max-w-none mx-auto" data-tilt data-tilt-max="10" data-tilt-speed="400" data-tilt-glare="true" data-tilt-max-glare="0.2">
                <div class="absolute inset-0 bg-indigo-600/10 dark:bg-indigo-900/20 rounded-full blur-[100px] pointer-events-none -z-10"></div>
                <img src="{{ asset('images/hero.jpg') }}" alt="Motorcycle Parts Shop" class="w-full h-auto drop-shadow-2xl relative z-10 hover:scale-[1.02] transition-transform duration-500" fetchpriority="high">
                
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
    <section id="about" class="py-24 relative overflow-hidden bg-gray-900 dark:bg-gray-950 text-white border-y border-gray-800">
        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-5"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="flex flex-col lg:flex-row items-center gap-16">
                <div class="flex-1 w-full relative">
                    <div class="absolute inset-0 bg-blue-600/20 rounded-[3rem] -rotate-6 transform scale-105 pointer-events-none"></div>
                    <img src="{{ asset('images/about.jpg') }}" alt="Expert Mechanics" class="w-full h-auto rounded-[3rem] shadow-2xl relative z-10 border border-gray-700" loading="lazy">
                </div>
                
                <div class="flex-1">
                    <div class="inline-flex items-center gap-2 text-indigo-400 font-bold text-sm uppercase tracking-widest mb-4">
                        <i data-lucide="info" class="w-4 h-4"></i> About Us
                    </div>
                    <h2 class="text-4xl md:text-5xl font-black tracking-tighter mb-6 text-white">Trusted by Riders Across Bulacan</h2>
                    <p class="text-lg text-gray-400 font-medium mb-10 leading-relaxed">
                        Since 2009, Edmar Motorcycle Parts has been the go-to shop for riders in Pandi and beyond. We combine quality products with expert service to keep you safely on the road.
                    </p>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="flex items-center gap-3"><i data-lucide="check-circle" class="w-5 h-5 text-indigo-500"></i><span class="font-bold text-gray-300">Genuine OEM Parts</span></div>
                        <div class="flex items-center gap-3"><i data-lucide="check-circle" class="w-5 h-5 text-indigo-500"></i><span class="font-bold text-gray-300">Expert Mechanics</span></div>
                        <div class="flex items-center gap-3"><i data-lucide="check-circle" class="w-5 h-5 text-indigo-500"></i><span class="font-bold text-gray-300">Fast Turnaround</span></div>
                        <div class="flex items-center gap-3"><i data-lucide="check-circle" class="w-5 h-5 text-indigo-500"></i><span class="font-bold text-gray-300">Fair Pricing</span></div>
                        <div class="flex items-center gap-3"><i data-lucide="check-circle" class="w-5 h-5 text-indigo-500"></i><span class="font-bold text-gray-300">Gcash Payments</span></div>
                        <div class="flex items-center gap-3"><i data-lucide="check-circle" class="w-5 h-5 text-indigo-500"></i><span class="font-bold text-gray-300">15+ Years Experience</span></div>
                    </div>
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
                        <div><h4 class="font-black text-gray-900 dark:text-white">Phone</h4><p class="text-sm font-medium text-gray-500 dark:text-gray-400">+63 977-6634-982</p></div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-950 p-6 rounded-3xl border border-gray-100 dark:border-gray-800 flex items-center gap-4">
                        <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 rounded-2xl flex items-center justify-center shrink-0"><i data-lucide="mail" class="w-6 h-6"></i></div>
                        <div><h4 class="font-black text-gray-900 dark:text-white">Email</h4><p class="text-sm font-medium text-gray-500 dark:text-gray-400"><a href="mailto:edmarmotorshop@gmail.com" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">edmarmotorshop@gmail.com</a></p></div>
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
                        <img src="{{ asset('images/logo-light.svg') }}" alt="EM Parts" class="h-10 w-auto block dark:hidden">
                        <img src="{{ asset('images/logo-dark.svg') }}" alt="EM Parts" class="h-10 w-auto hidden dark:block">
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
                        <li class="flex items-center gap-3 text-gray-500 dark:text-gray-400 font-medium"><i data-lucide="phone" class="w-4 h-4 text-gray-400"></i> +63 977-6634-982</li>
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

        // Conditionally disable Vanilla Tilt on touch devices for performance
        if ('ontouchstart' in window || navigator.maxTouchPoints > 0) {
            document.querySelectorAll('[data-tilt]').forEach(el => {
                if (el.vanillaTilt) el.vanillaTilt.destroy();
                el.removeAttribute('data-tilt');
            });
        }

        // Inline Compatibility Checker AJAX Engine
        (function() {
            const form = document.getElementById('compatForm');
            const makeInput = document.getElementById('compatMake');
            const modelInput = document.getElementById('compatModel');
            const partInput = document.getElementById('compatPart');
            const resultsBox = document.getElementById('compatResultsBox');
            const isAuthenticated = {{ auth()->check() ? 'true' : 'false' }};
            const toastContainer = document.getElementById('toast-container');
            const suggestionTimers = {};

            if (!form) return;

            function loadSuggestions(field, value, datalistId) {
                clearTimeout(suggestionTimers[datalistId]);
                const datalist = document.getElementById(datalistId);
                const query = (value || '').trim();

                if (!datalist || query.length < 1) {
                    if (datalist) datalist.innerHTML = '';
                    return;
                }

                suggestionTimers[datalistId] = setTimeout(() => {
                    fetch(`{{ route('inventory.suggestions') }}?field=${encodeURIComponent(field)}&q=${encodeURIComponent(query)}`, {
                        headers: { 'Accept': 'application/json' }
                    })
                        .then(response => response.ok ? response.json() : [])
                        .then(data => {
                            datalist.innerHTML = '';
                            data.forEach(item => {
                                const option = document.createElement('option');
                                option.value = item.value;
                                datalist.appendChild(option);
                            });
                        })
                        .catch(() => { datalist.innerHTML = ''; });
                }, 180);
            }

            makeInput.addEventListener('input', () => loadSuggestions('brand', makeInput.value, 'landingMakeSuggestions'));
            modelInput.addEventListener('input', () => loadSuggestions('model', modelInput.value, 'landingModelSuggestions'));
            partInput.addEventListener('input', () => loadSuggestions('part_name', partInput.value, 'landingPartSuggestions'));

            // Toast Notification System
            function showToast(message, type = 'success') {
                const toast = document.createElement('div');
                const colors = type === 'success' ? 'bg-gray-900 dark:bg-gray-800 text-white' : 'bg-red-500 text-white';
                const icon = type === 'success' 
                    ? '<svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>'
                    : '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';
                
                toast.className = `flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg transition-all duration-300 pointer-events-auto ${colors}`;
                toast.innerHTML = `${icon} <span class="font-medium text-sm">${message}</span>`;
                
                toastContainer.appendChild(toast);

                setTimeout(() => {
                    toast.style.opacity = '0';
                    setTimeout(() => toast.remove(), 300);
                }, 3000);
            }

            // Add to Cart Logic
            async function addToCart(id) {
                if (!isAuthenticated) {
                    window.location.href = "{{ route('login') }}";
                    return;
                }

                const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
                const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';

                try {
                    const response = await fetch(`/cart/add/${id}`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    });

                    const data = await response.json();

                    if (response.ok) {
                        showToast(data.success || 'Item added to cart successfully!');
                    } else {
                        showToast(data.error || 'Failed to add item', 'error');
                    }
                } catch (error) {
                    console.error('Cart Error:', error);
                    showToast('Network error or session expired', 'error');
                }
            }

            // Event Delegation for buttons
            resultsBox.addEventListener('click', (e) => {
                const btn = e.target.closest('.add-to-cart-btn');
                if (btn) {
                    e.preventDefault();
                    const id = btn.dataset.id;
                    addToCart(id);
                }
            });

            // Highlight matching text
            function highlightText(text, query) {
                if (!query || !text) return text;
                const terms = query.trim().split(/\s+/).filter(t => t.length > 0);
                if (terms.length === 0) return text;
                const regex = new RegExp(`(${terms.join('|')})`, 'gi');
                return String(text).replace(regex, '<span class="highlight">$1</span>');
            }

            function triggerSearch(e) {
                if (e) e.preventDefault();

                const make = makeInput.value.trim();
                const model = modelInput.value.trim();
                const part = partInput.value.trim();
                const query = `${make} ${model} ${part}`.trim();

                if (query.length < 2) {
                    showToast('Please enter at least 2 characters to search.', 'error');
                    return;
                }

                resultsBox.innerHTML = `
                    <div class="flex items-center justify-center p-12">
                        <svg class="animate-spin h-8 w-8 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                `;

                fetch(`/search-parts?model=${encodeURIComponent(query)}`, {
                    headers: { 'Accept': 'application/json' }
                })
                    .then(res => {
                        if (!res.ok) throw new Error('Network response was not ok');
                        return res.json();
                    })
                    .then(data => {
                        resultsBox.innerHTML = '';

                        if (!Array.isArray(data) || data.length === 0) {
                            resultsBox.innerHTML = `
                                <div class="bg-red-50/80 dark:bg-red-900/10 rounded-[2rem] border border-red-200/60 dark:border-red-900/30 p-8 text-center max-w-3xl mx-auto mt-4">
                                    <p class="font-bold text-[17px] text-[#dc2626] dark:text-red-400 mb-2">No matching parts found</p>
                                    <p class="text-[13px] text-red-400 dark:text-red-500 font-bold tracking-wide">Try adjusting your search terms or check a different motorcycle model.</p>
                                </div>
                            `;
                        } else {
                            const grid = document.createElement('div');
                            grid.className = 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4';
                            
                            data.forEach(item => {
                                const card = document.createElement('div');
                                card.className = 'bg-white dark:bg-gray-800 rounded-xl overflow-hidden shadow-sm hover:shadow-lg border border-gray-100 dark:border-gray-700 hover:border-indigo-300 dark:hover:border-indigo-500 transition-all duration-300 flex flex-col group';
                                
                                const stockBadge = item.quantity > 0
                                    ? `<span class="text-[11px] font-medium text-gray-500">${item.quantity} in stock</span>`
                                    : `<span class="text-[11px] font-medium text-red-500">Out of Stock</span>`;

                                let actionBtn = '';
                                if (isAuthenticated) {
                                    actionBtn = item.quantity > 0
                                        ? `<button data-id="${item.id}" class="add-to-cart-btn mt-3 w-full bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold py-2 rounded-lg shadow-sm transition-colors active:scale-95 cursor-pointer">Add to Cart</button>`
                                        : `<a href="/preorder/${item.id}" class="mt-3 block text-center w-full bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold py-2 rounded-lg shadow-sm transition-colors active:scale-95">Pre-order</a>`;
                                } else {
                                    actionBtn = `<a href="{{ route('login') }}" class="mt-3 block text-center w-full bg-gray-100 text-gray-600 text-sm font-semibold py-2 rounded-lg hover:bg-gray-200">Login to Order</a>`;
                                }

                                const brandText = item.brand ? `<p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-1">${item.brand}</p>` : '';
                                const modelBadge = item.model ? `<div class="mt-1 flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-700 rounded px-1.5 py-0.5 inline-block"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg><span class="truncate max-w-[120px]">${item.model}</span></div>` : '';
                                
                                const imageHtml = item.image_url 
                                    ? `<img src="${item.image_url}" alt="${item.part_name}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">`
                                    : `<svg class="w-16 h-16 text-gray-300 dark:text-gray-500 group-hover:scale-110 group-hover:text-indigo-200 transition-all duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>`;

                                card.innerHTML = `
                                    <div class="aspect-square bg-gray-50 dark:bg-gray-700 flex items-center justify-center p-0 relative overflow-hidden group-hover:bg-gray-100 dark:group-hover:bg-gray-600 transition-colors border-b border-gray-100 dark:border-gray-700">
                                        ${imageHtml}
                                        ${item.quantity <= 0 ? '<div class="absolute top-2 right-2 bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded uppercase tracking-wider shadow-sm">Sold Out</div>' : ''}
                                    </div>
                                    <div class="p-4 flex-1 flex flex-col text-left">
                                        ${brandText}
                                        <h3 class="text-sm font-medium text-gray-800 dark:text-white line-clamp-2 leading-snug flex-1" title="${item.part_name}">
                                            ${highlightText(item.part_name, query)}
                                        </h3>
                                        ${modelBadge}
                                        <div class="flex items-end justify-between mt-3">
                                            <div class="leading-none">
                                                <span class="text-xs font-semibold text-orange-500 align-top">₱</span><span class="text-lg font-bold text-orange-500">${parseFloat(item.price).toFixed(2)}</span>
                                            </div>
                                            ${stockBadge}
                                        </div>
                                        ${actionBtn}
                                    </div>
                                `;
                                grid.appendChild(card);
                            });
                            resultsBox.appendChild(grid);
                        }
                    }).catch((err) => {
                        console.error(err);
                        resultsBox.innerHTML = `
                            <div class="bg-red-50/80 dark:bg-red-900/10 rounded-[2rem] border border-red-200/60 dark:border-red-900/30 p-8 text-center max-w-3xl mx-auto mt-4">
                                <p class="font-bold text-[17px] text-[#dc2626] dark:text-red-400 mb-2">Error connecting to server</p>
                                <p class="text-[13px] text-red-400 dark:text-red-500 font-bold tracking-wide">Please try again in a moment or type slower to avoid rate limits.</p>
                            </div>
                        `;
                    });
            }

            form.addEventListener('submit', triggerSearch);
        })();
    </script>
</body>
</html>
