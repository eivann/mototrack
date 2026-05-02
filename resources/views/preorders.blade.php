<x-app-layout>
    <div class="min-h-screen bg-gray-50 py-8 px-4 sm:px-6 lg:px-8 font-sans">
        
        <div class="max-w-7xl mx-auto">
            <!-- Header Section -->
            <div class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight flex items-center gap-3">
                        <svg class="w-8 h-8 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                        Process Pre-orders
                    </h1>
                    <p class="mt-2 text-sm text-gray-500">Manage customer requests for out-of-stock or custom parts.</p>
                </div>
            </div>

            <!-- TABLE -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-100 text-xs tracking-wider uppercase text-gray-500">
                                <th class="p-4 font-semibold">Order ID</th>
                                <th class="p-4 font-semibold">Customer</th>
                                <th class="p-4 font-semibold">Requested Part</th>
                                <th class="p-4 font-semibold text-center">Down Payment</th>
                                <th class="p-4 font-semibold text-center">Status</th>
                                <th class="p-4 font-semibold text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 text-sm">
                            @forelse($preorders as $order)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="p-4 align-top font-bold text-gray-900">
                                    #{{ str_pad($order->id, 4, '0', STR_PAD_LEFT) }}
                                </td>
                                <td class="p-4 align-top font-medium text-gray-900">
                                    {{ $order->customer_name }}
                                </td>
                                <td class="p-4 align-top">
                                    <div class="font-bold text-gray-900">{{ $order->part_name }}</div>
                                    <div class="text-xs text-gray-500 mt-1 flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                        {{ $order->model }}
                                    </div>
                                    <div class="text-xs font-bold text-indigo-600 mt-1">Qty: {{ $order->quantity }}</div>
                                </td>
                                <td class="p-4 align-top text-center font-bold text-gray-900">
                                    ₱{{ number_format($order->down_payment, 2) }}
                                </td>
                                <td class="p-4 align-top text-center">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold 
                                        {{ $order->status == 'pending' ? 'bg-amber-100 text-amber-800' : '' }}
                                        {{ $order->status == 'completed' ? 'bg-emerald-100 text-emerald-800' : '' }}
                                        {{ $order->status == 'cancelled' ? 'bg-red-100 text-red-800' : '' }}">
                                        {{ ucfirst($order->status) }}
                                    </span>
                                </td>
                                <td class="p-4 align-top text-right">
                                    @if($order->status == 'pending')
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- COMPLETE -->
                                        <form method="POST" action="/preorders/{{ $order->id }}/complete" onsubmit="return confirm('Mark this pre-order as completed?');">
                                            @csrf
                                            <button title="Mark Completed" class="p-1.5 text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            </button>
                                        </form>

                                        <!-- CANCEL -->
                                        <form method="POST" action="/preorders/{{ $order->id }}/cancel" onsubmit="return confirm('Are you sure you want to cancel this pre-order?');">
                                            @csrf
                                            <button title="Cancel Order" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            </button>
                                        </form>
                                    </div>
                                    @else
                                        <span class="text-xs text-gray-400 italic">No actions available</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="p-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                                        <p class="font-medium text-gray-600">No pre-orders found.</p>
                                        <p class="text-sm mt-1">When customers request parts, they will appear here.</p>
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