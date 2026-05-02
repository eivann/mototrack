<x-app-layout>
    <script src="//unpkg.com/alpinejs" defer></script>
    <div class="min-h-screen bg-gray-50 py-8 px-4 sm:px-6 lg:px-8 font-sans" x-data="posSystem()">
        
        <div class="max-w-7xl mx-auto">
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight mb-8">
                Point of Sale
            </h1>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Left Side: Product Search -->
                <div class="lg:col-span-2 flex flex-col gap-6">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Search & Add to Cart</label>
                        <div class="relative">
                            <input 
                                type="text" 
                                x-model="searchQuery"
                                placeholder="Scan barcode or type part name, brand, model..." 
                                class="w-full pl-12 pr-4 py-4 rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 text-lg shadow-sm transition-shadow"
                            >
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                        </div>

                        <!-- Search Results -->
                        <div class="mt-4 max-h-[500px] overflow-y-auto pr-2 custom-scrollbar">
                            <template x-for="item in filteredItems" :key="item.id">
                                <div @click="addToCart(item)" 
                                     class="group flex items-center justify-between p-4 mb-3 rounded-xl border border-gray-100 hover:border-indigo-300 hover:bg-indigo-50/50 cursor-pointer transition-all active:scale-[0.99]"
                                     :class="item.quantity <= 0 ? 'opacity-50 pointer-events-none' : ''">
                                    <div>
                                        <h3 class="font-bold text-gray-900 group-hover:text-indigo-900 text-lg" x-text="item.part_name"></h3>
                                        <p class="text-sm text-gray-500 mt-1">
                                            <span x-text="item.brand"></span> • <span x-text="item.model"></span>
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-extrabold text-indigo-600 text-xl">₱<span x-text="formatMoney(item.price)"></span></div>
                                        <div class="text-xs font-semibold mt-1" :class="item.quantity <= 0 ? 'text-red-500' : (item.quantity <= 5 ? 'text-amber-500' : 'text-emerald-500')">
                                            <span x-text="item.quantity"></span> in stock
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <div x-show="filteredItems.length === 0" class="text-center py-12 text-gray-400">
                                No parts found matching your search.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Side: The Cart -->
                <div class="flex flex-col gap-6">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col h-full">
                        <div class="flex items-center justify-between mb-4 pb-4 border-b border-gray-100">
                            <h2 class="text-xl font-bold text-gray-900">Current Order</h2>
                            <span class="bg-indigo-100 text-indigo-800 text-xs font-bold px-3 py-1 rounded-full"><span x-text="cart.length"></span> items</span>
                        </div>

                        <!-- Cart Items -->
                        <div class="flex-1 overflow-y-auto mb-4 min-h-[300px]">
                            <template x-for="(cartItem, index) in cart" :key="cartItem.id">
                                <div class="flex justify-between items-start mb-4 bg-gray-50 p-3 rounded-lg">
                                    <div class="flex-1 pr-3">
                                        <div class="font-bold text-gray-900 text-sm" x-text="cartItem.part_name"></div>
                                        <div class="text-xs text-gray-500">₱<span x-text="formatMoney(cartItem.price)"></span> each</div>
                                    </div>
                                    <div class="flex flex-col items-end gap-2">
                                        <div class="font-bold text-gray-900">₱<span x-text="formatMoney(cartItem.price * cartItem.quantity)"></span></div>
                                        <div class="flex items-center bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm">
                                            <button type="button" @click="updateQuantity(index, -1)" class="px-2 py-1 text-gray-600 hover:bg-gray-100 transition"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg></button>
                                            <span class="w-8 text-center text-sm font-bold bg-gray-50 py-1" x-text="cartItem.quantity"></span>
                                            <button type="button" @click="updateQuantity(index, 1)" class="px-2 py-1 text-gray-600 hover:bg-gray-100 transition"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg></button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <div x-show="cart.length === 0" class="flex flex-col items-center justify-center h-full text-gray-400">
                                <svg class="w-16 h-16 mb-4 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                Cart is empty
                            </div>
                        </div>

                        <!-- Totals & Checkout -->
                        <div class="border-t border-gray-100 pt-4 mt-auto">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-gray-500 font-medium">Subtotal</span>
                                <span class="text-gray-900 font-bold">₱<span x-text="formatMoney(cartTotal)"></span></span>
                            </div>
                            <div class="flex justify-between items-center mb-6">
                                <span class="text-gray-900 font-extrabold text-xl">Total</span>
                                <span class="text-indigo-600 font-extrabold text-2xl">₱<span x-text="formatMoney(cartTotal)"></span></span>
                            </div>

                            <div class="bg-gray-50 p-4 rounded-xl mb-6 border border-gray-100">
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Amount Tendered (Cash)</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 font-bold sm:text-lg">₱</span>
                                    </div>
                                    <input type="number" x-model.number="amountTendered" class="w-full pl-8 pr-4 py-3 border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 font-bold text-lg" placeholder="0.00">
                                </div>
                                <div class="flex justify-between items-center mt-3 pt-3 border-t border-gray-200" x-show="amountTendered > 0">
                                    <span class="text-gray-600 font-semibold">Change</span>
                                    <span class="font-extrabold text-lg" :class="change < 0 ? 'text-red-500' : 'text-emerald-500'">₱<span x-text="formatMoney(change)"></span></span>
                                </div>
                            </div>

                            <form method="POST" action="/sales/store" id="checkoutForm">
                                @csrf
                                <input type="hidden" name="cart_data" :value="JSON.stringify(cart)">
                                <input type="hidden" name="amount_tendered" :value="amountTendered">
                                <button type="button" @click="submitCheckout()" 
                                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-4 px-6 rounded-xl shadow-lg shadow-indigo-200 transition-all active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                                        :disabled="cart.length === 0 || amountTendered < cartTotal">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    Complete Checkout
                                </button>
                            </form>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Error/Success Toasts can go here if needed -->
    @if(session('error'))
        <div class="fixed bottom-5 right-5 bg-red-500 text-white p-4 rounded-xl shadow-xl z-50">
            {{ session('error') }}
        </div>
    @endif

    <script>
        function posSystem() {
            return {
                searchQuery: '',
                inventory: @json($items),
                cart: [],
                amountTendered: null,

                get filteredItems() {
                    if (this.searchQuery === '') {
                        return this.inventory;
                    }
                    const q = this.searchQuery.toLowerCase();
                    return this.inventory.filter(item => {
                        return (item.part_name && item.part_name.toLowerCase().includes(q)) ||
                               (item.brand && item.brand.toLowerCase().includes(q)) ||
                               (item.model && item.model.toLowerCase().includes(q)) ||
                               (item.category && item.category.toLowerCase().includes(q));
                    });
                },

                get cartTotal() {
                    return this.cart.reduce((total, item) => total + (item.price * item.quantity), 0);
                },

                get change() {
                    if (!this.amountTendered) return 0;
                    return this.amountTendered - this.cartTotal;
                },

                addToCart(item) {
                    if (item.quantity <= 0) return;
                    
                    const existingItemIndex = this.cart.findIndex(i => i.id === item.id);
                    if (existingItemIndex !== -1) {
                        // Check if adding one more exceeds stock
                        if (this.cart[existingItemIndex].quantity < item.quantity) {
                            this.cart[existingItemIndex].quantity++;
                        } else {
                            alert("Cannot add more. Not enough stock available.");
                        }
                    } else {
                        // Add new item to cart
                        this.cart.push({
                            id: item.id,
                            part_name: item.part_name,
                            price: item.price,
                            maxStock: item.quantity,
                            quantity: 1
                        });
                    }
                },

                updateQuantity(index, change) {
                    const item = this.cart[index];
                    const newQty = item.quantity + change;
                    
                    if (newQty <= 0) {
                        this.cart.splice(index, 1);
                    } else if (newQty <= item.maxStock) {
                        item.quantity = newQty;
                    } else {
                        alert("Cannot exceed available stock.");
                    }
                },

                formatMoney(amount) {
                    return parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
                },

                submitCheckout() {
                    if (this.cart.length === 0) {
                        alert("Cart is empty.");
                        return;
                    }
                    if (this.amountTendered < this.cartTotal) {
                        alert("Amount tendered is less than the total.");
                        return;
                    }
                    document.getElementById('checkoutForm').submit();
                }
            }
        }
    </script>
    <style>
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