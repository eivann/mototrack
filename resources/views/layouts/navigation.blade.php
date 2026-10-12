@php
    $isAdmin = auth()->check() && auth()->user()->role === 'owner';
    $dashboardRoute = auth()->check()
        ? ($isAdmin ? route('dashboard') : route('customer.dashboard'))
        : route('login');
    $cartCount = count(session('cart', []));
        
    $navItems = [
        [
            'label' => 'Home',
            'mobile_label' => 'Home',
            'href' => url('/'),
            'icon' => 'home',
            'active' => request()->is('/'),
            'show' => true,
        ],
        [
            'label' => 'Browse Parts',
            'mobile_label' => 'Browse Parts',
            'href' => route('browse'),
            'icon' => 'package',
            'active' => request()->routeIs('browse'),
            'show' => true,
        ],
        [
            'label' => 'Compatibility',
            'mobile_label' => 'Compatibility Checker',
            'href' => route('compatibility'),
            'icon' => 'check-circle-2',
            'active' => request()->routeIs('compatibility*'),
            'show' => true,
        ],
        [
            'label' => 'Pre-Order',
            'mobile_label' => 'Pre-orders',
            'href' => route('preorder.parts'),
            'icon' => 'clipboard-list',
            'active' => request()->routeIs('preorder.parts') || request()->is('preorder-parts') || request()->is('preorder-custom') || request()->is('preorder/*'),
            'show' => !$isAdmin,
        ],
        [
            'label' => 'Services',
            'mobile_label' => 'Services',
            'href' => url('/#services'),
            'icon' => 'settings',
            'active' => request()->is('services'),
            'show' => !$isAdmin,
        ],
        [
            'label' => 'About',
            'mobile_label' => 'About',
            'href' => url('/#about'),
            'icon' => 'info',
            'active' => request()->is('about') || request()->routeIs('faq'),
            'show' => !$isAdmin,
            'dropdown' => [
                ['label' => 'About Us', 'href' => url('/#about'), 'icon' => 'info'],
                ['label' => 'FAQ', 'href' => route('faq'), 'icon' => 'help-circle'],
            ],
        ],
        [
            'label' => 'Contact',
            'mobile_label' => 'Contact Us',
            'href' => url('/#contact'),
            'icon' => 'phone',
            'active' => request()->is('contact'),
            'show' => !$isAdmin,
        ],
        [
            'label' => 'Admin Panel',
            'mobile_label' => 'Admin Panel',
            'href' => route('dashboard'),
            'icon' => 'shield',
            'active' => request()->routeIs('dashboard'),
            'show' => $isAdmin,
        ],
    ];
@endphp

