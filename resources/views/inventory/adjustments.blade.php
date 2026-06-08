<x-app-layout>
<div class="min-h-screen bg-gray-50 dark:bg-gray-950 py-10 px-4" x-data="{ 
    activeTab: 'damaged', 
    showDamageModal: {{ $errors->any() && old('action_taken') === null ? 'true' : 'false' }}, 
    showReturnModal: {{ $errors->any() && old('action_taken') !== null ? 'true' : 'false' }} 
}">
    <div class="max-w-7xl mx-auto space-y-8">
        
        <!-- Header -->
        <div class="flex justify-between items-center bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 p-8 rounded-[2.5rem] shadow-sm">
            <div>
                <h2 class="text-2xl font-black text-gray-900 dark:text-white italic">Inventory Adjustments</h2>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mt-1">Record and track damaged items and customer returns</p>
            </div>
            <div class="flex gap-4">
                <button @click="showDamageModal = true" class="bg-red-600 hover:bg-red-700 text-white font-black text-xs uppercase tracking-widest px-6 py-3 rounded-xl shadow-lg transition-transform active:scale-95">
                    Log Damage
                </button>
                <button @click="showReturnModal = true" class="bg-indigo-600 hover:bg-indigo-700 text-white font-black text-xs uppercase tracking-widest px-6 py-3 rounded-xl shadow-lg transition-transform active:scale-95">
                    Log Return
                </button>
            </div>
        </div>

        @if(session('success'))
            <div class="p-4 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 font-bold rounded-2xl border border-emerald-100 dark:border-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        <!-- Navigation Tabs -->
        <div class="flex gap-4 border-b border-gray-200 dark:border-gray-800">
            <button @click="activeTab = 'damaged'" :class="activeTab === 'damaged' ? 'border-red-600 text-red-600' : 'border-transparent text-gray-400'" class="pb-4 px-2 font-black text-xs uppercase tracking-widest border-b-2 transition-all">
                Damaged Items
            </button>
            <button @click="activeTab = 'returns'" :class="activeTab === 'returns' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-400'" class="pb-4 px-2 font-black text-xs uppercase tracking-widest border-b-2 transition-all">
                Product Returns
            </button>
        </div>

        <!-- Damaged Items List -->
        <div x-show="activeTab === 'damaged'" class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 overflow-hidden shadow-sm">
            <div class="p-8 border-b border-gray-100 dark:border-gray-800">
                <h3 class="text-lg font-black text-gray-900 dark:text-white italic">Damaged Items Log</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800/50 text-[10px] font-black text-gray-400 uppercase tracking-widest">
                            <th class="p-6">Date</th>
                            <th class="p-6">Part Name</th>
                            <th class="p-6">Quantity</th>
                            <th class="p-6">Notes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                        @forelse($damagedItems as $item)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                                <td class="p-6 text-sm text-gray-500 font-bold">{{ $item->created_at->format('M d, Y H:i') }}</td>
                                <td class="p-6 font-bold text-gray-900 dark:text-white">{{ $item->item->part_name }}</td>
                                <td class="p-6"><span class="px-2.5 py-1 rounded-lg bg-red-50 text-red-600 text-xs font-black">{{ $item->quantity }} Units</span></td>
                                <td class="p-6 text-sm text-gray-500 max-w-xs truncate">{{ $item->notes ?? 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-12 text-center text-gray-400 font-bold italic">No damaged items registered.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($damagedItems->hasPages())
                <div class="p-6 border-t border-gray-50 dark:border-gray-800">{{ $damagedItems->links() }}</div>
            @endif
        </div>

        <!-- Product Returns List -->
        <div x-show="activeTab === 'returns'" class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 overflow-hidden shadow-sm" x-cloak>
            <div class="p-8 border-b border-gray-100 dark:border-gray-800">
                <h3 class="text-lg font-black text-gray-900 dark:text-white italic">Product Returns Log</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800/50 text-[10px] font-black text-gray-400 uppercase tracking-widest">
                            <th class="p-6">Date</th>
                            <th class="p-6">Part Name</th>
                            <th class="p-6">Customer</th>
                            <th class="p-6">Quantity</th>
                            <th class="p-6">Action Taken</th>
                            <th class="p-6">Notes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                        @forelse($productReturns as $item)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                                <td class="p-6 text-sm text-gray-500 font-bold">{{ $item->created_at->format('M d, Y H:i') }}</td>
                                <td class="p-6 font-bold text-gray-900 dark:text-white">{{ $item->item->part_name }}</td>
                                <td class="p-6 text-sm text-gray-500 font-bold">{{ $item->customer->name ?? 'Walk-in' }}</td>
                                <td class="p-6"><span class="px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-600 text-xs font-black">{{ $item->quantity }} Units</span></td>
                                <td class="p-6">
                                    <span class="px-2.5 py-1 rounded-lg text-xs font-black {{ $item->action_taken === 'restocked' ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600' }}">
                                        {{ ucfirst($item->action_taken) }}
                                    </span>
                                </td>
                                <td class="p-6 text-sm text-gray-500 max-w-xs truncate">{{ $item->notes ?? 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-12 text-center text-gray-400 font-bold italic">No returns registered.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($productReturns->hasPages())
                <div class="p-6 border-t border-gray-50 dark:border-gray-800">{{ $productReturns->links() }}</div>
            @endif
        </div>

    </div>

    <!-- Log Damage Modal -->
    <div x-show="showDamageModal" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-black/55 backdrop-blur-sm" x-cloak>
        <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 max-w-md w-full rounded-3xl p-8 shadow-2xl relative">
            <h3 class="text-xl font-black text-gray-900 dark:text-white italic mb-6">Log Damaged Stock</h3>
            <form method="POST" action="{{ route('inventory.adjustments.store_damaged') }}">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Select Item</label>
                        <select name="inventory_item_id" required class="w-full border border-gray-200 dark:border-gray-800 rounded-xl px-4 py-3 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white outline-none">
                            @foreach($inventoryItems as $item)
                                <option value="{{ $item->id }}" {{ old('inventory_item_id') == $item->id && old('action_taken') === null ? 'selected' : '' }}>{{ $item->part_name }} (Stock: {{ $item->quantity }})</option>
                            @endforeach
                        </select>
                        @if($errors->any() && old('action_taken') === null)
                            @error('inventory_item_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        @endif
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Quantity</label>
                        <input type="number" name="quantity" min="1" value="{{ old('action_taken') === null ? old('quantity') : '' }}" required class="w-full border border-gray-200 dark:border-gray-800 rounded-xl px-4 py-3 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white outline-none">
                        @if($errors->any() && old('action_taken') === null)
                            @error('quantity') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        @endif
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Notes / Reason</label>
                        <textarea name="notes" rows="3" class="w-full border border-gray-200 dark:border-gray-800 rounded-xl px-4 py-3 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white outline-none">{{ old('action_taken') === null ? old('notes') : '' }}</textarea>
                        @if($errors->any() && old('action_taken') === null)
                            @error('notes') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        @endif
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-8">
                    <button type="button" @click="showDamageModal = false" class="px-5 py-3 border border-gray-200 dark:border-gray-850 rounded-xl font-bold text-xs uppercase tracking-widest text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-800">Cancel</button>
                    <button type="submit" class="px-5 py-3 bg-red-600 hover:bg-red-700 text-white font-bold text-xs uppercase tracking-widest rounded-xl shadow-lg">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Log Return Modal -->
    <div x-show="showReturnModal" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-black/55 backdrop-blur-sm" x-cloak>
        <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 max-w-md w-full rounded-3xl p-8 shadow-2xl relative">
            <h3 class="text-xl font-black text-gray-900 dark:text-white italic mb-6">Log Product Return</h3>
            <form method="POST" action="{{ route('inventory.adjustments.store_return') }}">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Select Item</label>
                        <select name="inventory_item_id" required class="w-full border border-gray-200 dark:border-gray-800 rounded-xl px-4 py-3 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white outline-none">
                            @foreach($inventoryItems as $item)
                                <option value="{{ $item->id }}" {{ old('inventory_item_id') == $item->id && old('action_taken') !== null ? 'selected' : '' }}>{{ $item->part_name }}</option>
                            @endforeach
                        </select>
                        @if($errors->any() && old('action_taken') !== null)
                            @error('inventory_item_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        @endif
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Customer (Optional)</label>
                        <select name="customer_id" class="w-full border border-gray-200 dark:border-gray-800 rounded-xl px-4 py-3 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white outline-none">
                            <option value="">Walk-in</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" {{ old('customer_id') == $c->id && old('action_taken') !== null ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                        @if($errors->any() && old('action_taken') !== null)
                            @error('customer_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        @endif
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Quantity</label>
                        <input type="number" name="quantity" min="1" value="{{ old('action_taken') !== null ? old('quantity') : '' }}" required class="w-full border border-gray-200 dark:border-gray-800 rounded-xl px-4 py-3 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white outline-none">
                        @if($errors->any() && old('action_taken') !== null)
                            @error('quantity') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        @endif
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Action Taken</label>
                        <select name="action_taken" required class="w-full border border-gray-200 dark:border-gray-800 rounded-xl px-4 py-3 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white outline-none">
                            <option value="restocked" {{ old('action_taken') == 'restocked' ? 'selected' : '' }}>Put back in Inventory (Restock)</option>
                            <option value="discarded" {{ old('action_taken') == 'discarded' ? 'selected' : '' }}>Mark Damaged/Discard (Do not Restock)</option>
                        </select>
                        @if($errors->any() && old('action_taken') !== null)
                            @error('action_taken') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        @endif
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Notes / Reason</label>
                        <textarea name="notes" rows="3" class="w-full border border-gray-200 dark:border-gray-800 rounded-xl px-4 py-3 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white outline-none">{{ old('action_taken') !== null ? old('notes') : '' }}</textarea>
                        @if($errors->any() && old('action_taken') !== null)
                            @error('notes') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        @endif
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-8">
                    <button type="button" @click="showReturnModal = false" class="px-5 py-3 border border-gray-200 dark:border-gray-850 rounded-xl font-bold text-xs uppercase tracking-widest text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-800">Cancel</button>
                    <button type="submit" class="px-5 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs uppercase tracking-widest rounded-xl shadow-lg">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
</x-app-layout>
