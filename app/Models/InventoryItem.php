<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasAuditTrail;

class InventoryItem extends Model
{
    use HasAuditTrail;

    protected $table = 'inventory_items';

    public $timestamps = false; // IMPORTANT (no created_at, updated_at)

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
        'image_path'
    ];

    protected $appends = ['image_url'];

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
        return $this->image_path 
            ? asset('storage/' . $this->image_path)
            : 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="400" height="400" viewBox="0 0 24 24" fill="none" stroke="%239CA3AF" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>';
    }

    /**
     * Scope a query to search for inventory items.
     */
    public function scopeSearch($query, $searchTerm)
    {
        $searchTerm = trim($searchTerm);
        if (empty($searchTerm)) {
            return $query;
        }

        $stopwords = ['for', 'the', 'and', 'with', 'in', 'on', 'at', 'to', 'a', 'an', 'of', 'my'];
        $all_terms = explode(' ', strtolower($searchTerm));
        $terms = array_filter($all_terms, function($word) use ($stopwords) {
            return !in_array(trim($word), $stopwords) && strlen(trim($word)) > 1;
        });

        if (empty($terms)) {
            $terms = $all_terms;
        }

        $likeOperator = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';

        return $query->where(function ($q) use ($terms, $likeOperator) {
            foreach ($terms as $term) {
                $q->where(function ($inner) use ($term, $likeOperator) {
                    $inner->where('sku', $likeOperator, "%$term%")
                          ->orWhere('model', $likeOperator, "%$term%")
                          ->orWhere('part_name', $likeOperator, "%$term%")
                          ->orWhere('category', $likeOperator, "%$term%")
                          ->orWhere('brand', $likeOperator, "%$term%");
                });
            }
        });
    }
}
