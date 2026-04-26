<x-app-layout>
<div class="p-6">

    <h1 class="text-2xl font-bold mb-4">Inventory Items</h1>

    <!-- SEARCH -->
    <form method="GET" action="/inventory" class="mb-4 flex gap-2">
        <input type="text" name="search"
            class="border p-2 rounded w-64"
            placeholder="Search..."
            value="{{ $search }}">

        <button class="bg-blue-500 text-white px-4 py-2 rounded">Search</button>
        <a href="/inventory" class="bg-gray-500 text-white px-4 py-2 rounded">Reset</a>
    </form>

    <!-- ADD ITEM -->
    <div class="bg-white shadow rounded p-4 mb-6">
        <h2 class="font-semibold mb-3">Add Item</h2>

        <form method="POST" action="/inventory">
            @csrf

            <div class="grid grid-cols-4 gap-3">
                <input name="category" placeholder="Category" class="border p-2 rounded">
                <input name="part_name" placeholder="Part Name" class="border p-2 rounded">
                <input name="brand" placeholder="Brand" class="border p-2 rounded">
                <input name="model" placeholder="Model" class="border p-2 rounded">

                <input name="quantity" type="number" placeholder="Quantity" class="border p-2 rounded">
                <input name="notes" placeholder="Notes" class="border p-2 rounded">
                <input name="price" type="number" placeholder="Price" class="border p-2 rounded">
            </div>

            <button class="bg-green-600 text-white px-4 py-2 rounded mt-3">
                Add Item
            </button>
        </form>
    </div>

    <!-- TABLE -->
    <div class="bg-white shadow rounded overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-3">#</th>
                    <th class="p-3">Category</th>
                    <th class="p-3">Part</th>
                    <th class="p-3">Brand</th>
                    <th class="p-3">Model</th>
                    <th class="p-3">Qty</th>
                    <th class="p-3">Notes</th>
                    <th class="p-3">Price</th>
                    <th class="p-3">Action</th>
                </tr>
            </thead>

            <tbody>
                @forelse($items as $item)
                <tr class="border-t">
                    <td class="p-3">{{ $loop->iteration }}</td>
                    <td class="p-3">{{ $item->category }}</td>
                    <td class="p-3">{{ $item->part_name }}</td>
                    <td class="p-3">{{ $item->brand }}</td>
                    <td class="p-3">{{ $item->model }}</td>
                    <td class="p-3">{{ $item->quantity }}</td>
                    <td class="p-3">{{ $item->notes }}</td>
                    <td class="p-3">₱{{ number_format($item->price, 2) }}</td>
                    <td class="p-3 flex gap-2">
                        <a href="/inventory/{{ $item->id }}/edit"
                           class="bg-yellow-500 text-white px-2 py-1 rounded text-xs">
                           Edit
                        </a>

                        <form method="POST" action="/inventory/{{ $item->id }}">
                            @csrf
                            @method('DELETE')
                            <button class="bg-red-500 text-white px-2 py-1 rounded text-xs">
                                Delete
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="p-4 text-center text-red-500">
                        No items found
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
</x-app-layout>