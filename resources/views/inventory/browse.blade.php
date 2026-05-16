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
                class="w-full border dark:border-gray-700 p-3 rounded-lg shadow-sm focus:ring focus:ring-blue-200 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500"
            >
            <button class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 shadow">
                Search
            </button>
        </form>

        <a href="/cart" class="w-full md:w-auto flex items-center justify-center gap-2 bg-white dark:bg-gray-800 border-2 border-blue-600 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 px-6 py-2.5 rounded-lg shadow font-bold transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            View Cart
            @if(session()->has('cart') && count(session('cart')) > 0)
                <span class="bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full ml-1">
                    {{ count(session('cart')) }}
                </span>
            @endif
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- FILTER SIDEBAR -->
        <form method="GET" action="/browse" class="lg:col-span-3 bg-white dark:bg-gray-800 border dark:border-gray-700 p-5 rounded-2xl shadow-sm h-fit lg:sticky top-24">
            <h2 class="font-bold text-gray-900 dark:text-white mb-4 uppercase tracking-wider text-xs">Filters</h2>
            <input type="hidden" name="search" value="{{ request('search') }}">

            <!-- CATEGORY -->
            <div class="mb-5">
                <label class="block mb-1.5 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Category</label>
                <select name="category" class="w-full border-gray-200 dark:border-gray-600 p-2.5 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-blue-500">
                    <option value="">All Categories</option>
                    <option value="engine" {{ request('category')=='engine'?'selected':'' }}>Engine</option>
                    <option value="brakes" {{ request('category')=='brakes'?'selected':'' }}>Brakes</option>
                    <option value="electrical" {{ request('category')=='electrical'?'selected':'' }}>Electrical</option>
                    <option value="drivetrain" {{ request('category')=='drivetrain'?'selected':'' }}>Drivetrain</option>
                    <option value="accessories" {{ request('category')=='accessories'?'selected':'' }}>Accessories</option>
                </select>
            </div>

            <!-- SORT -->
            <div class="mb-5">
                <label class="block mb-1.5 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Sort By</label>
                <select name="sort" class="w-full border-gray-200 dark:border-gray-600 p-2.5 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-blue-500">
                    <option value="">Default</option>
                    <option value="low_high" {{ request('sort')=='low_high'?'selected':'' }}>Price Low → High</option>
                    <option value="high_low" {{ request('sort')=='high_low'?'selected':'' }}>Price High → Low</option>
                </select>
            </div>

            <button class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-xl font-bold shadow-lg shadow-blue-200 dark:shadow-none transition-all active:scale-95">
                Apply Filters
            </button>
        </form>

        <!-- PRODUCTS -->
        <div class="lg:col-span-9">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">

            @forelse($items as $item)
                <div class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-xl shadow hover:shadow-lg transition p-4">
                    <h3 class="font-semibold text-lg mb-1 text-gray-900 dark:text-white">{{ $item->part_name }}</h3>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">{{ $item->brand }} • {{ $item->model }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Category: {{ $item->category }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">Stock: {{ $item->quantity }}</p>
                    <p class="text-green-600 dark:text-green-400 font-bold mt-2 text-lg">₱{{ number_format($item->price, 2) }}</p>

                    @auth
                        <form method="POST" action="/cart/add/{{ $item->id }}" class="mt-3">
                            @csrf
                            @if($item->quantity == 0)
                                <a href="/preorder/{{ $item->id }}" class="btn btn-warning w-full block text-center bg-amber-500 text-white py-2 rounded-lg hover:bg-amber-600">Pre-order</a>
                            @else
                                <button class="btn btn-primary w-full bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Add to Cart</button>
                            @endif
                        </form>
                    @else
                        <div class="mt-3">
                            <a href="{{ route('login') }}" class="block text-center w-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-sm font-semibold py-2 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">Login to Order</a>
                        </div>
                    @endauth
                </div>
            @empty
                <p class="text-gray-500 dark:text-gray-400">No items found.</p>
            @endforelse

            </div>

            <div class="mt-8">
                <div class="mb-4 text-[11px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 md:hidden text-center">
                    Showing {{ $items->firstItem() }} - {{ $items->lastItem() }} of {{ $items->total() }} items
                    <span class="block mt-1 text-indigo-600 dark:text-indigo-400">Page {{ $items->currentPage() }} of {{ $items->lastPage() }}</span>
                </div>
                {{ $items->links() }}
            </div>
        </div>

    </div>

</div>

</x-app-layout>