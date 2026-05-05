<x-app-layout>
<div class="max-w-5xl mx-auto p-6">

    <h2 class="text-2xl font-bold mb-6">Your Cart</h2>
    
    @if(session('error'))
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    @if(session('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    @if(session('cart') && count($cart) > 0)

    <div class="bg-white shadow rounded-xl overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                <tr>
                    <th class="p-4">Item</th>
                    <th class="p-4">Price</th>
                    <th class="p-4">Qty</th>
                    <th class="p-4">Total</th>
                    <th class="p-4 text-center">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y">
                @php $grandTotal = 0; @endphp

                @foreach($cart as $id => $item)
                    @php 
                        $total = $item['price'] * $item['quantity'];
                        $grandTotal += $total;
                    @endphp

                <tr class="hover:bg-gray-50">
                    <td class="p-4 font-medium">
                        {{ $item['part_name'] ?? 'Unknown' }}
                    </td>

                    <td class="p-4 text-green-600">
                        ₱{{ number_format($item['price'], 2) }}
                    </td>

                    <!-- UPDATE QTY -->
                    <td class="p-4">
                        <form action="/cart/update/{{ $id }}" method="POST" class="flex gap-2">
                            @csrf
                            <input type="number" name="quantity" value="{{ $item['quantity'] }}" 
                                   class="w-16 border rounded px-2 py-1">
                            <button class="bg-blue-500 text-white px-2 rounded">Update</button>
                        </form>
                    </td>

                    <td class="p-4 font-semibold">
                        ₱{{ number_format($total, 2) }}
                    </td>

                    <!-- REMOVE -->
                    <td class="p-4 text-center">
                        <form action="/cart/remove/{{ $id }}" method="POST">
                            @csrf
                            <button class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">
                                Remove
                            </button>
                        </form>
                    </td>
                </tr>

                @endforeach
            </tbody>
        </table>

        <!-- TOTAL -->
        <div class="flex justify-between p-6 bg-gray-50">
            <span class="font-semibold">Total:</span>
            <span class="text-green-600 font-bold">
                ₱{{ number_format($grandTotal, 2) }}
            </span>
        </div>
    </div>

    <!-- BUTTONS -->
    <div class="flex justify-between mt-6">
        <a href="/browse" class="bg-gray-200 px-4 py-2 rounded hover:bg-gray-300">
            Continue Shopping
        </a>

        <a href="/checkout" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700">
            Checkout
        </a>
    </div>

    @else
        <p>Your cart is empty.</p>
    @endif

</div>
</x-app-layout>