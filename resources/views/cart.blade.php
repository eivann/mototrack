<x-app-layout>
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-6">
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

    <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">Your Cart</h2>

    @if(session('error'))
        <div class="mb-4 bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-300 px-4 py-3 rounded relative">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    @if(session('success'))
        <div class="mb-4 bg-green-100 dark:bg-green-900/30 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-300 px-4 py-3 rounded relative">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    @if(session('cart') && count($cart) > 0)

    <div class="bg-white dark:bg-gray-800 shadow dark:shadow-none border dark:border-gray-700 rounded-xl overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-100 dark:bg-gray-700/50 text-gray-600 dark:text-gray-400 uppercase text-xs">
                <tr>
                    <th class="p-4">Item</th>
                    <th class="p-4">Price</th>
                    <th class="p-4">Qty</th>
                    <th class="p-4">Total</th>
                    <th class="p-4 text-center">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @php $grandTotal = 0; @endphp

                @foreach($cart as $id => $item)
                    @php
                        $total = $item['price'] * $item['quantity'];
                        $grandTotal += $total;
                    @endphp

                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                    <td class="p-4 font-medium text-gray-900 dark:text-white">{{ $item['part_name'] ?? 'Unknown' }}</td>
                    <td class="p-4 text-green-600 dark:text-green-400">₱{{ number_format($item['price'], 2) }}</td>

                    <td class="p-4">
                        <form action="/cart/update/{{ $id }}" method="POST" class="flex gap-2">
                            @csrf
                            <input type="number" name="quantity" value="{{ $item['quantity'] }}"
                                   class="w-16 border dark:border-gray-600 rounded px-2 py-1 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <button class="bg-blue-500 text-white px-2 rounded">Update</button>
                        </form>
                    </td>

                    <td class="p-4 font-semibold text-gray-900 dark:text-white">₱{{ number_format($total, 2) }}</td>

                    <td class="p-4 text-center">
                        <form action="/cart/remove/{{ $id }}" method="POST">
                            @csrf
                            <button class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">Remove</button>
                        </form>
                    </td>
                </tr>

                @endforeach
            </tbody>
        </table>

        <!-- TOTAL -->
        <div class="flex justify-between p-6 bg-gray-50 dark:bg-gray-700/30 border-t border-gray-100 dark:border-gray-700">
            <span class="font-semibold text-gray-700 dark:text-gray-300">Total:</span>
            <span class="text-green-600 dark:text-green-400 font-bold">₱{{ number_format($grandTotal, 2) }}</span>
        </div>
    </div>

    <!-- BUTTONS -->
    <div class="flex justify-between mt-6">
        <a href="/browse" class="bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 px-4 py-2 rounded hover:bg-gray-300 dark:hover:bg-gray-600">
            Continue Shopping
        </a>
        <a href="/checkout" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700">
            Checkout
        </a>
    </div>

    @else
        <p class="text-gray-500 dark:text-gray-400">Your cart is empty.</p>
    @endif

</div>
</div>
</x-app-layout>