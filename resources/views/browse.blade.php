<x-app-layout>
<div class="p-6 max-w-7xl mx-auto">

    <!-- 🔍 SEARCH BAR -->
    <form method="GET" action="/browse" class="mb-6 flex gap-2">
        <input 
            type="text" 
            name="search" 
            value="{{ request('search') }}"
            placeholder="Search parts..." 
            class="w-full border p-3 rounded-lg shadow-sm focus:ring focus:ring-blue-200"
        >

        <button class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
            Search
        </button>
    </form>

    <div class="grid grid-cols-4 gap-6">

        <!-- 🎛 FILTER SIDEBAR -->
        <form method="GET" action="/browse" class="col-span-1 bg-white p-4 rounded-xl shadow">

            <h2 class="font-bold text-lg mb-4">Filters</h2>

            <!-- keep search when filtering -->
            <input type="hidden" name="search" value="{{ request('search') }}">

            <!-- CATEGORY -->
            <div class="mb-4">
                <label class="block mb-1 font-semibold">Category</label>
                <select name="category" class="w-full border p-2 rounded">
                    <option value="">All</option>
                    <option value="engine" {{ request('category')=='engine'?'selected':'' }}>Engine</option>
                    <option value="brakes" {{ request('category')=='brakes'?'selected':'' }}>Brakes</option>
                    <option value="electrical" {{ request('category')=='electrical'?'selected':'' }}>Electrical</option>
                    <option value="drivetrain" {{ request('category')=='drivetrain'?'selected':'' }}>Drivetrain</option>
                    <option value="accessories" {{ request('category')=='accessories'?'selected':'' }}>Accessories</option>
                </select>
            </div>

            <!-- SORT -->
            <div class="mb-4">
                <label class="block mb-1 font-semibold">Sort</label>
                <select name="sort" class="w-full border p-2 rounded">
                    <option value="">Default</option>
                    <option value="low_high" {{ request('sort')=='low_high'?'selected':'' }}>Price Low → High</option>
                    <option value="high_low" {{ request('sort')=='high_low'?'selected':'' }}>Price High → Low</option>
                </select>
            </div>

            <button class="w-full bg-green-600 text-white py-2 rounded hover:bg-green-700">
                Apply Filters
            </button>
        </form>

        <!-- 🛒 PRODUCTS -->
        <div class="col-span-3 grid grid-cols-3 gap-6">

            @forelse($items as $item)
                <div class="bg-white rounded-xl shadow hover:shadow-lg transition p-4">

                    <h3 class="font-semibold text-lg mb-1">
                        {{ $item->part_name }}
                    </h3>

                    <p class="text-gray-500 text-sm">
                        {{ $item->brand }} • {{ $item->model }}
                    </p>

                    <p class="text-xs text-gray-400 mt-1">
                        Category: {{ $item->category }}
                    </p>

                    <p class="text-xs text-gray-400">
                        Stock: {{ $item->quantity }}
                    </p>

                    <p class="text-green-600 font-bold mt-2 text-lg">
                        ₱{{ number_format($item->price, 2) }}
                    </p>

                    <form method="POST" action="/cart/add" class="mt-3">
                        @csrf
                        <input type="hidden" name="id" value="{{ $item->id }}">
                        @if($item->quantity == 0)
    <a href="/preorder/{{ $item->id }}" class="btn btn-warning w-full">
        Pre-order
    </a>
@else
    <button class="btn btn-primary w-full bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Add to Cart</button>
@endif
                    </form>

                </div>
            @empty
                <p>No items found.</p>
            @endforelse

        </div>

    </div>

</div>
</x-app-layout>