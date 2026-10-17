<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\Motorcycle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Owner-only Motorcycle Catalogue Manager (B2.1).
 *
 * Maintains the supported motorcycle makes and models in the existing
 * `motorcycles` table INDEPENDENTLY of any part fitment. A model added here has
 * zero `part_compatibility` rows until the owner links a part through the
 * existing Add Fitment workflow, so adding a motorcycle NEVER implies that any
 * product fits it. The customer dropdowns already read the `motorcycles` table
 * directly, so catalogue rows appear to customers even with no linked parts.
 */
class MotorcycleCatalogueController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', InventoryItem::class);

        $search = trim((string) $request->query('search', ''));

        $motorcycles = Motorcycle::query()
            ->search($search)
            ->withCount('inventoryItems')
            ->orderBy('brand')
            ->orderBy('model')
            ->get();

        $groups = $motorcycles->groupBy(fn ($m) => $m->brand);

        return view('inventory.motorcycle_catalogue', compact('groups', 'search'));
    }

    /**
     * Add a make with one or more models. The model field accepts a
     * comma/semicolon/newline separated list, exactly like Add Fitment, so the
     * owner can add several models of one make in a single save. No part is
     * linked. Existing rows are reused (case/spacing-insensitive) so duplicates
     * are not created.
     */
    public function store(Request $request)
    {
        Gate::authorize('viewAny', InventoryItem::class);

        $data = $request->validate([
            'brand' => 'required|string|min:1|max:100',
            'model' => 'required|string|min:1|max:600',
            'year' => [
                'nullable', 'string', 'max:20',
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
        ]);

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
        $existing = [];

        DB::transaction(function () use ($models, $data, $year, &$added, &$existing) {
            $brand = Motorcycle::canonicalBrand($data['brand']);

            foreach ($models as $name) {
                $before = Motorcycle::query()->count();
                $motorcycle = Motorcycle::findOrCreateCatalogue($brand, $name, $year, $data['engine_type'] ?? null);

                if (Motorcycle::query()->count() > $before) {
                    $added[] = $motorcycle->label();
                } else {
                    $existing[] = $motorcycle->label();
                }
            }
        });

        if ($added === []) {
            return back()->with('success', 'Already in the catalogue: ' . implode(', ', $existing) . '.');
        }

        $message = 'Added to catalogue: ' . implode(', ', $added) . '.';
        if ($existing !== []) {
            $message .= ' Already present (skipped): ' . implode(', ', $existing) . '.';
        }

        return back()->with('success', $message);
    }

    /**
     * Remove a catalogue motorcycle. Refused while any part is still linked to it,
     * so confirmed fitments are never silently destroyed.
     */
    public function destroy(Motorcycle $motorcycle)
    {
        Gate::authorize('viewAny', InventoryItem::class);

        if ($motorcycle->inventoryItems()->exists()) {
            return back()->withErrors([
                'catalogue' => "{$motorcycle->label()} still has confirmed part fitments. Remove those fitments first.",
            ]);
        }

        $label = $motorcycle->label();
        $motorcycle->delete();

        return back()->with('success', "Removed from catalogue: {$label}.");
    }
}
