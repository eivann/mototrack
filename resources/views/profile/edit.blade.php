<x-app-layout>
    <x-slot name="header">
        <h2 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center">
                <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            {{ __('Account Profile') }}
        </h2>
    </x-slot>

    <div class="py-10 bg-gray-50 dark:bg-gray-950 min-h-screen transition-colors duration-300">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            <div class="relative mb-8 rounded-[2rem] overflow-hidden shadow-2xl p-8" style="background: linear-gradient(135deg, #3730a3 0%, #4f46e5 100%);">
                <div class="absolute inset-0 opacity-10 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')]"></div>
                <div class="relative z-10 flex flex-col sm:flex-row items-center sm:items-start gap-6">
                    {{-- Avatar --}}
                    <div class="w-24 h-24 rounded-3xl bg-white/20 backdrop-blur flex items-center justify-center text-white font-black text-4xl shadow-xl border-2 border-white/30 shrink-0 select-none">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="text-center sm:text-left">
                        <h3 class="text-2xl font-black text-white tracking-tight">{{ auth()->user()->name }}</h3>
                        <p class="text-indigo-200 mt-1 font-medium">{{ auth()->user()->email }}</p>
                        <div class="flex flex-wrap items-center justify-center sm:justify-start gap-3 mt-4">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/15 text-white text-xs font-bold uppercase tracking-widest border border-white/20">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                {{ ucfirst(auth()->user()->role ?? 'customer') }}
                            </span>
                            @if(auth()->user()->google_id)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/15 text-white text-xs font-bold uppercase tracking-widest border border-white/20">
                                <svg class="w-3 h-3" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#fff"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#fff"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#fff"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#fff"/></svg>
                                Google Linked
                            </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Left Column --}}
                <div class="lg:col-span-2 space-y-6">

                    {{-- Profile Information --}}
                    <div class="bg-white dark:bg-gray-900 rounded-[2rem] border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                        <div class="px-8 pt-8 pb-2 flex items-center gap-4 border-b border-gray-100 dark:border-gray-800">
                            <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
                            <div class="pb-4">
                                <h3 class="font-black text-gray-900 dark:text-white uppercase tracking-tight">Profile Information</h3>
                                <p class="text-xs text-gray-400 font-medium mt-0.5">Update your name and email address</p>
                            </div>
                        </div>
                        <div class="p-8">
                            @include('profile.partials.update-profile-information-form')
                        </div>
                    </div>

                    {{-- Update Password --}}
                    <div class="bg-white dark:bg-gray-900 rounded-[2rem] border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                        <div class="px-8 pt-8 pb-2 flex items-center gap-4 border-b border-gray-100 dark:border-gray-800">
                            <div class="w-10 h-10 rounded-2xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            </div>
                            <div class="pb-4">
                                <h3 class="font-black text-gray-900 dark:text-white uppercase tracking-tight">Update Password</h3>
                                <p class="text-xs text-gray-400 font-medium mt-0.5">Use a strong, random password to stay secure</p>
                            </div>
                        </div>
                        <div class="p-8">
                            @include('profile.partials.update-password-form')
                        </div>
                    </div>
                </div>

                {{-- Right Column --}}
                <div class="space-y-6">

                    {{-- Quick Info Card --}}
                    <div class="bg-white dark:bg-gray-900 rounded-[2rem] border border-gray-100 dark:border-gray-800 shadow-sm p-6">
                        <h4 class="font-black text-gray-900 dark:text-white uppercase tracking-tight text-sm mb-4">Account Details</h4>
                        <ul class="space-y-4">
                            <li class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Role</p>
                                    <p class="text-sm font-bold text-gray-800 dark:text-gray-200 capitalize">{{ auth()->user()->role ?? 'Customer' }}</p>
                                </div>
                            </li>
                            <li class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Email</p>
                                    <p class="text-sm font-bold text-gray-800 dark:text-gray-200 truncate">{{ auth()->user()->email }}</p>
                                </div>
                            </li>
                            <li class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-xl bg-{{ auth()->user()->google_id ? 'blue' : 'gray' }}-50 dark:bg-{{ auth()->user()->google_id ? 'blue' : 'gray' }}-900/30 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4 text-{{ auth()->user()->google_id ? 'blue' : 'gray' }}-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Google Login</p>
                                    <p class="text-sm font-bold {{ auth()->user()->google_id ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400 dark:text-gray-500' }}">
                                        {{ auth()->user()->google_id ? 'Connected' : 'Not connected' }}
                                    </p>
                                </div>
                            </li>
                        </ul>
                    </div>

                    {{-- Delete Account --}}
                    <div class="bg-white dark:bg-gray-900 rounded-[2rem] border border-red-100 dark:border-red-900/30 shadow-sm overflow-hidden">
                        <div class="px-6 pt-6 pb-2 flex items-center gap-3 border-b border-red-100 dark:border-red-900/30">
                            <div class="w-9 h-9 rounded-xl bg-red-50 dark:bg-red-900/30 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </div>
                            <div class="pb-4">
                                <h3 class="font-black text-red-600 dark:text-red-400 uppercase tracking-tight text-sm">Danger Zone</h3>
                                <p class="text-[10px] text-gray-400 font-medium mt-0.5">Irreversible account actions</p>
                            </div>
                        </div>
                        <div class="p-6">
                            @include('profile.partials.delete-user-form')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
