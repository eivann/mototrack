<x-app-layout>
    <div class="min-h-screen bg-gray-50 py-8 px-4 sm:px-6 lg:px-8 font-sans">
        
        <div class="max-w-7xl mx-auto" x-data="{ activeTab: 'sales' }">
            <!-- Header Section -->
            <div class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">System Reports</h1>
                    <p class="mt-2 text-sm text-gray-500">Analyze sales performance and monitor inventory levels.</p>
                </div>
                
                <!-- Tab Switches -->
                <div class="flex bg-gray-100 p-1 rounded-xl">
                    <button @click="activeTab = 'sales'" :class="activeTab === 'sales' ? 'bg-white text-indigo-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'" class="px-6 py-2 text-sm font-bold rounded-lg transition-all">Sales History</button>
                    <button @click="activeTab = 'stock'" :class="activeTab === 'stock' ? 'bg-white text-indigo-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'" class="px-6 py-2 text-sm font-bold rounded-lg transition-all">Stock Levels</button>
                </div>
            </div>

            <!-- SALES TAB CONTENT -->
            <div x-show="activeTab === 'sales'" x-transition>
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <!-- Total Revenue Card -->
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

                    <!-- Total Transactions Card -->
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

                <!-- Filters Section -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                        
                        <!-- Quick Filters -->
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-semibold text-gray-700 mr-2">Quick Filters:</span>
                            <a href="{{ url()->current() }}?filter=today" class="px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ request('filter') == 'today' ? 'bg-indigo-600 text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">Today</a>
                            <a href="{{ url()->current() }}?filter=week" class="px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ request('filter') == 'week' ? 'bg-indigo-600 text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">This Week</a>
                            <a href="{{ url()->current() }}?filter=month" class="px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ request('filter') == 'month' ? 'bg-indigo-600 text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">This Month</a>
                            <a href="{{ url()->current() }}" class="px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ !request('filter') && !request('from') ? 'bg-gray-800 text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">All Time</a>
                        </div>

                        <!-- Custom Date Range -->
                        <form method="GET" class="flex flex-col sm:flex-row items-end sm:items-center gap-3">
                            <div class="flex items-center gap-2">
                                <div class="flex flex-col">
                                    <label for="from" class="text-xs font-semibold text-gray-500 mb-1">From Date</label>
                                    <input type="date" name="from" value="{{ request('from') }}" class="border border-gray-300 rounded-lg text-sm px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                                </div>
                                <span class="text-gray-400 mt-5">-</span>
                                <div class="flex flex-col">
                                    <label for="to" class="text-xs font-semibold text-gray-500 mb-1">To Date</label>
                                    <input type="date" name="to" value="{{ request('to') }}" class="border border-gray-300 rounded-lg text-sm px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                                </div>
                            </div>
                            <button type="submit" class="mt-4 sm:mt-0 bg-indigo-600 text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 shadow-sm transition-colors flex items-center gap-2 h-[38px]">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                                Filter Custom
                            </button>
                        </form>

                    </div>
                </div>

                <!-- Sales Table -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-100 text-xs tracking-wider uppercase text-gray-500">
                                    <th class="p-5 font-semibold">Item Sold</th>
                                    <th class="p-5 font-semibold text-center">Quantity</th>
                                    <th class="p-5 font-semibold text-right">Total Price</th>
                                    <th class="p-5 font-semibold text-center">Date & Time</th>
                                    <th class="p-5 font-semibold text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 text-sm">
                                @forelse($sales as $sale)
                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                        <td class="p-5 font-semibold text-gray-900">
                                            {{ $sale->item->part_name ?? 'Unknown Item' }}
                                            <div class="text-xs text-gray-400 font-normal mt-0.5">{{ $sale->item->brand ?? '' }} {{ $sale->item->model ?? '' }}</div>
                                        </td>
                                        <td class="p-5 text-center font-medium">
                                            <span class="bg-gray-100 text-gray-700 px-2.5 py-1 rounded-full text-xs">{{ $sale->quantity }}</span>
                                        </td>
                                        <td class="p-5 text-right font-bold text-gray-900">
                                            ₱{{ number_format($sale->total_price, 2) }}
                                        </td>
                                        <td class="p-5 text-center text-gray-500">
                                            {{ $sale->created_at->format('M d, Y') }}
                                            <div class="text-xs text-gray-400 mt-0.5">{{ $sale->created_at->format('h:i A') }}</div>
                                        </td>
                                        <td class="p-5 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <a href="/sales/{{ $sale->id }}/receipt" class="inline-flex items-center gap-1 bg-green-50 text-green-600 hover:bg-green-100 hover:text-green-700 px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                    Receipt
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="p-10 text-center text-gray-500">
                                            <div class="flex flex-col items-center justify-center">
                                                <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                <p class="font-medium text-gray-600">No sales found for this period</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- STOCK TAB CONTENT -->
            <div x-show="activeTab === 'stock'" x-transition>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-100 text-xs tracking-wider uppercase text-gray-500">
                                    <th class="p-5 font-semibold">Inventory Item</th>
                                    <th class="p-5 font-semibold">Category</th>
                                    <th class="p-5 font-semibold text-center">Stock Level</th>
                                    <th class="p-5 font-semibold text-right">Unit Price</th>
                                    <th class="p-5 font-semibold text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 text-sm">
                                @foreach($inventoryItems as $item)
                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                        <td class="p-5">
                                            <div class="font-bold text-gray-900">{{ $item->part_name }}</div>
                                            <div class="text-xs text-gray-400 mt-0.5">{{ $item->brand }} • {{ $item->model }}</div>
                                        </td>
                                        <td class="p-5">
                                            <span class="px-2.5 py-1 bg-gray-100 text-gray-600 rounded-lg text-xs font-semibold">{{ $item->category }}</span>
                                        </td>
                                        <td class="p-5 text-center font-bold">
                                            {{ $item->quantity }}
                                        </td>
                                        <td class="p-5 text-right font-bold text-gray-900">
                                            ₱{{ number_format($item->price, 2) }}
                                        </td>
                                        <td class="p-5 text-center">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-widest
                                                {{ $item->quantity <= 0 ? 'bg-red-100 text-red-800' : ($item->quantity <= ($item->low_stock_threshold ?? 5) ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800') }}">
                                                {{ $item->quantity <= 0 ? 'Out of Stock' : ($item->quantity <= ($item->low_stock_threshold ?? 5) ? 'Low Stock' : 'In Stock') }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>