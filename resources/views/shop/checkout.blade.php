<x-app-layout>
<div class="max-w-4xl mx-auto p-6">

    <h2 class="text-2xl font-bold mb-6 text-gray-800 dark:text-white">Checkout</h2>

    @if(session('error'))
        <x-alert type="error" :message="session('error')" />
    @endif

    @if(session('success'))
        <x-alert type="success" :message="session('success')" />
    @endif

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
                <a href="/cart" class="inline-flex items-center gap-2 text-sm font-bold text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors focus:outline-none focus:ring-2 focus:ring-gray-400 rounded">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Modify Cart
                </a>
            </div>

            <div class="flex-1">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 sm:p-8">
                    <form method="POST" action="/checkout" class="space-y-6"
                          x-data="{ loading: false, agreed: false, showTerms: false, scrolledToBottom: false }"
                          @submit="if (!agreed) { $event.preventDefault(); return; } loading = true">
                        @csrf

                        <!-- PAYMENT METHOD -->
                        <div class="space-y-4">
                            <label class="block text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                Payment Method
                            </label>

                            <!-- GCash QR Section -->
                            <div class="bg-white dark:bg-gray-800 border-2 border-blue-500 rounded-xl p-6 text-center shadow-sm">
                                <input type="hidden" name="payment_method" value="gcash">
                                <div class="flex items-center justify-center gap-2 mb-4">
                                    <span class="w-5 h-5 rounded-full bg-blue-500 flex items-center justify-center">
                                        <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                    </span>
                                    <span class="text-base font-bold text-gray-900 dark:text-white">GCash Payment</span>
                                </div>
                                
                                <div class="mx-auto bg-gray-50 dark:bg-gray-700 w-48 h-48 rounded-2xl flex flex-col items-center justify-center border border-gray-200 dark:border-gray-600 mb-4 p-4">
                                    <svg class="w-16 h-16 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                                    </svg>
                                    <span class="text-xs font-bold text-gray-500 dark:text-gray-400">Scan QR Code</span>
                                </div>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400 font-medium">Please scan the QR code to send your payment. Once confirmed by our staff, your order will be processed.</p>
                            </div>
                        </div>

                        <!-- TOTAL PAYMENT INFO -->
                        <div>
                            <label class="block text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-3 flex items-center gap-2">
                                Total Payment Amount (100%)
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 uppercase">Auto-calculated</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <span class="text-gray-400 font-bold">₱</span>
                                </div>
                                <input type="number" name="amount_tendered" value="{{ number_format($grandTotal, 2, '.', '') }}" step="0.01" readonly
                                    class="w-full pl-10 pr-4 py-4 bg-gray-50 dark:bg-gray-900 border-gray-200 dark:border-gray-700 rounded-xl text-lg font-bold text-gray-500 dark:text-gray-400 cursor-not-allowed focus:outline-none">
                            </div>
                            <p class="text-[10px] text-gray-400 mt-2 italic flex items-start gap-1.5">
                                <svg class="w-3 h-3 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Full payment of the grand total is required to process in-stock orders.
                            </p>
                        </div>

                        <!-- TERMS & CONDITIONS CHECKBOX -->
                        <div class="border-t border-gray-100 dark:border-gray-700 pt-5">
                            <div class="flex items-start gap-3">
                                <input type="checkbox" id="agree-terms" x-model="agreed" :disabled="!scrolledToBottom"
                                    class="mt-0.5 w-5 h-5 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500 disabled:opacity-40 disabled:cursor-not-allowed transition-opacity">
                                <label for="agree-terms" class="text-sm text-gray-600 dark:text-gray-400 leading-snug">
                                    I have read and agree to the
                                    <button type="button" @click="showTerms = true" class="text-blue-600 dark:text-blue-400 font-bold hover:underline focus:outline-none">Terms and Conditions</button>.
                                    <span x-show="!scrolledToBottom" x-cloak class="block mt-1 text-[10px] text-amber-600 dark:text-amber-400 font-bold uppercase tracking-wider">
                                        Please read the Terms and Conditions first
                                    </span>
                                </label>
                            </div>
                        </div>

                        <button id="submit-pay-btn"
                                :disabled="loading || !agreed"
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-black py-5 rounded-2xl shadow-xl shadow-blue-200 dark:shadow-none transition-all active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            <svg x-show="loading" class="animate-spin w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            <svg x-show="!loading" class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            <span id="btn-text" x-text="agreed ? 'Place Order' : 'Agree to Terms First'"></span>
                        </button>

                        <!-- T&C MODAL -->
                        <template x-teleport="body">
                            <div x-show="showTerms" x-cloak
                                 class="fixed inset-0 z-[100] flex items-center justify-center p-4"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0"
                                 x-transition:enter-end="opacity-100"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100"
                                 x-transition:leave-end="opacity-0">
                                <!-- Backdrop -->
                                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showTerms = false"></div>

                                <!-- Modal -->
                                <div class="relative bg-white dark:bg-gray-800 rounded-3xl shadow-2xl w-full max-w-2xl max-h-[85vh] flex flex-col overflow-hidden border border-gray-100 dark:border-gray-700"
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                                     x-transition:enter-end="opacity-100 scale-100 translate-y-0">

                                    <!-- Header -->
                                    <div class="flex items-center justify-between px-6 sm:px-8 py-5 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50 shrink-0">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 bg-blue-50 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                            </div>
                                            <h3 class="text-lg font-black text-gray-900 dark:text-white tracking-tight">Terms and Conditions</h3>
                                        </div>
                                        <button @click="showTerms = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500" aria-label="Close">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </button>
                                    </div>

                                    <!-- Scrollable Content -->
                                    <div class="overflow-y-auto flex-1 px-6 sm:px-8 py-6 text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-6"
                                         x-ref="termsContent"
                                         @scroll="
                                            let el = $refs.termsContent;
                                            if (el.scrollTop + el.clientHeight >= el.scrollHeight - 20) {
                                                scrolledToBottom = true;
                                            }
                                         ">

                                        <div>
                                            <h4 class="font-black text-gray-900 dark:text-white text-base mb-3">1. Order Details</h4>
                                            <ul class="space-y-2 list-disc list-inside text-gray-600 dark:text-gray-400">
                                                <li>Each order includes details on the amount paid, price, and product information.</li>
                                                <li>For pre-orders, the downpayment amount and remaining balance will also be shown.</li>
                                            </ul>
                                        </div>

                                        <div>
                                            <h4 class="font-black text-gray-900 dark:text-white text-base mb-3">2. Multiple Orders</h4>
                                            <ul class="space-y-2 list-disc list-inside text-gray-600 dark:text-gray-400">
                                                <li>Customers may place two or more orders at the same time, even if the products are different.</li>
                                                <li>Each order is processed and tracked separately, with its own status under Order History.</li>
                                            </ul>
                                        </div>

                                        <div>
                                            <h4 class="font-black text-gray-900 dark:text-white text-base mb-3">3. Payment</h4>
                                            <ul class="space-y-2 list-disc list-inside text-gray-600 dark:text-gray-400">
                                                <li>In-stock products require full payment via GCash upon checkout to confirm the order.</li>
                                                <li>Pre-orders are purely reservation-based and are paid in full upon pickup at the shop.</li>
                                            </ul>
                                        </div>

                                        <div>
                                            <h4 class="font-black text-gray-900 dark:text-white text-base mb-3">4. Compatibility Checker</h4>
                                            <ul class="space-y-2 list-disc list-inside text-gray-600 dark:text-gray-400">
                                                <li>The compatibility checker only checks products and brands currently available in Edmar Motorcycle Parts and Accessories' inventory.</li>
                                                <li>It is not a general compatibility checker for all motorcycle parts or brands worldwide, and results only apply to items listed on this website.</li>
                                            </ul>
                                        </div>

                                        <div>
                                            <h4 class="font-black text-gray-900 dark:text-white text-base mb-3">5. Refund Policy</h4>
                                            <ul class="space-y-2 list-disc list-inside text-gray-600 dark:text-gray-400">
                                                <li><strong>Change of mind:</strong> A refund may still be requested even after full payment or downpayment, as long as the product has not yet been picked up from the shop.</li>
                                                <li><strong>Damaged or defective product:</strong> A refund, replacement, or repair may be requested if the product received is damaged or defective, in accordance with the Consumer Act of the Philippines (RA 7394). Requests are subject to review to confirm the damage is a product defect and not caused by mishandling after pickup.</li>
                                            </ul>
                                        </div>

                                        <div>
                                            <h4 class="font-black text-gray-900 dark:text-white text-base mb-3">6. Cancellation</h4>
                                            <ul class="space-y-2 list-disc list-inside text-gray-600 dark:text-gray-400">
                                                <li>Orders may be cancelled before pickup at the shop.</li>
                                                <li>Cancellation after pickup is no longer allowed and instead follows the refund policy above.</li>
                                            </ul>
                                        </div>

                                        <!-- Scroll anchor marker -->
                                        <div class="text-center pt-4 pb-2">
                                            <p class="text-[10px] text-gray-400 dark:text-gray-500 uppercase tracking-widest font-bold">— End of Terms —</p>
                                        </div>
                                    </div>

                                    <!-- Footer -->
                                    <div class="px-6 sm:px-8 py-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50 shrink-0 flex items-center justify-between gap-4">
                                        <p class="text-xs font-bold" :class="scrolledToBottom ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400'">
                                            <span x-show="!scrolledToBottom">↓ Scroll down to read all terms</span>
                                            <span x-show="scrolledToBottom" x-cloak>✓ You've read the terms</span>
                                        </p>
                                        <button type="button" @click="showTerms = false"
                                                class="px-6 py-3 rounded-xl font-black text-sm transition-all focus:outline-none focus:ring-2 focus:ring-offset-2"
                                                :class="scrolledToBottom ? 'bg-blue-600 hover:bg-blue-700 text-white shadow-lg focus:ring-blue-500' : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 focus:ring-gray-400'">
                                            <span x-text="scrolledToBottom ? 'I Understand' : 'Close'"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
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