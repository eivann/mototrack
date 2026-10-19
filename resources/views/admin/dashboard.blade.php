<x-app-layout>
    <div x-data="{ 
        activeTab: new URLSearchParams(window.location.search).get('tab') || 'overview',
        recentActivities: {{ Js::from($recentActivities->map(fn($a) => ['id' => $a->id, 'title' => $a->data['title'], 'time' => $a->created_at->diffForHumans()])) }},
        initEcho() {
            if (window.Echo) {
                window.Echo.private('App.Models.User.{{ auth()->id() }}')
                    .notification((notification) => {
                        this.recentActivities.unshift({
                            id: notification.id,
                            title: notification.title,
                            time: 'Just now'
                        });
                        if (this.recentActivities.length > 5) this.recentActivities.pop();
                    });
            }
        }
    }" x-init="initEcho()" class="min-h-screen bg-gray-50 dark:bg-gray-950 pb-12">
        <!-- Section Header -->
        <div class="sticky top-[64px] md:top-[80px] lg:top-[88px] z-40 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 shadow-sm">
            <div class="w-full px-4 sm:px-8 lg:px-12">
                <div class="flex justify-between items-center py-4">
                    <div class="flex items-center gap-4">
                        <div class="w-11 h-11 bg-indigo-50 dark:bg-indigo-900/30 rounded-2xl flex items-center justify-center text-indigo-600 dark:text-indigo-300">
                            <i data-lucide="activity" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-black text-gray-900 dark:text-white tracking-tighter">Admin Dashboard</h2>
                            <p class="text-[10px] font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-[0.2em]">Operations overview</p>
                        </div>
                </div>
            </div>

            </div>
        </div>

        <div class="w-full px-4 sm:px-8 lg:px-12 py-8">
            <div class="flex flex-col md:flex-row gap-8 items-start">
                
                <!-- Sidebar Navigation (Left) -->
                <div class="w-full md:w-64 flex-shrink-0 order-1 space-y-2 md:sticky md:top-[176px]">
                    @foreach([
                        'overview' => 'Overview',
                        'inventory' => 'Inventory',
                        'reservations' => 'Reservations',
                        'sales' => 'POS & Sales',
                        'repairs' => 'Service Jobs',
                        'preorders' => 'Pre-orders',
                        'refunds' => 'Returns & Refunds',
                        'reports' => 'Reports'
                    ] as $id => $label)
                        <button
                            @click="activeTab = '{{ $id }}'"
                            :class="activeTab === '{{ $id }}' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-200 dark:shadow-none' : 'text-gray-500 dark:text-gray-300 bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-indigo-600 dark:hover:text-indigo-400'"
                            class="w-full text-left py-4 px-6 rounded-2xl font-black text-xs uppercase tracking-widest text-gray-600 dark:text-gray-300 transition-all focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 flex items-center justify-between group"
                        >
                            <span class="flex items-center gap-3">
                                @php
                                    $icons = [
                                        'overview' => 'layout-dashboard',
                                        'inventory' => 'package',
                                        'reservations' => 'bookmark',
                                        'sales' => 'credit-card',
                                        'repairs' => 'wrench',
                                        'preorders' => 'clipboard-list',
                                        'refunds' => 'rotate-ccw',
                                        'reports' => 'bar-chart-2'
                                    ];
                                @endphp
                                <span class="nav-icon inline-flex w-5 h-5 shrink-0 items-center justify-center transition-transform group-hover:scale-110" :class="activeTab === '{{ $id }}' ? 'text-white' : 'text-gray-400 group-hover:text-indigo-500'" data-nav-icon="{{ $id }}"><i data-lucide="{{ $icons[$id] }}" class="w-5 h-5"></i></span>
                                {{ $label }}
                            </span>
                            
                            @if($id === 'reservations' && isset($pendingReservations) && $pendingReservations->count() > 0)
                                <span class="inline-flex items-center justify-center px-2 py-1 text-[10px] font-black leading-none text-white bg-amber-500 rounded-full">{{ $pendingReservations->groupBy(fn ($r) => $r->transaction_reference ?? 'row-'.$r->id)->count() }}</span>
                            @elseif($id === 'inventory' && ($stats['lowStockCount'] ?? 0) > 0)
                                <span class="inline-flex items-center justify-center px-2 py-1 text-[10px] font-black leading-none text-white bg-red-500 rounded-full">{{ $stats['lowStockCount'] }}</span>
                            @elseif($id === 'refunds' && isset($pendingRefunds) && $pendingRefunds->count() > 0)
                                <span class="inline-flex items-center justify-center px-2 py-1 text-[10px] font-black leading-none text-white bg-red-500 rounded-full">{{ $pendingRefunds->count() }}</span>
                            @else
                                <i data-lucide="chevron-right" class="w-4 h-4 opacity-50" :class="activeTab === '{{ $id }}' ? 'text-white opacity-100' : 'text-gray-400'"></i>
                            @endif
                        </button>
                    @endforeach
                </div>

                <!-- Main Content (Right) -->
                <div class="w-full md:flex-1 min-w-0 order-2">
                    
                    <!-- Overview Tab -->
                    <div x-show="activeTab === 'overview'" x-transition class="space-y-6">
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                            <div class="lg:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="bg-white dark:bg-gray-900 p-4 sm:p-5 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm hover:shadow-md transition-all flex flex-col justify-between">
                                    <div>
                                        <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Total Products</div>
                                        <div class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white mb-2">{{ number_format($stats['totalProducts']) }}</div>
                                    </div>
                                    <div class="flex items-center gap-1.5 text-green-600 font-bold text-[11px] bg-green-50 dark:bg-green-900/20 w-fit px-2.5 py-0.5 rounded-full italic">+12.5% this month</div>
                                </div>
                                <div class="bg-white dark:bg-gray-900 p-4 sm:p-5 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm hover:shadow-md transition-all flex flex-col justify-between">
                                    <div>
                                        <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Daily Revenue</div>
                                        <div class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white mb-2">₱{{ number_format($stats['todayRevenue'], 2) }}</div>
                                    </div>
                                    <div class="flex items-center gap-1.5 text-indigo-600 font-bold text-[11px] bg-indigo-50 dark:bg-indigo-900/20 w-fit px-2.5 py-0.5 rounded-full italic">Today's collection</div>
                                </div>
                                <div class="bg-white dark:bg-gray-900 p-4 sm:p-5 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm hover:shadow-md transition-all flex flex-col justify-between">
                                    <div>
                                        <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Low Stock Alerts</div>
                                        <div class="text-2xl sm:text-3xl font-black mb-2 {{ ($stats['lowStockCount'] ?? 0) > 0 ? 'text-amber-600' : 'text-gray-900 dark:text-white' }}">{{ number_format($stats['lowStockCount'] ?? 0) }}</div>
                                    </div>
                                    <a href="{{ route('low-stock.index') }}" class="inline-flex items-center gap-1.5 {{ ($stats['lowStockCount'] ?? 0) > 0 ? 'text-amber-600 font-bold text-[11px] bg-amber-50 dark:bg-amber-900/20 hover:bg-amber-100 dark:hover:bg-amber-900/40 px-2.5 py-0.5 rounded-full' : 'text-gray-400 font-bold text-[11px] bg-gray-50 dark:bg-gray-800/50 px-2.5 py-0.5 rounded-full' }} transition-colors">
                                        <i data-lucide="alert-triangle" class="w-3 h-3"></i>
                                        {{ ($stats['lowStockCount'] ?? 0) > 0 ? 'View restock list' : 'All stocked' }}
                                    </a>
                                </div>
                                <div class="bg-white dark:bg-gray-900 p-4 sm:p-5 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm hover:shadow-md transition-all flex flex-col justify-between">
                                    <div>
                                        <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Out of Stock</div>
                                        <div class="text-2xl sm:text-3xl font-black mb-2 {{ ($outOfStockCount ?? 0) > 0 ? 'text-red-600' : 'text-gray-900 dark:text-white' }}">{{ number_format($outOfStockCount ?? 0) }}</div>
                                    </div>
                                    <a href="{{ route('low-stock.index') }}" class="inline-flex items-center gap-1.5 {{ ($outOfStockCount ?? 0) > 0 ? 'text-red-600 font-bold text-[11px] bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/40 px-2.5 py-0.5 rounded-full' : 'text-gray-400 font-bold text-[11px] bg-gray-50 dark:bg-gray-800/50 px-2.5 py-0.5 rounded-full' }} transition-colors">
                                        <i data-lucide="package-x" class="w-3 h-3"></i>
                                        {{ ($outOfStockCount ?? 0) > 0 ? 'Needs urgent restock' : 'Nothing to restock' }}
                                    </a>
                                </div>
                            </div>
                            
                            <!-- Live Activity -->
                            <div class="bg-white dark:bg-gray-900 p-4 sm:p-5 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 flex flex-col h-full">
                                <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3 flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 bg-indigo-600 rounded-full animate-ping"></span>
                                    Live Activity
                                </h3>
                                <div class="space-y-3 flex-1 overflow-y-auto scrollbar-thin max-h-[190px]">
                                    <template x-for="activity in recentActivities" :key="activity.id">
                                        <div class="flex items-start gap-3 group">
                                            <div class="bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 p-1.5 rounded-lg h-fit shrink-0 mt-0.5">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="text-xs font-bold text-gray-900 dark:text-white truncate uppercase tracking-tight" x-text="activity.title"></div>
                                                <div class="text-[10px] font-medium text-gray-400" x-text="activity.time"></div>
                                            </div>
                                        </div>
                                    </template>
                                    <div x-show="recentActivities.length === 0" style="display: none;" class="text-center py-6 text-gray-400 text-xs font-bold italic">No recent activity</div>
                                </div>
                            </div>
                        </div>

                        <!-- Store Health (Operation Center) -->
                        <div class="bg-indigo-600 rounded-2xl p-4 sm:p-5 text-white relative overflow-hidden shadow-lg shadow-indigo-200 dark:shadow-none">
                            <div class="absolute -right-20 -top-20 w-80 h-80 bg-white/10 rounded-full blur-3xl"></div>
                            <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                <div>
                                    <h3 class="text-lg font-black tracking-tight mb-1">Operation Center</h3>
                                    <p class="text-indigo-100 text-xs font-medium leading-relaxed">System is tracking <span class="text-white font-black underline">{{ $stats['activeRepairs'] }} service jobs</span>. Stock levels for {{ $stats['lowStockCount'] }} parts are currently critical.</p>
                                </div>
                                <div class="flex flex-wrap gap-2.5 shrink-0">
                                    <button @click="activeTab = 'repairs'" class="px-5 py-2.5 bg-white text-indigo-600 rounded-xl font-black text-xs hover:scale-105 transition-all shadow-md focus:outline-none focus:ring-2 focus:ring-white">Manage Jobs</button>
                                    <button @click="activeTab = 'inventory'" class="px-5 py-2.5 bg-indigo-500/50 text-white border border-white/20 rounded-xl font-black text-xs hover:bg-indigo-400 transition-all focus:outline-none focus:ring-2 focus:ring-white">Quick Audit</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Inventory Tab -->
                    <div x-show="activeTab === 'inventory'" x-cloak class="space-y-6">
                        <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 overflow-hidden shadow-sm">
                            <div class="p-8 border-b border-gray-100 dark:border-gray-800 flex flex-wrap justify-between items-center gap-3">
                                <h3 class="text-xl font-black text-gray-900 dark:text-white italic">Stock Summary</h3>
                                <div class="flex flex-wrap gap-4 items-center">
                                    <a href="/inventory/adjustments" class="text-indigo-600 font-black text-xs uppercase tracking-widest hover:underline mr-4">Damaged & Returns</a>
                                    <a href="/inventory" class="text-indigo-600 font-black text-xs uppercase tracking-widest hover:underline">View All</a>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left">
                                    <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                                        @foreach($inventorySummary as $item)
                                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                                                <td class="p-6 font-bold text-gray-900 dark:text-white">{{ $item->part_name }}</td>
                                                <td class="p-6">
                                                    <span class="px-3 py-1 rounded-lg {{ $item->quantity <= ($item->low_stock_threshold ?? 5) ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-600' }} font-black text-xs">
                                                        {{ $item->quantity }} Units
                                                    </span>
                                                </td>
                                                <td class="p-6 font-black text-gray-900 dark:text-white text-right">₱{{ number_format($item->price, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Low-stock quick-access panel: threshold alerts, counts and
                             restock links in one place, so a critical part is never
                             buried in the full inventory list. -->
                        <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 overflow-hidden shadow-sm">
                            <div class="p-8 border-b border-gray-100 dark:border-gray-800 flex flex-wrap justify-between items-center gap-3">
                                <div>
                                    <h3 class="text-xl font-black text-gray-900 dark:text-white italic">Low Stock Alerts</h3>
                                    <p class="text-xs text-gray-400 font-bold mt-1">Parts at or below their alert threshold</p>
                                </div>
                                <div class="flex flex-wrap gap-3 items-center">
                                    <span class="px-3 py-1 bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded-full font-black text-xs uppercase tracking-wider">
                                        {{ $lowStockItems->count() }} Low
                                    </span>
                                    <span class="px-3 py-1 bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-full font-black text-xs uppercase tracking-wider">
                                        {{ $outOfStockCount }} Out
                                    </span>
                                    <a href="{{ route('low-stock.index') }}" class="text-indigo-600 font-black text-xs uppercase tracking-widest hover:underline">View All</a>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left">
                                    <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                                        @forelse($lowStockItems as $item)
                                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                                                <td class="p-6">
                                                    <div class="font-bold text-gray-900 dark:text-white">{{ $item->part_name }}</div>
                                                    <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-1">{{ $item->brand }} {{ $item->model }}</div>
                                                </td>
                                                <td class="p-6">
                                                    <span class="px-3 py-1 rounded-lg {{ $item->quantity == 0 ? 'bg-red-50 text-red-600' : 'bg-amber-50 text-amber-600' }} font-black text-xs">
                                                        {{ $item->quantity }} left
                                                    </span>
                                                </td>
                                                <td class="p-6 text-xs font-bold text-gray-500 dark:text-gray-400">Alerts at {{ $item->low_stock_threshold }}</td>
                                                <td class="p-6 text-right">
                                                    <a href="{{ route('inventory.edit', $item) }}" class="inline-flex items-center gap-1 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-all shadow-sm">
                                                        <i data-lucide="plus" class="w-3.5 h-3.5"></i> Restock
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="p-10 text-center text-gray-400 font-bold italic">No parts are below their alert threshold.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Sales Tab -->
                    <div x-show="activeTab === 'sales'" x-cloak class="space-y-6">
                        <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 overflow-hidden shadow-sm">
                            <div class="p-8 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center">
                                <h3 class="text-xl font-black text-gray-900 dark:text-white italic">Recent POS Activity</h3>
                                <a href="/sales" class="bg-indigo-600 text-white px-4 py-2 rounded-xl font-black text-[10px] uppercase tracking-widest shadow-lg">New Sale</a>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left">
                                    <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                                        @foreach($recentSales as $sale)
                                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                                                <td class="p-6 font-bold text-gray-900 dark:text-white">{{ $sale->transaction_reference ?? 'SALE-'.$sale->id }}</td>
                                                <td class="p-6 text-gray-500 text-sm font-medium">{{ $sale->customer->name ?? 'Walk-in' }}</td>
                                                <td class="p-6 font-black text-emerald-600 text-right">₱{{ number_format($sale->total_price, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Service Jobs Tab -->
                    <div x-show="activeTab === 'repairs'" x-cloak class="space-y-6">
                        <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 overflow-hidden shadow-sm">
                            <div class="p-8 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center">
                                <h3 class="text-xl font-black text-gray-900 dark:text-white italic">Active Repairs Tracker</h3>
                                <a href="{{ route('repairs.index') }}" class="text-indigo-600 font-black text-xs uppercase tracking-widest hover:underline">Manage All</a>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left">
                                    <thead class="bg-gray-50/50 dark:bg-gray-800/30 text-[10px] font-black uppercase tracking-widest text-gray-400">
                                        <tr>
                                            <th class="p-6">Motorcycle & Requested Issue</th>
                                            <th class="p-6">Customer</th>
                                            <th class="p-6">Status</th>
                                            <th class="p-6 text-right">Cost</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                                        @forelse($activeServiceJobs as $job)
                                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                                                <td class="p-6">
                                                    <div class="font-bold text-gray-900 dark:text-white">{{ $job->bike_model }}</div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                        <span class="font-semibold text-indigo-600 dark:text-indigo-400">Issue:</span> {{ $job->issue }}
                                                    </div>
                                                </td>
                                                <td class="p-6">
                                                    <div class="text-sm font-bold text-gray-800 dark:text-gray-200">{{ $job->customer_name ?? $job->customer?->name ?? 'Walk-in Customer' }}</div>
                                                </td>
                                                <td class="p-6">
                                                    <span class="px-3 py-1 rounded-full {{ $job->status === 'pending' ? 'bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-300' : 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-300' }} font-black text-[10px] uppercase tracking-widest">
                                                        {{ $job->status === 'ongoing' ? 'Ongoing' : $job->status }}
                                                    </span>
                                                </td>
                                                <td class="p-6 font-black text-gray-900 dark:text-white text-right">₱{{ number_format($job->cost, 2) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="p-8 text-center text-gray-400 font-bold italic">No active repairs in progress.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Pre-orders Tab -->
                    <div x-show="activeTab === 'preorders'" x-cloak class="space-y-6">
                        <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 overflow-hidden shadow-sm">
                            <div class="p-8 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center">
                                <h3 class="text-xl font-black text-gray-900 dark:text-white italic">Recent Pre-orders</h3>
                                <a href="/preorders" class="text-indigo-600 font-black text-xs uppercase tracking-widest hover:underline">Manage All</a>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left">
                                    <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                                        @forelse($recentPreorders as $preorder)
                                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                                                <td class="p-6">
                                                    <div class="font-bold text-gray-900 dark:text-white">{{ $preorder->part_name }}</div>
                                                    <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-1">{{ $preorder->customer_name }}</div>
                                                </td>
                                                <td class="p-6">
                                                    <span class="px-4 py-1 rounded-full {{ $preorder->status === 'pending' ? 'bg-amber-50 text-amber-600' : ($preorder->status === 'completed' ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600') }} font-black text-[10px] uppercase tracking-widest">
                                                        {{ $preorder->status }}
                                                    </span>
                                                </td>
                                                <td class="p-6 font-black text-gray-900 dark:text-white text-right">
                                                    ₱{{ number_format($preorder->total, 2) }}
                                                    @if($preorder->is_paid)
                                                        <div class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 mt-1 uppercase">Paid in full</div>
                                                    @elseif($preorder->status === 'cancelled' || $preorder->status === 'expired')
                                                        <div class="text-[10px] font-bold text-gray-400 mt-1 uppercase">{{ $preorder->status }}</div>
                                                    @elseif(($preorder->down_payment ?? 0) > 0)
                                                        <div class="text-[10px] font-bold text-amber-600 dark:text-amber-400 mt-1 uppercase">DP: ₱{{ number_format($preorder->down_payment, 2) }}</div>
                                                    @else
                                                        <div class="text-[10px] font-bold text-gray-400 mt-1 uppercase">Cash at pickup</div>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="p-6 text-center text-gray-500 font-bold italic">No recent pre-orders found.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Reservations Tab -->
                    <div x-show="activeTab === 'reservations'" x-cloak class="space-y-6">
                        <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 overflow-hidden shadow-sm">
                            <div class="p-8 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center">
                                <div>
                                    <h3 class="text-xl font-black text-gray-900 dark:text-white italic">Customer Reservations</h3>
                                    <p class="text-xs text-gray-400 font-bold mt-1">Pending in-stock reservations awaiting 24-hour in-store payment and pickup</p>
                                </div>
                                <span class="px-3 py-1 bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded-full font-black text-xs uppercase tracking-wider">
                                    {{ $pendingReservations->groupBy(fn ($r) => $r->transaction_reference ?? 'row-'.$r->id)->count() }} Pending
                                </span>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left">
                                    <thead class="bg-gray-50/50 dark:bg-gray-800/30 text-[10px] font-black uppercase tracking-widest text-gray-400">
                                        <tr>
                                            <th class="p-6">Item & Reference</th>
                                            <th class="p-6">Customer</th>
                                            <th class="p-6">Qty & Total</th>
                                            <th class="p-6">24h Pickup Deadline</th>
                                            <th class="p-6 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                                        @forelse($pendingReservations->groupBy(fn ($r) => $r->transaction_reference ?? 'row-'.$r->id) as $ref => $group)
                                            @php $res = $group->first(); $groupTotal = (float) $group->sum('total_price'); $deadline = $res->expires_at ?? ($res->reserved_at ? $res->reserved_at->addHours(24) : $res->created_at->addHours(24)); @endphp
                                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                                                <td class="p-6">
                                                    @foreach($group as $line)
                                                        <div class="font-bold text-gray-900 dark:text-white">{{ $line->item?->part_name ?? 'Unknown Item' }}</div>
                                                    @endforeach
                                                    <div class="text-[10px] font-mono text-gray-400 uppercase mt-0.5">{{ $res->transaction_reference }}</div>
                                                </td>
                                                <td class="p-6">
                                                    <div class="text-sm font-bold text-gray-800 dark:text-gray-200">{{ $res->customer?->name ?? 'Online Customer' }}</div>
                                                    <div class="text-xs text-gray-400">{{ $res->customer?->phone ?? $res->customer?->email ?? '' }}</div>
                                                </td>
                                                <td class="p-6 font-black text-gray-900 dark:text-white">
                                                    <div>x{{ $group->sum('quantity') }} • ₱{{ number_format($groupTotal, 2) }}</div>
                                                </td>
                                                <td class="p-6">
                                                    @if(now()->lt($deadline))
                                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-300 font-black text-[10px] uppercase tracking-wider">
                                                            <i data-lucide="clock" class="w-3 h-3"></i> {{ $deadline->diffForHumans() }}
                                                        </span>
                                                        <div class="text-[10px] text-gray-400 font-bold mt-1">{{ $deadline->format('M d, h:i A') }}</div>
                                                    @else
                                                        <span class="px-3 py-1 rounded-full bg-red-50 text-red-600 dark:bg-red-900/30 dark:text-red-400 font-black text-[10px] uppercase tracking-wider">
                                                            Expired
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="p-6 text-right">
                                                    <div class="flex items-center justify-end gap-2">
                                                        @if($res->transaction_reference && now()->lt($deadline))
                                                            <x-cash-pickup-modal
                                                                :action="route('sales.complete', $res->transaction_reference)"
                                                                :reference="$res->transaction_reference"
                                                                :total="$groupTotal"
                                                                :lines="$group->map(fn ($l) => ['name' => $l->item?->part_name ?? 'Unknown Item', 'qty' => $l->quantity, 'subtotal' => (float) $l->total_price])->values()->all()" />
                                                        @endif
                                                        @if($res->transaction_reference)
                                                            <a href="{{ route('sales.receipt', $res->transaction_reference) }}" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg">
                                                                <i data-lucide="receipt" class="w-3.5 h-3.5"></i> View
                                                            </a>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="p-12 text-center text-gray-400 font-bold italic">No active customer reservations.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Settled reservations: cancelled and expired records stay visible
                             to staff so a customer cancellation is never invisible here. --}}
                        @if(isset($settledReservations) && $settledReservations->count() > 0)
                        <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 overflow-hidden shadow-sm">
                            <div class="p-8 border-b border-gray-100 dark:border-gray-800">
                                <h3 class="text-xl font-black text-gray-900 dark:text-white italic">Cancelled &amp; Expired Reservations</h3>
                                <p class="text-xs text-gray-400 font-bold mt-1">Released reservations kept for history. Expired records are shown separately from voluntary cancellations.</p>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left">
                                    <thead class="bg-gray-50/50 dark:bg-gray-800/30 text-[10px] font-black uppercase tracking-widest text-gray-400">
                                        <tr>
                                            <th class="p-6">Item &amp; Reference</th>
                                            <th class="p-6">Customer</th>
                                            <th class="p-6">Status</th>
                                            <th class="p-6">Reason</th>
                                            <th class="p-6 text-right">Qty &amp; Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                                        @foreach($settledReservations as $res)
                                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                                                <td class="p-6">
                                                    <div class="font-bold text-gray-900 dark:text-white">{{ $res->item?->part_name ?? 'Unknown Item' }}</div>
                                                    <div class="text-[10px] font-mono text-gray-400 uppercase mt-0.5">{{ $res->transaction_reference }}</div>
                                                </td>
                                                <td class="p-6 text-sm font-bold text-gray-800 dark:text-gray-200">{{ $res->customer?->name ?? 'Online Customer' }}</td>
                                                <td class="p-6">
                                                    <span class="px-3 py-1 rounded-full font-black text-[10px] uppercase tracking-wider
                                                        {{ $res->status === 'expired' ? 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300' : '' }}
                                                        {{ $res->status === 'cancelled' ? 'bg-red-50 text-red-600 dark:bg-red-900/30 dark:text-red-300' : '' }}">
                                                        {{ $res->status }}
                                                    </span>
                                                </td>
                                                <td class="p-6 text-xs font-bold text-gray-500">{{ $res->cancellation_reason ?? '—' }}</td>
                                                <td class="p-6 font-black text-gray-900 dark:text-white text-right">x{{ $res->quantity }} • ₱{{ number_format($res->total_price, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Returns & Refunds Tab -->
                    <div x-show="activeTab === 'refunds'" x-cloak class="space-y-6">
                        <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 overflow-hidden shadow-sm">
                            <div class="p-8 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center">
                                <div>
                                    <h3 class="text-xl font-black text-gray-900 dark:text-white italic">Returns & Refunds</h3>
                                    <p class="text-xs text-gray-400 font-bold mt-1">Manage physical POS counter returns, damaged items, and refund requests</p>
                                </div>
                                <a href="{{ route('inventory.adjustments.index') }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-sm transition-all flex items-center gap-2">
                                    <i data-lucide="package-minus" class="w-4 h-4"></i>
                                    POS Returns & Adjustments
                                </a>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left" x-data="{ expandedRefund: null }">
                                    <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                                        @forelse($pendingRefunds ?? [] as $refund)
                                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors cursor-pointer" @click="expandedRefund = (expandedRefund === {{ $refund->id }} ? null : {{ $refund->id }})">
                                                <td class="p-6">
                                                    <div class="flex items-center gap-3">
                                                        <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="expandedRefund === {{ $refund->id }} ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                                        <div>
                                                            <div class="font-bold text-gray-900 dark:text-white">{{ $refund->sale->item?->part_name ?? 'Unknown Item' }}</div>
                                                            <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-1">{{ $refund->customer->name }} • {{ $refund->created_at->format('M d, Y') }}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="p-6">
                                                    <span class="px-4 py-1 rounded-full bg-orange-50 text-orange-600 font-black text-[10px] uppercase tracking-widest">
                                                        {{ $refund->reason }}
                                                    </span>
                                                </td>
                                                <td class="p-6 font-black text-gray-900 dark:text-white text-right">
                                                    ₱{{ number_format($refund->sale->total_price ?? 0, 2) }}
                                                </td>
                                            </tr>
                                            <!-- Expanded Refund Details -->
                                            <tr x-show="expandedRefund === {{ $refund->id }}" x-cloak class="bg-gray-50/30 dark:bg-gray-800/10">
                                                <td colspan="3" class="p-0 border-b border-gray-100 dark:border-gray-800">
                                                    <div class="p-8 pl-14 space-y-6">
                                                        {{-- Refund Method Badge --}}
                                                        <div>
                                                            <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Refund Method</h4>
                                                            @if($refund->refund_method === 'digital')
                                                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-50 text-indigo-600 font-black text-[10px] uppercase tracking-widest">
                                                                    <i data-lucide="smartphone" class="w-3 h-3"></i> Digital (QR Code)
                                                                </span>
                                                            @elseif($refund->refund_method === 'on_site')
                                                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-cyan-50 text-cyan-600 font-black text-[10px] uppercase tracking-widest">
                                                                    <i data-lucide="store" class="w-3 h-3"></i> On-site Pickup
                                                                    @if($refund->pickup_time)
                                                                        — {{ $refund->pickup_time->format('M d, Y g:i A') }}
                                                                    @endif
                                                                </span>
                                                            @else
                                                                <span class="px-3 py-1.5 rounded-lg bg-gray-50 text-gray-500 font-black text-[10px] uppercase tracking-widest">Not specified</span>
                                                            @endif
                                                        </div>

                                                        <div>
                                                            <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Customer Details</h4>
                                                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $refund->details ?? 'No additional details provided.' }}</p>
                                                        </div>

                                                        {{-- QR Code (for digital refunds) --}}
                                                        @if($refund->qr_code_path)
                                                        <div>
                                                            <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Customer QR Code</h4>
                                                            <a href="{{ Storage::url($refund->qr_code_path) }}" target="_blank" class="inline-block mt-2">
                                                                <img src="{{ Storage::url($refund->qr_code_path) }}" alt="Customer QR Code" class="h-40 rounded-xl object-cover border border-gray-200 dark:border-gray-700 shadow-sm hover:opacity-80 transition-opacity">
                                                            </a>
                                                            <p class="text-[10px] text-gray-400 font-bold mt-2">Click to view full size. Use this QR to send the refund amount.</p>
                                                        </div>
                                                        @endif

                                                        {{-- Photo Evidence --}}
                                                        @if($refund->photo_path)
                                                        <div>
                                                            <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Photo Evidence</h4>
                                                            <a href="{{ Storage::url($refund->photo_path) }}" target="_blank" class="inline-block mt-2">
                                                                <img src="{{ Storage::url($refund->photo_path) }}" alt="Refund Evidence" class="h-32 rounded-xl object-cover border border-gray-200 dark:border-gray-700 shadow-sm hover:opacity-80 transition-opacity">
                                                            </a>
                                                        </div>
                                                        @endif
                                                        
                                                        <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm mt-4">
                                                            <h4 class="text-xs font-black text-gray-900 dark:text-white uppercase tracking-widest mb-4">Admin Action</h4>
                                                            <div class="flex flex-col sm:flex-row gap-4">
                                                                <form action="{{ route('admin.refund.approve', $refund->id) }}" method="POST" class="flex-1 space-y-4">
                                                                    @csrf
                                                                    <textarea name="admin_notes" rows="2" placeholder="Reason for approval (required)" required class="w-full text-sm border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 rounded-xl focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                                                                    <div class="flex items-center gap-2 mb-4">
                                                                        <input type="checkbox" name="restock" id="restock_{{ $refund->id }}" value="1" class="rounded text-indigo-600 focus:ring-indigo-500 bg-gray-50 border-gray-300">
                                                                        <label for="restock_{{ $refund->id }}" class="text-xs font-bold text-gray-700 dark:text-gray-300">Restock item (increment inventory by {{ $refund->sale->quantity ?? 0 }})</label>
                                                                    </div>
                                                                    <button type="submit" class="w-full px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs uppercase tracking-widest rounded-xl transition-colors shadow-lg shadow-emerald-200 dark:shadow-none focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">Approve Refund</button>
                                                                </form>
                                                                
                                                                <form action="{{ route('admin.refund.reject', $refund->id) }}" method="POST" class="flex-1 space-y-4">
                                                                    @csrf
                                                                    <textarea name="admin_notes" rows="2" placeholder="Reason for rejection (required)" required class="w-full text-sm border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 rounded-xl focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                                                                    <div class="mb-4 h-[20px]"></div> <!-- Spacer to match approve side -->
                                                                    <button type="submit" class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-black text-xs uppercase tracking-widest rounded-xl transition-colors shadow-lg shadow-red-200 dark:shadow-none focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">Reject Refund</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="p-12 text-center">
                                                    <div class="w-16 h-16 bg-gray-50 dark:bg-gray-800 rounded-2xl flex items-center justify-center mx-auto mb-4 text-gray-400">
                                                        <i data-lucide="check-circle" class="w-8 h-8"></i>
                                                    </div>
                                                    <p class="text-gray-900 dark:text-white font-bold text-lg mb-1">All caught up!</p>
                                                    <p class="text-gray-400 text-sm font-medium">There are no pending refund requests.</p>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Reports Tab -->
                    <div x-show="activeTab === 'reports'" x-cloak class="space-y-5">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Revenue Card -->
                            <div class="bg-white dark:bg-gray-900 p-5 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm">
                                <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Monthly Revenue</div>
                                <div class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white mb-2">₱{{ number_format($stats['monthlyRevenue'], 2) }}</div>
                                <div class="flex items-center gap-2 text-indigo-600 font-bold text-[11px] bg-indigo-50 dark:bg-indigo-900/20 w-fit px-2.5 py-0.5 rounded-full italic">{{ number_format($stats['totalSalesCount']) }} Total Transactions</div>
                            </div>
                            
                            <!-- Repair Card -->
                            <div class="bg-white dark:bg-gray-900 p-5 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm">
                                <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Completed Repairs</div>
                                <div class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white mb-2">{{ number_format($stats['completedRepairs']) }}</div>
                                <div class="flex items-center gap-2 text-emerald-600 font-bold text-[11px] bg-emerald-50 dark:bg-emerald-900/20 w-fit px-2.5 py-0.5 rounded-full italic">All time</div>
                            </div>
                        </div>

                        <!-- Revenue Chart -->
                        <div class="bg-white dark:bg-gray-900 p-5 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm">
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest">Daily Revenue</h3>
                                    <p class="text-[10px] text-gray-400">Last 30 days</p>
                                </div>
                                <div class="flex items-center gap-2 text-emerald-600 font-bold text-xs bg-emerald-50 dark:bg-emerald-900/20 px-3 py-1 rounded-full">
                                    <span class="w-2 h-2 bg-emerald-500 rounded-full"></span>
                                    ₱{{ number_format(array_sum($chartData), 2) }} total
                                </div>
                            </div>
                            <div class="relative h-48 sm:h-52">
                                <canvas id="revenueChart"></canvas>
                            </div>
                        </div>

                        <div class="bg-indigo-600 rounded-2xl p-5 sm:p-6 text-white relative overflow-hidden shadow-lg shadow-indigo-200 dark:shadow-none">
                            <div class="absolute -right-20 -top-20 w-80 h-80 bg-white/10 rounded-full blur-3xl"></div>
                            <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div>
                                    <h3 class="text-lg font-black tracking-tight mb-1">Full Sales History</h3>
                                    <p class="text-indigo-100 text-xs font-medium">Export and view all detailed POS transactions and receipts.</p>
                                </div>
                                <a href="/sales/history" class="px-6 py-2.5 bg-white text-indigo-600 rounded-xl font-black text-xs hover:scale-105 transition-all shadow-md whitespace-nowrap shrink-0">View History</a>
                            </div>
                        </div>
                    </div>
                </div>

        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('revenueChart');
    if (!ctx) return;

    const isDark = document.documentElement.classList.contains('dark');
    const labels = @json($chartLabels);
    const data = @json($chartData);

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Revenue (₱)',
                data: data,
                backgroundColor: isDark ? 'rgba(99, 102, 241, 0.6)' : 'rgba(99, 102, 241, 0.8)',
                borderColor: 'rgba(99, 102, 241, 1)',
                borderWidth: 0,
                borderRadius: 6,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: isDark ? '#1f2937' : '#fff',
                    titleColor: isDark ? '#f9fafb' : '#111827',
                    bodyColor: isDark ? '#d1d5db' : '#4b5563',
                    borderColor: isDark ? '#374151' : '#e5e7eb',
                    borderWidth: 1,
                    padding: 12,
                    cornerRadius: 12,
                    titleFont: { weight: '800', size: 12 },
                    bodyFont: { weight: '600', size: 11 },
                    callbacks: {
                        label: function(context) {
                            return '₱' + context.parsed.y.toLocaleString(undefined, { minimumFractionDigits: 2 });
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        color: isDark ? '#6b7280' : '#9ca3af',
                        font: { size: 9, weight: '700' },
                        maxRotation: 45,
                        maxTicksLimit: 15,
                    },
                    border: { display: false }
                },
                y: {
                    grid: {
                        color: isDark ? 'rgba(55, 65, 81, 0.3)' : 'rgba(229, 231, 235, 0.5)',
                        drawTicks: false,
                    },
                    ticks: {
                        color: isDark ? '#6b7280' : '#9ca3af',
                        font: { size: 10, weight: '700' },
                        padding: 8,
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    },
                    border: { display: false },
                    beginAtZero: true,
                }
            }
        }
    });
});
</script>
</x-app-layout>
