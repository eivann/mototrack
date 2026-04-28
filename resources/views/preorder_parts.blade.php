<x-app-layout>
<div class="p-6">

    <h2 class="text-xl font-bold mb-4">Pre-order Unavailable Parts</h2>

    <div class="mb-4">
    <a href="/preorder-custom" class="bg-blue-500 text-white px-4 py-2 rounded">
        Request Custom Part
    </a>
</div>

    <div class="grid grid-cols-3 gap-4">
        @forelse($items as $item)
            <div class="bg-white p-4 rounded shadow">
                <h3 class="font-semibold">{{ $item->part_name }}</h3>
                <p>{{ $item->model }}</p>

                <p class="text-red-500">Out of Stock</p>

                <a href="/preorder/{{ $item->id }}" class="btn btn-warning mt-2">
                    Pre-order
                </a>
            </div>
        @empty
            <p>No items available for preorder.</p>
        @endforelse
    </div>

</div>
</x-app-layout>