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
                <span class="text-gray-500 dark:text-gray-400 font-bold uppercase tracking-widest text-xs">Estimated Total Due on Pickup</span>
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

                        <!-- IN-STORE CASH PAYMENT NOTICE (No Online Payment Collected) -->
                        <input type="hidden" name="payment_method" value="on_pickup">
                        <div class="bg-blue-50/70 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800/60 rounded-xl p-5 text-left">
                            <div class="flex items-start gap-3.5">
                                <div class="w-9 h-9 rounded-xl bg-blue-600/10 dark:bg-blue-500/20 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">Pay Upon In-Store Pickup</h3>
                                    <p class="text-xs text-gray-600 dark:text-gray-300 mt-1 leading-relaxed">
                                        No payment is collected online. Your items will be held for <strong class="text-blue-600 dark:text-blue-400">24 hours</strong>. Please present your reservation acknowledgement and tender cash payment (<strong class="text-gray-900 dark:text-white">₱{{ number_format($grandTotal, 2) }}</strong>) at the physical store counter upon claim.
                                    </p>
                                </div>
                            </div>
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
                            <span id="btn-text" x-text="agreed ? 'Place Reservation' : 'Agree to Terms First'"></span>
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
                                            <h4 class="font-black text-gray-900 dark:text-white text-base mb-3">1. Reservation & Order Details</h4>
                                            <ul class="space-y-2 list-disc list-inside text-gray-600 dark:text-gray-400">
                                                <li>Online checkout creates an unpaid stock reservation acknowledgement, holding available items exclusively for you.</li>
                                                <li>No online payment is collected during checkout. Full payment is made in cash at the physical shop counter upon pickup.</li>
                                            </ul>
                                        </div>

                                        <div>
                                            <h4 class="font-black text-gray-900 dark:text-white text-base mb-3">2. Multiple Orders</h4>
                                            <ul class="space-y-2 list-disc list-inside text-gray-600 dark:text-gray-400">
                                                <li>Customers may reserve multiple parts concurrently. Each reservation is tracked with its own status under your customer dashboard.</li>
                                            </ul>
                                        </div>

                                        <div>
                                            <h4 class="font-black text-gray-900 dark:text-white text-base mb-3">3. 24-Hour Pickup Policies</h4>
                                            <ul class="space-y-2 list-disc list-inside text-gray-600 dark:text-gray-400">
                                                <li><strong class="text-indigo-600 dark:text-indigo-400">In-Stock Reservations:</strong> In-stock items are allocated immediately. The 24-hour pickup countdown begins upon reservation creation. Unclaimed reservations automatically expire, releasing stock back to the catalog.</li>
                                                <li><strong class="text-amber-500">Pre-Orders:</strong> Pre-ordered items are ordered from suppliers. The 24-hour pickup countdown begins <em>only after</em> the item physically arrives at our shop and a readiness notification is sent to your account.</li>
                                            </ul>
                                        </div>

                                        <div>
                                            <h4 class="font-black text-gray-900 dark:text-white text-base mb-3">4. Compatibility Checker</h4>
                                            <ul class="space-y-2 list-disc list-inside text-gray-600 dark:text-gray-400">
                                                <li>The compatibility checker evaluates parts against database records of verified motorcycles in our inventory system.</li>
                                                <li>Please ensure your make, model, and year match your vehicle before reserving.</li>
                                            </ul>
                                        </div>

                                        <div>
                                            <h4 class="font-black text-gray-900 dark:text-white text-base mb-3">5. Cancellation Policy</h4>
                                            <ul class="space-y-2 list-disc list-inside text-gray-600 dark:text-gray-400">
                                                <li>You may cancel an unpaid reservation at any time prior to pickup directly through your customer dashboard with immediate stock restoration.</li>
                                            </ul>
                                        </div>

                                        <div>
                                            <h4 class="font-black text-gray-900 dark:text-white text-base mb-3">6. Returns & Physical Refunds</h4>
                                            <ul class="space-y-2 list-disc list-inside text-gray-600 dark:text-gray-400">
                                                <li>Purchases are completed in cash at the store POS. All returns, exchanges, or defect claims must be presented in person at the physical shop counter with the official receipt.</li>
                                                <li>Defective items are inspected by shop technicians in accordance with the Consumer Act of the Philippines (RA 7394) and processed as either a resellable return or damaged write-off.</li>
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