<x-app-layout>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8 font-sans transition-colors duration-300">
        <div class="max-w-4xl mx-auto">
            
            <!-- Header -->
            <div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                        <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        Edit Inventory Item
                    </h1>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Update the details for {{ $item->part_name }}.</p>
                </div>
                <a href="/inventory" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 px-4 py-2 rounded-xl font-bold shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors flex items-center gap-2 w-fit">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Back to Inventory
                </a>
            </div>

            <!-- Form Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-8 relative overflow-hidden"
                 x-data="{ 
                    category: '{{ $item->category }}', 
                    brand: '{{ $item->brand }}', 
                    sku: '{{ $item->sku }}',
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
                
                <form method="POST" action="/inventory/{{ $item->id }}" @submit="loading = true" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    
                    <input type="hidden" name="expected_quantity" value="{{ $item->quantity }}">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <!-- SKU -->
                        <div>
                            <label for="item-sku" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">SKU (Stock Keeping Unit)</label>
                            <div class="relative">
                                <input id="item-sku" type="text" name="sku" x-model="sku" class="w-full border-gray-200 dark:border-gray-600 dark:bg-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-shadow pr-10" placeholder="e.g. ENG-HON-001">
                                <button type="button" @click="generateSKU()" aria-label="Auto-generate SKU" class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-gray-400 hover:text-indigo-500 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-400 rounded" title="Generate SKU">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                </button>
                            </div>
                        </div>

                        <!-- Category -->
                        <div>
                            <label for="item-category" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Category</label>
                            <input id="item-category" type="text" name="category" x-model="category" list="inventory-category-suggestions" autocomplete="off" class="w-full border-gray-200 dark:border-gray-600 dark:bg-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-shadow" required>
                            <datalist id="inventory-category-suggestions">
                                @foreach($categories as $category)
                                    <option value="{{ $category }}"></option>
                                @endforeach
                            </datalist>
                        </div>

                        <!-- Part Name -->
                        <div>
                            <label for="item-part-name" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Part Name</label>
                            <input id="item-part-name" type="text" name="part_name" value="{{ $item->part_name }}" class="w-full border-gray-200 dark:border-gray-600 dark:bg-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-shadow" required>
                        </div>

                        <!-- Brand -->
                        <div>
                            <label for="item-brand" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Brand</label>
                            <input id="item-brand" type="text" name="brand" x-model="brand" class="w-full border-gray-200 dark:border-gray-600 dark:bg-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-shadow" required>
                        </div>

                        <!-- Model -->
                        <div>
                            <label for="item-model" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Motorcycle Model</label>
                            <input id="item-model" type="text" name="model" value="{{ $item->model }}" class="w-full border-gray-200 dark:border-gray-600 dark:bg-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-shadow" required>
                        </div>

                        <!-- Quantity -->
                        <div>
                            <label for="item-quantity" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Quantity in Stock</label>
                            <input id="item-quantity" type="number" name="quantity" value="{{ $item->quantity }}" class="w-full border-gray-200 dark:border-gray-600 dark:bg-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-shadow" required min="0">
                        </div>

                        <!-- Price -->
                        <div>
                            <label for="item-price" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Price (₱)</label>
                            <input id="item-price" type="number" name="price" value="{{ $item->price }}" step="0.01" class="w-full border-gray-200 dark:border-gray-600 dark:bg-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-shadow" required min="0">
                        </div>

                        <!-- Product Image -->
                        <div class="md:col-span-2">
                            <label for="item-image" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Product Image (Optional)</label>
                            @if($item->image_path)
                                <div class="mb-3">
                                    <p class="text-xs text-gray-500 mb-1">Current Image:</p>
                                    <img src="{{ $item->image_url }}" alt="Current image" class="h-20 w-20 object-cover rounded-lg border border-gray-200 dark:border-gray-700">
                                </div>
                            @endif
                            <input id="item-image" type="file" name="image" accept="image/jpeg, image/png, image/webp" class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-900/50 dark:file:text-indigo-400 dark:hover:file:bg-indigo-900/70 transition-colors cursor-pointer">
                            <p class="text-[10px] text-gray-500 mt-1">Supported formats: JPG, PNG, WEBP. Max size: 2MB.</p>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="mb-8">
                        <label for="item-notes" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Notes (Optional)</label>
                        <textarea id="item-notes" name="notes" rows="3" class="w-full border-gray-200 dark:border-gray-600 dark:bg-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-shadow">{{ $item->notes }}</textarea>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-4 pt-6 border-t border-gray-100 dark:border-gray-700">
                        <a href="/inventory" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-gray-400 rounded">
                            Cancel
                        </a>
                        <button type="submit" :disabled="loading" class="bg-gray-900 dark:bg-indigo-600 text-white px-6 py-2.5 rounded-xl font-bold shadow-sm hover:bg-gray-800 dark:hover:bg-indigo-700 transition-colors active:scale-[0.98] disabled:opacity-75 disabled:cursor-not-allowed flex items-center justify-center gap-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            <svg x-show="loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            Update Item
                        </button>
                    </div>
                </form>

            </div>

            <!-- Variations Section -->
            @if(is_null($item->parent_id))
            <div class="mt-8 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-8">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Product Variations</h2>
                
                @if($item->variations->count() > 0)
                <div class="overflow-x-auto mb-6">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b dark:border-gray-700 text-sm text-gray-500">
                                <th class="py-2">Variation Name</th>
                                <th class="py-2">SKU</th>
                                <th class="py-2">Stock</th>
                                <th class="py-2">Price</th>
                                <th class="py-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($item->variations as $var)
                            <tr class="border-b dark:border-gray-700 last:border-0 text-sm text-gray-900 dark:text-gray-300">
                                <td class="py-3 font-semibold">{{ $var->variation_name }}</td>
                                <td class="py-3">{{ $var->sku }}</td>
                                <td class="py-3">{{ $var->quantity }}</td>
                                <td class="py-3">₱{{ number_format($var->price, 2) }}</td>
                                <td class="py-3 flex gap-2">
                                    <a href="/inventory/{{ $var->id }}/edit" class="text-indigo-600 hover:underline">Edit</a>
                                    <form method="POST" action="/inventory/{{ $var->id }}" onsubmit="return confirm('Delete variation?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p class="text-sm text-gray-500 mb-6">No variations added yet.</p>
                @endif

                <!-- Add Variation Form -->
                <div class="bg-gray-50 dark:bg-gray-900/50 p-6 rounded-xl border border-gray-200 dark:border-gray-700">
                    <h3 class="text-md font-semibold mb-4 text-gray-800 dark:text-gray-200">Add New Variation</h3>
                    <form method="POST" action="/inventory">
                        @csrf
                        <input type="hidden" name="parent_id" value="{{ $item->id }}">
                        <input type="hidden" name="category" value="{{ $item->category }}">
                        <input type="hidden" name="brand" value="{{ $item->brand }}">
                        <input type="hidden" name="model" value="{{ $item->model }}">
                        <input type="hidden" name="part_name" value="{{ $item->part_name }}">
                        
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Variation Name</label>
                                <input type="text" name="variation_name" placeholder="e.g. Red, Size L" class="w-full text-sm border-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg" required>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">SKU (Optional)</label>
                                <input type="text" name="sku" placeholder="e.g. VAR-001" class="w-full text-sm border-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Quantity</label>
                                <input type="number" name="quantity" class="w-full text-sm border-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg" required min="0" value="0">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Price (₱)</label>
                                <input type="number" name="price" step="0.01" class="w-full text-sm border-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg" required min="0" value="{{ (float)$item->price }}">
                            </div>
                        </div>
                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-indigo-700 transition-colors">
                            Add Variation
                        </button>
                    </form>
                </div>
            </div>
            @endif

        </div>
    </div>
</x-app-layout>
