<nav x-data="{ open: false }" class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-xl sticky top-0 z-50 border-b border-gray-100 dark:border-gray-800 transition-all duration-300 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-20">
            <div class="flex items-center">
                <!-- Logo -->
                <div class="shrink-0 flex items-center transform hover:scale-105 transition-transform duration-300 mr-12">
                    <a href="{{ Auth::user()->role === 'owner' ? route('dashboard') : route('customer.dashboard') }}" class="flex items-center gap-3">
                        <div class="bg-blue-600 p-2 rounded-xl shadow-lg shadow-blue-200 dark:shadow-none">
                            <x-application-logo class="block h-7 w-auto fill-current text-white" />
                        </div>
                        <span class="text-2xl font-black tracking-tighter text-gray-900 dark:text-white hidden lg:block">
                            MOTO<span class="text-blue-600">TRACK</span>
                        </span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-1 sm:-my-px sm:flex h-full">
                    @php
                        $linkClasses = "inline-flex items-center px-4 h-full text-sm font-bold transition-all duration-300 group relative";
                        $activeClasses = "text-blue-600";
                        $inactiveClasses = "text-gray-600 hover:text-blue-600 dark:text-gray-300 dark:hover:text-white";
                    @endphp

                    <a href="/" class="{{ $linkClasses }} {{ request()->is('/') ? $activeClasses : $inactiveClasses }}">
                        <i data-lucide="home" class="w-5 h-5 mr-2.5 transition-all duration-300 group-hover:-translate-y-1"></i>
                        <span>Home</span>
                        @if(request()->is('/'))
                            <span class="absolute bottom-0 left-4 right-4 h-1 bg-blue-600 rounded-t-full shadow-[0_-2px_10px_rgba(37,99,235,0.4)]"></span>
                        @else
                            <span class="absolute bottom-0 left-1/2 -translate-x-1/2 w-0 h-1 bg-blue-400/50 rounded-t-full transition-all duration-300 group-hover:w-8"></span>
                        @endif
                    </a>
                    
                    @if(Auth::user()->role !== 'owner')
                        <a href="{{ route('browse') }}" class="{{ $linkClasses }} {{ request()->routeIs('browse') ? $activeClasses : $inactiveClasses }}">
                            <i data-lucide="shopping-bag" class="w-5 h-5 mr-2.5 transition-all duration-300 group-hover:-translate-y-1"></i>
                            <span>Browse</span>
                            @if(request()->routeIs('browse'))
                                <span class="absolute bottom-0 left-4 right-4 h-1 bg-blue-600 rounded-t-full shadow-[0_-2px_10px_rgba(37,99,235,0.4)]"></span>
                            @else
                                <span class="absolute bottom-0 left-1/2 -translate-x-1/2 w-0 h-1 bg-blue-400/50 rounded-t-full transition-all duration-300 group-hover:w-8"></span>
                            @endif
                        </a>
                        
                        <a href="{{ route('customer.dashboard') }}" class="{{ $linkClasses }} {{ request()->routeIs('customer.dashboard') ? $activeClasses : $inactiveClasses }}">
                            <i data-lucide="layout-dashboard" class="w-5 h-5 mr-2.5 transition-all duration-300 group-hover:-translate-y-1"></i>
                            <span>Dashboard</span>
                            @if(request()->routeIs('customer.dashboard'))
                                <span class="absolute bottom-0 left-4 right-4 h-1 bg-blue-600 rounded-t-full shadow-[0_-2px_10px_rgba(37,99,235,0.4)]"></span>
                            @else
                                <span class="absolute bottom-0 left-1/2 -translate-x-1/2 w-0 h-1 bg-blue-400/50 rounded-t-full transition-all duration-300 group-hover:w-8"></span>
                            @endif
                        </a>

                        <a href="{{ route('cart.view') }}" class="{{ $linkClasses }} {{ request()->routeIs('cart.view') ? $activeClasses : $inactiveClasses }}">
                            <div class="relative transition-all duration-300 group-hover:-translate-y-1 flex items-center">
                                <i data-lucide="shopping-cart" class="w-5 h-5 mr-2.5"></i>
                                @if(count(session('cart', [])) > 0)
                                    <span class="absolute -top-3 -left-2 flex h-5 w-5">
                                        <span class="relative inline-flex rounded-full h-5 w-5 bg-red-600 text-[10px] text-white items-center justify-center font-black border-2 border-white dark:border-gray-900 shadow-sm">
                                            {{ count(session('cart', [])) }}
                                        </span>
                                    </span>
                                @endif
                            </div>
                            <span>Cart</span>
                            @if(request()->routeIs('cart.view'))
                                <span class="absolute bottom-0 left-4 right-4 h-1 bg-blue-600 rounded-t-full shadow-[0_-2px_10px_rgba(37,99,235,0.4)]"></span>
                            @else
                                <span class="absolute bottom-0 left-1/2 -translate-x-1/2 w-0 h-1 bg-blue-400/50 rounded-t-full transition-all duration-300 group-hover:w-8"></span>
                            @endif
                        </a>
                    @else
                        <a href="{{ route('dashboard') }}" class="{{ $linkClasses }} {{ request()->routeIs('dashboard') ? $activeClasses : $inactiveClasses }}">
                            <i data-lucide="activity" class="w-5 h-5 mr-2.5 transition-transform group-hover:-translate-y-0.5"></i>
                            <span>Admin Dashboard</span>
                            @if(request()->routeIs('dashboard'))
                                <span class="absolute bottom-0 left-4 right-4 h-1 bg-blue-600 rounded-t-full"></span>
                            @endif
                        </a>
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="56">
                    <x-slot name="trigger">
                        <button class="group inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-sm font-bold rounded-2xl text-gray-800 dark:text-gray-200 hover:bg-white dark:hover:bg-gray-700 hover:shadow-md transition-all duration-300">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 flex-shrink-0 aspect-square rounded-xl bg-blue-600 flex items-center justify-center text-white text-base font-black shadow-lg group-hover:rotate-6 transition-transform">
                                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                </div>
                                <div class="text-left flex-grow">
                                    <p class="text-[10px] text-blue-600 dark:text-blue-400 font-black uppercase tracking-widest leading-none mb-1">Account</p>
                                    <p class="leading-none font-bold text-gray-900 dark:text-white whitespace-nowrap">{{ Auth::user()->name }}</p>
                                </div>
                            </div>

                            <div class="ms-3">
                                <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400 group-hover:text-blue-600 transition-colors"></i>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="px-4 py-3 bg-gray-50/50 dark:bg-gray-800/50">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">User Settings</p>
                        </div>
                        <x-dropdown-link :href="route('profile.edit')" class="flex items-center gap-3 py-3 transition-colors">
                            <div class="p-1.5 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-blue-600">
                                <i data-lucide="user" class="w-4 h-4"></i>
                            </div>
                            <span class="font-semibold">{{ __('My Profile') }}</span>
                        </x-dropdown-link>

                        <div class="border-t border-gray-100 dark:border-gray-800"></div>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                    class="flex items-center gap-3 py-3 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/10 transition-colors"
                                    onclick="event.preventDefault(); this.closest('form').submit();">
                                <div class="p-1.5 bg-red-50 dark:bg-red-900/20 rounded-lg">
                                    <i data-lucide="log-out" class="w-4 h-4"></i>
                                </div>
                                <span class="font-semibold">{{ __('Sign Out') }}</span>
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-3 rounded-2xl text-gray-400 dark:text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 focus:outline-none transition-all duration-300">
                    <i :class="{'hidden': open, 'block': ! open }" data-lucide="menu" class="w-6 h-6"></i>
                    <i :class="{'hidden': ! open, 'block': open }" data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="hidden sm:hidden border-t border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-2xl">
        <div class="pt-4 pb-6 space-y-2 px-4">
            <a href="/" class="flex items-center px-4 py-4 rounded-2xl text-base font-bold {{ request()->is('/') ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50' }}">
                <i data-lucide="home" class="w-5 h-5 mr-4"></i>
                Home
            </a>
            
            @if(Auth::user()->role !== 'owner')
                <a href="/browse" class="flex items-center px-4 py-4 rounded-2xl text-base font-bold {{ request()->is('browse') ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50' }}">
                    <i data-lucide="shopping-bag" class="w-5 h-5 mr-4"></i>
                    Browse Products
                </a>
                
                <a href="{{ route('customer.dashboard') }}" class="flex items-center px-4 py-4 rounded-2xl text-base font-bold {{ request()->routeIs('customer.dashboard') ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50' }}">
                    <i data-lucide="layout-dashboard" class="w-5 h-5 mr-4"></i>
                    Customer Dashboard
                </a>

                <a href="/cart" class="flex items-center px-4 py-4 rounded-2xl text-base font-bold {{ request()->is('cart') ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50' }}">
                    <i data-lucide="shopping-cart" class="w-5 h-5 mr-4"></i>
                    My Cart
                </a>
            @else
                <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-4 rounded-2xl text-base font-bold {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50' }}">
                    <i data-lucide="activity" class="w-5 h-5 mr-4"></i>
                    Admin Dashboard
                </a>
            @endif
        </div>

        <div class="pt-6 pb-8 border-t border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50">
            <div class="flex items-center px-8 mb-6">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-600 to-blue-400 flex items-center justify-center text-white text-lg font-black shadow-lg mr-4">
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                </div>
                <div>
                    <div class="font-black text-gray-900 dark:text-white">{{ Auth::user()->name }}</div>
                    <div class="text-sm text-gray-500 font-medium">{{ Auth::user()->email }}</div>
                </div>
            </div>

            <div class="px-4 space-y-2">
                <a href="{{ route('profile.edit') }}" class="flex items-center px-4 py-4 rounded-2xl text-base font-bold text-gray-600 hover:bg-white transition-colors">
                    <i data-lucide="user" class="w-5 h-5 mr-4 text-gray-400"></i>
                    Profile Settings
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <a href="{{ route('logout') }}"
                            class="flex items-center px-4 py-4 rounded-2xl text-base font-bold text-red-600 hover:bg-red-50 transition-colors"
                            onclick="event.preventDefault(); this.closest('form').submit();">
                        <i data-lucide="log-out" class="w-5 h-5 mr-4"></i>
                        Sign Out
                    </a>
                </form>
            </div>
        </div>
    </div>
</nav>
