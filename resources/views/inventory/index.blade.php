<x-app-layout>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8 px-4 sm:px-6 lg:px-8 font-sans">
        <div class="max-w-7xl mx-auto">
            <div class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                        <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                        Manage Inventory
                    </h1>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Add, edit, or remove parts from your catalog.</p>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 mb-8 relative overflow-hidden"
                 x-data="{
                    category: '',
                    brand: '',
                    sku: '',
                    loading: false,
                    generateSKU() {
                        if(!this.category || !this.brand) {
                            alert('Please enter Category and Brand first');
                            return;
                        }
                        const c = this.category.substring(0, 3).toUpperCase();
                        const b = this.brand.substring(0, 3).toUpperCase();
                        const r = Math.floor(1000 + Math.random() * 9000);
                        this.sku = `${c}-${b}-${r}`;
                    }
                 }">
                <div class="absolute top-0 left-0 w-1 h-full bg-indigo-500"></div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Add New Item
                </h2>
                <form method="POST" action="/inventory" @submit="loading = true">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="relative group">
                            <label for="item-sku" class="sr-only">SKU</label>
                            <input id="item-sku" name="sku" x-model="sku" placeholder="SKU (e.g. ENG-HON-001)" class="w-full border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 pr-10">
                            <button type="button" @click="generateSKU()" aria-label="Auto-generate SKU" class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-gray-400 hover:text-indigo-500 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-400 rounded" title="Generate SKU">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                            </button>
                        </div>
                        <div>
                            <label for="item-category" class="sr-only">Category</label>
                            <input id="item-category" name="category" x-model="category" list="inventory-category-suggestions" placeholder="Choose or type category" autocomplete="off" class="w-full border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                            <datalist id="inventory-category-suggestions">
                                @foreach($categories as $catOption)
                                    <option value="{{ $catOption }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                        <div>
                            <label for="item-part-name" class="sr-only">Part Name</label>
                            <input id="item-part-name" name="part_name" placeholder="Part Name" class="w-full border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                        </div>
                        <div>
                            <label for="item-brand" class="sr-only">Brand</label>
                            <select id="item-brand" name="brand" x-model="brand" class="w-full border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 searchable-select" required>
                                <option value="">Select Brand</option>
                                @foreach($brands as $b)
                                    <option value="{{ $b }}">{{ $b }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="item-model" class="sr-only">Motorcycle Model</label>
                            <input id="item-model" name="model" placeholder="Motorcycle Model" class="w-full border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                        </div>
                        <div>
                            <label for="item-quantity" class="sr-only">Quantity</label>
                            <input id="item-quantity" name="quantity" type="number" placeholder="Quantity" class="w-full border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                        </div>
                        <div>
                            <label for="item-price" class="sr-only">Price in Pesos</label>
                            <input id="item-price" name="price" type="number" step="0.01" placeholder="Price (₱)" class="w-full border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                        </div>
                        <div>
                            <label for="item-notes" class="sr-only">Notes (Optional)</label>
                            <input id="item-notes" name="notes" placeholder="Notes (Optional)" class="w-full border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <button :disabled="loading"
                                class="inline-flex items-center gap-2 bg-gray-900 dark:bg-indigo-600 text-white px-6 py-2.5 rounded-xl font-bold shadow-sm hover:bg-gray-800 dark:hover:bg-indigo-700 transition-colors active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-75 disabled:cursor-not-allowed">
                            <svg x-show="loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            Save Item to Catalog
                        </button>
                    </div>
                </form>
            </div>

            <!-- Filters & Search (Audit #20) -->
            <div class="mb-6 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-4">
                <form method="GET" action="/inventory" class="flex flex-col md:flex-row items-center justify-between gap-4">
                    <!-- Left side: Filters -->
                    <div class="flex items-center gap-3 w-full md:w-auto">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                        <select name="category" class="w-full md:w-48 border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500" onchange="this.form.submit()">
                            <option value="">All Categories</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}" {{ (isset($category) && $category == $cat) ? 'selected' : '' }}>{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <!-- Right side: Search -->
                    <div class="flex items-center gap-2 w-full md:w-auto">
                        <div class="relative flex-grow md:w-64">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                            <input type="text" name="search" class="w-full pl-9 pr-4 py-2.5 text-sm border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500" placeholder="Search products..." value="{{ $search ?? '' }}">
                        </div>
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-xl text-sm font-bold shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Search</button>
                        @if(!empty($search) || !empty($category))
                            <a href="/inventory" class="bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-4 py-2.5 rounded-xl text-sm font-bold shadow-sm hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors focus:outline-none">Clear</a>
                        @endif
                    </div>
                </form>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($items as $item)
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden hover:shadow-md transition-shadow relative p-5 flex flex-col justify-between">
                    <div>
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="font-bold text-gray-900 dark:text-white text-lg leading-tight">{{ $item->part_name }}</h3>
                            <div class="flex flex-col items-end gap-1">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 uppercase tracking-tight">{{ $item->sku ?? 'No SKU' }}</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300">{{ $item->category }}</span>
                            </div>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">{{ $item->brand }} • {{ $item->model }}</p>
                        @if($item->notes)
                            <p class="text-xs text-gray-400 dark:text-gray-500 italic mb-4 line-clamp-2">"{{ $item->notes }}"</p>
                        @endif
                    </div>
                    <div class="mt-auto">
                        <div class="flex justify-between items-end mb-4">
                            <div>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mb-0.5">Price</p>
                                <p class="font-extrabold text-gray-900 dark:text-white text-xl">₱{{ number_format($item->price, 2) }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-gray-400 dark:text-gray-500 mb-0.5">Stock</p>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ $item->quantity <= 0 ? 'bg-red-100 dark:bg-red-900/40 text-red-800 dark:text-red-300' : ($item->quantity <= ($item->low_stock_threshold ?? 5) ? 'bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300' : 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-300') }}">{{ $item->quantity }}</span>
                            </div>
                        </div>
                        <div class="flex gap-2 pt-4 border-t border-gray-100 dark:border-gray-700">
                            <a href="/inventory/{{ $item->id }}/edit" class="flex-1 text-center bg-gray-50 dark:bg-gray-700 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 font-semibold py-2 rounded-xl transition-colors text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">Edit</a>
                            <form method="POST" action="/inventory/{{ $item->id }}" class="flex-1" onsubmit="return confirm('Delete this item?');">
                                @csrf @method('DELETE')
                                <button class="w-full text-center bg-gray-50 dark:bg-gray-700 hover:bg-red-50 dark:hover:bg-red-900/30 text-gray-600 dark:text-gray-300 hover:text-red-600 dark:hover:text-red-400 font-semibold py-2 rounded-xl transition-colors text-sm focus:outline-none focus:ring-2 focus:ring-red-400">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-span-full bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-12 text-center">
                    <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-3 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    <p class="font-medium text-gray-600 dark:text-gray-400">No inventory items found.</p>
                    <p class="text-sm mt-1 text-gray-500 dark:text-gray-500">Try a different search or add a new item above.</p>
                </div>
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
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.searchable-select').forEach((el) => {
                new TomSelect(el, {
                    create: true, // Allow user to add a new brand if not in list
                    sortField: {
                        field: "text",
                        direction: "asc"
                    },
                    maxOptions: 50
                });
            });
        });
    </script>
</x-app-layout>