<div x-data="{ open: false }" class="sticky top-0 z-50 w-full">
<nav class="bg-white/95 dark:bg-gray-900/95 backdrop-blur-xl border-b border-gray-100 dark:border-gray-800 transition-all duration-300 shadow-sm">
    <div class="w-full px-4 lg:px-6">
        <div class="flex justify-between items-center h-16 md:h-20 lg:h-[88px]">
            <div class="flex items-center shrink-0 lg:flex-1 justify-start">
                <!-- Logo -->
                <div class="flex items-center transform hover:scale-105 transition-transform duration-300 mr-4 lg:mr-6">
                    <a href="{{ url('/') }}" class="flex items-center gap-2 lg:gap-3">
                        <img src="{{ asset('images/logo-light.svg') }}" alt="EM Parts" class="h-10 md:h-12 lg:h-14 w-auto object-contain transition-transform duration-500 block dark:hidden">
                        <img src="{{ asset('images/logo-dark.svg') }}" alt="EM Parts" class="h-10 md:h-12 lg:h-14 w-auto object-contain transition-transform duration-500 hidden dark:block">
                        <div class="hidden sm:flex flex-col leading-none">
                            <span class="text-[14px] lg:text-[16px] font-black text-[#1a1f2c] dark:text-white uppercase tracking-tight">Edmar Motorcycle</span>
                            <span class="text-[8px] font-bold tracking-[0.2em] text-indigo-600 dark:text-indigo-400 uppercase mt-1">Parts and Accessories</span>
                        </div>
                    </a>
            </div>
            </div>

            <!-- Navigation Links -->
            <div class="hidden lg:flex justify-center space-x-4 lg:space-x-6 h-full items-center px-2 mx-4">
                    @php
                        $linkClasses = "inline-flex items-center text-[12px] lg:text-[13px] font-bold transition-all duration-300 relative whitespace-nowrap";
                        $activeClasses = "text-indigo-600 dark:text-indigo-400";
                        $inactiveClasses = "text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-white";
                    @endphp

                    @foreach($navItems as $item)
                        @if($item['show'])
                            @if(!empty($item['dropdown']))
                                {{-- Dropdown nav item --}}
                                <div class="relative" x-data="{ dropdownOpen: false }" @mouseenter="dropdownOpen = true" @mouseleave="dropdownOpen = false">
                                    <button @click="dropdownOpen = !dropdownOpen" class="{{ $linkClasses }} {{ $item['active'] ? $activeClasses : $inactiveClasses }} gap-1">
                                        <span>{{ $item['label'] }}</span>
                                        <i data-lucide="chevron-down" class="w-3.5 h-3.5 transition-transform duration-200" :class="dropdownOpen ? 'rotate-180' : ''"></i>
                                    </button>
                                    <div x-show="dropdownOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-1" x-cloak
                                        class="absolute top-full left-0 mt-2 w-48 bg-white dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-800 shadow-xl py-2 z-50">
                                        @foreach($item['dropdown'] as $subItem)
                                            <a href="{{ $subItem['href'] }}" class="flex items-center gap-3 px-4 py-2.5 text-[13px] font-semibold text-gray-600 dark:text-gray-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                                <i data-lucide="{{ $subItem['icon'] }}" class="w-4 h-4"></i>
                                                {{ $subItem['label'] }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <a href="{{ $item['href'] }}" class="{{ $linkClasses }} {{ $item['active'] ? $activeClasses : $inactiveClasses }}">
                                    <span>{{ $item['label'] }}</span>
                                </a>
                            @endif
                        @endif
                    @endforeach
                </div>
            <div class="hidden lg:flex lg:flex-1 items-center justify-end gap-3 lg:gap-4 shrink-0">
                {{-- Global Theme Toggle --}}
                <button id="globalThemeToggle" aria-label="Toggle light/dark mode" title="Toggle Light/Dark Mode"
                    class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 transition-colors focus:outline-none">
                    <i id="globalThemeIcon" data-lucide="sun" class="w-[18px] h-[18px]"></i>
                </button>

                @auth
                @if(!$isAdmin)
                {{-- Cart Icon (customers only) --}}
                <a href="/cart" class="relative w-10 h-10 rounded-full flex items-center justify-center border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 hover:text-indigo-600 dark:hover:text-indigo-400 transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2" aria-label="Shopping cart">
                    <i data-lucide="shopping-cart" class="w-4 h-4"></i>
                    @if($cartCount > 0)
                        <span class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-indigo-600 text-white text-[10px] font-black shadow-sm">{{ $cartCount > 9 ? '9+' : $cartCount }}</span>
                    @endif
                </a>
                @endif
                @endauth

                <!-- Dashboard Button -->
                @auth
                <a href="{{ $dashboardRoute }}" class="inline-flex items-center gap-2 bg-[#5244e1] hover:bg-[#4336bf] text-white px-5 py-2.5 rounded-full text-[13px] font-bold transition-all shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    <i data-lucide="layout-grid" class="w-4 h-4"></i>
                    <span>Dashboard</span>
                </a>
                @else
                <a href="{{ route('login') }}" class="inline-flex items-center gap-2 text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 text-[13px] font-bold transition-all">
                    <i data-lucide="log-in" class="w-4 h-4"></i>
                    <span>Sign In</span>
                </a>
                <a href="{{ route('register') }}" class="inline-flex items-center gap-2 bg-[#5244e1] hover:bg-[#4336bf] text-white px-5 py-2.5 rounded-full text-[13px] font-bold transition-all shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    <i data-lucide="user-plus" class="w-4 h-4"></i>
                    <span>Get Started</span>
                </a>
                @endauth
                
                {{-- Notification Bell --}}
                @auth
                <div class="relative group" x-data="{ open: false, hasNew: {{ auth()->user()->unreadNotifications->count() > 0 ? 'true' : 'false' }} }" @click.away="open = false">
                    <button @click="open = !open; hasNew = false"
                        :aria-expanded="open"
                        aria-label="Toggle notifications"
                        class="w-10 h-10 rounded-full flex items-center justify-center border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 hover:text-indigo-600 dark:hover:text-indigo-400 transition-all duration-300 relative focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
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
                                            <i data-lucide="{{ str_contains(strtolower($notification->data['title'] ?? ''), 'repair') ? 'wrench' : 'package' }}" class="w-5 h-5"></i>
                                        </div>
                                    </div>
                                    
                                    <!-- Center: Content -->
                                    <div class="flex-1 min-w-0 pr-6">
                                        <h4 class="text-xs font-bold {{ $notification->unread() ? 'text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400' }} truncate">
                                            {{ $notification->data['title'] ?? 'Notification' }}
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
                                        <button type="submit" aria-label="Mark notification as read" class="p-1 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 rounded text-indigo-600 dark:text-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500">
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
                                <a href="{{ route('notifications.index') }}" class="flex items-center gap-1.5 text-[11px] font-bold text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
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
                {{-- User Profile Dropdown --}}
                <div class="relative" x-data="{ open: false }" @click.away="open = false">
                    <button @click="open = !open" :aria-expanded="open" aria-label="User menu" class="w-10 h-10 rounded-full bg-gradient-to-tr from-indigo-600 to-indigo-400 flex items-center justify-center text-white text-sm font-black shadow-md hover:shadow-lg hover:scale-105 transition-all focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </button>
                    <div x-show="open"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                         x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                         class="absolute right-0 mt-3 w-64 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-700 overflow-hidden z-[70]"
                         x-cloak>
                        <div class="p-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50">
                            <p class="font-black text-sm text-gray-900 dark:text-white truncate">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ auth()->user()->email }}</p>
                        </div>
                        <div class="py-2">
                            <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-4 py-3 text-sm font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <i data-lucide="settings" class="w-4 h-4 text-gray-400"></i>
                                Settings
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="flex items-center gap-3 w-full px-4 py-3 text-sm font-bold text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                    <i data-lucide="log-out" class="w-4 h-4"></i>
                                    Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                @endauth


            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center lg:hidden">
                <button @click="open = ! open" :aria-expanded="open" aria-label="Toggle navigation menu" aria-controls="mobile-nav" class="inline-flex items-center justify-center p-3 rounded-2xl text-gray-400 dark:text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-300">
                    <i :class="{'hidden': open, 'block': ! open }" data-lucide="menu" class="w-6 h-6"></i>
                    <i :class="{'hidden': ! open, 'block': open }" data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
        </div>
    </div>

