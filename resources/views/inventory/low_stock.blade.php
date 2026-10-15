<x-app-layout>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8 px-4 sm:px-6 lg:px-8 font-sans">
        <div class="max-w-7xl mx-auto">
            <div class="mb-8">
                <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                    <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    Low Stock Alerts
                </h1>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Items that are below their designated threshold and need restocking.</p>
                <a href="{{ route('dashboard', ['tab' => 'inventory']) }}" class="mt-3 inline-flex items-center gap-1.5 text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Back to Dashboard
                </a>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700 text-xs tracking-wider uppercase text-gray-500 dark:text-gray-400">
                                <th class="p-5 font-semibold">Part Name</th>
                                <th class="p-5 font-semibold text-center">Category</th>
                                <th class="p-5 font-semibold text-center">Image</th>
                                <th class="p-5 font-semibold text-center">Remaining Quantity</th>
                                <th class="p-5 font-semibold text-center">Threshold Alert Level</th>
                                <th class="p-5 font-semibold text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                            @forelse($lowStockItems as $item)
                                @php
                                    $highlighted = request()->query('highlight') == $item->id;
                                    $imageUrl = $item->image_url;
                                @endphp
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors {{ $highlighted ? 'bg-amber-50/70 dark:bg-amber-900/10 ring-1 ring-inset ring-amber-300 dark:ring-amber-700' : '' }}">
                                    <td class="p-5 font-semibold text-gray-900 dark:text-white">
                                        <div class="flex items-center gap-2">
                                            <span>{{ $item->part_name }}</span>
                                            @if($highlighted)
                                                <span class="text-[9px] bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 px-1.5 py-0.5 rounded font-bold uppercase">Alert</span>
                                            @endif
                                            @if($item->sku)
                                                <span class="text-[9px] bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 px-1.5 py-0.5 rounded font-bold uppercase">{{ $item->sku }}</span>
                                            @endif
                                        </div>
                                        <div class="text-xs text-gray-400 dark:text-gray-500 font-normal mt-0.5">{{ $item->brand }} {{ $item->model }}</div>
                                    </td>
                                    <td class="p-5 text-center text-gray-600 dark:text-gray-400">{{ $item->category }}</td>
                                    <td class="p-5">
                                        <div class="flex justify-center">
                                            @if($imageUrl)
                                                <img src="{{ $imageUrl }}" alt="{{ $item->part_name }}" loading="lazy" onerror="this.onerror=null;this.src='data:image/svg+xml;base64,{{ base64_encode('<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"400\" height=\"400\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"#9CA3AF\" stroke-width=\"1\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><path d=\"M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z\"></path><polyline points=\"3.27 6.96 12 12.01 20.73 6.96\"></polyline><line x1=\"12\" y1=\"22.08\" x2=\"12\" y2=\"12\"></line></svg>') }}'" class="w-14 h-14 rounded-xl object-cover border border-gray-100 dark:border-gray-700 bg-gray-100 dark:bg-gray-800">
                                            @else
                                                <div class="w-14 h-14 rounded-xl border border-gray-100 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-400" aria-hidden="true">
                                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="p-5 text-center">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ $item->quantity == 0 ? 'bg-red-100 dark:bg-red-900/40 text-red-800 dark:text-red-300' : 'bg-orange-100 dark:bg-orange-900/40 text-orange-800 dark:text-orange-300' }}">
                                            {{ $item->quantity }} left
                                        </span>
                                    </td>
                                    <td class="p-5 text-center text-gray-500 dark:text-gray-400">Alerts at {{ $item->low_stock_threshold }}</td>
                                    <td class="p-5 text-center">
                                        <a href="/inventory/{{ $item->id }}/edit" class="inline-flex items-center gap-1 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 hover:text-indigo-700 dark:hover:text-indigo-300 px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                            Restock Item
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-10 text-center text-gray-500 dark:text-gray-400">
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
