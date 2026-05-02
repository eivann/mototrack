<x-app-layout>
<div class="p-6 max-w-7xl mx-auto">

    <!-- HEADER / SEARCH BAR -->
    <div class="mb-6 flex flex-col md:flex-row items-center justify-between gap-4">
        <form method="GET" action="/browse" class="w-full md:flex-1 flex gap-2">
            <input 
                type="text" 
                name="search" 
                value="{{ request('search') }}"
                placeholder="Search parts..." 
                class="w-full border p-3 rounded-lg shadow-sm focus:ring focus:ring-blue-200"
            >

            <button class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 shadow">
                Search
            </button>
        </form>

        <a href="/cart" class="w-full md:w-auto flex items-center justify-center gap-2 bg-white border-2 border-blue-600 text-blue-600 hover:bg-blue-50 px-6 py-2.5 rounded-lg shadow font-bold transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            View Cart
            @if(session()->has('cart') && count(session('cart')) > 0)
                <span class="bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full ml-1">
                    {{ count(session('cart')) }}
                </span>
            @endif
        </a>
    </div>

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
        <div class="col-span-3">
            <div class="grid grid-cols-3 gap-6">

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

                    <form method="POST" action="/cart/add/{{ $item->id }}" class="mt-3">
                        @csrf
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

            <div class="mt-8">
                {{ $items->links() }}
            </div>

        </div>

    </div>

</div>

    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         class="fixed bottom-6 right-6 bg-green-600 text-white px-6 py-3 rounded-xl shadow-xl flex items-center gap-3 z-50">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <span class="font-semibold">{{ session('success') }}</span>
    </div>
    @endif

</x-app-layout>