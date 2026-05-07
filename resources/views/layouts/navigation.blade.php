<nav x-data="{ open: false }" class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-xl sticky top-0 z-50 border-b border-gray-100 dark:border-gray-800 transition-all duration-300 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-20">
            <div class="flex items-center">
                <!-- Logo -->
                <div class="shrink-0 flex items-center transform hover:scale-105 transition-transform duration-300 mr-12">
                    <a href="{{ auth()->check() ? (auth()->user()->role === 'owner' ? route('dashboard') : route('customer.dashboard')) : url('/') }}" class="flex items-center gap-4">
                        <img src="{{ asset('images/logo.png') }}" alt="EM Parts" class="h-12 w-auto object-contain">
                        <div class="flex flex-col leading-none hidden lg:flex">
                            <span class="text-xl font-black tracking-tighter text-gray-900 dark:text-white uppercase">Edmar Motorcycle</span>
                            <span class="text-[10px] font-bold tracking-widest text-gray-500 dark:text-gray-400 uppercase mt-0.5">Parts and Accessories</span>
                        </div>
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
                    
                    @if(!auth()->check() || auth()->user()->role !== 'owner')
                        <a href="{{ route('browse') }}" class="{{ $linkClasses }} {{ request()->routeIs('browse') ? $activeClasses : $inactiveClasses }}">
                            <i data-lucide="shopping-bag" class="w-5 h-5 mr-2.5 transition-all duration-300 group-hover:-translate-y-1"></i>
                            <span>Browse</span>
                            @if(request()->routeIs('browse'))
                                <span class="absolute bottom-0 left-4 right-4 h-1 bg-blue-600 rounded-t-full shadow-[0_-2px_10px_rgba(37,99,235,0.4)]"></span>
                            @else
                                <span class="absolute bottom-0 left-1/2 -translate-x-1/2 w-0 h-1 bg-blue-400/50 rounded-t-full transition-all duration-300 group-hover:w-8"></span>
                            @endif
                        </a>

                        <a href="{{ route('compatibility.view') }}" class="{{ $linkClasses }} {{ request()->routeIs('compatibility.view') ? $activeClasses : $inactiveClasses }}">
                            <i data-lucide="search" class="w-5 h-5 mr-2.5 transition-all duration-300 group-hover:-translate-y-1"></i>
                            <span>Compatibility</span>
                            @if(request()->routeIs('compatibility.view'))
                                <span class="absolute bottom-0 left-4 right-4 h-1 bg-blue-600 rounded-t-full shadow-[0_-2px_10px_rgba(37,99,235,0.4)]"></span>
                            @else
                                <span class="absolute bottom-0 left-1/2 -translate-x-1/2 w-0 h-1 bg-blue-400/50 rounded-t-full transition-all duration-300 group-hover:w-8"></span>
                            @endif
                        </a>
                    @endif

                    @auth
                        @if(auth()->user()->role !== 'owner')
                            <a href="{{ route('customer.dashboard') }}" class="{{ $linkClasses }} {{ request()->routeIs('customer.dashboard') ? $activeClasses : $inactiveClasses }}">
                                <i data-lucide="layout-dashboard" class="w-5 h-5 mr-2.5 transition-all duration-300 group-hover:-translate-y-1"></i>
                                <span>{{ auth()->user()->role === 'owner' ? 'My Orders' : 'Dashboard' }}</span>
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
                        @endif

                        @if(auth()->user()->role === 'owner')
                            <a href="{{ route('dashboard') }}" class="{{ $linkClasses }} {{ request()->routeIs('dashboard') ? $activeClasses : $inactiveClasses }}">
                                <i data-lucide="activity" class="w-5 h-5 mr-2.5 transition-transform group-hover:-translate-y-0.5"></i>
                                <span>Admin Panel</span>
                                @if(request()->routeIs('dashboard'))
                                    <span class="absolute bottom-0 left-4 right-4 h-1 bg-blue-600 rounded-t-full shadow-[0_-2px_10px_rgba(37,99,235,0.4)]"></span>
                                @else
                                    <span class="absolute bottom-0 left-1/2 -translate-x-1/2 w-0 h-1 bg-blue-400/50 rounded-t-full transition-all duration-300 group-hover:w-8"></span>
                                @endif
                            </a>
                        @endif
                    @endauth
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6 gap-2">
                {{-- Notification Bell --}}
                @auth
                <div class="relative group" x-data="{ open: false, hasNew: {{ auth()->user()->unreadNotifications->count() > 0 ? 'true' : 'false' }} }" @click.away="open = false">
                    <button @click="open = !open; hasNew = false" 
                        class="w-10 h-10 rounded-full flex items-center justify-center border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 hover:text-indigo-600 dark:hover:text-indigo-400 transition-all duration-300 relative">
                        <i data-lucide="bell" class="w-4 h-4" :class="hasNew ? 'animate-bounce' : ''"></i>
                        <template x-if="hasNew">
                            <span class="absolute top-0 right-0 flex h-3 w-3">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-indigo-600 border-2 border-white dark:border-gray-900"></span>
                            </span>
                        </template>
                    </button>

                    <div x-show="open" 
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                         x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                         class="absolute right-0 mt-3 w-80 md:w-[400px] bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-700 overflow-hidden z-[70]"
                         x-cloak>
                        
                        <div class="p-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50/50 dark:bg-gray-900/50">
                            <h3 class="font-bold text-sm text-gray-900 dark:text-white uppercase tracking-tight">Recent Notifications</h3>
                            <template x-if="hasNew">
                                <span class="px-2 py-0.5 bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 text-[10px] font-black rounded-full">{{ auth()->user()->unreadNotifications->count() }} NEW</span>
                            </template>
                        </div>

                        <div class="overflow-y-auto scrollbar-thin" style="max-height: 280px;">
                            @forelse(auth()->user()->notifications->take(10) as $notification)
                                <div class="relative flex items-center gap-4 p-4 {{ $notification->unread() ? 'bg-indigo-50/50 dark:bg-indigo-900/10' : '' }} hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors border-b border-gray-50 dark:border-gray-700 last:border-0 group">
                                    <!-- Left: Icon/Avatar -->
                                    <div class="shrink-0">
                                        <div class="w-10 h-10 rounded-full {{ $notification->unread() ? 'bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600' : 'bg-gray-100 dark:bg-gray-700 text-gray-400' }} flex items-center justify-center">
                                            <i data-lucide="{{ str_contains(strtolower($notification->data['title']), 'repair') ? 'wrench' : 'package' }}" class="w-5 h-5"></i>
                                        </div>
                                    </div>
                                    
                                    <!-- Center: Content -->
                                    <div class="flex-1 min-w-0 pr-6">
                                        <h4 class="text-xs font-bold {{ $notification->unread() ? 'text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400' }} truncate">
                                            {{ $notification->data['title'] }}
                                        </h4>
                                        <p class="text-[11px] {{ $notification->unread() ? 'text-gray-600 dark:text-gray-400' : 'text-gray-400 dark:text-gray-500' }} line-clamp-1 mt-0.5">
                                            {{ $notification->data['message'] }}
                                        </p>
                                        <p class="text-[9px] text-gray-400 dark:text-gray-500 mt-1 uppercase tracking-widest font-semibold">
                                            {{ $notification->created_at->diffForHumans() }}
                                        </p>
                                    </div>

                                    <!-- Right: Action (Only for unread) -->
                                    @if($notification->unread())
                                    <form action="{{ route('notifications.read', $notification->id) }}" method="POST" class="absolute right-4 opacity-0 group-hover:opacity-100 transition-opacity">
                                        @csrf
                                        <button type="submit" class="p-1 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 rounded text-indigo-600 dark:text-indigo-400" title="Mark as read">
                                            <i data-lucide="check" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                    @endif

                                    <!-- Link Overlay -->
                                    <a href="{{ $notification->data['link'] ?? '#' }}" class="absolute inset-0 z-0 {{ $notification->unread() ? 'pr-12' : '' }}"></a>
                                </div>
                            @empty
                                <div class="p-8 text-center">
                                    <div class="bg-gray-100 dark:bg-gray-700 w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-3">
                                        <i data-lucide="bell-off" class="w-5 h-5 text-gray-400"></i>
                                    </div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">All caught up!</p>
                                </div>
                            @endforelse
                        </div>

                        <!-- Dropdown Footer Actions -->
                        <div class="p-3 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <a href="{{ auth()->user()->role === 'owner' ? route('low-stock.index') : route('customer.dashboard') }}" class="flex items-center gap-1.5 text-[11px] font-bold text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                    <i data-lucide="list" class="w-3.5 h-3.5"></i>
                                    See all
                                </a>
                                <form action="{{ route('notifications.read_all') }}" method="POST">
                                    @csrf
                                    <button class="flex items-center gap-1.5 text-[11px] font-bold text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                        <i data-lucide="check-check" class="w-3.5 h-3.5"></i>
                                        Mark all read
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                @endauth

                {{-- Global Theme Toggle --}}
                <button id="globalThemeToggle" title="Toggle Light/Dark Mode"
                    class="w-10 h-10 rounded-full flex items-center justify-center border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:text-blue-600 dark:hover:text-blue-400 transition-all duration-300">
                    <i id="globalThemeIcon" data-lucide="moon" class="w-4 h-4"></i>
                </button>
                @auth
                    <x-dropdown align="right" width="56">
                        <x-slot name="trigger">
                            <button class="group inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-sm font-bold rounded-2xl text-gray-800 dark:text-gray-200 hover:bg-white dark:hover:bg-gray-700 hover:shadow-md transition-all duration-300">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 flex-shrink-0 aspect-square rounded-xl bg-blue-600 flex items-center justify-center text-white text-base font-black shadow-lg group-hover:rotate-6 transition-transform">
                                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                    </div>
                                    <div class="text-left flex-grow">
                                        <p class="text-[10px] text-blue-600 dark:text-blue-400 font-black uppercase tracking-widest leading-none mb-1">Account</p>
                                        <p class="leading-none font-bold text-gray-900 dark:text-white whitespace-nowrap text-ellipsis overflow-hidden max-w-[100px]">{{ auth()->user()->name }}</p>
                                    </div>
                                </div>

                                <div class="ms-3">
                                    <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400 group-hover:text-blue-600 transition-colors"></i>
                                </div>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="px-4 py-3 bg-gray-50/50 dark:bg-gray-800/50 border-b border-gray-100 dark:border-gray-700">
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
                @else
                    <div class="flex items-center gap-2">
                        <a href="{{ route('login') }}" class="px-5 py-2 text-sm font-bold text-gray-600 hover:text-blue-600 transition-colors">Sign In</a>
                        <a href="{{ route('register') }}" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-xl shadow-lg shadow-blue-200 dark:shadow-none transition-all active:scale-95">Get Started</a>
                    </div>
                @endauth
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
    <div x-show="open" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="sm:hidden border-t border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-2xl">
        <div class="pt-4 pb-6 space-y-2 px-4">
            <a href="/" class="flex items-center px-4 py-4 rounded-2xl text-base font-bold {{ request()->is('/') ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50' }}">
                <i data-lucide="home" class="w-5 h-5 mr-4"></i>
                Home
            </a>

            @if(!auth()->check() || auth()->user()->role !== 'owner')
                <a href="/browse" class="flex items-center px-4 py-4 rounded-2xl text-base font-bold {{ request()->is('browse') ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50' }}">
                    <i data-lucide="shopping-bag" class="w-5 h-5 mr-4"></i>
                    Browse Products
                </a>

                <a href="/compatibility" class="flex items-center px-4 py-4 rounded-2xl text-base font-bold {{ request()->is('compatibility') ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50' }}">
                    <i data-lucide="search" class="w-5 h-5 mr-4"></i>
                    Compatibility Checker
                </a>
            @endif
            
            @auth
                @if(auth()->user()->role !== 'owner')
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
            @else
                <div class="border-t border-gray-100 dark:border-gray-800 my-4 pt-4">
                    <a href="{{ route('login') }}" class="flex items-center px-4 py-4 rounded-2xl text-base font-bold text-gray-600 hover:bg-gray-50">
                        <i data-lucide="log-in" class="w-5 h-5 mr-4"></i>
                        Sign In
                    </a>
                    <a href="{{ route('register') }}" class="flex items-center px-4 py-4 rounded-2xl text-base font-bold text-blue-600 hover:bg-blue-50">
                        <i data-lucide="user-plus" class="w-5 h-5 mr-4"></i>
                        Get Started
                    </a>
                </div>
            @endauth
            {{-- Mobile Theme Toggle --}}
            <button id="mobileThemeToggle"
                class="flex items-center w-full px-4 py-4 rounded-2xl text-base font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800">
                <i id="mobileThemeIcon" data-lucide="moon" class="w-5 h-5 mr-4"></i>
                <span id="mobileThemeLabel">Dark Mode</span>
            </button>
        </div>

        @auth
            <div class="pt-6 pb-8 border-t border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50">
                <div class="flex items-center px-8 mb-6">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-600 to-blue-400 flex items-center justify-center text-white text-lg font-black shadow-lg mr-4">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="overflow-hidden">
                        <div class="font-black text-gray-900 dark:text-white truncate">{{ auth()->user()->name }}</div>
                        <div class="text-sm text-gray-500 font-medium truncate">{{ auth()->user()->email }}</div>
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
        @endauth
    </div>
