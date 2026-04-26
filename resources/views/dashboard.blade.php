<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">
            Owner Dashboard
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-6">

            <a href="/inventory" class="bg-white p-6 rounded-xl shadow hover:shadow-lg">
                📦 <h3 class="font-bold text-lg">Manage Inventory</h3>
            </a>

            <a href="/sales/create" class="bg-white p-6 rounded-xl shadow hover:shadow-lg">
                💰 <h3 class="font-bold text-lg">Record Sale</h3>
            </a>

            <a href="/sales/history" class="bg-white p-6 rounded-xl shadow hover:shadow-lg">
                📊 <h3 class="font-bold text-lg">Generate Reports</h3>
            </a>

            <a href="/repairs" class="bg-white p-6 rounded-xl shadow hover:shadow-lg">
                🔧 <h3 class="font-bold text-lg">Track Repair</h3>
            </a>

            <a href="#" class="bg-white p-6 rounded-xl shadow hover:shadow-lg">
                🧾 <h3 class="font-bold text-lg">Process Pre-order</h3>
            </a>

            <a href="#" class="bg-white p-6 rounded-xl shadow hover:shadow-lg">
                ⚠️ <h3 class="font-bold text-lg">Low Stock Alert</h3>
            </a>

        </div>
    </div>
</x-app-layout>