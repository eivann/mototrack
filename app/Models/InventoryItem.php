<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
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
        'low_stock_notified'
    ];

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