</nav>

<script>
(function() {
    var htmlEl = document.documentElement;
    var toggle = document.getElementById('globalThemeToggle');
    var mobileToggle = document.getElementById('mobileThemeToggle');
    var icon = document.getElementById('globalThemeIcon');
    var mobileIcon = document.getElementById('mobileThemeIcon');
    var mobileLabel = document.getElementById('mobileThemeLabel');

    function applyTheme(isDark) {
        console.log('MotoTrack Theme Change:', isDark ? 'DARK' : 'LIGHT');
        if (isDark) {
            htmlEl.classList.add('dark');
            localStorage.setItem('theme', 'dark');
            if (icon) { icon.setAttribute('data-lucide', 'sun'); }
            if (mobileIcon) { mobileIcon.setAttribute('data-lucide', 'sun'); }
            if (mobileLabel) { mobileLabel.textContent = 'Light Mode'; }
        } else {
            htmlEl.classList.remove('dark');
            localStorage.setItem('theme', 'light');
            if (icon) { icon.setAttribute('data-lucide', 'moon'); }
            if (mobileIcon) { mobileIcon.setAttribute('data-lucide', 'moon'); }
            if (mobileLabel) { mobileLabel.textContent = 'Dark Mode'; }
        }
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    var savedTheme = localStorage.getItem('theme');
    console.log('Current Saved Theme:', savedTheme);
    applyTheme(savedTheme !== 'light');

    if (toggle) {
        toggle.addEventListener('click', function() {
            var target = !htmlEl.classList.contains('dark');
            applyTheme(target);
        });
    }
    if (mobileToggle) {
        mobileToggle.addEventListener('click', function() {
            var target = !htmlEl.classList.contains('dark');
            applyTheme(target);
        });
    }
})();
</script>
