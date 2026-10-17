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
                                <th class="p-4 font-semibold text-center">Total Price</th>
                                <th class="p-4 font-semibold text-center">Status</th>
                                <th class="p-4 font-semibold text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                            @forelse($preorders as $order)
                            <tr id="preorder-{{ $order->id }}" class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
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
                                <td class="p-4 align-top text-center font-bold text-gray-900 dark:text-white">₱{{ number_format($order->total, 2) }}</td>
                                <td class="p-4 align-top text-center">
                                    @php
                                        $stage = $order->stage();
                                        $stageClasses = [
                                            'pending_sourcing' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                            'awaiting_arrival' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300',
                                            'ready' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
                                            'completed' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
                                            'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
                                            'expired' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300',
                                        ][$stage['key']];
                                    @endphp
                                    <div class="flex flex-col items-center gap-0.5">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold {{ $stageClasses }}">{{ $stage['label'] }}</span>
                                        @if($stage['key'] === 'ready' && !$order->ready_notified_at)
                                            <span class="text-[10px] text-amber-600 font-medium" title="Part arrived, but the customer notice is pending or failed">Notice pending / failed</span>
                                        @endif
                                        @if($stage['key'] === 'ready' && $order->pickup_expires_at)
                                            <span class="text-[10px] text-gray-500 dark:text-gray-400 font-medium">Pickup by: {{ $order->pickup_expires_at->format('M d, h:i A') }}</span>
                                        @endif
                                        @if($order->is_paid && $order->status === 'pending')
                                            <span class="text-[10px] text-emerald-600 font-medium">Already paid — no extra charge</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="p-4 align-top text-right">
                                    <div class="flex flex-wrap items-center justify-end gap-2">
                                        @if($order->status === 'pending')
                                            @if(!$order->is_stocked && !$order->sourced_at)
                                                <form method="POST" action="{{ route('preorders.source', $order) }}">
                                                    @csrf
                                                    <button class="px-3 py-1.5 text-xs font-bold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-900/30 dark:text-indigo-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-400">Mark sourced</button>
                                                </form>
                                            @endif

                                            @if(!$order->is_stocked)
                                                <form method="POST" action="{{ route('preorders.stock', $order) }}" onsubmit="return confirm('Mark this part as arrived and notify the customer? The 24-hour pickup deadline starts when the notice is recorded.');">
                                                    @csrf
                                                    <button class="px-3 py-1.5 text-xs font-bold text-blue-700 bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">Mark arrived &amp; notify</button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('preorders.notify-ready', $order) }}">
                                                    @csrf
                                                    <button class="px-3 py-1.5 text-xs font-bold text-amber-700 bg-amber-50 hover:bg-amber-100 dark:bg-amber-900/30 dark:text-amber-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-400" title="Preserves the existing 24-hour deadline">{{ $order->ready_notified_at ? 'Resend arrival notice' : 'Send arrival notice' }}</button>
                                                </form>
                                                <x-cash-pickup-modal
                                                    :action="route('preorders.pickup', $order)"
                                                    :reference="'Pre-order #' . str_pad($order->id, 4, '0', STR_PAD_LEFT)"
                                                    :total="(float) $order->total"
                                                    :already-paid="(bool) $order->is_paid"
                                                    :lines="[['name' => $order->part_name, 'qty' => $order->quantity, 'subtotal' => (float) $order->total]]" />
                                            @endif

                                            <form method="POST" action="{{ route('preorders.cancel', $order) }}" onsubmit="return confirm('Cancel this pre-order?');">
                                                @csrf
                                                <button class="px-3 py-1.5 text-xs font-bold text-red-700 bg-red-50 hover:bg-red-100 dark:bg-red-900/30 dark:text-red-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-400">Cancel</button>
                                            </form>
                                        @endif

                                        <form method="POST" action="{{ route('preorders.destroy', $order) }}" class="inline" onsubmit="return confirm('Delete this pre-order record?');">
                                            @csrf @method('DELETE')
                                            <button title="Delete record" aria-label="Delete pre-order record" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-red-400">
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