<x-app-layout>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8 px-4 sm:px-6 lg:px-8 font-sans">
        <div class="max-w-7xl mx-auto">

            @if(session('success'))
                <div class="mb-6 rounded-2xl bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 p-4 text-sm font-bold text-emerald-800 dark:text-emerald-300" role="status">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-6 rounded-2xl bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 p-4 text-sm font-bold text-red-800 dark:text-red-300" role="alert">
                    <ul class="list-disc ml-5 space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                        <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        Motorcycle Catalogue
                    </h1>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Maintain the supported motorcycle makes and models. These appear in the customer compatibility dropdowns even before any part is linked. Adding a motorcycle here does not make any product compatible with it.</p>
                </div>
                <a href="{{ route('compatibility.manage') }}" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 px-4 py-2 rounded-xl font-bold shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors flex items-center gap-2 w-fit">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Compatibility Records
                </a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Add form -->
                <div class="lg:col-span-1">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Add makes &amp; models</h2>
                        <form method="POST" action="{{ route('motorcycles.catalogue.store') }}" class="space-y-4">
                            @csrf
                            <div>
                                <label for="brand" class="block text-xs font-bold text-gray-600 dark:text-gray-300 mb-1">Make / Brand</label>
                                <input id="brand" name="brand" type="text" required maxlength="100" value="{{ old('brand') }}" placeholder="e.g. Honda"
                                       class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label for="model" class="block text-xs font-bold text-gray-600 dark:text-gray-300 mb-1">Model(s)</label>
                                <textarea id="model" name="model" rows="3" required maxlength="600" placeholder="e.g. TMX 125, TMX 155, Click 125"
                                          class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('model') }}</textarea>
                                <p class="mt-1 text-[11px] text-gray-400">Separate several models with commas, semicolons or new lines.</p>
                            </div>
                            <div>
                                <label for="year" class="block text-xs font-bold text-gray-600 dark:text-gray-300 mb-1">Year <span class="font-normal text-gray-400">(optional)</span></label>
                                <input id="year" name="year" type="text" maxlength="20" value="{{ old('year') }}" placeholder="e.g. 2020 or 2018-2022"
                                       class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <p class="mt-1 text-[11px] text-gray-400">Leave blank to apply to all years of the model.</p>
                            </div>
                            <div>
                                <label for="engine_type" class="block text-xs font-bold text-gray-600 dark:text-gray-300 mb-1">Engine / Variant <span class="font-normal text-gray-400">(optional)</span></label>
                                <input id="engine_type" name="engine_type" type="text" maxlength="100" value="{{ old('engine_type') }}" placeholder="e.g. Carburetor"
                                       class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm px-6 py-2.5 rounded-xl shadow-sm transition-colors">
                                Add to catalogue
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Catalogue list -->
                <div class="lg:col-span-2">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                        <form method="GET" action="{{ route('motorcycles.catalogue') }}" class="mb-6 flex flex-col sm:flex-row gap-3">
                            <input type="text" name="search" value="{{ $search }}" placeholder="Search make or model..."
                                   class="flex-1 rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-xl font-bold text-sm transition-colors">Search</button>
                            @if($search !== '')
                                <a href="{{ route('motorcycles.catalogue') }}" class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 px-6 py-2.5 rounded-xl font-bold text-sm hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors text-center">Clear</a>
                            @endif
                        </form>

                        @forelse($groups as $brand => $rows)
                            <div class="mb-6 last:mb-0">
                                <h3 class="text-sm font-extrabold text-gray-900 dark:text-white uppercase tracking-wide mb-2">{{ $brand }}</h3>
                                <div class="divide-y divide-gray-100 dark:divide-gray-700 border border-gray-100 dark:border-gray-700 rounded-xl overflow-hidden">
                                    @foreach($rows as $m)
                                        <div class="flex items-center justify-between gap-3 px-4 py-3">
                                            <div>
                                                <span class="text-sm font-bold text-gray-800 dark:text-gray-100">{{ $m->model }}</span>
                                                @if($m->year)
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">({{ $m->year }})</span>
                                                @endif
                                                @if($m->engine_type)
                                                    <span class="ml-1 text-[11px] text-gray-400">· {{ $m->engine_type }}</span>
                                                @endif
                                                <span class="ml-2 inline-block text-[11px] font-semibold px-2 py-0.5 rounded-full {{ $m->inventory_items_count > 0 ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400' }}">
                                                    {{ $m->inventory_items_count }} {{ \Illuminate\Support\Str::plural('part', $m->inventory_items_count) }}
                                                </span>
                                            </div>
                                            @if($m->inventory_items_count === 0)
                                                <form method="POST" action="{{ route('motorcycles.catalogue.destroy', $m->id) }}"
                                                      onsubmit="return confirm('Remove {{ $m->label() }} from the catalogue?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-xs font-bold text-red-600 hover:text-red-700 dark:text-red-400">Remove</button>
                                                </form>
                                            @else
                                                <span class="text-[11px] text-gray-400" title="Remove its fitments first">Linked</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <p class="text-center text-sm text-gray-500 dark:text-gray-400 py-10">
                                {{ $search !== '' ? 'No makes or models match your search.' : 'No motorcycles in the catalogue yet. Add a make and its models to populate the customer dropdowns.' }}
                            </p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
