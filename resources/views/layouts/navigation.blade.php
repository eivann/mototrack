@php
    $dashboardRoute = auth()->check()
        ? (auth()->user()->role === 'owner' ? route('dashboard') : route('customer.dashboard'))
        : route('login');
        
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
            'label' => 'Services',
            'mobile_label' => 'Services',
            'href' => url('/services'),
            'icon' => 'settings',
            'active' => request()->is('services'),
            'show' => true,
        ],
        [
            'label' => 'About',
            'mobile_label' => 'About Us',
            'href' => url('/about'),
            'icon' => 'info',
            'active' => request()->is('about'),
            'show' => true,
        ],
        [
            'label' => 'Compatibility',
            'mobile_label' => 'Compatibility Checker',
            'href' => route('compatibility.view'),
            'icon' => 'search',
            'active' => request()->routeIs('compatibility.view'),
            'show' => true,
        ],
        [
            'label' => 'Pre-Order',
            'mobile_label' => 'Pre-orders',
            'href' => route('preorder.parts'),
            'icon' => 'clipboard-list',
            'active' => request()->routeIs('preorder.parts') || request()->is('preorder-parts') || request()->is('preorder-custom') || request()->is('preorder/*'),
            'show' => true,
        ],
        [
            'label' => 'Contact',
            'mobile_label' => 'Contact Us',
            'href' => url('/contact'),
            'icon' => 'phone',
            'active' => request()->is('contact'),
            'show' => true,
        ],
    ];
@endphp

<nav x-data="{ open: false }" class="bg-white/95 dark:bg-gray-900/95 backdrop-blur-xl sticky top-0 z-50 border-b border-gray-100 dark:border-gray-800 transition-all duration-300 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16 md:h-20 lg:h-[88px]">
            <div class="flex items-center">
                <!-- Logo -->
                <div class="shrink-0 flex items-center transform hover:scale-105 transition-transform duration-300 mr-4 xl:mr-10">
                    <a href="{{ url('/') }}" class="flex items-center gap-3 xl:gap-4">
                        <img src="{{ asset('images/logo.png') . '?v=' . filemtime(public_path('images/logo.png')) }}" alt="EM Parts" class="h-10 md:h-12 lg:h-14 w-auto object-contain group-hover:scale-110 transition-transform duration-500">
                        <div class="hidden sm:flex flex-col leading-none">
                            <span class="text-[16px] xl:text-[18px] font-black text-[#1a1f2c] dark:text-white uppercase tracking-tight">Edmar Motorcycle</span>
                            <span class="text-[9px] font-bold tracking-[0.2em] text-indigo-600 dark:text-indigo-400 uppercase mt-1">Parts and Accessories</span>
                        </div>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden lg:flex space-x-6 xl:space-x-8 h-full items-center ml-4">
                    @php
                        $linkClasses = "inline-flex items-center text-[13px] xl:text-[14px] font-bold transition-all duration-300 relative whitespace-nowrap";
                        $activeClasses = "text-indigo-600 dark:text-indigo-400";
                        $inactiveClasses = "text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-white";
                    @endphp

                    @foreach($navItems as $item)
                        @if($item['show'])
                            <a href="{{ $item['href'] }}" class="{{ $linkClasses }} {{ $item['active'] ? $activeClasses : $inactiveClasses }}">
                                <span>{{ $item['label'] }}</span>
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>

            <div class="hidden lg:flex lg:items-center gap-4 xl:gap-6">
                {{-- Global Theme Toggle --}}
                <button id="globalThemeToggle" aria-label="Toggle light/dark mode" title="Toggle Light/Dark Mode"
                    class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 transition-colors focus:outline-none">
                    <i id="globalThemeIcon" data-lucide="sun" class="w-[18px] h-[18px]"></i>
                </button>

                <!-- Dashboard Button -->
                <a href="{{ $dashboardRoute }}" class="inline-flex items-center gap-2 bg-[#5244e1] hover:bg-[#4336bf] text-white px-5 py-2.5 rounded-full text-[13px] font-bold transition-all shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    <i data-lucide="layout-grid" class="w-4 h-4"></i>
                    <span>Dashboard</span>
                </a>
                
                {{-- Notification Bell --}}
                @auth
                <div class="relative group hidden" x-data="{ open: false, hasNew: {{ auth()->user()->unreadNotifications->count() > 0 ? 'true' : 'false' }} }" @click.away="open = false">
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

    <!-- Responsive Navigation Menu -->
    <div id="mobile-nav" x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="lg:hidden border-t border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-2xl">
        <div class="pt-4 pb-6 space-y-2 px-4">
            @foreach($navItems as $item)
                @if($item['show'])
                    <a href="{{ $item['href'] }}" class="flex items-center px-4 py-4 rounded-2xl text-base font-bold {{ $item['active'] ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-300' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800' }}">
                        <i data-lucide="{{ $item['icon'] }}" class="w-5 h-5 mr-4"></i>
                        {{ $item['mobile_label'] }}
                    </a>
                @endif
            @endforeach

            @guest
                <div class="border-t border-gray-100 dark:border-gray-800 my-4 pt-4">
                    <a href="{{ route('login') }}" class="flex items-center px-4 py-4 rounded-2xl text-base font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800">
                        <i data-lucide="log-in" class="w-5 h-5 mr-4"></i>
                        Sign In
                    </a>
                    <a href="{{ route('register') }}" class="flex items-center px-4 py-4 rounded-2xl text-base font-bold text-blue-600 dark:text-blue-300 hover:bg-blue-50 dark:hover:bg-blue-900/20">
                        <i data-lucide="user-plus" class="w-5 h-5 mr-4"></i>
                        Get Started
                    </a>
                </div>
            @endguest
            {{-- Mobile Theme Toggle --}}
            <button id="mobileThemeToggle" aria-label="Toggle light/dark mode"
                class="flex items-center w-full px-4 py-4 rounded-2xl text-base font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-2xl">
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
