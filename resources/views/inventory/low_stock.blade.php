<x-app-layout>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8 px-4 sm:px-6 lg:px-8 font-sans">
        <div class="max-w-7xl mx-auto">
            <div class="mb-8">
                <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                    <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    Low Stock Alerts
                </h1>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Items that are below their designated threshold and need restocking.</p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700 text-xs tracking-wider uppercase text-gray-500 dark:text-gray-400">
                                <th class="p-5 font-semibold">Part Name</th>
                                <th class="p-5 font-semibold text-center">Category</th>
                                <th class="p-5 font-semibold text-center">Remaining Quantity</th>
                                <th class="p-5 font-semibold text-center">Threshold Alert Level</th>
                                <th class="p-5 font-semibold text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                            @forelse($lowStockItems as $item)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                                    <td class="p-5 font-semibold text-gray-900 dark:text-white">
                                        <div class="flex items-center gap-2">
                                            <span>{{ $item->part_name }}</span>
                                            @if($item->sku)
                                                <span class="text-[9px] bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 px-1.5 py-0.5 rounded font-bold uppercase">{{ $item->sku }}</span>
                                            @endif
                                        </div>
                                        <div class="text-xs text-gray-400 dark:text-gray-500 font-normal mt-0.5">{{ $item->brand }} {{ $item->model }}</div>
                                    </td>
                                    <td class="p-5 text-center text-gray-600 dark:text-gray-400">{{ $item->category }}</td>
                                    <td class="p-5 text-center">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ $item->quantity == 0 ? 'bg-red-100 dark:bg-red-900/40 text-red-800 dark:text-red-300' : 'bg-orange-100 dark:bg-orange-900/40 text-orange-800 dark:text-orange-300' }}">
                                            {{ $item->quantity }} left
                                        </span>
                                    </td>
                                    <td class="p-5 text-center text-gray-500 dark:text-gray-400">Alerts at {{ $item->low_stock_threshold }}</td>
                                    <td class="p-5 text-center">
                                        <a href="/inventory/{{ $item->id }}/edit" class="inline-flex items-center gap-1 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 hover:text-indigo-700 dark:hover:text-indigo-300 px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                            Restock Item
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="p-10 text-center text-gray-500 dark:text-gray-400">
                                        <div class="flex flex-col items-center justify-center">
                                            <svg class="w-12 h-12 text-green-500 dark:text-green-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            <p class="font-medium text-gray-600 dark:text-gray-400">All stock levels look great!</p>
                                            <p class="text-sm mt-1 text-gray-500 dark:text-gray-500">No items are currently below their threshold.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-8">
                <div class="mb-4 text-[11px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 md:hidden text-center">
                    Showing {{ $lowStockItems->firstItem() }} - {{ $lowStockItems->lastItem() }} of {{ $lowStockItems->total() }} alerts
                    <span class="block mt-1 text-indigo-600 dark:text-indigo-400">Page {{ $lowStockItems->currentPage() }} of {{ $lowStockItems->lastPage() }}</span>
                </div>
                {{ $lowStockItems->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
