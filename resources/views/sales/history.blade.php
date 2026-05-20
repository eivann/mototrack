<x-app-layout>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8 px-4 sm:px-6 lg:px-8 font-sans">
        <div class="max-w-7xl mx-auto" x-data="{ activeTab: 'sales' }">
            <div class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">System Reports</h1>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Analyze sales performance and monitor inventory levels.</p>
                </div>
                <div class="flex bg-gray-100 dark:bg-gray-800 p-1 rounded-xl">
                    <button @click="activeTab = 'sales'" :class="activeTab === 'sales' ? 'bg-white dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'" class="px-6 py-2 text-sm font-bold rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-indigo-500">Sales History</button>
                    <button @click="activeTab = 'stock'" :class="activeTab === 'stock' ? 'bg-white dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'" class="px-6 py-2 text-sm font-bold rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-indigo-500">Stock Levels</button>
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
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-8">
                        <div>
                            <span class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3">Quick Filters</span>
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ url()->current() }}?filter=today" class="px-4 py-2.5 text-xs font-bold rounded-xl transition-all focus:outline-none focus:ring-2 focus:ring-indigo-500 {{ request('filter') == 'today' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-200 dark:shadow-none' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">Today</a>
                                <a href="{{ url()->current() }}?filter=week" class="px-4 py-2.5 text-xs font-bold rounded-xl transition-all focus:outline-none focus:ring-2 focus:ring-indigo-500 {{ request('filter') == 'week' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-200 dark:shadow-none' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">This Week</a>
                                <a href="{{ url()->current() }}?filter=month" class="px-4 py-2.5 text-xs font-bold rounded-xl transition-all focus:outline-none focus:ring-2 focus:ring-indigo-500 {{ request('filter') == 'month' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-200 dark:shadow-none' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">This Month</a>
                                <a href="{{ url()->current() }}" class="px-4 py-2.5 text-xs font-bold rounded-xl transition-all focus:outline-none focus:ring-2 focus:ring-indigo-500 {{ !request('filter') && !request('from') ? 'bg-gray-900 dark:bg-gray-600 text-white shadow-lg shadow-gray-200 dark:shadow-none' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">All Time</a>
                            </div>
                        </div>
                        <form method="GET" class="w-full lg:w-auto" x-data="{ loading: false }" @submit="loading = true">
                            <span class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3">Custom Range</span>
                            <div class="flex flex-col sm:flex-row items-end gap-3">
                                <div class="grid grid-cols-2 gap-3 w-full sm:w-auto">
                                    <div class="flex flex-col">
                                        <label for="filter-from" class="text-[10px] font-black text-gray-400 uppercase mb-1.5 ml-1">From</label>
                                        <input id="filter-from" type="date" name="from" value="{{ request('from') }}" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white rounded-xl text-sm px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div class="flex flex-col">
                                        <label for="filter-to" class="text-[10px] font-black text-gray-400 uppercase mb-1.5 ml-1">To</label>
                                        <input id="filter-to" type="date" name="to" value="{{ request('to') }}" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white rounded-xl text-sm px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                </div>
                                <button type="submit" :disabled="loading" class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-xl text-xs font-bold shadow-lg shadow-blue-200 dark:shadow-none transition-all active:scale-[0.98] h-[46px] flex items-center justify-center gap-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-75 disabled:cursor-not-allowed">
                                    <svg x-show="loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    <i x-show="!loading" data-lucide="filter" class="w-4 h-4"></i>
                                    Apply
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="hidden md:block overflow-x-auto">
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
                                            <a href="/sales/{{ $sale->id }}/receipt" aria-label="View receipt for {{ $sale->item->part_name ?? 'sale' }}" class="inline-flex items-center gap-1 bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400 hover:bg-green-100 dark:hover:bg-green-900/50 px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-green-400">
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

                    <!-- MOBILE SALES CARDS -->
                    <div class="grid grid-cols-1 gap-4 p-4 md:hidden">
                        @forelse($sales as $sale)
                            <div class="bg-gray-50 dark:bg-gray-700/30 p-4 rounded-xl border border-gray-100 dark:border-gray-600">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <h4 class="font-bold text-gray-900 dark:text-white">{{ $sale->item->part_name ?? 'Unknown Item' }}</h4>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $sale->item->brand ?? '' }} {{ $sale->item->model ?? '' }}</p>
                                    </div>
                                    <span class="bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 px-2.5 py-1 rounded-lg text-xs font-bold">
                                        Qty: {{ $sale->quantity }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-200/50 dark:border-gray-600/50">
                                    <div>
                                        <p class="text-[10px] text-gray-400 uppercase font-black mb-1">Total Amount</p>
                                        <p class="text-sm font-bold text-gray-900 dark:text-white">₱{{ number_format($sale->total_price, 2) }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-[10px] text-gray-400 uppercase font-black mb-1">{{ $sale->created_at->format('M d, Y') }}</p>
                                        <a href="/sales/{{ $sale->id }}/receipt" class="text-xs font-bold text-green-600 dark:text-green-400 flex items-center justify-end gap-1">
                                            View Receipt <i data-lucide="chevron-right" class="w-3 h-3"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="p-8 text-center text-gray-500">
                                No sales recorded.
                            </div>
                        @endforelse
                    </div>
                <div class="mt-8 px-4">
                    <div class="mb-4 text-[11px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 md:hidden text-center">
                        Showing {{ $sales->firstItem() }} - {{ $sales->lastItem() }} of {{ $sales->total() }} results
                        <span class="block mt-1 text-indigo-600 dark:text-indigo-400">Page {{ $sales->currentPage() }} of {{ $sales->lastPage() }}</span>
                    </div>
                    {{ $sales->links() }}
                </div>
                </div>
            </div>

            <!-- STOCK TAB -->
            <div x-show="activeTab === 'stock'" x-transition x-cloak>
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="hidden md:block overflow-x-auto">
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
                                            <x-status-badge :status="$item->quantity <= 0 ? 'low' : ($item->quantity <= ($item->low_stock_threshold ?? 5) ? 'low' : 'ok')" 
                                                class="text-[10px] uppercase tracking-widest">
                                                {{ $item->quantity <= 0 ? 'Out of Stock' : ($item->quantity <= ($item->low_stock_threshold ?? 5) ? 'Low Stock' : 'In Stock') }}
                                            </x-status-badge>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- MOBILE STOCK CARDS -->
                    <div class="grid grid-cols-1 gap-4 p-4 md:hidden">
                        @foreach($inventoryItems as $item)
                            <div class="bg-gray-50 dark:bg-gray-700/30 p-4 rounded-xl border border-gray-100 dark:border-gray-600">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <h4 class="font-bold text-gray-900 dark:text-white">{{ $item->part_name }}</h4>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $item->brand }} • {{ $item->model }}</p>
                                    </div>
                                    <span class="px-2 py-1 bg-white dark:bg-gray-800 text-[10px] font-black uppercase tracking-widest rounded-md border border-gray-100 dark:border-gray-600">
                                        {{ $item->category }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-200/50 dark:border-gray-600/50">
                                    <div class="text-center">
                                        <p class="text-[10px] text-gray-400 uppercase font-black mb-1">Stock</p>
                                        <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $item->quantity }}</p>
                                    </div>
                                    <div class="text-center">
                                        <p class="text-[10px] text-gray-400 uppercase font-black mb-1">Price</p>
                                        <p class="text-sm font-bold text-gray-900 dark:text-white">₱{{ number_format($item->price, 2) }}</p>
                                    </div>
                                    <div class="text-right">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-[10px] font-black uppercase tracking-widest
                                            {{ $item->quantity <= 0 ? 'bg-red-100 dark:bg-red-900/40 text-red-800 dark:text-red-300' : ($item->quantity <= ($item->low_stock_threshold ?? 5) ? 'bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300' : 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-300') }}">
                                            {{ $item->quantity <= 0 ? 'Out' : ($item->quantity <= ($item->low_stock_threshold ?? 5) ? 'Low' : 'OK') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="p-6 border-t border-gray-100 dark:border-gray-700">
                        <div class="mb-4 text-[11px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 md:hidden text-center">
                            Showing {{ $inventoryItems->firstItem() }} - {{ $inventoryItems->lastItem() }} of {{ $inventoryItems->total() }} stock items
                            <span class="block mt-1 text-indigo-600 dark:text-indigo-400">Page {{ $inventoryItems->currentPage() }} of {{ $inventoryItems->lastPage() }}</span>
                        </div>
                        {{ $inventoryItems->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>