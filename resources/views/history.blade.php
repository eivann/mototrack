<x-app-layout>
<div class="p-6">
    <h1>Sales History</h1>

    <form method="GET" class="mb-3">
    <label for="from">from:</label>
    <input type="date" name="from">
    <label for="to">to:</label>
    <input type="date" name="to">
    <button type="submit">Filter</button>
    <a href="?filter=today">Today</a>
</form>

<div>
    <h3>Total Sales: ₱ {{ number_format($totalSales, 2) }}</h3>
    <h4>Total Transactions: {{ $totalTransactions }}</h4>
</div>
   <table class="w-full border border-gray-300 bg-white shadow rounded">
    <thead class="bg-gray-200">
        <tr>
            <th class="p-3 text-left">Item</th>
            <th class="p-3 text-center">Quantity</th>
            <th class="p-3 text-center">Total Price</th>
            <th class="p-3 text-center">Date</th>
            <th class="p-3 text-center">Action</th>
        </tr>
    </thead>
    <tbody>
        @foreach($sales as $sale)
<tr class="border-t bg-white hover:bg-gray-100">
            <td class="p-3">{{ $sale->item->part_name }}</td>
            <td class="p-3 text-center">{{ $sale->quantity }}</td>
            <td class="p-3 text-center">₱ {{ number_format($sale->total_price, 2) }}</td>
            <td class="p-3 text-center">{{ $sale->created_at }}</td>

            <td class="p-3 text-center">
    <div class="flex justify-center gap-2">

    <a href="/sales/{{ $sale->id }}/receipt"
        class="inline-block !bg-green-500 !text-white px-3 py-1 rounded hover:!bg-green-600">
        Receipt
    </a>

    <form action="/sales/{{ $sale->id }}" method="POST">
    @csrf
    @method('DELETE')
    <button class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
        Delete
    </button>
</form>

</td>
        </tr>
        @endforeach
    </tbody>
</table>
</div>

</div>

</x-app-layout>