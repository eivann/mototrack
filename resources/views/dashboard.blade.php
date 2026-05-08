<x-app-layout>
    <div x-data="{ activeTab: 'overview' }" class="min-h-screen bg-gray-50 dark:bg-gray-950 pb-12">
        <!-- Premium Header -->
        <div class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 sticky top-0 z-30 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-4">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-gradient-to-br from-indigo-600 to-indigo-900 rounded-2xl flex items-center justify-center shadow-lg">
                            <span class="text-white text-2xl font-black">M</span>
                        </div>
                        <div>
                            <h2 class="text-2xl font-black text-gray-900 dark:text-white tracking-tighter">MOTOTRACK</h2>
                            <p class="text-[10px] font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-[0.2em]">Master Dashboard</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <div class="hidden md:flex flex-col items-end">
                            <span class="text-xs font-black text-gray-900 dark:text-white uppercase tracking-tight">{{ Auth::user()->name }}</span>
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Store Owner</span>
                        </div>
                        <div class="h-10 w-10 rounded-full bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 border border-indigo-100 dark:border-indigo-800 shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>
                        </div>
                    </div>
                </div>

                <!-- Navigation Tabs -->
                <div class="flex gap-2 overflow-x-auto no-scrollbar pb-0 mt-2">
                    @foreach(['overview' => 'Overview', 'inventory' => 'Inventory', 'sales' => 'POS & Sales', 'repairs' => 'Service Jobs', 'reports' => 'Reports'] as $id => $label)
                        <button 
                            @click="activeTab = '{{ $id }}'"
                            :class="activeTab === '{{ $id }}' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-200 dark:shadow-none' : 'text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800'"
                            class="whitespace-nowrap py-3 px-6 rounded-xl font-black text-xs uppercase tracking-widest transition-all mb-4"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex flex-col md:flex-row gap-8 items-start">
                <!-- Main Content (Left) -->
                <div class="w-full md:flex-1 space-y-8 min-w-0 order-2 md:order-1">
                    
                    <!-- Overview Tab -->
                    <div x-show="activeTab === 'overview'" x-transition class="space-y-8">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div class="bg-white dark:bg-gray-900 p-8 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm hover:shadow-xl transition-all">
                                <div class="text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Total Products</div>
                                <div class="text-4xl font-black text-gray-900 dark:text-white mb-4">{{ number_format($stats['totalProducts']) }}</div>
                                <div class="flex items-center gap-2 text-green-600 font-bold text-xs bg-green-50 dark:bg-green-900/20 w-fit px-3 py-1 rounded-full italic">+12.5% this month</div>
                            </div>
                            <div class="bg-white dark:bg-gray-900 p-8 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm hover:shadow-xl transition-all">
                                <div class="text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Daily Revenue</div>
                                <div class="text-4xl font-black text-gray-900 dark:text-white mb-4">₱{{ number_format($stats['todayRevenue'], 2) }}</div>
                                <div class="flex items-center gap-2 text-indigo-600 font-bold text-xs bg-indigo-50 dark:bg-indigo-900/20 w-fit px-3 py-1 rounded-full italic">Today's collection</div>
                            </div>
                        </div>

                        <!-- Store Health (The Big Blue Card) -->
                        <div class="bg-indigo-600 rounded-[3rem] p-10 text-white relative overflow-hidden shadow-2xl shadow-indigo-300 dark:shadow-none">
                            <div class="absolute -right-20 -top-20 w-80 h-80 bg-white/10 rounded-full blur-3xl"></div>
                            <div class="relative z-10">
                                <h3 class="text-4xl font-black tracking-tighter mb-4 italic">Operation Center</h3>
                                <p class="text-indigo-100 text-lg max-w-md font-medium leading-relaxed mb-8">System is tracking <span class="text-white font-black underline">{{ $stats['activeRepairs'] }} service jobs</span>. Stock levels for {{ $stats['lowStockCount'] }} parts are currently critical.</p>
                                <div class="flex flex-wrap gap-4">
                                    <button @click="activeTab = 'repairs'" class="px-8 py-4 bg-white text-indigo-600 rounded-2xl font-black text-sm hover:scale-105 transition-all shadow-xl">Manage Jobs</button>
                                    <button @click="activeTab = 'inventory'" class="px-8 py-4 bg-indigo-500/50 text-white border border-white/20 rounded-2xl font-black text-sm hover:bg-indigo-400 transition-all">Quick Audit</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Inventory Tab -->
                    <div x-show="activeTab === 'inventory'" x-cloak class="space-y-6">
                        <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 overflow-hidden shadow-sm">
                            <div class="p-8 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center">
                                <h3 class="text-xl font-black text-gray-900 dark:text-white italic">Stock Summary</h3>
                                <a href="/inventory" class="text-indigo-600 font-black text-xs uppercase tracking-widest hover:underline">View All &rarr;</a>
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
                            <div class="p-8 border-b border-gray-100 dark:border-gray-800">
                                <h3 class="text-xl font-black text-gray-900 dark:text-white italic">Active Repairs Tracker</h3>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left">
                                    <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                                        @foreach($activeServiceJobs as $job)
                                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                                                <td class="p-6 font-bold text-gray-900 dark:text-white">{{ $job->bike_model }}</td>
                                                <td class="p-6">
                                                    <span class="px-4 py-1 rounded-full {{ $job->status === 'pending' ? 'bg-amber-50 text-amber-600' : 'bg-blue-50 text-blue-600' }} font-black text-[10px] uppercase tracking-widest">
                                                        {{ $job->status === 'ongoing' ? 'Ongoing' : $job->status }}
                                                    </span>
                                                </td>
                                                <td class="p-6 font-black text-gray-900 dark:text-white text-right">₱{{ number_format($job->cost, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Reports Tab -->
                    <div x-show="activeTab === 'reports'" x-cloak>
                        <div class="bg-white dark:bg-gray-900 rounded-[3rem] p-16 text-center border border-gray-100 dark:border-gray-800">
                            <div class="w-20 h-20 bg-indigo-50 dark:bg-indigo-900/30 rounded-3xl flex items-center justify-center mx-auto mb-6 text-indigo-600">
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>
                            </div>
                            <h3 class="text-2xl font-black text-gray-900 dark:text-white mb-2">Performance Reports</h3>
                            <p class="text-gray-400 font-bold mb-8">Deep analytics and shop growth trends are being prepared.</p>
                            <a href="/sales/history" class="inline-flex px-8 py-4 bg-indigo-600 text-white font-black rounded-2xl shadow-xl hover:scale-105 transition-all">Go to History</a>
                        </div>
                    </div>
                </div>

                <!-- Sidebar (Right) -->
                <div class="w-full md:w-80 space-y-8 flex-shrink-0 order-1 md:order-2">
                    <div class="bg-gradient-to-br from-indigo-600 to-indigo-800 p-8 rounded-[2.5rem] shadow-xl text-white relative overflow-hidden group">
                        <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-white/10 rounded-full blur-3xl"></div>
                        <h4 class="text-xl font-black mb-2 italic tracking-tighter uppercase">Pro Tip</h4>
                        <p class="text-xs text-indigo-100 mb-8 font-medium leading-relaxed">
                            @if($stats['lowStockCount'] > 0)
                                Attention: <span class="text-white font-black underline">{{ $stats['lowStockCount'] }} parts</span> are running low. Tap below to see the full audit.
                            @else
                                Shop is operating at peak efficiency. No immediate restocks required.
                            @endif
                        </p>
                        <button @click="activeTab = 'inventory'" class="w-full py-4 bg-white text-indigo-600 rounded-2xl font-black text-[10px] uppercase tracking-widest shadow-xl active:scale-95 transition-all">Audit Inventory</button>
                    </div>

                    <div class="bg-white dark:bg-gray-900 p-8 rounded-[2.5rem] shadow-sm border border-gray-100 dark:border-gray-800">
                        <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-6 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 bg-indigo-600 rounded-full animate-ping"></span>
                            Live Activity
                        </h3>
                        <div class="space-y-6">
                            @forelse($recentActivities as $activity)
                                <div class="flex gap-4 group">
                                    <div class="bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 p-2 rounded-xl h-fit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-xs font-black text-gray-900 dark:text-white truncate uppercase tracking-tight">{{ $activity->data['title'] }}</div>
                                        <div class="text-[10px] font-bold text-gray-400 mt-0.5">{{ $activity->created_at->diffForHumans() }}</div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-4 text-gray-400 text-xs font-bold italic">No recent activity</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>