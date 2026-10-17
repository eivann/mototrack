<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\Motorcycle;
use App\Services\CompatibilityMatcher;
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

        // After a failed save the form is reopened with what the owner typed.
        $restore = null;
        if (old('_item')) {
            $form = [
                'brand' => (string) old('brand', ''),
                'model' => (string) old('model', ''),
                'year' => (string) old('year', ''),
                'engine_type' => (string) old('engine_type', ''),
                'notes' => (string) old('notes', ''),
            ];

            if (old('_edit') && ($bike = Motorcycle::find((int) old('_edit')))) {
                $form = array_merge($form, [
                    'brand' => (string) $bike->brand,
                    'model' => (string) $bike->model,
                    'year' => (string) $bike->year,
                    'engine_type' => (string) $bike->engine_type,
                ]);
            }

            $restore = [
                'itemId' => (int) old('_item'),
                'itemName' => (string) InventoryItem::query()->whereKey((int) old('_item'))->value('part_name'),
                'editId' => old('_edit') ? (int) old('_edit') : null,
                'form' => $form,
            ];
        }

        return view('inventory.compatibility_admin', compact('parts', 'search', 'restore'));
    }

    /**
     * Add one or more motorcycle fitments to a part. The model field accepts a
     * comma/semicolon/newline separated list ("TMX 125, TMX 155") so one part can
     * be linked to several models of the same brand in one save. Brand and model
     * spellings are reused from existing records (case/spacing-insensitive) so the
     * customer dropdowns never show the same bike twice.
     */
    public function store(Request $request, InventoryItem $item)
    {
        Gate::authorize('update', $item);

        $data = $this->validated($request);

        $models = collect(preg_split('/[,;\r\n]+/', (string) $data['model']))
            ->map(fn ($m) => trim((string) preg_replace('/\s+/u', ' ', $m)))
            ->filter()
            ->unique(fn ($m) => mb_strtolower($m))
            ->values();

        if ($models->isEmpty()) {
            return back()->withErrors(['model' => 'Enter at least one model.'])->withInput();
        }

        foreach ($models as $name) {
            if (mb_strlen($name) > 100) {
                return back()->withErrors(['model' => 'Each model name must be 100 characters or fewer.'])->withInput();
            }
        }

        $year = $data['year'] ?? null;
        $added = [];
        $skipped = [];

        DB::transaction(function () use ($models, $data, $year, $item, &$added, &$skipped) {
            // Serialise concurrent saves for the same part so a double submit or a
            // second tab cannot attach the same fitment twice.
            InventoryItem::query()->whereKey($item->id)->lockForUpdate()->first();

            $brand = $this->canonicalBrand($data['brand']);

            foreach ($models as $name) {
                $motorcycle = $this->findOrCreateMotorcycle($brand, $name, $year, $data['engine_type'] ?? null);
                $label = $this->label($motorcycle);

                if ($item->motorcycles()->where('motorcycles.id', $motorcycle->id)->exists()) {
                    $skipped[] = $label;
                    continue;
                }

                $item->motorcycles()->attach($motorcycle->id, ['notes' => $data['notes'] ?? null]);
                $added[] = $label;
            }
        });

        if ($added === []) {
            return back()->withErrors([
                'fitment' => implode(', ', $skipped) . (count($skipped) > 1 ? ' are' : ' is') . ' already compatible with this part.',
            ])->withInput();
        }

        $message = 'Fitment added: ' . implode(', ', $added) . '.';
        if ($skipped !== []) {
            $message .= ' Already linked (skipped): ' . implode(', ', $skipped) . '.';
        }

        return back()->with('success', $message);
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
     * Searchable product selector: id, name, SKU and brand of matching parts.
     */
    public function parts(Request $request)
    {
        Gate::authorize('viewAny', InventoryItem::class);

        $term = trim((string) $request->query('q', ''));
        $likeOp = DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';

        if (mb_strlen($term) < 2) {
            return response()->json([]);
        }

        return response()->json(
            InventoryItem::query()
                ->where(function ($q) use ($term, $likeOp) {
                    $q->where('part_name', $likeOp, "%{$term}%")
                        ->orWhere('sku', $likeOp, "%{$term}%")
                        ->orWhere('brand', $likeOp, "%{$term}%");
                })
                ->orderBy('part_name')
                ->limit(15)
                ->get(['id', 'part_name', 'sku', 'brand'])
        );
    }

    private function label(Motorcycle $m): string
    {
        return trim("{$m->brand} {$m->model}") . ($m->year ? " ({$m->year})" : '');
    }

    /**
     * Reuse the spelling already on record for a brand, ignoring case and spacing.
     */
    private function canonicalBrand(string $brand): string
    {
        $brand = trim((string) preg_replace('/\s+/u', ' ', $brand));
        $key = CompatibilityMatcher::normalize($brand);

        $existing = Motorcycle::query()->pluck('brand')
            ->first(fn ($b) => CompatibilityMatcher::normalize($b) === $key);

        return $existing ?? $brand;
    }

    private function findOrCreateMotorcycle(string $brand, string $model, ?string $year, ?string $engine): Motorcycle
    {
        $modelKey = CompatibilityMatcher::normalize($model);
        $brandKey = CompatibilityMatcher::normalize($brand);

        $found = Motorcycle::query()->get()->first(
            fn ($m) => CompatibilityMatcher::normalize($m->brand) === $brandKey
                && CompatibilityMatcher::normalize($m->model) === $modelKey
                && trim((string) $m->year) === trim((string) $year)
        );

        return $found ?? Motorcycle::create([
            'brand' => $brand,
            'model' => $model,
            'year' => $year,
            'engine_type' => $engine,
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
            'model' => 'required|string|min:1|max:600',
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
