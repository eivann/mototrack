<x-app-layout>
    <div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8 font-sans">
        <div class="max-w-4xl mx-auto">
            
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight flex items-center gap-3">
                        <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        Edit Inventory Item
                    </h1>
                    <p class="mt-2 text-sm text-gray-500">Update the details for {{ $item->part_name }}.</p>
                </div>
                <a href="/inventory" class="bg-white border border-gray-200 text-gray-600 px-4 py-2 rounded-xl font-bold shadow-sm hover:bg-gray-50 transition-colors flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Back to Inventory
                </a>
            </div>

            <!-- Form Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 relative overflow-hidden">
                <div class="absolute top-0 left-0 w-1 h-full bg-indigo-500"></div>
                
                <form method="POST" action="/inventory/{{ $item->id }}">
                    @csrf
                    @method('PUT')
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <!-- Category -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Category</label>
                            <input type="text" name="category" value="{{ $item->category }}" class="w-full border-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 transition-shadow" required>
                        </div>

                        <!-- Part Name -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Part Name</label>
                            <input type="text" name="part_name" value="{{ $item->part_name }}" class="w-full border-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 transition-shadow" required>
                        </div>

                        <!-- Brand -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Brand</label>
                            <input type="text" name="brand" value="{{ $item->brand }}" class="w-full border-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 transition-shadow" required>
                        </div>

                        <!-- Model -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Motorcycle Model</label>
                            <input type="text" name="model" value="{{ $item->model }}" class="w-full border-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 transition-shadow" required>
                        </div>

                        <!-- Quantity -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Quantity in Stock</label>
                            <input type="number" name="quantity" value="{{ $item->quantity }}" class="w-full border-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 transition-shadow" required min="0">
                        </div>

                        <!-- Price -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Price (₱)</label>
                            <input type="number" name="price" value="{{ $item->price }}" step="0.01" class="w-full border-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 transition-shadow" required min="0">
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="mb-8">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Notes (Optional)</label>
                        <textarea name="notes" rows="3" class="w-full border-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 transition-shadow">{{ $item->notes }}</textarea>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-4 pt-6 border-t border-gray-100">
                        <a href="/inventory" class="text-gray-500 hover:text-gray-700 font-medium transition-colors">
                            Cancel
                        </a>
                        <button type="submit" class="bg-indigo-600 text-white px-8 py-3 rounded-xl font-bold shadow-md hover:bg-indigo-700 hover:shadow-lg transition-all active:scale-[0.98] flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Save Changes
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>