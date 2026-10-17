<?php

namespace App\Models;

use App\Services\CompatibilityMatcher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Motorcycle extends Model
{
    protected $fillable = [
        'brand',
        'model',
        'year',
        'engine_type',
    ];

    public function inventoryItems(): BelongsToMany
    {
        return $this->belongsToMany(InventoryItem::class, 'part_compatibility')
                    ->withPivot('notes')
                    ->withTimestamps();
    }

    /**
     * Human label for a catalogue row: "Honda TMX 125 (2018-2022)".
     */
    public function label(): string
    {
        return trim("{$this->brand} {$this->model}") . ($this->year ? " ({$this->year})" : '');
    }

    /**
     * Reuse the brand spelling already on record (case/space-insensitive) so the
     * catalogue and the customer dropdowns never show the same make twice.
     */
    public static function canonicalBrand(string $brand): string
    {
        $brand = trim((string) preg_replace('/\s+/u', ' ', $brand));
        $key = CompatibilityMatcher::normalize($brand);

        $existing = static::query()->pluck('brand')
            ->first(fn ($b) => CompatibilityMatcher::normalize($b) === $key);

        return $existing ?? $brand;
    }

    /**
     * Find an existing catalogue row (ignoring case and spacing on brand/model,
     * exact on year) or create one. Null and "" years are treated as the same
     * value, because PostgreSQL would treat two NULL years as distinct rows and a
     * unique index alone could not prevent that duplicate.
     */
    public static function findOrCreateCatalogue(string $brand, string $model, ?string $year = null, ?string $engine = null): self
    {
        $brand = static::canonicalBrand($brand);
        $model = trim((string) preg_replace('/\s+/u', ' ', $model));
        $year = trim((string) $year) === '' ? null : trim((string) $year);

        $brandKey = CompatibilityMatcher::normalize($brand);
        $modelKey = CompatibilityMatcher::normalize($model);

        $found = static::query()->get()->first(
            fn ($m) => CompatibilityMatcher::normalize($m->brand) === $brandKey
                && CompatibilityMatcher::normalize($m->model) === $modelKey
                && trim((string) $m->year) === trim((string) $year)
        );

        return $found ?? static::create([
            'brand' => $brand,
            'model' => $model,
            'year' => $year,
            'engine_type' => (trim((string) $engine) === '' ? null : trim((string) $engine)),
        ]);
    }

    /**
     * Case-insensitive catalogue search over brand and model.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $like = $query->getConnection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';

        return $query->where(function ($q) use ($term, $like) {
            $q->where('brand', $like, "%{$term}%")
              ->orWhere('model', $like, "%{$term}%");
        });
    }
}
