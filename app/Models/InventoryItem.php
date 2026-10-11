<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use App\Traits\HasAuditTrail;

class InventoryItem extends Model
{
    use HasAuditTrail;

    protected $table = 'inventory_items';

    public const CATEGORY_CACHE_KEY = 'inventory_categories_v4';

    // Audit #15: the inventory_items table has created_at/updated_at columns,
    // but this model intentionally does not maintain them (writes leave them
    // NULL). Do not order by those columns — use the monotonic `id` instead
    // (e.g. `latest('id')`). Flipping this to true would require backfilling
    // existing NULL rows, so it is deliberately left off.
    public $timestamps = false;

    protected $fillable = [
        'sku',
        'category',
        'part_name',
        'brand',
        'model',
        'quantity',
        'notes',
        'price',
        'low_stock_threshold',
        'low_stock_notified',
        'image_path',
        'parent_id',
        'variation_name'
    ];

    protected $appends = ['image_url'];

    public function parent()
    {
        return $this->belongsTo(InventoryItem::class, 'parent_id');
    }

    public function variations()
    {
        return $this->hasMany(InventoryItem::class, 'parent_id');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function motorcycles()
    {
        return $this->belongsToMany(Motorcycle::class, 'part_compatibility')
                    ->withPivot('notes')
                    ->withTimestamps();
    }

    public function damagedItems()
    {
        return $this->hasMany(DamagedItem::class);
    }

    public function productReturns()
    {
        return $this->hasMany(ProductReturn::class);
    }

    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'integer',
        'low_stock_threshold' => 'integer',
        'low_stock_notified' => 'boolean',
    ];

    /**
     * Set the item's sku to uppercase.
     */
    protected function sku(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            set: fn (?string $value) => $value ? strtoupper($value) : null,
        );
    }

    /**
     * Set the item's category to title case.
     */
    protected function category(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            set: fn (string $value) => ucwords($value),
        );
    }

    /**
     * Set the item's part name to title case.
     */
    protected function partName(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            set: fn (string $value) => ucwords($value),
        );
    }

    /**
     * Set the item's brand to title case.
     */
    protected function brand(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            set: fn (string $value) => ucwords($value),
        );
    }

    /**
     * Set the item's model to title case.
     */
    protected function model(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            set: fn (string $value) => ucwords($value),
        );
    }

    /**
     * Get the full URL for the product image, or a placeholder if null.
     */
    public function getImageUrlAttribute()
    {
        if (empty($this->image_path)) {
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="400" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>';
            return 'data:image/svg+xml;base64,' . base64_encode($svg);
        }

        if (str_starts_with($this->image_path, 'http://') || str_starts_with($this->image_path, 'https://')) {
            return $this->image_path;
        }

        if (file_exists(public_path('images/' . $this->image_path))) {
            return asset('images/' . $this->image_path);
        }

        if (file_exists(public_path($this->image_path))) {
            return asset($this->image_path);
        }

        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($this->image_path)) {
            return \Illuminate\Support\Facades\Storage::disk('public')->url($this->image_path);
        }

        if (str_starts_with($this->image_path, 'products/')) {
            return asset('images/' . $this->image_path);
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->image_path);
    }

    /**
     * Distinct, display-ready category list for filter dropdowns.
     *
     * Defends against legacy rows where a JSON array was stored as a single
     * category string (e.g. '["Accessories","Brakes"]') by flattening it back
     * into individual categories, so the filter never shows raw JSON.
     * Cache key bumped to v3 to bust the previous (v2) stale cache.
     */
    public static function distinctCategories()
    {
        return Cache::remember(self::CATEGORY_CACHE_KEY, now()->addHours(6), function () {
            return static::query()
                ->whereNotNull('category')
                ->distinct()
                ->pluck('category')
                ->flatMap(function ($value) {
                    $decoded = json_decode((string) $value, true);

                    return is_array($decoded) ? $decoded : [$value];
                })
                ->map(fn ($value) => is_string($value) ? trim($value) : null)
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->toArray();
        });
    }

    /**
     * Scope a query to search for inventory items.
     *
     * Audit #7: the search logic lives in InventorySearchService; this scope
     * is a thin forwarder so existing call sites and chaining are unchanged.
     */
    public function scopeSearch($query, $searchTerm)
    {
        return app(\App\Services\InventorySearchService::class)->apply($query, $searchTerm);
    }
}
