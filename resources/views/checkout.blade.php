<x-app-layout>
<div class="max-w-4xl mx-auto p-6">

    <h2 class="text-2xl font-bold mb-6 text-gray-800">Checkout</h2>

    @if(session('cart') && count($cart) > 0)

    <div class="bg-white shadow-lg rounded-xl overflow-hidden">
        <table class="min-w-full text-sm text-left">
            <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                <tr>
                    <th class="p-4">Item</th>
                    <th class="p-4">Price</th>
                    <th class="p-4">Qty</th>
                    <th class="p-4">Total</th>
                </tr>
            </thead>

            <tbody class="divide-y">
                @php $grandTotal = 0; @endphp

                @foreach($cart as $item)
                    @php 
                        $total = $item['price'] * $item['quantity']; 
                        $grandTotal += $total;
                    @endphp

                    <tr class="hover:bg-gray-50 transition">
                        <td class="p-4 font-medium text-gray-800">
                            {{ $item['part_name'] ?? 'Unknown Item' }}
                        </td>
                        <td class="p-4 text-green-600">
                            ₱{{ number_format($item['price'], 2) }}
                        </td>
                        <td class="p-4">
                            {{ $item['quantity'] }}
                        </td>
                        <td class="p-4 font-semibold">
                            ₱{{ number_format($total, 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- TOTAL -->
        <div class="flex justify-between items-center p-6 bg-gray-50">
            <h3 class="text-lg font-semibold text-gray-700">
                Total:
            </h3>
            <h3 class="text-xl font-bold text-green-600">
                ₱{{ number_format($grandTotal, 2) }}
            </h3>
        </div>
    </div>

    <!-- BUTTONS -->
    <div class="flex justify-between mt-6">
        <a href="/cart" 
           class="px-5 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg transition">
            Back to Cart
        </a>

        <form method="POST" action="/checkout" class="flex flex-col items-end gap-4">
            @csrf
            
            <div class="flex items-center gap-3">
                <label class="font-semibold text-gray-700">Down Payment (₱):</label>
                <input type="number" name="amount_tendered" step="0.01" min="0" class="border rounded-lg p-2 w-48 shadow-sm focus:ring focus:ring-green-200" placeholder="Optional...">
            </div>

            <button class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition shadow">
                Confirm Order
            </button>
        </form>
    </div>

    @else
        <div class="bg-white p-6 rounded-lg shadow text-center">
            <p class="text-gray-500">Your cart is empty.</p>
            <a href="/browse" class="text-blue-600 hover:underline mt-2 inline-block">
                Browse items
            </a>
        </div>
    @endif

</div>
</x-app-layout>