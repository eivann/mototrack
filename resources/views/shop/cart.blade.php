<x-app-layout>
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-6">
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

    <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">Your Cart</h2>

    @if(session('error'))
        <x-alert type="error" :message="session('error')" />
    @endif

    @if(session('success'))
        <x-alert type="success" :message="session('success')" />
    @endif

    @if(session('cart') && count($cart) > 0)
    <div class="space-y-6">
        <!-- DESKTOP TABLE -->
        <div class="hidden md:block bg-white dark:bg-gray-800 shadow-sm border border-gray-100 dark:border-gray-700 rounded-2xl overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700 text-xs tracking-wider uppercase text-gray-500 dark:text-gray-400">
                        <th class="p-5 font-semibold">Item Details</th>
                        <th class="p-5 font-semibold text-center">Price</th>
                        <th class="p-5 font-semibold text-center">Quantity</th>
                        <th class="p-5 font-semibold text-right">Subtotal</th>
                        <th class="p-5 font-semibold text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                    @php $grandTotal = 0; @endphp
                    @foreach($cart as $id => $item)
                        @php
                            $total = $item['price'] * $item['quantity'];
                            $grandTotal += $total;
                        @endphp
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                            <td class="p-5">
                                <div class="font-bold text-gray-900 dark:text-white">{{ $item['part_name'] ?? 'Unknown' }}</div>
                                <div class="text-xs text-gray-400 mt-0.5">{{ $item['brand'] ?? '' }} • {{ $item['model'] ?? '' }}</div>
                            </td>
                            <td class="p-5 text-center font-medium text-gray-600 dark:text-gray-400">₱{{ number_format($item['price'], 2) }}</td>
                            <td class="p-5">
                                <form action="/cart/update/{{ $id }}" method="POST"
                                      x-data="{ loading: false }" @submit="loading = true"
                                      class="flex items-center justify-center gap-2">
                                    @csrf
                                    <label for="qty-desktop-{{ $id }}" class="sr-only">Quantity for {{ $item['part_name'] ?? 'item' }}</label>
                                    <input id="qty-desktop-{{ $id }}" type="number" name="quantity" value="{{ $item['quantity'] }}" min="1"
                                           class="w-16 border-gray-200 dark:border-gray-600 rounded-lg px-2 py-1.5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-center text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <button :disabled="loading"
                                            aria-label="Update quantity for {{ $item['part_name'] ?? 'item' }}"
                                            class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white p-2.5 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 disabled:opacity-75 min-w-[44px] min-h-[44px]">
                                        <svg x-show="!loading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                        <svg x-show="loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    </button>
                                </form>
                            </td>
                            <td class="p-5 text-right font-bold text-gray-900 dark:text-white">₱{{ number_format($total, 2) }}</td>
                            <td class="p-5 text-center">
                                <form action="/cart/remove/{{ $id }}" method="POST"
                                      x-data="{ loading: false }" @submit="loading = true">
                                    @csrf
                                    <button :disabled="loading"
                                            aria-label="Remove {{ $item['part_name'] ?? 'item' }} from cart"
                                            class="inline-flex items-center justify-center text-red-500 hover:text-red-700 p-2.5 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1 disabled:opacity-75 min-w-[44px] min-h-[44px]">
                                        <svg x-show="!loading" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        <svg x-show="loading" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- MOBILE CARDS -->
        <div class="grid grid-cols-1 gap-4 md:hidden">
            @php $grandTotal = 0; @endphp
            @foreach($cart as $id => $item)
                @php
                    $total = $item['price'] * $item['quantity'];
                    $grandTotal += $total;
                @endphp
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm relative">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="font-bold text-gray-900 dark:text-white leading-tight">{{ $item['part_name'] ?? 'Unknown' }}</h3>
                            <p class="text-xs text-gray-500 mt-1">{{ $item['brand'] ?? '' }} • {{ $item['model'] ?? '' }}</p>
                        </div>
                        <form action="/cart/remove/{{ $id }}" method="POST"
                              x-data="{ loading: false }" @submit="loading = true">
                            @csrf
                            <button :disabled="loading"
                                    aria-label="Remove {{ $item['part_name'] ?? 'item' }} from cart"
                                    class="inline-flex items-center justify-center text-gray-400 hover:text-red-500 transition-colors p-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-400 disabled:opacity-75 min-w-[44px] min-h-[44px]">
                                <svg x-show="!loading" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                <svg x-show="loading" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            </button>
                        </form>
                    </div>
                    
                    <div class="flex justify-between items-end">
                        <div class="space-y-1">
                            <p class="text-xs text-gray-400 uppercase font-bold tracking-wider">Quantity</p>
                            <form action="/cart/update/{{ $id }}" method="POST"
                                  x-data="{ loading: false }" @submit="loading = true"
                                  class="flex items-center gap-2">
                                @csrf
                                <label for="qty-mobile-{{ $id }}" class="sr-only">Quantity for {{ $item['part_name'] ?? 'item' }}</label>
                                <input id="qty-mobile-{{ $id }}" type="number" name="quantity" value="{{ $item['quantity'] }}" min="1"
                                       class="w-16 border-gray-200 dark:border-gray-600 rounded-lg px-2 py-1.5 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white text-center text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <button :disabled="loading"
                                        aria-label="Update quantity for {{ $item['part_name'] ?? 'item' }}"
                                        class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white p-2.5 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 disabled:opacity-75 min-w-[44px] min-h-[44px]">
                                    <svg x-show="!loading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                    <svg x-show="loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                </button>
                            </form>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-400 uppercase font-bold tracking-wider mb-1">Subtotal</p>
                            <p class="text-lg font-black text-blue-600 dark:text-blue-400">₱{{ number_format($total, 2) }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- TOTAL SUMMARY -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
            <div class="flex justify-between items-center mb-6">
                <span class="text-gray-500 dark:text-gray-400 font-bold uppercase tracking-widest text-xs">Grand Total</span>
                <span class="text-3xl font-black text-gray-900 dark:text-white">₱{{ number_format($grandTotal, 2) }}</span>
            </div>
            <div class="flex flex-col sm:flex-row gap-4">
                <a href="/browse" class="flex-1 text-center bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 py-4 rounded-xl font-bold hover:bg-gray-200 dark:hover:bg-gray-600 transition-all focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2">
                    Continue Shopping
                </a>
                <a href="/checkout" class="flex-1 text-center bg-green-600 text-white py-4 rounded-xl font-bold shadow-lg shadow-green-200 dark:shadow-none hover:bg-green-700 transition-all active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                    Secure Checkout
                </a>
            </div>
        </div>
    </div>

    @else
        <div class="bg-white dark:bg-gray-800 p-10 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm text-center">
            <div class="w-20 h-20 bg-gray-50 dark:bg-gray-900 rounded-full flex items-center justify-center mx-auto mb-6 text-gray-300 dark:text-gray-600">
                <i data-lucide="shopping-cart" class="w-10 h-10"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Your cart is empty</h3>
            <p class="text-gray-500 dark:text-gray-400 mb-8 max-w-sm mx-auto">Looks like you haven't added any items to your cart yet. Browse our products to find what you need.</p>
            <a href="/browse" class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-xl transition-all shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                Shop Now
            </a>
        </div>
    @endif

</div>
</div>
</x-app-layout>