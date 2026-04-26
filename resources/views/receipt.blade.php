<x-app-layout>
<div class="max-w-xl mx-auto p-6 bg-white shadow rounded">

    <h2 class="text-xl font-bold mb-4 text-center">Repair Receipt</h2>

    <hr class="mb-4">

    <p><strong>Repair ID:</strong> {{ $repair->id }}</p>
    <p><strong>Date:</strong> {{ $repair->created_at->format('Y-m-d H:i') }}</p>

    <hr class="my-4">

    <p><strong>Customer:</strong> {{ $repair->customer_name }}</p>
    <p><strong>Bike Model:</strong> {{ $repair->bike_model }}</p>
    <p><strong>Issue:</strong> {{ $repair->issue }}</p>

    <hr class="my-4">

    <p>
        <strong>Status:</strong> 
        <span class="
            px-2 py-1 rounded text-white text-xs
            {{ $repair->status == 'pending' ? 'bg-yellow-500' : '' }}
            {{ $repair->status == 'ongoing' ? 'bg-blue-500' : '' }}
            {{ $repair->status == 'done' ? 'bg-green-600' : '' }}
        ">
            {{ ucfirst($repair->status) }}
        </span>
    </p>

    <p class="mt-2">
        <strong>Payment:</strong> 
        @if($repair->is_paid)
            <span class="text-green-600 font-bold">PAID</span>
        @else
            <span class="text-red-500 font-bold">UNPAID</span>
        @endif
    </p>

    <hr class="my-4">

    <h3 class="text-lg font-bold">
        Total Cost: ₱{{ number_format($repair->cost ?? 0, 2) }}
    </h3>

    <hr class="my-4">

    <div class="text-center mt-6">
        <button onclick="window.print()"
            class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            Print Receipt
        </button>
    </div>

</div>
</x-app-layout>