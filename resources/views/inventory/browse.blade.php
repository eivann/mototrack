<x-app-layout>
<div x-data="{ showFilters: window.innerWidth >= 1024 }" class="px-4 sm:px-6 py-6 max-w-7xl mx-auto">

    <!-- HEADER / SEARCH BAR -->
    <div class="mb-6 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
        <form method="GET" action="/browse" x-data="{ loading: false }" @submit="loading = true" class="w-full sm:flex-1 flex gap-2">
            <label for="browse-search" class="sr-only">Search parts</label>
            <input
                id="browse-search"
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search parts..."
                class="w-full border dark:border-gray-700 p-3 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500"
            >
            <button
                :disabled="loading"
                class="inline-flex items-center gap-2 bg-blue-600 text-white px-5 py-2.5 rounded-lg hover:bg-blue-700 shadow font-semibold transition-all focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-75 disabled:cursor-not-allowed shrink-0"
                aria-label="Search">
                <svg x-show="loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span>Search</span>
            </button>
        </form>

        <div class="flex items-center gap-2">
            <!-- Mobile filter toggle (hidden on lg+) -->
            <button
                @click="showFilters = !showFilters"
                :aria-expanded="showFilters"
                aria-controls="filter-panel"
                class="lg:hidden inline-flex items-center gap-2 bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 hover:border-blue-500 hover:text-blue-600 px-4 py-2.5 rounded-lg font-bold transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
                </svg>
                <span x-text="showFilters ? 'Hide Filters' : 'Filters'"></span>
            </button>

            <a href="/cart"
               aria-label="View cart{{ session()->has('cart') && count(session('cart')) > 0 ? ', ' . count(session('cart')) . ' items' : '' }}"
               class="inline-flex items-center justify-center gap-2 bg-white dark:bg-gray-800 border-2 border-blue-600 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 px-5 py-2.5 rounded-lg shadow font-bold transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span>View Cart</span>
                @if(session()->has('cart') && count(session('cart')) > 0)
                    <span class="bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full" aria-hidden="true">
                        {{ count(session('cart')) }}
                    </span>
                @endif
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- FILTER SIDEBAR -->
        <div id="filter-panel"
             :class="showFilters ? '' : 'hidden lg:block'"
             class="lg:col-span-3">
            <form method="GET" action="/browse" x-data="{ loading: false }" @submit="loading = true"
                  class="bg-white dark:bg-gray-800 border dark:border-gray-700 p-5 rounded-2xl shadow-sm h-fit lg:sticky top-24">
                <h2 class="font-bold text-gray-900 dark:text-white mb-4 uppercase tracking-wider text-xs">Filters</h2>
                <input type="hidden" name="search" value="{{ request('search') }}">

                <!-- CATEGORY -->
                <div class="mb-5">
                    <label for="filter-category" class="block mb-1.5 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Category</label>
                    <select id="filter-category" name="category"
                            class="w-full border-gray-200 dark:border-gray-600 p-2.5 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- SORT -->
                <div class="mb-5">
                    <label for="filter-sort" class="block mb-1.5 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Sort By</label>
                    <select id="filter-sort" name="sort"
                            class="w-full border-gray-200 dark:border-gray-600 p-2.5 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Default</option>
                        <option value="low_high" {{ request('sort')=='low_high'?'selected':'' }}>Price Low → High</option>
                        <option value="high_low" {{ request('sort')=='high_low'?'selected':'' }}>Price High → Low</option>
                    </select>
                </div>

                <button
                    :disabled="loading"
                    class="w-full inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-xl font-bold shadow-lg shadow-blue-200 dark:shadow-none transition-all active:scale-95 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-75 disabled:cursor-not-allowed">
                    <svg x-show="loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    Apply Filters
                </button>
            </form>
        </div>

        <!-- PRODUCTS -->
        <div class="lg:col-span-9">
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">

            @forelse($items as $item)
                <div class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-xl shadow hover:shadow-lg transition p-4 flex flex-col group">
                    <div class="aspect-square w-full rounded-lg overflow-hidden mb-4 bg-gray-50 dark:bg-gray-900/50 border border-gray-100 dark:border-gray-800">
                        <img src="{{ $item->image_url }}" alt="{{ $item->part_name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    </div>
                    <h3 class="font-semibold text-lg mb-1 text-gray-900 dark:text-white">{{ $item->part_name }}</h3>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">{{ $item->brand }} • {{ $item->model }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Category: {{ $item->category }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">Stock: {{ $item->quantity }}</p>
                    <p class="text-green-600 dark:text-green-400 font-bold mt-2 text-lg">₱{{ number_format($item->price, 2) }}</p>

                    <div class="mt-auto pt-3">
                        @auth
                            @if($item->quantity == 0)
                                <a href="/preorder/{{ $item->id }}"
                                   class="block text-center w-full bg-amber-500 text-white py-3 rounded-lg hover:bg-amber-600 font-semibold focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition-colors">
                                    Pre-order
                                </a>
                            @else
                                <form method="POST" action="/cart/add/{{ $item->id }}" x-data="{ loading: false }" @submit="loading = true">
                                    @csrf
                                    <button
                                        :disabled="loading"
                                        aria-label="Add {{ $item->part_name }} to cart"
                                        class="w-full inline-flex items-center justify-center gap-2 bg-blue-600 text-white px-4 py-3 rounded-lg hover:bg-blue-700 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all disabled:opacity-75 disabled:cursor-not-allowed">
                                        <svg x-show="loading" class="animate-spin w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                        </svg>
                                        <span x-text="loading ? 'Adding...' : 'Add to Cart'"></span>
                                    </button>
                                </form>
                            @endif
                        @else
                            <a href="{{ route('login') }}"
                               class="block text-center w-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-sm font-semibold py-3 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 transition-colors">
                                Login to Order
                            </a>
                        @endauth
                    </div>
                </div>
            @empty
                <div class="col-span-full py-12 text-center">
                    <p class="text-gray-500 dark:text-gray-400">No items found matching your criteria.</p>
                </div>
            @endforelse

            </div>

            <div class="mt-8">
                <div class="mb-4 text-[11px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 md:hidden text-center" aria-live="polite">
                    Showing {{ $items->firstItem() }} - {{ $items->lastItem() }} of {{ $items->total() }} items
                    <span class="block mt-1 text-indigo-600 dark:text-indigo-400">Page {{ $items->currentPage() }} of {{ $items->lastPage() }}</span>
                </div>
                {{ $items->links() }}
            </div>
        </div>

    </div>

</div>
</x-app-layout>