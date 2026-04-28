<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Customer Dashboard
        </h2>
    </x-slot>

    <div class="p-6 max-w-7xl mx-auto">

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

            <!-- Browse Parts -->
            <a href="/browse" class="bg-white shadow rounded-lg p-6 hover:bg-gray-50 transition">
                <h3 class="text-lg font-semibold mb-2">Browse Parts</h3>
                <p class="text-gray-500 text-sm">View available motorcycle parts</p>
            </a>

            <!-- Cart -->
            <a href="/cart" class="bg-white shadow rounded-lg p-6 hover:bg-gray-50 transition">
                <h3 class="text-lg font-semibold mb-2">View Cart</h3>
                <p class="text-gray-500 text-sm">Check items in your cart</p>
            </a>

            <!-- Compatibility -->
            <a href="/compatibility" class="bg-white shadow rounded-lg p-6 hover:bg-gray-50 transition">
                <h3 class="text-lg font-semibold mb-2">Compatibility Checker</h3>
                <p class="text-gray-500 text-sm">Find parts for your motorcycle</p>
            </a>

            <!-- Pre-order -->
            <a href="/preorder-parts" class="bg-white shadow rounded-lg p-6 hover:bg-gray-50 transition">
                <h3 class="text-lg font-semibold mb-2">Pre-order Part</h3>
                <p class="text-gray-500 text-sm">Request unavailable parts</p>
            </a>

        </div>

    </div>

</x-app-layout>