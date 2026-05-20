<x-app-layout>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8 px-4 sm:px-6 lg:px-8 font-sans">
        <div class="max-w-7xl mx-auto">
            <div class="mb-8">
                <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                    <svg class="w-8 h-8 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    Process Pre-orders
                </h1>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Manage customer requests for out-of-stock or custom parts.</p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700 text-xs tracking-wider uppercase text-gray-500 dark:text-gray-400">
                                <th class="p-4 font-semibold">Order ID</th>
                                <th class="p-4 font-semibold">Customer</th>
                                <th class="p-4 font-semibold">Requested Part</th>
                                <th class="p-4 font-semibold text-center">Down Payment</th>
                                <th class="p-4 font-semibold text-center">Status</th>
                                <th class="p-4 font-semibold text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                            @forelse($preorders as $order)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                                <td class="p-4 align-top font-bold text-gray-900 dark:text-white">#{{ str_pad($order->id, 4, '0', STR_PAD_LEFT) }}</td>
                                <td class="p-4 align-top font-medium text-gray-900 dark:text-white">{{ $order->customer_name }}</td>
                                <td class="p-4 align-top">
                                    <div class="font-bold text-gray-900 dark:text-white">{{ $order->part_name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                        {{ $order->model }}
                                    </div>
                                    <div class="text-xs font-bold text-indigo-600 dark:text-indigo-400 mt-1">Qty: {{ $order->quantity }}</div>
                                </td>
                                <td class="p-4 align-top text-center font-bold text-gray-900 dark:text-white">₱{{ number_format($order->down_payment, 2) }}</td>
                                <td class="p-4 align-top text-center">
                                    <x-status-badge :status="$order->status" />
                                </td>
                                <td class="p-4 align-top text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        @if(!$order->is_paid)
                                            <form method="POST" action="/preorders/{{ $order->id }}/pay" onsubmit="return confirm('Confirm payment received?');">
                                                @csrf
                                                <button title="Collect Payment" aria-label="Collect payment for pre-order" class="p-1.5 text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-400">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-emerald-600 dark:text-emerald-400" title="Paid">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            </span>
                                        @endif

                                        @if($order->status == 'pending')
                                            <form method="POST" action="/preorders/{{ $order->id }}/complete" onsubmit="return confirm('Mark this pre-order as completed?');">
                                                @csrf
                                                <button title="Mark Completed" aria-label="Mark pre-order as completed" class="p-1.5 text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-400">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                </button>
                                            </form>
                                            <form method="POST" action="/preorders/{{ $order->id }}/cancel" onsubmit="return confirm('Cancel this pre-order?');">
                                                @csrf
                                                <button title="Cancel Order" aria-label="Cancel pre-order" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-red-400">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                </button>
                                            </form>
                                        @endif
                                        
                                        <form method="POST" action="/preorders/{{ $order->id }}" class="inline" onsubmit="return confirm('Delete this pre-order?');">
                                            @csrf @method('DELETE')
                                            <button title="Delete Record" aria-label="Delete pre-order record" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-red-400">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="p-12 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                                        <p class="font-medium text-gray-600 dark:text-gray-400">No pre-orders found.</p>
                                        <p class="text-sm mt-1 text-gray-500 dark:text-gray-500">When customers request parts, they will appear here.</p>
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
                    Showing {{ $preorders->firstItem() }} - {{ $preorders->lastItem() }} of {{ $preorders->total() }} pre-orders
                    <span class="block mt-1 text-indigo-600 dark:text-indigo-400">Page {{ $preorders->currentPage() }} of {{ $preorders->lastPage() }}</span>
                </div>
                {{ $preorders->links() }}
            </div>
        </div>
    </div>
</x-app-layout>