<x-app-layout>
<div class="min-h-screen bg-gray-50 dark:bg-gray-950 py-10 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto space-y-8">

        <!-- Header Card -->
        <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 p-8 sm:p-10 rounded-[2.5rem] shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 text-[10px] font-black uppercase tracking-widest mb-3">
                    <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                    Advance Reservations
                </div>
                <h1 class="text-3xl sm:text-4xl font-black text-gray-900 dark:text-white italic tracking-tight">Pre-Order Unavailable Parts</h1>
                <p class="text-xs sm:text-sm font-bold text-gray-400 uppercase tracking-widest mt-2">These catalog items are currently awaiting stock. Reserve yours in advance — pay only upon physical pickup!</p>
            </div>
            <div class="flex items-center gap-3 shrink-0">
                <a href="{{ url('/preorder-custom') }}" class="inline-flex items-center gap-2 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white font-black text-xs uppercase tracking-widest px-6 py-4 rounded-2xl shadow-lg shadow-indigo-500/20 transition-all hover:scale-105 active:scale-95">
                    <i data-lucide="plus-circle" class="w-4 h-4"></i>
                    Request Custom Part
                </a>
            </div>
        </div>

        <!-- Parts Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @forelse($items as $item)
                <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-3xl p-6 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between group">
                    <div>
                        <div class="aspect-square w-full rounded-2xl bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-800 mb-4 flex items-center justify-center overflow-hidden relative">
                            @if($item->image)
                                <img src="{{ Storage::url($item->image) }}" alt="{{ $item->part_name }}" class="w-full h-full object-contain p-4 group-hover:scale-105 transition-transform duration-300">
                            @else
                                <div class="text-center p-4">
                                    <i data-lucide="package" class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-2"></i>
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Catalog Part</span>
                                </div>
                            @endif
                            <span class="absolute top-3 right-3 px-2.5 py-1 rounded-xl text-[10px] font-black uppercase tracking-wider bg-red-50 text-red-600 dark:bg-red-900/30 dark:text-red-400 border border-red-100 dark:border-red-800">
                                Out of Stock
                            </span>
                        </div>

                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-2 py-0.5 rounded-md bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 text-[10px] font-black uppercase tracking-widest">{{ $item->category->name ?? 'Part' }}</span>
                            @if($item->brand)
                                <span class="px-2 py-0.5 rounded-md bg-gray-100 dark:bg-gray-800 text-gray-500 text-[10px] font-bold uppercase tracking-wider">{{ $item->brand->name ?? $item->brand }}</span>
                            @endif
                        </div>

                        <h3 class="font-black text-gray-900 dark:text-white text-base leading-snug line-clamp-2 mb-1">{{ $item->part_name }}</h3>
                        <p class="text-xs text-gray-400 font-medium mb-3">Model: <span class="font-bold text-gray-600 dark:text-gray-300">{{ $item->model ?? 'Universal' }}</span></p>
                    </div>

                    <div class="pt-4 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between gap-3">
                        <div>
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Est. Price</span>
                            <span class="text-lg font-black text-emerald-600 dark:text-emerald-400">₱{{ number_format($item->price, 2) }}</span>
                        </div>
                        <a href="{{ url('/preorder/' . $item->id) }}" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-black text-xs uppercase tracking-widest shadow-md shadow-amber-500/20 transition-all hover:scale-105 active:scale-95">
                            <i data-lucide="clipboard-list" class="w-4 h-4"></i>
                            Pre-Order
                        </a>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-20 text-center bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 p-8">
                    <div class="w-16 h-16 bg-gray-50 dark:bg-gray-800 rounded-2xl flex items-center justify-center mx-auto mb-4 text-gray-400">
                        <i data-lucide="check-circle" class="w-8 h-8 text-emerald-500"></i>
                    </div>
                    <h3 class="text-lg font-black text-gray-900 dark:text-white italic">All Catalog Items Are Currently In Stock!</h3>
                    <p class="text-gray-400 text-xs font-bold uppercase tracking-widest mt-2 max-w-md mx-auto">There are no out-of-stock catalog items waiting for pre-order right now. You can browse the active catalog or submit a custom request.</p>
                    <div class="mt-6 flex justify-center gap-3">
                        <a href="{{ route('browse') }}" class="px-5 py-3 rounded-xl bg-indigo-600 text-white text-xs font-black uppercase tracking-widest hover:bg-indigo-700 transition-colors">Browse Stocked Parts</a>
                        <a href="{{ url('/preorder-custom') }}" class="px-5 py-3 rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-xs font-black uppercase tracking-widest hover:bg-gray-200 transition-colors">Custom Request</a>
                    </div>
                </div>
            @endforelse
        </div>

    </div>
</div>
</x-app-layout>