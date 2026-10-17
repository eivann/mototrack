<x-app-layout>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8 px-4 sm:px-6 lg:px-8 font-sans" x-data="fitmentManager(@js($restore))" @keydown.escape.window="open && close()">
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
                        <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
                        Compatibility Records
                    </h1>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Maintain which motorcycle makes, models and years each part fits. One part can fit many motorcycles.</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('motorcycles.catalogue') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl font-bold shadow-sm transition-colors flex items-center gap-2 w-fit">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        Motorcycle Catalogue
                    </a>
                    <a href="{{ route('inventory.index') }}" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 px-4 py-2 rounded-xl font-bold shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors flex items-center gap-2 w-fit">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        Back to Inventory
                    </a>
                </div>
            </div>

            {{-- Searchable product selector: pick any part and open its fitment form directly. --}}
            <div class="mb-6 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5">
                <label for="quick-part" class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Quick add: find a product to link to motorcycles</label>
                <div class="relative">
                    <input id="quick-part" type="search" x-model="pickQuery" @input.debounce.250ms="searchParts()" autocomplete="off"
                           placeholder="Type a part name, brand or SKU (2+ characters)..."
                           class="w-full border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <ul x-show="pickResults.length > 0" x-cloak class="absolute z-30 mt-1 w-full max-h-72 overflow-y-auto bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl shadow-lg divide-y divide-gray-100 dark:divide-gray-700">
                        <template x-for="p in pickResults" :key="p.id">
                            <li>
                                <button type="button" @click="pickPart(p)" class="w-full text-left px-4 py-2.5 hover:bg-indigo-50 dark:hover:bg-gray-700 text-sm">
                                    <span class="font-bold text-gray-900 dark:text-white" x-text="p.part_name"></span>
                                    <span class="ml-2 text-xs text-gray-500 dark:text-gray-400" x-text="[p.brand, p.sku].filter(Boolean).join(' · ')"></span>
                                </button>
                            </li>
                        </template>
                    </ul>
                    <p x-show="pickEmpty" x-cloak class="mt-2 text-xs text-gray-500 dark:text-gray-400">No product matches that search.</p>
                </div>
            </div>

            <form method="GET" action="{{ route('compatibility.manage') }}" class="mb-6 flex flex-col sm:flex-row gap-3">
                <label for="compat-search" class="sr-only">Search parts</label>
                <input id="compat-search" type="search" name="search" value="{{ $search }}" placeholder="Search part name, brand or SKU..." class="flex-1 border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-xl font-bold text-sm transition-colors">Search</button>
                @if($search !== '')
                    <a href="{{ route('compatibility.manage') }}" class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 px-6 py-2.5 rounded-xl font-bold text-sm hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">Clear</a>
                @endif
            </form>

            <div class="space-y-6">
                @forelse($parts as $item)
                    @php
                        $fits = $item->motorcycles;
                        $moto = $fits->map(function ($m) {
                            return trim($m->brand . ' ' . $m->model . ($m->year ? ' (' . $m->year . ')' : ''));
                        })->implode(', ');
                    @endphp
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div class="min-w-0">
                                <h2 class="text-lg font-black text-gray-900 dark:text-white truncate">{{ $item->part_name }}</h2>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    <span class="font-semibold text-indigo-600 dark:text-indigo-400">Compatible with:</span>
                                    <span class="text-gray-500 dark:text-gray-400">{{ $moto !== '' ? $moto : 'No records yet' }}</span>
                                </p>
                            </div>
                            <div class="flex items-center gap-3 shrink-0">
                                <span class="px-3 py-1 rounded-full font-black text-[10px] uppercase tracking-widest {{ $fits->count() > 0 ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400' : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400' }}">
                                    {{ $fits->count() }} {{ \Illuminate\Support\Str::plural('fitment', $fits->count()) }}
                                </span>
                                <button type="button"
                                        @click="openAdd({{ $item->id }}, {{ Js::from($item->part_name) }})"
                                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl font-black text-xs uppercase tracking-widest transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                    + Add Fitment
                                </button>
                            </div>
                        </div>

                        @if($fits->isNotEmpty())
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-sm">
                                    <thead class="bg-gray-50 dark:bg-gray-900/50 text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500">
                                        <tr>
                                            <th class="p-4">Make / Brand</th>
                                            <th class="p-4">Model</th>
                                            <th class="p-4">Year</th>
                                            <th class="p-4">Engine</th>
                                            <th class="p-4">Notes</th>
                                            <th class="p-4 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                        @foreach($fits as $m)
                                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors" wire:key="fit-{{ $item->id }}-{{ $m->id }}">
                                                <td class="p-4 font-bold text-gray-900 dark:text-white">{{ $m->brand }}</td>
                                                <td class="p-4 text-gray-700 dark:text-gray-300">{{ $m->model }}</td>
                                                <td class="p-4 text-gray-700 dark:text-gray-300">{{ $m->year ?: '—' }}</td>
                                                <td class="p-4 text-gray-700 dark:text-gray-300">{{ $m->engine_type ?: '—' }}</td>
                                                <td class="p-4 text-gray-500 dark:text-gray-400 text-xs">{{ $m->pivot->notes ?: '—' }}</td>
                                                <td class="p-4 text-right whitespace-nowrap">
                                                    <button type="button"
                                                                    @click="openEdit({{ $item->id }}, {{ Js::from($item->part_name) }}, {{ $m->id }}, {{ Js::from(['brand' => $m->brand, 'model' => $m->model, 'year' => $m->year, 'engine_type' => $m->engine_type, 'notes' => $m->pivot->notes]) }})"
                                                                    class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 font-bold text-xs mr-3">Edit</button>
                                                    <form action="{{ route('compatibility.destroy', [$item->id, $m->id]) }}" method="POST" class="inline"
                                                                      onsubmit="return confirm('Remove this fitment record?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 font-bold text-xs">Remove</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="p-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                <p class="font-bold text-gray-900 dark:text-white mb-1">No motorcycle is linked to this part yet.</p>
                                <p class="text-xs">Add a fitment so customers searching by bike can find it.</p>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-12 text-center">
                        <p class="text-gray-900 dark:text-white font-bold text-lg mb-1">No parts found.</p>
                        <p class="text-gray-400 text-sm">{{ $search !== '' ? 'Try a different search term.' : 'Add inventory items first.' }}</p>
                    </div>
                @endforelse
            </div>

            <div class="mt-8">
                {{ $parts->links() }}
            </div>
        </div>

        {{-- Add / edit fitment modal: part of the page-level fitmentManager() scope above. --}}
        <div x-show="open" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             role="dialog" aria-modal="true" aria-labelledby="fitment-modal-title">
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="close()"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-700 w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">
                <h3 id="fitment-modal-title" class="text-lg font-black text-gray-900 dark:text-white mb-1" x-text="editId ? 'Edit fitment note' : 'Add fitment'"></h3>
                <p class="text-xs font-bold text-indigo-600 dark:text-indigo-400 mb-1" x-text="itemName"></p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-5" x-text="editId ? 'Only the note can be changed. To fix the make, model or year, remove this fitment and add the correct one.' : 'Link this part to a motorcycle. A part can fit several makes, models and years.'"></p>

                <form :action="actionUrl()" method="POST" class="space-y-4" @submit="submitting = true">
                    @csrf
                    <input type="hidden" name="_item" :value="itemId">
                    <input type="hidden" name="_edit" :value="editId">
                    <template x-if="editId"><input type="hidden" name="_method" value="PATCH"></template>

                    <div>
                        <label for="fit-brand" class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Make / Brand</label>
                        <input id="fit-brand" type="text" name="brand" list="fit-brand-list" autocomplete="off" required placeholder="e.g. Honda"
                               x-model="form.brand" :readonly="!!editId" :disabled="!!editId"
                               class="w-full border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-60">
                        <datalist id="fit-brand-list">
                            <template x-for="b in options.brands" :key="b"><option :value="b"></option></template>
                        </datalist>
                    </div>

                    <div>
                        <label for="fit-model" class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Model(s)</label>
                        <input id="fit-model" type="text" name="model" list="fit-model-list" autocomplete="off" required placeholder="e.g. TMX 125  (several: TMX 125, TMX 155)"
                               x-model="form.model" :readonly="!!editId" :disabled="!!editId"
                               class="w-full border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-60">
                        <datalist id="fit-model-list">
                            <template x-for="m in modelOptions()" :key="m"><option :value="m"></option></template>
                        </datalist>
                        <p x-show="!editId" class="mt-1 text-[10px] text-gray-400">To link this part to several models of the same brand, separate them with commas.</p>
                    </div>

                    <div>
                        <label for="fit-year" class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Year <span class="normal-case font-semibold text-gray-400">(single year or range, optional)</span></label>
                        <input id="fit-year" type="text" name="year" inputmode="numeric" placeholder="e.g. 2020 or 2018-2022"
                               x-model="form.year" :readonly="!!editId" :disabled="!!editId"
                               class="w-full border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-60">
                        <p class="mt-1 text-[10px] text-gray-400">Leave blank if the part fits all years. The customer Year filter only appears once at least one fitment has a year.</p>
                    </div>

                    <div>
                        <label for="fit-engine" class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Engine Type <span class="normal-case font-semibold text-gray-400">(optional)</span></label>
                        <input id="fit-engine" type="text" name="engine_type" placeholder="e.g. 125cc"
                               x-model="form.engine_type" :readonly="!!editId" :disabled="!!editId"
                               class="w-full border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-60">
                    </div>

                    <div>
                        <label for="fit-notes" class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Notes <span class="normal-case font-semibold text-gray-400">(optional)</span></label>
                        <textarea id="fit-notes" name="notes" rows="2" maxlength="255" placeholder="e.g. Fits the Caliper variant only"
                                  x-model="form.notes"
                                  class="w-full border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="submit" :disabled="submitting" class="flex-1 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 disabled:cursor-not-allowed text-white py-3 rounded-xl font-black text-xs uppercase tracking-widest transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2" x-text="submitting ? 'Saving...' : (editId ? 'Save Note' : 'Save Fitment')">Save Fitment</button>
                        <button type="button" @click="close()" class="px-6 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 py-3 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>[x-cloak] { display: none !important; }</style>
    <script>
        function fitmentManager(restore) {
            const blank = () => ({ brand: '', model: '', year: '', engine_type: '', notes: '' });
            const norm = (v) => String(v || '').toLowerCase().replace(/\s+/g, ' ').trim();

            return {
                open: false,
                itemId: null,
                itemName: '',
                editId: null,
                submitting: false,
                options: { brands: [], models: [] },
                form: blank(),
                pickQuery: '',
                pickResults: [],
                pickEmpty: false,
                loaded: false,

                init() {
                    // After a failed save the server redirects back; reopen the form with what was typed.
                    if (restore && restore.itemId) {
                        this.itemId = restore.itemId;
                        this.itemName = restore.itemName || '';
                        this.editId = restore.editId || null;
                        this.form = Object.assign(blank(), restore.form || {});
                        this.open = true;
                        this.loadOptions();
                    }
                },

                actionUrl() {
                    const base = @js(url('/inventory')) + '/' + this.itemId + '/compatibility';
                    return this.editId ? base + '/' + this.editId : base;
                },

                openAdd(id, name) {
                    this.itemId = id;
                    this.itemName = name;
                    this.editId = null;
                    this.form = blank();
                    this.submitting = false;
                    this.open = true;
                    this.loadOptions();
                },

                openEdit(id, name, motorcycleId, record) {
                    this.itemId = id;
                    this.itemName = name;
                    this.editId = motorcycleId;
                    this.form = Object.assign(blank(), record || {});
                    this.submitting = false;
                    this.open = true;
                },

                close() {
                    this.open = false;
                    this.submitting = false;
                    this.editId = null;
                    this.itemId = null;
                    this.itemName = '';
                    this.form = blank();
                },

                loadOptions() {
                    if (this.loaded) return;
                    fetch(@js(route('compatibility.manage.options')), { headers: { 'Accept': 'application/json' } })
                        .then(r => r.ok ? r.json() : { brands: [], models: [] })
                        .then(data => { this.options = { brands: data.brands || [], models: data.models || [] }; this.loaded = true; })
                        .catch(() => {});
                },

                modelOptions() {
                    const brand = norm(this.form.brand);
                    const names = this.options.models
                        .filter(m => brand === '' || norm(m.brand) === brand)
                        .map(m => m.model);
                    return [...new Set(names)];
                },

                searchParts() {
                    const q = this.pickQuery.trim();
                    this.pickEmpty = false;
                    if (q.length < 2) { this.pickResults = []; return; }
                    fetch(@js(route('compatibility.manage.parts')) + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } })
                        .then(r => r.ok ? r.json() : [])
                        .then(rows => { this.pickResults = rows; this.pickEmpty = rows.length === 0; })
                        .catch(() => { this.pickResults = []; });
                },

                pickPart(p) {
                    this.pickQuery = '';
                    this.pickResults = [];
                    this.pickEmpty = false;
                    this.openAdd(p.id, p.part_name);
                },
            };
        }
    </script>
</x-app-layout>
