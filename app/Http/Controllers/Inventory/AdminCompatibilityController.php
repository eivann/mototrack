<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\Motorcycle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AdminCompatibilityController extends Controller
{
    /**
     * Owner view of every fitment record, grouped by part.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', InventoryItem::class);

        $search = trim((string) $request->query('search', ''));

        $parts = InventoryItem::with(['motorcycles' => fn ($q) => $q->orderBy('brand')->orderBy('model')])
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('part_name', 'like', "%{$search}%")
                        ->orWhere('brand', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->orderBy('part_name')
            ->paginate(15)
            ->withQueryString();

        return view('inventory.compatibility_admin', compact('parts', 'search'));
    }

    /**
     * Add a motorcycle fitment to a part. The same part can be linked to many
     * makes/models/years — one row per combination in `part_compatibility`.
     */
    public function store(Request $request, InventoryItem $item)
    {
        Gate::authorize('update', $item);

        $data = $this->validated($request);

        $motorcycle = Motorcycle::firstOrCreate(
            [
                'brand' => $data['brand'],
                'model' => $data['model'],
                'year' => $data['year'] ?? null,
            ],
            ['engine_type' => $data['engine_type'] ?? null]
        );

        $alreadyLinked = $item->motorcycles()->where('motorcycles.id', $motorcycle->id)->exists();

        if ($alreadyLinked) {
            return back()->withErrors([
                'fitment' => "{$motorcycle->brand} {$motorcycle->model}" . ($motorcycle->year ? " ({$motorcycle->year})" : '') . ' is already compatible with this part.',
            ])->withInput();
        }

        $item->motorcycles()->attach($motorcycle->id, [
            'notes' => $data['notes'] ?? null,
        ]);

        return back()->with('success', "Fitment added: {$data['brand']} {$data['model']}.");
    }

    /**
     * Update the note on an existing fitment without detaching the motorcycle.
     */
    public function update(Request $request, InventoryItem $item, Motorcycle $motorcycle)
    {
        Gate::authorize('update', $item);

        $data = $request->validate([
            'notes' => 'nullable|string|max:255',
        ]);

        $exists = $item->motorcycles()->where('motorcycles.id', $motorcycle->id)->exists();

        if (! $exists) {
            abort(404, 'That fitment is not linked to this part.');
        }

        $item->motorcycles()->updateExistingPivot($motorcycle->id, [
            'notes' => $data['notes'] ?? null,
        ]);

        return back()->with('success', 'Fitment note updated.');
    }

    /**
     * Remove one fitment link. The motorcycle record itself is retained so other
     * parts using the same make/model keep their link.
     */
    public function destroy(InventoryItem $item, Motorcycle $motorcycle)
    {
        Gate::authorize('update', $item);

        $detached = $item->motorcycles()->detach($motorcycle->id);

        if ($detached === 0) {
            abort(404, 'That fitment is not linked to this part.');
        }

        return back()->with('success', 'Fitment removed.');
    }

    /**
     * Distinct makes already on record, used to populate the datalist.
     */
    public function options()
    {
        Gate::authorize('viewAny', InventoryItem::class);

        return response()->json([
            'brands' => Motorcycle::query()
                ->whereNotNull('brand')->where('brand', '!=', '')
                ->distinct()->orderBy('brand')->pluck('brand'),
            'models' => Motorcycle::query()
                ->whereNotNull('model')->where('model', '!=', '')
                ->select('brand', 'model')->distinct()->orderBy('model')->get(),
            'years' => Motorcycle::query()
                ->whereNotNull('year')->where('year', '!=', '')
                ->distinct()->orderByDesc('year')->pluck('year'),
        ]);
    }

    /**
     * @return array{brand: string, model: string, year: string|null, engine_type: string|null, notes: string|null}
     */
    private function validated(Request $request): array
    {
        $driver = DB::connection()->getDriverName();

        return $request->validate([
            'brand' => 'required|string|min:1|max:100',
            'model' => 'required|string|min:1|max:100',
            'year' => [
                'nullable',
                'string',
                'max:20',
                // Reject the placeholder value a browser submits when the user
                // never made a choice, and any non-numeric/out-of-range year.
                Rule::notIn(['', 'none', 'null', 'undefined', 'select', '--']),
                function ($attribute, $value, $fail) {
                    if ($value === null || $value === '') {
                        return;
                    }

                    if (! preg_match('/^(19|20)\d{2}(-(19|20)\d{2})?$/', (string) $value)) {
                        $fail('Enter a single year (e.g. 2020) or a range (e.g. 2018-2022).');
                    }
                },
            ],
            'engine_type' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:255',
        ]);
    }
}
