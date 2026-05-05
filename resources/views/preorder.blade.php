<x-app-layout>
<div class="min-h-screen bg-gray-100 py-10">

    <div class="max-w-xl mx-auto bg-white shadow-lg rounded-xl p-8">

        <h2 class="text-2xl font-bold mb-6">📦 Pre-order Item</h2>

        <!-- ITEM INFO -->
        <div class="mb-6 p-4 border rounded-lg bg-gray-50">
            <p class="text-lg font-semibold">{{ $item->part_name }}</p>
            <p class="text-sm text-gray-600">
                Model: {{ $item->model ?? 'N/A' }}
            </p>
            <p class="text-green-600 font-bold mt-1">
                ₱{{ number_format($item->price, 2) }}
            </p>
        </div>

        @if ($errors->any())
    <div class="mb-4 text-red-500 text-sm">
        <ul>
            @foreach ($errors->all() as $error)
                <li>• {{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

        <!-- FORM -->
        <form method="POST" action="/preorder/store">
            @csrf
            <input type="hidden" name="part_name" value="{{ $item->part_name }}">
            <input type="hidden" name="model" value="{{ $item->model }}">

            <!-- Quantity -->
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Quantity</label>
                <input 
                    type="number" 
                    name="quantity" 
                    min="1"
                    required
                    class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-400"
                >
            </div>

            <!-- Down Payment -->
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Down Payment</label>
                <input 
                    type="number" 
                    name="down_payment" 
                    min="0"
                    class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-400"
                >
            </div>

            <!-- Buttons -->
            <div class="flex justify-between mt-6">

                <a href="/preorder-parts" 
                   class="text-gray-600 hover:underline">
                    ← Back
                </a>

                <button 
                    type="submit"
                    class="bg-yellow-500 text-white px-6 py-2 rounded-lg hover:bg-yellow-600 transition">
                    Submit Pre-order
                </button>

            </div>
            @if(session('success'))
    <div class="mb-4 text-green-600">
        {{ session('success') }}
    </div>
@endif
        </form>

    </div>

</div>
</x-app-layout>