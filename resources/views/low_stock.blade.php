<x-app-layout>
    <div class="min-h-screen bg-gray-50 py-8 px-4 sm:px-6 lg:px-8 font-sans">
        
        <div class="max-w-7xl mx-auto">
            <!-- Header Section -->
            <div class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight flex items-center gap-3">
                        <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        Low Stock Alerts
                    </h1>
                    <p class="mt-2 text-sm text-gray-500">Items that are below their designated threshold and need restocking.</p>
                </div>
            </div>

            <!-- Data Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-100 text-xs tracking-wider uppercase text-gray-500">
                                <th class="p-5 font-semibold">Part Name</th>
                                <th class="p-5 font-semibold text-center">Category</th>
                                <th class="p-5 font-semibold text-center">Remaining Quantity</th>
                                <th class="p-5 font-semibold text-center">Threshold Alert Level</th>
                                <th class="p-5 font-semibold text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 text-sm">
                            @forelse($lowStockItems as $item)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="p-5 font-semibold text-gray-900">
                                        {{ $item->part_name }}
                                        <div class="text-xs text-gray-400 font-normal mt-0.5">{{ $item->brand }} {{ $item->model }}</div>
                                    </td>
                                    <td class="p-5 text-center text-gray-600">
                                        {{ $item->category }}
                                    </td>
                                    <td class="p-5 text-center">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ $item->quantity == 0 ? 'bg-red-100 text-red-800' : 'bg-orange-100 text-orange-800' }}">
                                            {{ $item->quantity }} left
                                        </span>
                                    </td>
                                    <td class="p-5 text-center text-gray-500">
                                        Alerts at {{ $item->low_stock_threshold }}
                                    </td>
                                    <td class="p-5 text-center">
                                        <a href="/inventory/{{ $item->id }}/edit" class="inline-flex items-center gap-1 bg-indigo-50 text-indigo-600 hover:bg-indigo-100 hover:text-indigo-700 px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                            Restock Item
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="p-10 text-center text-gray-500">
                                        <div class="flex flex-col items-center justify-center">
                                            <svg class="w-12 h-12 text-green-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            <p class="font-medium text-gray-600">All stock levels look great!</p>
                                            <p class="text-sm mt-1">No items are currently below their threshold.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
