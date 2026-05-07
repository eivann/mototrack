<x-app-layout>
<div class="max-w-4xl mx-auto p-6">

    <h2 class="text-2xl font-bold mb-6 text-gray-800 dark:text-white">Checkout</h2>

    @if(session('cart') && count($cart) > 0)
    <div class="space-y-8">
        <!-- ORDER SUMMARY -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <!-- Desktop Table -->
            <div class="hidden md:block">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700 text-xs tracking-wider uppercase text-gray-500 dark:text-gray-400">
                            <th class="p-5 font-semibold">Item</th>
                            <th class="p-5 font-semibold text-center">Price</th>
                            <th class="p-5 font-semibold text-center">Qty</th>
                            <th class="p-5 font-semibold text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                        @php $grandTotal = 0; @endphp
                        @foreach($cart as $item)
                            @php
                                $total = $item['price'] * $item['quantity'];
                                $grandTotal += $total;
                            @endphp
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                                <td class="p-5 font-bold text-gray-900 dark:text-white">{{ $item['part_name'] ?? 'Unknown Item' }}</td>
                                <td class="p-5 text-center text-gray-600 dark:text-gray-400">₱{{ number_format($item['price'], 2) }}</td>
                                <td class="p-5 text-center">{{ $item['quantity'] }}</td>
                                <td class="p-5 text-right font-bold text-gray-900 dark:text-white">₱{{ number_format($total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Mobile List -->
            <div class="md:hidden divide-y divide-gray-100 dark:divide-gray-700">
                @php $grandTotal = 0; @endphp
                @foreach($cart as $item)
                    @php
                        $total = $item['price'] * $item['quantity'];
                        $grandTotal += $total;
                    @endphp
                    <div class="p-5 flex justify-between items-center">
                        <div>
                            <div class="font-bold text-gray-900 dark:text-white text-sm">{{ $item['part_name'] ?? 'Unknown Item' }}</div>
                            <div class="text-xs text-gray-400 mt-1">₱{{ number_format($item['price'], 2) }} × {{ $item['quantity'] }}</div>
                        </div>
                        <div class="font-black text-gray-900 dark:text-white text-sm">₱{{ number_format($total, 2) }}</div>
                    </div>
                @endforeach
            </div>

            <div class="bg-gray-50 dark:bg-gray-900/50 p-6 flex justify-between items-center border-t border-gray-100 dark:border-gray-700">
                <span class="text-gray-500 dark:text-gray-400 font-bold uppercase tracking-widest text-xs">Final Total</span>
                <span class="text-2xl font-black text-blue-600 dark:text-blue-400">₱{{ number_format($grandTotal, 2) }}</span>
            </div>
        </div>

        <!-- PAYMENT FORM -->
        <div class="flex flex-col lg:flex-row gap-8">
            <div class="lg:w-1/3">
                <a href="/cart" class="inline-flex items-center gap-2 text-sm font-bold text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Modify Cart
                </a>
            </div>

            <div class="flex-1">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 sm:p-8">
                    <form method="POST" action="/checkout" class="space-y-6">
                        @csrf
                        <input type="hidden" name="payment_method" value="gcash">

                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-2xl p-5 flex gap-4">
                            <div class="bg-blue-600 p-2.5 rounded-xl h-fit">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            </div>
                            <div>
                                <h4 class="font-black text-blue-900 dark:text-blue-300 uppercase tracking-tight text-sm">Secure GCash Payment</h4>
                                <p class="text-xs text-blue-700/70 dark:text-blue-400/70 mt-1 leading-relaxed">You will be securely redirected to Xendit's encrypted gateway to complete your transaction via GCash.</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-3">Down Payment Amount (₱)</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <span class="text-gray-400 font-bold">₱</span>
                                </div>
                                <input type="number" name="amount_tendered" step="0.01" min="0"
                                    class="w-full pl-10 pr-4 py-4 bg-gray-50 dark:bg-gray-900 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-blue-500 focus:border-blue-500 text-lg font-bold text-gray-900 dark:text-white"
                                    placeholder="0.00 (Optional)">
                            </div>
                            <p class="text-[10px] text-gray-400 mt-2 italic">Leave empty to pay full amount now, or enter a partial payment.</p>
                        </div>

                        <button class="w-full bg-blue-600 hover:bg-blue-700 text-white font-black py-5 rounded-2xl shadow-xl shadow-blue-200 dark:shadow-none transition-all active:scale-[0.98] flex items-center justify-center gap-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            Pay with GCash
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @else
        <div class="bg-white dark:bg-gray-800 border dark:border-gray-700 p-6 rounded-lg shadow text-center">
            <p class="text-gray-500 dark:text-gray-400">Your cart is empty.</p>
            <a href="/browse" class="text-blue-600 dark:text-blue-400 hover:underline mt-2 inline-block">Browse items</a>
        </div>
    @endif

</div>
</x-app-layout>