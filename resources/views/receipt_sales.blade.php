<x-app-layout>
<div class="p-6 max-w-md mx-auto bg-white shadow rounded">

    <h2 class="text-xl font-bold mb-4">Receipt</h2>

    <p><strong>Receipt #:</strong> 
    {{ 'MOTOTRACK-' . str_pad($sale->id, 5, '0', STR_PAD_LEFT) }}
</p>
    <p><strong>Item:</strong> {{ $sale->item->part_name }}</p>
    <p><strong>Quantity:</strong> {{ $sale->quantity }}</p>
    <p><strong>Price:</strong> ₱{{ number_format($sale->price, 2) }}</p>
<p><strong>Brand:</strong> {{ $sale->item->brand }}</p>
<p><strong>Model:</strong> {{ $sale->item->model }}</p>

    <hr class="my-3">

    <h3 class="font-bold">
        Total: ₱{{ number_format($sale->total_price, 2) }}
    </h3>

    <p class="mt-2">
        <strong>Date:</strong> {{ $sale->created_at }}
    </p>

    <button onclick="window.print()"
        class="mt-4 bg-blue-600 text-white px-3 py-2 rounded">
        Print
    </button>

</div>
</x-app-layout>