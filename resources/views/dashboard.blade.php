<x-app-layout>
    <div x-data="{ activeTab: 'overview' }" class="min-h-screen bg-gray-50 dark:bg-gray-950 pb-12">
        <!-- Premium Header -->
        <div class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 sticky top-0 z-30 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-4">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-gradient-to-br from-primary to-accent rounded-2xl flex items-center justify-center shadow-lg transform hover:scale-105 transition-transform">
                            <span class="text-white text-2xl font-black">M</span>
                        </div>
                        <div>
                            <h2 class="text-2xl font-black text-gray-900 dark:text-white tracking-tighter">MOTOTRACK</h2>
                            <p class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-widest">Management Dashboard</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <span class="hidden md:block text-sm font-medium text-gray-500 dark:text-gray-400">Welcome back, <span class="text-gray-900 dark:text-white font-bold">{{ Auth::user()->name }}</span></span>
                        <div class="h-8 w-8 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>
                        </div>
                    </div>
                </div>

                <!-- Navigation Tabs -->
                <div class="flex gap-1 overflow-x-auto no-scrollbar pb-0 mt-2">
                    <template x-for="tab in [
                        { id: 'overview', label: 'Overview', icon: 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z' },
                        { id: 'inventory', label: 'Inventory', icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4' },
                        { id: 'sales', label: 'POS & Sales', icon: 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z' },
                        { id: 'repairs', label: 'Service Jobs', icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z' },
                        { id: 'reports', label: 'Intelligence', icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z' }
                    ]">
                        <button 
                            @click="activeTab = tab.id"
                            :class="activeTab === tab.id ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300'"
                            class="whitespace-nowrap py-4 px-6 border-b-2 font-bold text-sm transition-all flex items-center gap-2"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-html="tab.icon"></svg>
                            <span x-text="tab.label"></span>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Overview Tab Content -->
            <div x-show="activeTab === 'overview'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Products Stat -->
                    <div class="bg-white dark:bg-gray-900 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800 hover:shadow-xl transition-all group">
                        <div class="flex justify-between items-start mb-4">
                            <div class="bg-blue-50 dark:bg-blue-900/30 p-3 rounded-2xl text-blue-600 dark:text-blue-400 group-hover:scale-110 transition-transform">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>
                            </div>
                            <span class="text-xs font-bold text-green-600 bg-green-50 dark:bg-green-900/30 px-2 py-1 rounded-full">+12%</span>
                        </div>
                        <div class="text-3xl font-black text-gray-900 dark:text-white mb-1">{{ number_format($stats['totalProducts']) }}</div>
                        <div class="text-sm font-bold text-gray-400 uppercase tracking-tighter">Total Products</div>
                    </div>

                    <!-- Low Stock Stat -->
                    <div class="bg-white dark:bg-gray-900 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800 hover:shadow-xl transition-all group">
                        <div class="flex justify-between items-start mb-4">
                            <div class="bg-orange-50 dark:bg-orange-900/30 p-3 rounded-2xl text-orange-600 dark:text-orange-400 group-hover:scale-110 transition-transform">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>
                            </div>
                            @if($stats['lowStockCount'] > 0)
                                <span class="text-xs font-bold text-red-600 bg-red-50 dark:bg-red-900/30 px-2 py-1 rounded-full">Action Required</span>
                            @else
                                <span class="text-xs font-bold text-green-600 bg-green-50 dark:bg-green-900/30 px-2 py-1 rounded-full">All Good</span>
                            @endif
                        </div>
                        <div class="text-3xl font-black text-gray-900 dark:text-white mb-1">{{ $stats['lowStockCount'] }}</div>
                        <div class="text-sm font-bold text-gray-400 uppercase tracking-tighter">Low Stock Items</div>
                    </div>

                    <!-- Active Jobs Stat -->
                    <div class="bg-white dark:bg-gray-900 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800 hover:shadow-xl transition-all group">
                        <div class="flex justify-between items-start mb-4">
                            <div class="bg-purple-50 dark:bg-purple-900/30 p-3 rounded-2xl text-purple-600 dark:text-purple-400 group-hover:scale-110 transition-transform">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>
                            </div>
                            <span class="text-xs font-bold text-yellow-600 bg-yellow-50 dark:bg-yellow-900/30 px-2 py-1 rounded-full">{{ $stats['activeRepairs'] }} Pending</span>
                        </div>
                        <div class="text-3xl font-black text-gray-900 dark:text-white mb-1">{{ $stats['activeRepairs'] }}</div>
                        <div class="text-sm font-bold text-gray-400 uppercase tracking-tighter">Active Service Jobs</div>
                    </div>

                    <!-- Revenue Stat -->
                    <div class="bg-white dark:bg-gray-900 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800 hover:shadow-xl transition-all group">
                        <div class="flex justify-between items-start mb-4">
                            <div class="bg-green-50 dark:bg-green-900/30 p-3 rounded-2xl text-green-600 dark:text-green-400 group-hover:scale-110 transition-transform">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>
                            </div>
                            <span class="text-xs font-bold text-green-600 bg-green-50 dark:bg-green-900/30 px-2 py-1 rounded-full">Today</span>
                        </div>
                        <div class="text-3xl font-black text-gray-900 dark:text-white mb-1">₱{{ number_format($stats['todayRevenue'], 2) }}</div>
                        <div class="text-sm font-bold text-gray-400 uppercase tracking-tighter">Daily Revenue</div>
                    </div>
                </div>

                <!-- Recent Activities (Mirroring Prototype) -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-2 space-y-6">
                        <div class="bg-white dark:bg-gray-900 p-8 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800">
                            <h3 class="text-xl font-black text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                                <span class="w-2 h-8 bg-indigo-600 rounded-full"></span>
                                Recent Activities
                            </h3>
                            <div class="space-y-4">
                                @forelse($recentActivities as $activity)
                                    <div class="flex items-center gap-4 p-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 rounded-2xl transition-colors border border-transparent hover:border-gray-100 dark:hover:border-gray-700">
                                        <div class="bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 w-12 h-12 rounded-xl flex items-center justify-center">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="{{ str_contains(strtolower($activity->data['title']), 'repair') ? 'M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z' : 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z' }}" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>
                                        </div>
                                        <div class="flex-1">
                                            <div class="text-sm font-bold text-gray-900 dark:text-white">{{ $activity->data['title'] }}</div>
                                            <div class="text-xs font-medium text-gray-400">{{ $activity->created_at->diffForHumans() }}</div>
                                        </div>
                                        <a href="{{ $activity->data['link'] ?? '#' }}" class="text-gray-400 hover:text-indigo-600 transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>
                                        </a>
                                    </div>
                                @empty
                                    <div class="text-center py-8">
                                        <p class="text-gray-500 dark:text-gray-400 font-bold">No recent activities.</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                    
                    <div class="space-y-6">
                        <div class="bg-gradient-to-br from-indigo-600 to-purple-700 p-8 rounded-3xl shadow-xl text-white relative overflow-hidden group">
                            <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-white/10 rounded-full blur-3xl group-hover:scale-150 transition-transform duration-700"></div>
                            <h4 class="text-lg font-black mb-2 italic">Pro Tip</h4>
                            <p class="text-sm text-indigo-100 mb-6">Automate your inventory checks by setting threshold alerts in the Intelligence tab.</p>
                            <button @click="activeTab = 'reports'" class="w-full py-3 bg-white text-indigo-600 rounded-xl font-black text-sm shadow-lg hover:shadow-indigo-500/30 transition-all active:scale-95">Upgrade Reports</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Placeholder for other tabs (Linking to existing routes) -->
            <div x-show="activeTab !== 'overview'" class="flex flex-col items-center justify-center py-20 bg-white dark:bg-gray-900 rounded-3xl border border-dashed border-gray-300 dark:border-gray-700">
                <div class="w-20 h-20 bg-indigo-50 dark:bg-indigo-900/30 rounded-full flex items-center justify-center text-indigo-600 dark:text-indigo-400 mb-6">
                    <svg class="w-10 h-10 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>
                </div>
                <h3 class="text-2xl font-black text-gray-900 dark:text-white mb-2">Ready to Launch</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-8 text-center max-w-sm">This module is linked to your existing functional routes. Click below to manage your data.</p>
                <div class="flex gap-4">
                    <a :href="'/' + activeTab" class="px-8 py-4 bg-indigo-600 text-white rounded-2xl font-black shadow-lg hover:bg-indigo-700 transition-all">Go to <span x-text="activeTab.charAt(0).toUpperCase() + activeTab.slice(1)"></span></a>
                    <button @click="activeTab = 'overview'" class="px-8 py-4 bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white rounded-2xl font-black">Back to Home</button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>