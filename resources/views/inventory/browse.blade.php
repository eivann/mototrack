<x-app-layout>
<div x-data="browsePage()" class="px-4 sm:px-6 py-6 max-w-7xl mx-auto">

    <!-- HEADER / SEARCH BAR (Sticky) -->
    <div x-ref="browseHeader" class="mb-6 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 sticky top-16 md:top-20 lg:top-[88px] z-40 bg-white dark:bg-gray-900 -mx-4 sm:-mx-6 px-4 sm:px-6 pt-6 pb-4 -mt-6 border-b border-gray-100 dark:border-gray-800">
        <form method="GET" action="/browse" x-data="browseFilters()" @submit="loading = true" class="w-full sm:flex-1 flex gap-2">
            <label for="browse-search" class="sr-only">Search parts</label>
            <input
                id="browse-search"
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search parts..."
                list="browse-search-suggestions"
                autocomplete="off"
                @input.debounce.250ms="loadSuggestions('search', $event.target.value, 'browseSearch')"
                class="w-full border dark:border-gray-700 p-3 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500"
            >
            <input type="hidden" name="category" value="{{ request('category') }}">
            <input type="hidden" name="sort" value="{{ request('sort') }}">
            <input type="hidden" name="model" value="{{ request('model') }}">
            <datalist id="browse-search-suggestions">
                <template x-for="suggestion in browseSearch" :key="suggestion.field + '-' + suggestion.value">
                    <option :value="suggestion.value" :label="suggestion.field"></option>
                </template>
            </datalist>
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

        <!-- FILTER SIDEBAR (Sticky on Desktop, Fixed bottom sheet on Mobile) -->
        {{-- Desktop: pinned just below the sticky search bar (offset measured in browsePage()); it only
             scrolls itself when taller than the space left. Mobile: bottom sheet opened by "Filters". --}}
        <div id="filter-panel"
             :class="showFilters ? '' : 'hidden lg:block'"
             class="lg:col-span-3 lg:sticky lg:self-start z-30"
             x-show="showFilters || isDesktop"
             x-cloak>
            {{-- On mobile the panel is pinned to the viewport so it opens where the
                 user is already looking instead of above their current scroll position. --}}
            <div class="fixed inset-0 z-40 bg-black/40 lg:hidden" x-show="showFilters" @click="showFilters = false" x-cloak aria-hidden="true"></div>
            <form method="GET" action="/browse" x-data="browseFilters()" @submit="loading = true"
                  :class="showFilters ? 'fixed inset-x-0 bottom-0 z-50 max-h-[80vh] overflow-y-auto rounded-t-2xl' : ''"
                  class="browse-filter-form bg-white dark:bg-gray-800 border dark:border-gray-700 p-5 rounded-2xl shadow-sm lg:overflow-y-auto lg:!static lg:!inset-auto">
                <h2 class="font-bold text-gray-900 dark:text-white mb-4 uppercase tracking-wider text-xs flex items-center justify-between">Filters
                    <button type="button" @click="showFilters = false" class="lg:hidden -mr-1 p-2 rounded-xl text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500" aria-label="Close filters">✕</button>
                </h2>
                <input type="hidden" name="search" value="{{ request('search') }}">

                <!-- CATEGORY -->
                <div class="mb-5">
                    <label for="filter-category" class="block mb-1.5 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Category</label>
                    <select id="filter-category" name="category"
                            class="w-full border-gray-200 dark:border-gray-600 p-2.5 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                            @if(is_string($cat) && !empty($cat))
                                <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                            @endif
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

                <!-- COMPATIBILITY -->
                <div class="mb-5 border-t border-gray-100 dark:border-gray-700 pt-5 mt-5">
                    <label for="filter-model" class="block mb-1.5 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        Compatibility Check
                    </label>
                    <p class="text-[10px] text-gray-400 dark:text-gray-500 mb-2 leading-tight font-medium">Enter your motorcycle model to filter compatible parts (e.g., TMX 125).</p>
                    <div class="relative">
                        <input
                            type="text"
                            id="filter-model"
                            name="model"
                            value="{{ request('model') }}"
                            placeholder="Type model (e.g. TMX 125)..."
                            list="filter-model-suggestions"
                            autocomplete="off"
                            @input.debounce.250ms="loadSuggestions('model', $event.target.value, 'modelSuggestions')"
                            class="w-full border-gray-200 dark:border-gray-600 p-2.5 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-medium text-sm"
                        >
                        <datalist id="filter-model-suggestions">
                            <template x-for="suggestion in modelSuggestions" :key="suggestion.value">
                                <option :value="suggestion.value"></option>
                            </template>
                        </datalist>
                    </div>
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
                @if(request()->hasAny(['search', 'category', 'sort', 'model']) && array_filter(request()->only(['search', 'category', 'sort', 'model'])))
                    <a href="{{ route('browse') }}" class="mt-3 flex items-center justify-center w-full text-sm font-bold text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-300 transition-colors">
                        Clear filters
                    </a>
                @endif
            </form>

        </div>

        <!-- PRODUCTS -->
        <div class="lg:col-span-9">
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">

            @forelse($items as $item)
                <div class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-xl shadow hover:shadow-lg transition p-4 flex flex-col group"
                     x-data="{ 
                        selectedId: '{{ $item->id }}', 
                        selectedPrice: {{ (float)($item->price ?? 0) }}, 
                        selectedStock: {{ $item->quantity }} 
                     }">
                    <x-product-image :item="$item" :eager="$loop->index < 3" sizes="(min-width: 1280px) 290px, (min-width: 640px) 45vw, 92vw" class="aspect-square w-full rounded-lg mb-4 border border-gray-100 dark:border-gray-800" img-class="p-2 group-hover:scale-105 transition-transform duration-500" />
                    <h3 class="font-semibold text-lg mb-1 text-gray-900 dark:text-white">{{ $item->part_name }}</h3>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">{{ $item->brand }} • {{ $item->model }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Category: {{ $item->category }}</p>
                    
                    <p class="text-xs text-gray-400 dark:text-gray-500">Stock: <span x-text="selectedStock"></span></p>
                    <p class="text-green-600 dark:text-green-400 font-bold mt-2 text-lg">₱<span x-text="selectedPrice.toFixed(2)"></span></p>

                    @if($item->variations && $item->variations->count() > 0)
                        <div class="mt-3">
                            <label class="sr-only">Select Variation</label>
                            <select x-on:change="
                                const opt = $event.target.options[$event.target.selectedIndex];
                                selectedId = opt.value;
                                selectedPrice = parseFloat(opt.dataset.price) || {{ (float)($item->price ?? 0) }};
                                selectedStock = parseInt(opt.dataset.stock) || 0;
                            " class="w-full text-sm border-gray-200 dark:border-gray-600 p-2 rounded-lg bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-blue-500 focus:border-blue-500">
                                <option value="{{ $item->id }}" data-price="{{ $item->price }}" data-stock="{{ $item->quantity }}">Base Option ({{ $item->quantity }} in stock)</option>
                                @foreach($item->variations as $var)
                                    <option value="{{ $var->id }}" data-price="{{ $var->price ?? $item->price }}" data-stock="{{ $var->quantity }}">
                                        {{ $var->variation_name }} ({{ $var->quantity }} in stock)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="mt-auto pt-4">
                        @if (auth()->check() && auth()->user()->role === 'owner')
                            {{-- ReDemo-2: owner/admin side is view-only; no cart or order actions. --}}
                            <p class="text-center text-xs font-semibold text-gray-400 dark:text-gray-500 py-3">View only</p>
                        @elseif (auth()->check())
                            <!-- Pre-order Button -->
                            <div x-show="selectedStock == 0" style="display: none;">
                                <a :href="'/preorder/' + selectedId"
                                   class="block text-center w-full bg-amber-500 text-white py-3 rounded-lg hover:bg-amber-600 font-semibold focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition-colors">
                                    Pre-order
                                </a>
                            </div>

                            <!-- Add to Cart Form -->
                            <div x-show="selectedStock > 0" style="display: none;">
                                <form method="POST" :action="'/cart/add/' + selectedId" x-data="{ loading: false }" @submit="loading = true">
                                    @csrf
                                    <button
                                        :disabled="loading"
                                        aria-label="Add to cart"
                                        class="w-full inline-flex items-center justify-center gap-2 bg-blue-600 text-white px-4 py-3 rounded-lg hover:bg-blue-700 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all disabled:opacity-75 disabled:cursor-not-allowed">
                                        <svg x-show="loading" class="animate-spin w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                        </svg>
                                        <span x-text="loading ? 'Adding...' : 'Add to Cart'"></span>
                                    </button>
                                </form>
                            </div>
                        @else
                            <a href="{{ route('login') }}"
                               class="block text-center w-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-sm font-semibold py-3 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 transition-colors">
                                Login to Order
                            </a>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-span-full py-12 text-center bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-8 shadow-sm">
                    <p class="text-gray-500 dark:text-gray-400 font-medium">No in-stock items found matching your criteria.</p>
                    <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">Looking for out-of-stock or special order parts?</p>
                    <a href="{{ route('preorder.parts') }}" class="inline-flex items-center gap-2 mt-4 px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-bold transition-all shadow-md">
                        Browse Pre-order Parts →
                    </a>
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
<script>
    function browsePage() {
        return {
            // showFilters = the mobile bottom sheet is open; isDesktop keeps the panel visible on lg+
            // and follows viewport changes (the panel used to stay hidden after resizing to desktop).
            showFilters: false,
            isDesktop: window.matchMedia('(min-width: 1024px)').matches,
            init() {
                const media = window.matchMedia('(min-width: 1024px)');
                const onChange = (event) => {
                    this.isDesktop = event.matches;
                    this.showFilters = false; // never carry an open bottom sheet across breakpoints
                };
                if (media.addEventListener) media.addEventListener('change', onChange); else media.addListener(onChange);

                // Offset the desktop filter panel by the real height of the sticky search bar.
                const header = this.$refs.browseHeader;
                const root = document.documentElement;
                const measure = () => {
                    if (header) root.style.setProperty('--browse-header-h', Math.round(header.getBoundingClientRect().height) + 'px');
                };
                measure();
                if (header && 'ResizeObserver' in window) new ResizeObserver(measure).observe(header);
                window.addEventListener('resize', measure, { passive: true });
            },
            browseSearch: [],
            suggestionTimers: {},
            loadSuggestions(field, value, target) {
                clearTimeout(this.suggestionTimers[target]);

                const query = (value || '').trim();
                if (query.length < 1) {
                    this[target] = [];
                    return;
                }

                this.suggestionTimers[target] = setTimeout(() => {
                    fetch(`{{ route('inventory.suggestions') }}?field=${encodeURIComponent(field)}&q=${encodeURIComponent(query)}`, {
                        headers: { 'Accept': 'application/json' }
                    })
                        .then(response => response.ok ? response.json() : [])
                        .then(data => { this[target] = data; })
                        .catch(() => { this[target] = []; });
                }, 180);
            }
        }
    }

    function browseFilters() {
        return {
            loading: false,
            modelSuggestions: [],
            suggestionTimers: {},
            loadSuggestions(field, value, target) {
                clearTimeout(this.suggestionTimers[target]);
                const query = (value || '').trim();
                if (query.length < 1) {
                    this[target] = [];
                    return;
                }
                this.suggestionTimers[target] = setTimeout(() => {
                    fetch(`{{ route('inventory.suggestions') }}?field=${encodeURIComponent(field)}&q=${encodeURIComponent(query)}`, {
                        headers: { 'Accept': 'application/json' }
                    })
                        .then(response => response.ok ? response.json() : [])
                        .then(data => { this[target] = data; })
                        .catch(() => { this[target] = []; });
                }, 180);
            }
        }
    }
</script>
<style>
    /* Desktop filter panel: below the sticky header + search bar, never under them. The fallback
       heights match the header (88px + border) and the search bar; browsePage() measures the real ones. */
    @media (min-width: 1024px) {
        #filter-panel {
            top: calc(var(--mt-nav-h, 89px) + var(--browse-header-h, 90px) + 1rem);
        }
        #filter-panel .browse-filter-form {
            max-height: calc(100vh - var(--mt-nav-h, 89px) - var(--browse-header-h, 90px) - 2rem);
            max-height: calc(100dvh - var(--mt-nav-h, 89px) - var(--browse-header-h, 90px) - 2rem);
        }
    }
</style>
</x-app-layout>
