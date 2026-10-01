<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" id="html-root">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#4f46e5">
        <link rel="manifest" href="/manifest.json">
        <link rel="apple-touch-icon" href="{{ asset('images/logo.png') }}">

        {{-- Apply theme before paint to avoid flash --}}
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

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <script src="https://unpkg.com/lucide@0.460.0"></script>
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
        <style>
            [x-cloak] { display: none !important; }
            
            /* Custom Scrollbar */
            ::-webkit-scrollbar { width: 6px; }
            ::-webkit-scrollbar-track { background: transparent; }
            ::-webkit-scrollbar-thumb { 
                background: #6366f1; 
                border-radius: 10px; 
            }
            ::-webkit-scrollbar-thumb:hover { background: #4f46e5; }
            
            .scrollbar-thin::-webkit-scrollbar { width: 4px; }
        </style>
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

            <!-- Notification Toast System -->
            @auth
            <div x-data="notificationToasts()" 
                 class="fixed top-24 right-5 z-[60] flex flex-col gap-4 pointer-events-none"
                 @keydown.window.escape="toasts = []">
                
                <template x-for="toast in toasts" :key="toast.id">
                    <div x-show="toast.visible"
                         x-transition:enter="transition ease-out duration-500"
                         x-transition:enter-start="opacity-0 translate-x-12 scale-90"
                         x-transition:enter-end="opacity-100 translate-x-0 scale-100"
                         x-transition:leave="transition ease-in duration-300"
                         x-transition:leave-start="opacity-100 translate-x-0"
                         x-transition:leave-end="opacity-0 translate-x-12"
                         class="pointer-events-auto w-80 md:w-96 bg-white/90 dark:bg-gray-800/90 backdrop-blur-xl border-l-4 border-indigo-600 dark:border-indigo-400 shadow-2xl rounded-2xl p-5 group hover:scale-[1.02] transition-all"
                         :class="toast.link ? 'cursor-pointer' : ''"
                         @click="if(toast.link) window.location.href = toast.link">
                        
                        <div class="flex items-start gap-4">
                            <div class="bg-indigo-100 dark:bg-indigo-900/50 p-2 rounded-xl text-indigo-600 dark:text-indigo-400 animate-pulse">
                                <i data-lucide="bell" class="w-5 h-5"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-black text-gray-900 dark:text-white text-sm uppercase tracking-tight" x-text="toast.title"></h4>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 line-clamp-2" x-text="toast.message"></p>
                                <div class="flex items-center gap-4 mt-3">
                                    <button @click.stop="markAsRead(toast.id); hapticPulse();" class="text-[10px] font-black uppercase tracking-widest text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-white transition-colors cursor-pointer py-2 pr-4">
                                        Dismiss
                                    </button>
                                    <span class="text-[10px] text-gray-400">•</span>
                                    <span class="text-[10px] text-gray-400 uppercase tracking-widest" x-text="toast.time"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <audio id="notif-sound" src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" preload="auto"></audio>
            </div>

            <script>
                function notificationToasts() {
                    return {
                        toasts: [],
                        init() {
                            document.addEventListener('DOMContentLoaded', () => {
                                setTimeout(() => {
                                    if (window.Echo) {
                                        window.Echo.private(`App.Models.User.{{\auth()->id()}}`)
                                            .notification((notification) => {
                                                this.addToast({
                                                    id: notification.id || Date.now(),
                                                    data: {
                                                        title: notification.title,
                                                        message: notification.message,
                                                        link: notification.link
                                                    }
                                                });
                                            });
                                    }
                                }, 1000);
                            });
                            const rawNotifs = @json(auth()->user()->unreadNotifications->take(3));
                            
                            if (rawNotifs.length > 0) {
                                // Delay slightly for a nice intro
                                setTimeout(() => {
                                    rawNotifs.forEach((n, index) => {
                                        setTimeout(() => {
                                            this.addToast(n);
                                        }, index * 400);
                                    });
                                }, 800);
                            }
                        },
                        addToast(n) {
                            const toast = {
                                id: n.id,
                                title: n.data.title,
                                message: n.data.message,
                                link: n.data.link,
                                time: 'Just Now',
                                visible: true
                            };
                            this.toasts.push(toast);
                            
                            // Play sound
                            const sound = document.getElementById('notif-sound');
                            if (sound) {
                                sound.currentTime = 0;
                                sound.play().catch(e => console.log('Autoplay blocked'));
                            }

                            // Auto hide after 8 seconds
                            setTimeout(() => {
                                this.removeToast(toast.id);
                            }, 8000);
                        },
                        removeToast(id) {
                            const index = this.toasts.findIndex(t => t.id === id);
                            if (index !== -1) {
                                this.toasts[index].visible = false;
                                setTimeout(() => {
                                    this.toasts = this.toasts.filter(t => t.id !== id);
                                }, 500);
                            }
                        },
                        markAsRead(id) {
                            fetch(`/notifications/${id}/read`, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                    'Content-Type': 'application/json'
                                }
                            }).then(() => this.removeToast(id));
                        }
                    }
                }
            </script>
            @endauth
            
            <!-- Global Flash Messages (Unified Toast System) -->
            @php
                $flash = null;
                if (session('success')) $flash = ['type' => 'success', 'message' => session('success'), 'title' => 'Success'];
                elseif (session('error')) $flash = ['type' => 'error', 'message' => session('error'), 'title' => 'Error'];
                elseif (session('warning')) $flash = ['type' => 'warning', 'message' => session('warning'), 'title' => 'Warning'];
                elseif (session('mock_sms')) $flash = ['type' => 'info', 'message' => session('mock_sms'), 'title' => 'SMS Alert (Mock)'];
            @endphp

            @if($flash)
                <div x-data="{ show: true }" 
                     x-show="show"
                     x-init="setTimeout(() => show = false, 6000)"
                     x-transition:enter="transition ease-out duration-500"
                     x-transition:enter-start="opacity-0 translate-y-12 scale-90"
                     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                     x-transition:leave="transition ease-in duration-300"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 translate-y-12"
                     class="fixed bottom-8 left-1/2 -translate-x-1/2 z-[100] w-full max-w-md px-4 pointer-events-none">
                    
                    <div class="pointer-events-auto bg-white/95 dark:bg-gray-800/95 backdrop-blur-xl border-l-4 rounded-2xl shadow-2xl p-5 flex items-start gap-4 
                        {{ $flash['type'] === 'success' ? 'border-emerald-500 shadow-emerald-200/20' : '' }}
                        {{ $flash['type'] === 'error' ? 'border-red-500 shadow-red-200/20' : '' }}
                        {{ $flash['type'] === 'warning' ? 'border-amber-500 shadow-amber-200/20' : '' }}
                        {{ $flash['type'] === 'info' ? 'border-blue-500 shadow-blue-200/20' : '' }}">
                        
                        <div class="shrink-0 p-2 rounded-xl
                            {{ $flash['type'] === 'success' ? 'bg-emerald-100 text-emerald-600' : '' }}
                            {{ $flash['type'] === 'error' ? 'bg-red-100 text-red-600' : '' }}
                            {{ $flash['type'] === 'warning' ? 'bg-amber-100 text-amber-600' : '' }}
                            {{ $flash['type'] === 'info' ? 'bg-blue-100 text-blue-600' : '' }}">
                            <i data-lucide="{{ $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'error' ? 'x-circle' : ($flash['type'] === 'warning' ? 'alert-triangle' : 'info')) }}" class="w-6 h-6"></i>
                        </div>
                        
                        <div class="flex-1 min-w-0">
                            <h4 class="font-black text-gray-900 dark:text-white text-sm uppercase tracking-tight">{{ $flash['title'] }}</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 leading-relaxed">{{ $flash['message'] }}</p>
                        </div>

                        <button @click="show = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
            @endif
        </div>

        <script>
            if (window.lucide) {
                lucide.createIcons();
            }

            // Global Haptic Feedback Helper
            function hapticPulse() {
                if (window.navigator && window.navigator.vibrate) {
                    window.navigator.vibrate(10);
                }
            }
        </script>
    </body>
</html>
