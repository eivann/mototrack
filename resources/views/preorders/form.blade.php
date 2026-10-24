<x-app-layout>
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-6 px-4">

    {{-- Two columns on desktop (item summary beside the form) so the whole
         pre-order fits one screen without scrolling; single column on mobile. --}}
    <div class="max-w-5xl mx-auto">

        <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white flex items-center gap-2">
            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
            Pre-order Item
        </h2>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

        <!-- ITEM INFO & PRICE BREAKDOWN -->
        <div class="overflow-hidden rounded-2xl border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm lg:sticky lg:top-24">
            <div class="p-5 sm:p-6 bg-gray-50/50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row gap-5 items-start">
                @if($item->image)
                    <div class="w-full sm:w-32 h-32 rounded-xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 flex items-center justify-center overflow-hidden shrink-0">
                        <img src="{{ Storage::url($item->image) }}" alt="{{ $item->part_name }}" class="max-w-full max-h-full object-contain p-2">
                    </div>
                @endif
                
                <div class="flex-1 min-w-0 w-full">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="px-2.5 py-1 rounded-md bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 text-[10px] font-black uppercase tracking-widest">{{ $item->category->name ?? 'Uncategorized' }}</span>
                        <span class="px-2.5 py-1 rounded-md bg-gray-100 dark:bg-gray-700/50 text-gray-600 dark:text-gray-400 text-[10px] font-black uppercase tracking-widest">{{ $item->brand->name ?? 'No Brand' }}</span>
                    </div>
                    <h3 class="text-xl font-black text-gray-900 dark:text-white truncate mb-1">{{ $item->part_name }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2 mb-4">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Compatible Model: <span class="font-bold text-gray-700 dark:text-gray-300">{{ $item->model ?? 'Universal' }}</span>
                    </p>
                    <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400">₱{{ number_format($item->price, 2) }}</div>
                </div>
            </div>

            <!-- Dynamic Price Breakdown via Alpine -->
            <div class="p-5 sm:p-6 bg-white dark:bg-gray-800" x-data="{
                price: {{ $item->price }},
                qty: 1,
                get total() { return this.price * this.qty; }
            }" @qty-changed.window="qty = parseInt($event.detail.value) || 1">
                
                <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-3">Price Breakdown</h4>
                
                <div class="space-y-3">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Unit Price</span>
                        <span class="font-bold text-gray-900 dark:text-white">₱{{ number_format($item->price, 2) }}</span>
                    </div>
                    
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-600 dark:text-gray-400" x-text="`Quantity (x${qty})`"></span>
                        <span class="font-bold text-gray-900 dark:text-white" x-text="`₱${total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`"></span>
                    </div>

                    <hr class="border-gray-100 dark:border-gray-700 my-2">

                    <div class="flex justify-between items-center">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-bold text-gray-900 dark:text-white">Total Amount</span>
                            <span class="px-2 py-0.5 rounded text-[9px] font-bold bg-gray-100 text-gray-600 dark:bg-gray-700 text-gray-400 uppercase tracking-wider">Pay on Pickup</span>
                        </div>
                        <span class="text-lg font-black text-gray-900 dark:text-white" x-text="`₱${total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- FORM COLUMN -->
        <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-lg rounded-2xl p-5 sm:p-6">

        @if ($errors->any())
            <div class="mb-4 text-red-500 dark:text-red-400 text-sm">
                <ul>@foreach ($errors->all() as $error)<li>• {{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <!-- FORM -->
        <form method="POST" action="/preorder/store"
              x-data="{ loading: false, agreed: false, showTerms: false, scrolledToBottom: false }"
              @submit="if (!agreed) { $event.preventDefault(); return; } loading = true">
            @csrf
            <input type="hidden" name="inventory_item_id" value="{{ $item->id }}">

            <!-- Quantity -->
            <div class="mb-4">
                <label for="quantity" class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">Quantity</label>
                <input type="number" id="quantity" name="quantity" min="1" value="1" required
                    @input="$dispatch('qty-changed', { value: $event.target.value })"
                    class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-400 outline-none transition-all">
            </div>


            <!-- Google reCAPTCHA -->
            <div class="mb-3 flex flex-col items-center">
                <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
            </div>
            {{-- SYS4-05 C1: reCAPTCHA is loaded only on pages that render the widget; placed after the widget so it exists when api.js auto-renders. --}}
            <script src="https://www.google.com/recaptcha/api.js" async defer></script>

            <!-- Honeypot -->
            <div class="hidden"><input type="text" name="my_extra_field" value=""></div>

            @if(session('success'))
                <div class="mb-4 text-green-600 dark:text-green-400">{{ session('success') }}</div>
            @endif

            <!-- TERMS & CONDITIONS CHECKBOX -->
            <div class="mb-4 border-t border-gray-100 dark:border-gray-700 pt-4">
                <div class="flex items-start gap-3">
                    <input type="checkbox" id="agree-terms-preorder" x-model="agreed" :disabled="!scrolledToBottom"
                        class="mt-0.5 w-5 h-5 rounded border-gray-300 dark:border-gray-600 text-amber-600 focus:ring-amber-500 disabled:opacity-40 disabled:cursor-not-allowed transition-opacity">
                    <label for="agree-terms-preorder" class="text-sm text-gray-600 dark:text-gray-400 leading-snug">
                        I have read and agree to the
                        <button type="button" @click="showTerms = true" class="text-amber-600 dark:text-amber-400 font-bold hover:underline focus:outline-none">Terms and Conditions</button>.
                        <span x-show="!scrolledToBottom" x-cloak class="block mt-1 text-[10px] text-amber-600 dark:text-amber-400 font-bold uppercase tracking-wider">
                            Please read the Terms and Conditions first
                        </span>
                    </label>
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex justify-between items-center mt-4">
                <a href="/preorder-parts" class="text-gray-600 dark:text-gray-400 hover:underline">← Back</a>
                <button type="submit" :disabled="loading || !agreed" class="bg-amber-500 hover:bg-amber-600 text-white font-semibold px-6 py-2 rounded-lg transition-colors shadow-sm flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg x-show="loading" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    <span x-text="agreed ? 'Submit Pre-order' : 'Agree to Terms First'"></span>
                </button>
            </div>

            <!-- T&C MODAL (Pre-order) -->
            <template x-teleport="body">
                <div x-show="showTerms" x-cloak
                     class="fixed inset-0 z-[100] flex items-center justify-center p-4"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0">
                    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showTerms = false"></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-3xl shadow-2xl w-full max-w-2xl max-h-[85vh] flex flex-col overflow-hidden border border-gray-100 dark:border-gray-700"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0">
                        <div class="flex items-center justify-between px-6 sm:px-8 py-5 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50 shrink-0">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-amber-50 dark:bg-amber-900/30 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                </div>
                                <h3 class="text-lg font-black text-gray-900 dark:text-white tracking-tight">Terms and Conditions</h3>
                            </div>
                            <button @click="showTerms = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors focus:outline-none" aria-label="Close">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                        <div class="overflow-y-auto flex-1 px-6 sm:px-8 py-6 text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-6"
                             x-ref="termsContent"
                             @scroll="
                                let el = $refs.termsContent;
                                if (el.scrollTop + el.clientHeight >= el.scrollHeight - 20) { scrolledToBottom = true; }
                             ">
                            <div>
                                <h4 class="font-black text-gray-900 dark:text-white text-base mb-3">1. Pre-Order & Reservation Details</h4>
                                <ul class="space-y-2 list-disc list-inside">
                                    <li>Pre-orders submit a reservation for out-of-stock items. No online payment is collected.</li>
                                    <li>Full payment is made in cash at the store counter upon physical pickup.</li>
                                </ul>
                            </div>
                            <div>
                                <h4 class="font-black text-gray-900 dark:text-white text-base mb-3">2. Multiple Orders</h4>
                                <ul class="space-y-2 list-disc list-inside">
                                    <li>Customers may submit multiple pre-orders or reservations. Each is tracked separately on your customer dashboard.</li>
                                </ul>
                            </div>
                            <div>
                                <h4 class="font-black text-gray-900 dark:text-white text-base mb-3">3. 24-Hour Pickup Rule for Pre-Orders</h4>
                                <ul class="space-y-2 list-disc list-inside">
                                    <li>Pre-ordered parts are sourced from our suppliers.</li>
                                    <li>The 24-hour pickup countdown begins <em>strictly after</em> the item physically arrives at our shop and a readiness notification is sent to your account.</li>
                                    <li>Unclaimed items after the 24-hour window are released to general inventory.</li>
                                </ul>
                            </div>
                            <div>
                                <h4 class="font-black text-gray-900 dark:text-white text-base mb-3">4. Compatibility Checker</h4>
                                <ul class="space-y-2 list-disc list-inside">
                                    <li>The compatibility checker verifies fitment against actual motorcycle specifications recorded in our database.</li>
                                </ul>
                            </div>
                            <div>
                                <h4 class="font-black text-gray-900 dark:text-white text-base mb-3">5. Cancellation</h4>
                                <ul class="space-y-2 list-disc list-inside">
                                    <li>You may cancel a pre-order from your dashboard at any time prior to collection at the shop.</li>
                                </ul>
                            </div>
                            <div>
                                <h4 class="font-black text-gray-900 dark:text-white text-base mb-3">6. Returns & POS Refunds</h4>
                                <ul class="space-y-2 list-disc list-inside">
                                    <li>All completed purchases and refunds are transacted in cash at the physical shop counter. Items must be inspected in person with the official POS receipt.</li>
                                </ul>
                            </div>
                            <div class="text-center pt-4 pb-2">
                                <p class="text-[10px] text-gray-400 dark:text-gray-500 uppercase tracking-widest font-bold">— End of Terms —</p>
                            </div>
                        </div>
                        <div class="px-6 sm:px-8 py-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50 shrink-0 flex items-center justify-between gap-4">
                            <p class="text-xs font-bold" :class="scrolledToBottom ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400'">
                                <span x-show="!scrolledToBottom">↓ Scroll down to read all terms</span>
                                <span x-show="scrolledToBottom" x-cloak>✓ You've read the terms</span>
                            </p>
                            <button type="button" @click="showTerms = false"
                                    class="px-6 py-3 rounded-xl font-black text-sm transition-all focus:outline-none focus:ring-2 focus:ring-offset-2"
                                    :class="scrolledToBottom ? 'bg-amber-500 hover:bg-amber-600 text-white shadow-lg focus:ring-amber-500' : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 focus:ring-gray-400'">
                                <span x-text="scrolledToBottom ? 'I Understand' : 'Close'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </form>

        </div>
        {{-- /FORM COLUMN --}}

      </div>
      {{-- /grid --}}

    </div>

</div>
</x-app-layout>