<x-app-layout>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8 px-4 sm:px-6 lg:px-8 font-sans">
        <div class="max-w-7xl mx-auto" x-data="{ activeTab: 'sales' }">
            <div class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">System Reports</h1>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Analyze sales performance and monitor inventory levels.</p>
                </div>
                <div class="flex bg-gray-100 dark:bg-gray-800 p-1 rounded-xl">
                    <button @click="activeTab = 'sales'" :class="activeTab === 'sales' ? 'bg-white dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'" class="px-6 py-2 text-sm font-bold rounded-lg transition-all">Sales History</button>
                    <button @click="activeTab = 'stock'" :class="activeTab === 'stock' ? 'bg-white dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'" class="px-6 py-2 text-sm font-bold rounded-lg transition-all">Stock Levels</button>
                </div>
            </div>

            <!-- SALES TAB -->
            <div x-show="activeTab === 'sales'" x-transition>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl shadow-lg shadow-green-500/30 p-6 text-white relative overflow-hidden">
                        <div class="absolute -right-6 -top-6 bg-white/10 w-24 h-24 rounded-full blur-2xl"></div>
                        <div class="relative z-10 flex items-center justify-between">
                            <div>
                                <p class="text-green-50 font-medium text-sm uppercase tracking-wider mb-1">Total Revenue</p>
                                <h3 class="text-4xl font-bold">₱{{ number_format($totalSales, 2) }}</h3>
                            </div>
                            <div class="bg-white/20 p-3 rounded-xl">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl shadow-lg shadow-blue-500/30 p-6 text-white relative overflow-hidden">
                        <div class="absolute -right-6 -top-6 bg-white/10 w-24 h-24 rounded-full blur-2xl"></div>
                        <div class="relative z-10 flex items-center justify-between">
                            <div>
                                <p class="text-blue-50 font-medium text-sm uppercase tracking-wider mb-1">Total Transactions</p>
                                <h3 class="text-4xl font-bold">{{ number_format($totalTransactions) }}</h3>
                            </div>
                            <div class="bg-white/20 p-3 rounded-xl">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 mb-8">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300 mr-2">Quick Filters:</span>
                            <a href="{{ url()->current() }}?filter=today" class="px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ request('filter') == 'today' ? 'bg-indigo-600 text-white shadow-md' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">Today</a>
                            <a href="{{ url()->current() }}?filter=week" class="px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ request('filter') == 'week' ? 'bg-indigo-600 text-white shadow-md' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">This Week</a>
                            <a href="{{ url()->current() }}?filter=month" class="px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ request('filter') == 'month' ? 'bg-indigo-600 text-white shadow-md' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">This Month</a>
                            <a href="{{ url()->current() }}" class="px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ !request('filter') && !request('from') ? 'bg-gray-800 dark:bg-gray-600 text-white shadow-md' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">All Time</a>
                        </div>
                        <form method="GET" class="flex flex-col sm:flex-row items-end sm:items-center gap-3">
                            <div class="flex items-center gap-2">
                                <div class="flex flex-col">
                                    <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">From Date</label>
                                    <input type="date" name="from" value="{{ request('from') }}" class="border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none">
                                </div>
                                <span class="text-gray-400 mt-5">-</span>
                                <div class="flex flex-col">
                                    <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">To Date</label>
                                    <input type="date" name="to" value="{{ request('to') }}" class="border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none">
                                </div>
                            </div>
                            <button type="submit" class="mt-4 sm:mt-0 bg-indigo-600 text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 shadow-sm transition-colors flex items-center gap-2 h-[38px]">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                                Filter Custom
                            </button>
                        </form>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700 text-xs tracking-wider uppercase text-gray-500 dark:text-gray-400">
                                    <th class="p-5 font-semibold">Item Sold</th>
                                    <th class="p-5 font-semibold text-center">Quantity</th>
                                    <th class="p-5 font-semibold text-right">Total Price</th>
                                    <th class="p-5 font-semibold text-center">Date & Time</th>
                                    <th class="p-5 font-semibold text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                                @forelse($sales as $sale)
                                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                                        <td class="p-5 font-semibold text-gray-900 dark:text-white">
                                            {{ $sale->item->part_name ?? 'Unknown Item' }}
                                            <div class="text-xs text-gray-400 dark:text-gray-500 font-normal mt-0.5">{{ $sale->item->brand ?? '' }} {{ $sale->item->model ?? '' }}</div>
                                        </td>
                                        <td class="p-5 text-center font-medium">
                                            <span class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-2.5 py-1 rounded-full text-xs">{{ $sale->quantity }}</span>
                                        </td>
                                        <td class="p-5 text-right font-bold text-gray-900 dark:text-white">₱{{ number_format($sale->total_price, 2) }}</td>
                                        <td class="p-5 text-center text-gray-500 dark:text-gray-400">
                                            {{ $sale->created_at->format('M d, Y') }}
                                            <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $sale->created_at->format('h:i A') }}</div>
                                        </td>
                                        <td class="p-5 text-center">
                                            <a href="/sales/{{ $sale->id }}/receipt" class="inline-flex items-center gap-1 bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400 hover:bg-green-100 dark:hover:bg-green-900/50 px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                Receipt
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="p-10 text-center text-gray-500 dark:text-gray-400">
                                            <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-3 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                            <p class="font-medium text-gray-600 dark:text-gray-400">No sales found for this period</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                <div class="mt-8">
                    {{ $sales->links() }}
                </div>
            </div>

            <!-- STOCK TAB -->
            <div x-show="activeTab === 'stock'" x-transition>
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700 text-xs tracking-wider uppercase text-gray-500 dark:text-gray-400">
                                    <th class="p-5 font-semibold">Inventory Item</th>
                                    <th class="p-5 font-semibold">Category</th>
                                    <th class="p-5 font-semibold text-center">Stock Level</th>
                                    <th class="p-5 font-semibold text-right">Unit Price</th>
                                    <th class="p-5 font-semibold text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                                @foreach($inventoryItems as $item)
                                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                                        <td class="p-5">
                                            <div class="font-bold text-gray-900 dark:text-white">{{ $item->part_name }}</div>
                                            <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $item->brand }} • {{ $item->model }}</div>
                                        </td>
                                        <td class="p-5"><span class="px-2.5 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-lg text-xs font-semibold">{{ $item->category }}</span></td>
                                        <td class="p-5 text-center font-bold text-gray-900 dark:text-white">{{ $item->quantity }}</td>
                                        <td class="p-5 text-right font-bold text-gray-900 dark:text-white">₱{{ number_format($item->price, 2) }}</td>
                                        <td class="p-5 text-center">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-widest
                                                {{ $item->quantity <= 0 ? 'bg-red-100 dark:bg-red-900/40 text-red-800 dark:text-red-300' : ($item->quantity <= ($item->low_stock_threshold ?? 5) ? 'bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300' : 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-300') }}">
                                                {{ $item->quantity <= 0 ? 'Out of Stock' : ($item->quantity <= ($item->low_stock_threshold ?? 5) ? 'Low Stock' : 'In Stock') }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                <div class="mt-8">
                    {{ $inventoryItems->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>