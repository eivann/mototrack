<x-app-layout>
    <div x-data="{ activeTab: 'overview' }" class="min-h-screen bg-gray-50 dark:bg-gray-950 pb-12">
        <!-- Section Header -->
        <div class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
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

                <!-- Navigation Tabs -->
                <div class="flex gap-2 overflow-x-auto no-scrollbar pb-4 px-1 mt-2">
                    @foreach(['overview' => 'Overview', 'inventory' => 'Inventory', 'sales' => 'POS & Sales', 'repairs' => 'Service Jobs', 'preorders' => 'Pre-orders', 'refunds' => 'Refund Requests', 'reports' => 'Reports'] as $id => $label)
                        <button
                            @click="activeTab = '{{ $id }}'"
                            :class="activeTab === '{{ $id }}' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-200 dark:shadow-none' : 'text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800'"
                            class="whitespace-nowrap py-3 px-6 rounded-xl font-black text-xs uppercase tracking-widest transition-all focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        >
                            {{ $label }}
                            @if($id === 'refunds' && isset($pendingRefunds) && $pendingRefunds->count() > 0)
                                <span class="ml-2 inline-flex items-center justify-center px-2 py-0.5 text-[9px] font-black leading-none text-white bg-red-500 rounded-full">{{ $pendingRefunds->count() }}</span>
                            @endif
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
                            <div class="bg-white dark:bg-gray-900 p-5 sm:p-8 rounded-2xl sm:rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm hover:shadow-xl transition-all">
                                <div class="text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Total Products</div>
                                <div class="text-3xl sm:text-4xl font-black text-gray-900 dark:text-white mb-4">{{ number_format($stats['totalProducts']) }}</div>
                                <div class="flex items-center gap-2 text-green-600 font-bold text-xs bg-green-50 dark:bg-green-900/20 w-fit px-3 py-1 rounded-full italic">+12.5% this month</div>
                            </div>
                            <div class="bg-white dark:bg-gray-900 p-5 sm:p-8 rounded-2xl sm:rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm hover:shadow-xl transition-all">
                                <div class="text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Daily Revenue</div>
                                <div class="text-3xl sm:text-4xl font-black text-gray-900 dark:text-white mb-4">₱{{ number_format($stats['todayRevenue'], 2) }}</div>
                                <div class="flex items-center gap-2 text-indigo-600 font-bold text-xs bg-indigo-50 dark:bg-indigo-900/20 w-fit px-3 py-1 rounded-full italic">Today's collection</div>
                            </div>
                        </div>

                        <!-- Store Health (The Big Blue Card) -->
                        <div class="bg-indigo-600 rounded-2xl sm:rounded-[3rem] p-6 sm:p-10 text-white relative overflow-hidden shadow-2xl shadow-indigo-300 dark:shadow-none">
                            <div class="absolute -right-20 -top-20 w-80 h-80 bg-white/10 rounded-full blur-3xl"></div>
                            <div class="relative z-10">
                                <h3 class="text-4xl font-black tracking-tighter mb-4 italic">Operation Center</h3>
                                <p class="text-indigo-100 text-lg max-w-md font-medium leading-relaxed mb-8">System is tracking <span class="text-white font-black underline">{{ $stats['activeRepairs'] }} service jobs</span>. Stock levels for {{ $stats['lowStockCount'] }} parts are currently critical.</p>
                                <div class="flex flex-wrap gap-4">
                                    <button @click="activeTab = 'repairs'" class="px-8 py-4 bg-white text-indigo-600 rounded-2xl font-black text-sm hover:scale-105 transition-all shadow-xl focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-indigo-600">Manage Jobs</button>
                                    <button @click="activeTab = 'inventory'" class="px-8 py-4 bg-indigo-500/50 text-white border border-white/20 rounded-2xl font-black text-sm hover:bg-indigo-400 transition-all focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-indigo-600">Quick Audit</button>
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
                                                    ₱{{ number_format($preorder->down_payment, 2) }}
                                                    <div class="text-[10px] font-bold text-gray-400 mt-1 uppercase">DP Received</div>
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

                    <!-- Refunds Tab -->
                    <div x-show="activeTab === 'refunds'" x-cloak class="space-y-6">
                        <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 overflow-hidden shadow-sm">
                            <div class="p-8 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center">
                                <h3 class="text-xl font-black text-gray-900 dark:text-white italic">Pending Refund Requests</h3>
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
                                                        <div>
                                                            <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Customer Details</h4>
                                                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $refund->details ?? 'No additional details provided.' }}</p>
                                                        </div>
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
                                                                    <textarea name="admin_notes" rows="2" placeholder="Approval notes (optional)" class="w-full text-sm border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 rounded-xl focus:ring-indigo-500 focus:border-indigo-500"></textarea>
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
                    <div x-show="activeTab === 'reports'" x-cloak class="space-y-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <!-- Revenue Card -->
                            <div class="bg-white dark:bg-gray-900 p-8 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm">
                                <div class="text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Monthly Revenue</div>
                                <div class="text-4xl font-black text-gray-900 dark:text-white mb-4">₱{{ number_format($stats['monthlyRevenue'], 2) }}</div>
                                <div class="flex items-center gap-2 text-indigo-600 font-bold text-xs bg-indigo-50 dark:bg-indigo-900/20 w-fit px-3 py-1 rounded-full italic">{{ number_format($stats['totalSalesCount']) }} Total Transactions</div>
                            </div>
                            
                            <!-- Repair Card -->
                            <div class="bg-white dark:bg-gray-900 p-8 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm">
                                <div class="text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Completed Repairs</div>
                                <div class="text-4xl font-black text-gray-900 dark:text-white mb-4">{{ number_format($stats['completedRepairs']) }}</div>
                                <div class="flex items-center gap-2 text-emerald-600 font-bold text-xs bg-emerald-50 dark:bg-emerald-900/20 w-fit px-3 py-1 rounded-full italic">All time</div>
                            </div>
                        </div>

                        <!-- Revenue Chart -->
                        <div class="bg-white dark:bg-gray-900 p-6 sm:p-8 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm">
                            <div class="flex items-center justify-between mb-6">
                                <div>
                                    <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest">Daily Revenue</h3>
                                    <p class="text-[10px] text-gray-400 mt-1">Last 30 days</p>
                                </div>
                                <div class="flex items-center gap-2 text-emerald-600 font-bold text-xs bg-emerald-50 dark:bg-emerald-900/20 px-3 py-1.5 rounded-full">
                                    <span class="w-2 h-2 bg-emerald-500 rounded-full"></span>
                                    ₱{{ number_format(array_sum($chartData), 2) }} total
                                </div>
                            </div>
                            <div class="relative" style="height: 300px;">
                                <canvas id="revenueChart"></canvas>
                            </div>
                        </div>

                        <div class="bg-indigo-600 rounded-[3rem] p-10 text-white relative overflow-hidden shadow-xl">
                            <div class="absolute -right-20 -top-20 w-80 h-80 bg-white/10 rounded-full blur-3xl"></div>
                            <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-6">
                                <div>
                                    <h3 class="text-2xl font-black tracking-tighter mb-2 italic">Full Sales History</h3>
                                    <p class="text-indigo-100 text-sm font-medium">Export and view all detailed POS transactions and receipts.</p>
                                </div>
                                <a href="/sales/history" class="px-8 py-4 bg-white text-indigo-600 rounded-2xl font-black text-sm hover:scale-105 transition-all shadow-lg whitespace-nowrap">View History</a>
                            </div>
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
                        <button @click="activeTab = 'inventory'" class="w-full py-4 bg-white text-indigo-600 rounded-2xl font-black text-[10px] uppercase tracking-widest shadow-xl active:scale-95 transition-all focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-indigo-700">Audit Inventory</button>
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