</nav>

    <!-- Off-canvas Backdrop -->
    <div x-show="open" 
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-900/80 backdrop-blur-sm z-40 lg:hidden" 
         @click="open = false" 
         x-cloak></div>

    <!-- Responsive Navigation Menu (Slide from right) -->
    <div id="mobile-nav" x-show="open"
         x-transition:enter="transition ease-in-out duration-300 transform"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in-out duration-300 transform"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="fixed top-0 right-0 h-screen w-[85%] max-w-sm bg-white dark:bg-gray-900 shadow-2xl z-50 lg:hidden flex flex-col"
         x-cloak>
         
         <div class="p-6 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center bg-gray-50/50 dark:bg-gray-900/50 shrink-0">
             <div class="flex items-center gap-3">
                 <span class="font-black text-gray-900 dark:text-white uppercase tracking-tight text-sm">Menu</span>
             </div>
             <button @click="open = false" aria-label="Close menu" class="p-2 -mr-2 rounded-xl text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                 <i data-lucide="x" class="w-6 h-6"></i>
             </button>
         </div>

         <div class="flex-1 overflow-y-auto custom-scrollbar">
             <div class="p-4 space-y-1">
                 @foreach($navItems as $item)
                     @if($item['show'])
                         @if(!empty($item['dropdown']))
                             {{-- Dropdown group in mobile --}}
                             <div x-data="{ subOpen: false }">
                                 <button @click="subOpen = !subOpen" class="flex items-center justify-between w-full px-4 py-4 rounded-2xl text-base font-bold {{ $item['active'] ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-300' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors' }}">
                                     <div class="flex items-center">
                                         <i data-lucide="{{ $item['icon'] }}" class="w-5 h-5 mr-4"></i>
                                         {{ $item['mobile_label'] }}
                                     </div>
                                     <i data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-200" :class="subOpen ? 'rotate-180' : ''"></i>
                                 </button>
                                 <div x-show="subOpen" x-collapse x-cloak class="pl-8 mt-1 space-y-1">
                                     @foreach($item['dropdown'] as $subItem)
                                         <a href="{{ $subItem['href'] }}" class="flex items-center px-4 py-3 rounded-xl text-sm font-semibold text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                             <i data-lucide="{{ $subItem['icon'] }}" class="w-4 h-4 mr-3"></i>
                                             {{ $subItem['label'] }}
                                         </a>
                                     @endforeach
                                 </div>
                             </div>
                         @else
                             <a href="{{ $item['href'] }}" class="flex items-center px-4 py-4 rounded-2xl text-base font-bold {{ $item['active'] ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-300' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors' }}">
                                 <i data-lucide="{{ $item['icon'] }}" class="w-5 h-5 mr-4"></i>
                                 {{ $item['mobile_label'] }}
                             </a>
                         @endif
                     @endif
                 @endforeach

                 @guest
                     <div class="border-t border-gray-100 dark:border-gray-800 my-4 pt-4">
                         <a href="{{ route('login') }}" class="flex items-center px-4 py-4 rounded-2xl text-base font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                             <i data-lucide="log-in" class="w-5 h-5 mr-4"></i>
                             Sign In
                         </a>
                         <a href="{{ route('register') }}" class="flex items-center px-4 py-4 rounded-2xl text-base font-bold text-blue-600 dark:text-blue-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors">
                             <i data-lucide="user-plus" class="w-5 h-5 mr-4"></i>
                             Get Started
                         </a>
                     </div>
                 @endguest
                 {{-- Mobile Theme Toggle --}}
                 <button id="mobileThemeToggle" aria-label="Toggle light/dark mode"
                     class="flex items-center w-full px-4 py-4 rounded-2xl text-base font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-2xl transition-colors">
                     <i id="mobileThemeIcon" data-lucide="moon" class="w-5 h-5 mr-4"></i>
                     <span id="mobileThemeLabel">Dark Mode</span>
                 </button>
             </div>

             @auth
                 <div class="pt-6 pb-8 border-t border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 mt-auto shrink-0">
                     <div class="flex items-center px-8 mb-6">
                         <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-600 to-blue-400 flex items-center justify-center text-white text-lg font-black shadow-lg mr-4 shrink-0">
                             {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                         </div>
                         <div class="overflow-hidden">
                             <div class="font-black text-gray-900 dark:text-white truncate">{{ auth()->user()->name }}</div>
                             <div class="text-sm text-gray-500 font-medium truncate">{{ auth()->user()->email }}</div>
                         </div>
                     </div>

                     <div class="px-4 space-y-2">
                         <a href="{{ $dashboardRoute }}" class="flex items-center px-4 py-4 rounded-2xl text-base font-bold text-gray-600 dark:text-gray-300 hover:bg-white dark:hover:bg-gray-700 transition-colors">
                             <i data-lucide="layout-dashboard" class="w-5 h-5 mr-4 text-gray-400"></i>
                             Dashboard
                         </a>

                         <a href="{{ route('profile.edit') }}" class="flex items-center px-4 py-4 rounded-2xl text-base font-bold text-gray-600 dark:text-gray-300 hover:bg-white dark:hover:bg-gray-700 transition-colors">
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
    </div>
</div>

<script>
(function() {
    var htmlEl = document.documentElement;
    var toggle = document.getElementById('globalThemeToggle');
    var mobileToggle = document.getElementById('mobileThemeToggle');
    var icon = document.getElementById('globalThemeIcon');
    var mobileIcon = document.getElementById('mobileThemeIcon');
    var mobileLabel = document.getElementById('mobileThemeLabel');

    function applyTheme(isDark) {
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
    applyTheme(savedTheme === 'dark');

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
