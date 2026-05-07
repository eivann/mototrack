<x-app-layout>
<div class="max-w-4xl mx-auto p-6">

    <h2 class="text-2xl font-bold mb-6 text-gray-800 dark:text-white">Checkout</h2>

    @if(session('cart') && count($cart) > 0)

    <div class="bg-white dark:bg-gray-800 shadow-lg dark:shadow-none border dark:border-gray-700 rounded-xl overflow-hidden">
        <table class="min-w-full text-sm text-left">
            <thead class="bg-gray-100 dark:bg-gray-700/50 text-gray-600 dark:text-gray-400 uppercase text-xs">
                <tr>
                    <th class="p-4">Item</th>
                    <th class="p-4">Price</th>
                    <th class="p-4">Qty</th>
                    <th class="p-4">Total</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @php $grandTotal = 0; @endphp

                @foreach($cart as $item)
                    @php
                        $total = $item['price'] * $item['quantity'];
                        $grandTotal += $total;
                    @endphp

                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                        <td class="p-4 font-medium text-gray-800 dark:text-white">{{ $item['part_name'] ?? 'Unknown Item' }}</td>
                        <td class="p-4 text-green-600 dark:text-green-400">₱{{ number_format($item['price'], 2) }}</td>
                        <td class="p-4 text-gray-700 dark:text-gray-300">{{ $item['quantity'] }}</td>
                        <td class="p-4 font-semibold text-gray-900 dark:text-white">₱{{ number_format($total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- TOTAL -->
        <div class="flex justify-between items-center p-6 bg-gray-50 dark:bg-gray-700/30 border-t border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">Total:</h3>
            <h3 class="text-xl font-bold text-green-600 dark:text-green-400">₱{{ number_format($grandTotal, 2) }}</h3>
        </div>
    </div>

    <!-- BUTTONS -->
    <div class="flex justify-between items-start mt-6">
        <a href="/cart" class="self-start px-5 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-600 rounded-lg transition font-medium">
            ← Back to Cart
        </a>

        <form method="POST" action="/checkout" class="flex flex-col items-end gap-4">
            @csrf

            {{-- GCash is the only payment method for customers --}}
            <input type="hidden" name="payment_method" value="gcash">

            <div class="flex items-center gap-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg px-4 py-2.5">
                <svg class="w-5 h-5 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
                <div>
                    <p class="text-sm font-bold text-blue-700 dark:text-blue-300">Payment via GCash</p>
                    <p class="text-xs text-blue-500 dark:text-blue-400">You will be redirected to GCash to complete payment.</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <label class="font-semibold text-gray-700 dark:text-gray-300">Down Payment (₱):</label>
                <input type="number" name="amount_tendered" step="0.01" min="0"
                    class="border dark:border-gray-600 rounded-lg p-2 w-48 shadow-sm focus:ring focus:ring-blue-300 bg-white dark:bg-gray-700 text-gray-900 dark:text-white outline-none"
                    placeholder="Optional">
            </div>

            <button class="inline-flex items-center gap-2 px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg transition shadow-md active:scale-[0.98]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
                Pay with GCash
            </button>
        </form>
    </div>

    @else
        <div class="bg-white dark:bg-gray-800 border dark:border-gray-700 p-6 rounded-lg shadow text-center">
            <p class="text-gray-500 dark:text-gray-400">Your cart is empty.</p>
            <a href="/browse" class="text-blue-600 dark:text-blue-400 hover:underline mt-2 inline-block">Browse items</a>
        </div>
    @endif

</div>
</x-app-layout>