<x-app-layout>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-6 px-4 sm:px-6 lg:px-8 font-sans transition-colors duration-300" x-data="posSystem()">

        <div class="max-w-7xl mx-auto">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
                <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">
                    Point of Sale
                </h1>
                <button type="button" @click="newSale()" x-show="cart.length > 0 || tendered !== ''" x-cloak
                        class="inline-flex items-center gap-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 font-bold py-2 px-4 rounded-xl shadow-sm text-sm transition-all focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    New Sale
                </button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8 items-start">

                <!-- Left Side: Product Search -->
                <div class="lg:col-span-2 flex flex-col gap-6">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 sm:p-6">
                        <label for="pos-search" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Search & Add Item</label>
                        <div class="relative">
                            <input
                                id="pos-search"
                                type="text"
                                x-model="searchQuery"
                                autocomplete="off"
                                placeholder="Search products manually by SKU, part name, brand, or model..."
                                class="w-full pl-12 pr-4 py-4 rounded-xl border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:border-indigo-500 dark:focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 text-lg shadow-sm transition-shadow"
                            >
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="h-6 w-6 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                        </div>

                        <!-- Category Filter Chips (F15) -->
                        <div class="mt-3 flex items-center gap-1.5 overflow-x-auto pb-1 custom-scrollbar" role="tablist" aria-label="Filter by category">
                            <template x-for="cat in categories" :key="cat">
                                <button type="button"
                                        @click="selectedCategory = cat"
                                        :class="selectedCategory === cat
                                            ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-200 dark:shadow-none'
                                            : 'bg-gray-100 dark:bg-gray-700/60 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
                                        class="px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all focus:outline-none focus:ring-2 focus:ring-indigo-500 active:scale-95 shrink-0"
                                        :aria-selected="selectedCategory === cat"
                                        role="tab">
                                    <span x-text="cat"></span>
                                </button>
                            </template>
                        </div>

                        <p class="mt-3 text-xs font-semibold text-gray-500 dark:text-gray-400" aria-live="polite">
                            <span x-show="filteredItems.length > visibleItems.length">
                                Showing <span x-text="visibleItems.length"></span> of <span x-text="filteredItems.length"></span> matching parts. Keep typing to narrow the list.
                            </span>
                            <span x-show="filteredItems.length <= visibleItems.length && filteredItems.length > 0">
                                <span x-text="filteredItems.length"></span> matching <span x-text="filteredItems.length === 1 ? 'part' : 'parts'"></span>
                            </span>
                        </p>

                        <!-- Search Results (only the first matches are rendered: the full catalogue is searched in memory) -->
                        <div class="pos-results mt-3 overflow-y-auto pr-2 custom-scrollbar">
                            <template x-for="item in visibleItems" :key="item.id">
                                <button type="button" @click="addToCart(item)" :disabled="item.quantity <= 0"
                                     class="group w-full text-left flex items-center justify-between gap-4 p-4 mb-3 rounded-xl border border-gray-100 dark:border-gray-700 hover:border-indigo-300 dark:hover:border-indigo-500 hover:bg-indigo-50/50 dark:hover:bg-indigo-900/20 cursor-pointer transition-all active:scale-[0.99] focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <div class="min-w-0">
                                        <h3 class="font-bold text-gray-900 dark:text-white group-hover:text-indigo-900 dark:group-hover:text-indigo-400 text-lg" x-text="item.part_name"></h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                            <span class="font-bold text-indigo-500 dark:text-indigo-400" x-show="item.sku" x-text="item.sku + ' • '"></span>
                                            <span x-text="item.brand"></span> • <span x-text="item.model"></span>
                                        </p>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <div class="font-extrabold text-indigo-600 dark:text-indigo-400 text-xl">₱<span x-text="money(toCents(item.price))"></span></div>
                                        <div class="text-xs font-semibold mt-1" :class="item.quantity <= 0 ? 'text-red-500' : (item.quantity <= 5 ? 'text-amber-500' : 'text-emerald-500')">
                                            <span x-text="item.quantity"></span> in stock
                                        </div>
                                    </div>
                                </button>
                            </template>
                            <div x-show="filteredItems.length === 0" class="text-center py-12 text-gray-400 dark:text-gray-500">
                                No parts found matching your search.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Side: The Cart (stays in view on desktop so the totals are always visible) -->
                <div class="pos-cart flex flex-col">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 sm:p-6 flex flex-col min-h-0 flex-1">
                        <div class="flex items-center justify-between mb-4 pb-4 border-b border-gray-100 dark:border-gray-700">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Current Transaction</h2>
                            <span class="bg-indigo-100 dark:bg-indigo-900/50 text-indigo-800 dark:text-indigo-300 text-xs font-bold px-3 py-1 rounded-full">
                                <span x-text="cart.length"></span> <span x-text="cart.length === 1 ? 'item' : 'items'"></span>
                            </span>
                        </div>

                        <!-- Cart Items -->
                        <div class="pos-cart-lines flex-1 overflow-y-auto mb-4 min-h-[80px] custom-scrollbar">
                            <template x-for="(cartItem, index) in cart" :key="cartItem.id">
                                <div class="flex justify-between items-start mb-3 bg-gray-50 dark:bg-gray-700/50 p-3 rounded-lg">
                                    <div class="flex-1 min-w-0 pr-3">
                                        <div class="font-bold text-gray-900 dark:text-white text-sm" x-text="cartItem.part_name"></div>
                                        <div class="text-[10px] text-gray-400 uppercase font-bold" x-show="cartItem.sku" x-text="cartItem.sku"></div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">₱<span x-text="money(toCents(cartItem.price))"></span> each</div>
                                    </div>
                                    <div class="flex flex-col items-end gap-2">
                                        <div class="font-bold text-gray-900 dark:text-white">₱<span x-text="money(totals.lineTotalsCents[index])"></span></div>
                                        <div class="flex items-center bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden shadow-sm">
                                            <button type="button" @click="updateQuantity(index, -1)" :aria-label="'Decrease quantity of ' + cartItem.part_name" class="min-w-[44px] min-h-[44px] flex items-center justify-center text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-400"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg></button>
                                            <input type="number" min="1" :max="cartItem.maxStock" step="1" inputmode="numeric"
                                                   x-model.number="cartItem.quantity"
                                                   @change="normalizeQuantity(cartItem)"
                                                   :aria-label="'Quantity of ' + cartItem.part_name"
                                                   class="w-12 text-center text-sm font-bold bg-gray-50 dark:bg-gray-900 py-1 text-gray-900 dark:text-white border-none focus:ring-0 p-0 m-0 custom-number-input">
                                            <button type="button" @click="updateQuantity(index, 1)" :aria-label="'Increase quantity of ' + cartItem.part_name" class="min-w-[44px] min-h-[44px] flex items-center justify-center text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-400"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg></button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <div x-show="cart.length === 0" class="flex flex-col items-center justify-center h-full py-6 text-gray-400 dark:text-gray-600">
                                <svg class="w-14 h-14 mb-3 text-gray-200 dark:text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                Transaction is empty — click an item to add it.
                            </div>
                        </div>

                        <!-- Totals & Checkout: every figure comes from MotoTrackPos.compute(), in centavos -->
                        <div class="border-t border-gray-100 dark:border-gray-700 pt-4 mt-auto" aria-live="polite">
                            <dl class="space-y-1.5 mb-4">
                                <div class="flex justify-between items-center text-sm">
                                    <dt class="text-gray-500 dark:text-gray-400 font-medium">Items</dt>
                                    <dd class="text-gray-900 dark:text-white font-bold"><span x-text="totals.unitCount"></span> <span x-text="totals.unitCount === 1 ? 'pc' : 'pcs'"></span></dd>
                                </div>
                                <div class="flex justify-between items-center">
                                    <dt class="text-gray-500 dark:text-gray-400 font-medium">Subtotal</dt>
                                    <dd class="text-gray-900 dark:text-white font-bold">₱<span x-text="money(totals.subtotalCents)"></span></dd>
                                </div>
                                <div class="flex justify-between items-center text-xs text-gray-400 dark:text-gray-500">
                                    <dt>VATable sales</dt>
                                    <dd>₱<span x-text="money(totals.netCents)"></span></dd>
                                </div>
                                <div class="flex justify-between items-center text-xs text-gray-400 dark:text-gray-500">
                                    <dt>VAT (<span x-text="totals.vatRatePercent"></span>%, included)</dt>
                                    <dd>₱<span x-text="money(totals.vatCents)"></span></dd>
                                </div>
                                <div class="flex justify-between items-center pt-1.5 border-t border-dashed border-gray-200 dark:border-gray-700">
                                    <dt class="text-gray-900 dark:text-white font-extrabold text-xl">Total <span class="text-[10px] font-bold text-gray-400 align-middle">VAT incl.</span></dt>
                                    <dd class="text-indigo-600 dark:text-indigo-400 font-extrabold text-2xl" data-pos-total>₱<span x-text="money(totals.totalCents)"></span></dd>
                                </div>
                            </dl>

                            <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-xl mb-4 border border-gray-100 dark:border-gray-700">
                                <div class="flex items-center justify-between mb-2">
                                    <label for="amount-tendered" class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amount Tendered (Cash)</label>
                                    <button type="button" @click="tenderExact()" :disabled="totals.totalCents <= 0"
                                            class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 hover:underline disabled:opacity-40 disabled:no-underline focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded">
                                        Exact amount
                                    </button>
                                </div>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 dark:text-gray-400 font-bold sm:text-lg">₱</span>
                                    </div>
                                    <input id="amount-tendered" type="number" step="0.01" min="0" max="999999" inputmode="decimal"
                                           x-model="tendered"
                                           :aria-invalid="totals.tenderEntered && !totals.tenderValid"
                                           aria-describedby="pos-change-status"
                                           class="w-full pl-8 pr-4 py-3 bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-indigo-500 dark:focus:border-indigo-400 text-gray-900 dark:text-white font-bold text-lg" placeholder="0.00">
                                </div>
                                <div id="pos-change-status" class="flex justify-between items-center mt-3 pt-3 border-t border-gray-200 dark:border-gray-600 min-h-[2.25rem]">
                                    <template x-if="totals.changeCents !== null && totals.changeCents >= 0">
                                        <div class="flex justify-between items-center w-full">
                                            <span class="text-gray-600 dark:text-gray-400 font-semibold">Change</span>
                                            <span class="font-extrabold text-lg text-emerald-600 dark:text-emerald-400" data-pos-change>₱<span x-text="money(totals.changeCents)"></span></span>
                                        </div>
                                    </template>
                                    <template x-if="totals.shortCents > 0">
                                        <div class="flex justify-between items-center w-full">
                                            <span class="text-red-600 dark:text-red-400 font-semibold">Short by</span>
                                            <span class="font-extrabold text-lg text-red-600 dark:text-red-400">₱<span x-text="money(totals.shortCents)"></span></span>
                                        </div>
                                    </template>
                                    <template x-if="totals.changeCents === null">
                                        <span class="text-xs font-semibold" :class="totals.tenderEntered ? 'text-red-600 dark:text-red-400' : 'text-gray-400 dark:text-gray-500'" x-text="tenderHint"></span>
                                    </template>
                                </div>
                            </div>

                            @if ($errors->any())
                                <div role="alert" class="mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-sm text-red-700 dark:text-red-300">
                                    @foreach ($errors->all() as $message)
                                        <p>{{ $message }}</p>
                                    @endforeach
                                </div>
                            @endif

                            <form method="POST" action="/sales/store" id="checkoutForm" @submit.prevent="submitCheckout()">
                                @csrf
                                {{-- Ids, quantities and the unit prices shown here. The server still prices the sale itself
                                     (under the stock lock); the shown prices only let it refuse a sale whose prices changed. --}}
                                <input type="hidden" name="cart_data" :value="JSON.stringify(cart.map(i => ({ id: i.id, quantity: i.quantity, price: i.price })))">
                                <input type="hidden" name="amount_tendered" :value="totals.tenderValid ? MotoTrackPos.centsToDecimalString(totals.tenderedCents) : ''">
                                <input type="hidden" name="expected_total" :value="MotoTrackPos.centsToDecimalString(totals.totalCents)">
                                <button type="submit"
                                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-4 px-6 rounded-xl shadow-lg shadow-indigo-200 dark:shadow-none transition-all active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                        :disabled="!totals.canCheckout || checkoutLoading">
                                    <svg x-show="checkoutLoading" x-cloak class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    <svg x-show="!checkoutLoading" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <span x-text="totals.canCheckout ? 'Complete Checkout · ₱' + money(totals.totalCents) : 'Complete Checkout'">Complete Checkout</span>
                                </button>
                            </form>
                        </div>

                    </div>
                </div>

            </div>
        </div>

        <!-- Accessible Toast Container (F16) -->
        <div x-cloak
             x-show="toast.show"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 translate-y-3 sm:translate-y-0 sm:translate-x-3"
             x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="opacity-100 translate-y-0 sm:translate-x-0"
             x-transition:leave-end="opacity-0 translate-y-2 sm:translate-y-0 sm:translate-x-2"
             class="fixed bottom-6 right-6 z-50 max-w-md w-[calc(100vw-3rem)] sm:w-auto shadow-2xl rounded-2xl border px-4 py-3.5 flex items-center justify-between gap-3 text-sm font-bold tracking-tight"
             :class="{
                 'bg-red-50 dark:bg-red-900/90 border-red-200 dark:border-red-700 text-red-800 dark:text-red-100': toast.type === 'error',
                 'bg-amber-50 dark:bg-amber-900/90 border-amber-200 dark:border-amber-700 text-amber-800 dark:text-amber-100': toast.type === 'warning',
                 'bg-blue-50 dark:bg-blue-900/90 border-blue-200 dark:border-blue-700 text-blue-800 dark:text-blue-100': toast.type === 'info',
                 'bg-emerald-50 dark:bg-emerald-900/90 border-emerald-200 dark:border-emerald-700 text-emerald-800 dark:text-emerald-100': toast.type === 'success'
             }"
             role="alert"
             aria-live="assertive">
            <div class="flex items-center gap-2.5 min-w-0">
                <template x-if="toast.type === 'error' || toast.type === 'warning'">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </template>
                <template x-if="toast.type === 'info' || toast.type === 'success'">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </template>
                <span class="truncate" x-text="toast.message"></span>
            </div>
            <button type="button" @click="toast.show = false" aria-label="Close notification" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-1 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-400 shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>

    <script src="{{ asset('js/pos-totals.js') }}"></script>
    <script>
        function posSystem() {
            const DRAFT_KEY = 'mototrack_pos_draft';
            const RESULT_LIMIT = 50;
            const inventory = @json($items).map(item => ({
                ...item,
                haystack: [item.sku, item.part_name, item.brand, item.model, item.category]
                    .filter(Boolean).join(' ').toLowerCase(),
            }));

            const rawCategories = [...new Set(inventory.map(i => i.category).filter(Boolean))].sort();
            const categories = ['All', ...rawCategories];

            return {
                searchQuery: '',
                selectedCategory: 'All',
                categories,
                inventory,
                cart: [],
                tendered: '',
                checkoutLoading: false,
                toast: {
                    show: false,
                    message: '',
                    type: 'error',
                    timeout: null,
                },

                showToast(message, type = 'error') {
                    if (this.toast.timeout) clearTimeout(this.toast.timeout);
                    this.toast.message = message;
                    this.toast.type = type;
                    this.toast.show = true;
                    this.toast.timeout = setTimeout(() => {
                        this.toast.show = false;
                    }, 4000);
                },

                init() {
                    this.restoreDraft(@json($restoreDraft));
                    this.$watch('cart', () => this.saveDraft(), { deep: true });
                },

                get filteredItems() {
                    let items = this.inventory;
                    if (this.selectedCategory && this.selectedCategory !== 'All') {
                        items = items.filter(item => item.category === this.selectedCategory);
                    }
                    const q = this.searchQuery.trim().toLowerCase();
                    if (q === '') return items;
                    const terms = q.split(/\s+/);
                    return items.filter(item => terms.every(t => item.haystack.includes(t)));
                },

                // Rendering every catalogue row made the POS slow to open; the search still covers all rows.
                get visibleItems() {
                    return this.filteredItems.slice(0, RESULT_LIMIT);
                },

                // The one source for every amount on this screen and for what is submitted.
                get totals() {
                    return MotoTrackPos.compute(this.cart, this.tendered);
                },

                get tenderHint() {
                    switch (this.totals.problem) {
                        case 'tender_invalid': return 'Enter a valid amount with at most 2 decimals.';
                        case 'tender_too_large': return 'Amount tendered cannot exceed ₱999,999.00.';
                        default: return this.cart.length ? 'Enter the cash received to see the change.' : 'Add parts, then enter the cash received.';
                    }
                },

                toCents(value) {
                    return MotoTrackPos.toCents(value) ?? 0;
                },

                money(cents) {
                    return MotoTrackPos.formatCents(cents || 0);
                },

                // Per-line ceiling: stock on hand, capped at the server's 9,999 units per item.
                limitMessage(line) {
                    return line.stock > MotoTrackPos.MAX_LINE_QUANTITY
                        ? 'A single sale can include at most 9,999 units of one item.'
                        : 'Cannot exceed available stock.';
                },

                cartLine(item, quantity) {
                    return {
                        id: item.id,
                        sku: item.sku,
                        part_name: item.part_name,
                        price: item.price,
                        stock: item.quantity,
                        maxStock: MotoTrackPos.maxQuantity(item.quantity),
                        quantity: quantity
                    };
                },

                addToCart(item) {
                    if (item.quantity <= 0) return;

                    const existing = this.cart.find(i => i.id === item.id);
                    if (existing) {
                        if (existing.quantity < existing.maxStock) {
                            existing.quantity++;
                        } else {
                            this.showToast(existing.stock > MotoTrackPos.MAX_LINE_QUANTITY ? this.limitMessage(existing) : "Cannot add more. Not enough stock available.", 'warning');
                        }
                    } else {
                        this.cart.push(this.cartLine(item, 1));
                    }
                },

                updateQuantity(index, change) {
                    const item = this.cart[index];
                    const newQty = (MotoTrackPos.toQuantity(item.quantity) || 0) + change;

                    if (newQty <= 0) {
                        this.cart.splice(index, 1);
                    } else if (newQty <= item.maxStock) {
                        item.quantity = newQty;
                    } else {
                        this.showToast(this.limitMessage(item), 'warning');
                    }
                },

                normalizeQuantity(item) {
                    const qty = MotoTrackPos.toQuantity(item.quantity);
                    if (qty < 1) {
                        item.quantity = 1;
                    } else if (qty > item.maxStock) {
                        item.quantity = item.maxStock;
                        this.showToast(this.limitMessage(item), 'warning');
                    } else {
                        item.quantity = qty;
                    }
                },

                tenderExact() {
                    this.tendered = MotoTrackPos.centsToDecimalString(this.totals.totalCents);
                },

                newSale() {
                    if (this.cart.length && !confirm('Start a new transaction? Current items will be cleared.')) return;
                    this.cart = [];
                    this.tendered = '';
                    this.searchQuery = '';
                    this.selectedCategory = 'All';
                    this.clearDraft();
                    this.$nextTick(() => document.getElementById('pos-search')?.focus());
                },

                // Cart draft (this browser tab only): lets a refused checkout come back with the cart intact,
                // re-priced from the current catalogue so the screen again matches what will be charged.
                saveDraft() {
                    try {
                        sessionStorage.setItem(DRAFT_KEY, JSON.stringify(this.cart.map(i => ({ id: i.id, quantity: i.quantity }))));
                    } catch (e) { /* storage unavailable: the POS works without a draft */ }
                },
                clearDraft() {
                    try { sessionStorage.removeItem(DRAFT_KEY); } catch (e) {}
                },
                restoreDraft(shouldRestore) {
                    let saved = [];
                    try { saved = JSON.parse(sessionStorage.getItem(DRAFT_KEY) || '[]'); } catch (e) { saved = []; }
                    if (!shouldRestore || !Array.isArray(saved)) {
                        this.clearDraft();
                        return;
                    }
                    saved.forEach(line => {
                        const item = this.inventory.find(i => i.id === line.id);
                        if (!item || this.cart.some(i => i.id === item.id)) return; // unknown or duplicate entry
                        const qty = Math.min(MotoTrackPos.toQuantity(line.quantity), MotoTrackPos.maxQuantity(item.quantity));
                        if (qty > 0) {
                            this.cart.push(this.cartLine(item, qty));
                        }
                    });
                },

                submitCheckout() {
                    const t = this.totals;
                    if (this.checkoutLoading) return;
                    if (t.problem === 'empty') { this.showToast("Transaction is empty. Add items to checkout.", 'warning'); return; }
                    if (t.problem === 'quantity') { this.showToast("Check the quantities in the transaction.", 'warning'); return; }
                    if (t.problem === 'insufficient') { this.showToast("Amount tendered is less than the total.", 'error'); return; }
                    if (t.problem === 'tender_too_large') { this.showToast("Amount tendered cannot exceed ₱999,999.00.", 'error'); return; }
                    if (!t.canCheckout) { this.showToast("Enter a valid cash amount.", 'error'); return; }
                    this.checkoutLoading = true;
                    this.saveDraft();
                    document.getElementById('checkoutForm').submit();
                }
            }
        }
    </script>
    <style>
        /* Desktop: results and cart share the height left under the header, so the POS itself
           does not need a page scrollbar; long lists scroll inside their own panel. */
        .pos-results { max-height: 500px; }
        /* Short screens keep the natural page flow (the page scrolls) so nothing is squeezed or clipped. */
        @media (min-width: 1024px) and (min-height: 720px) {
            /* 19rem / 7rem = the title row, card padding, search field and page padding above/below. */
            .pos-results {
                max-height: calc(100vh - var(--mt-main-top, 89px) - 19rem);
                max-height: calc(100dvh - var(--mt-main-top, 89px) - 19rem);
                min-height: 10rem;
            }
            .pos-cart {
                position: sticky;
                top: calc(var(--mt-nav-h, 89px) + 1rem);
                max-height: calc(100vh - var(--mt-main-top, 89px) - 7rem);
                max-height: calc(100dvh - var(--mt-main-top, 89px) - 7rem);
            }
        }
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</x-app-layout>
