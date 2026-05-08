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

        return $query->where(function ($q) use ($terms) {
            foreach ($terms as $term) {
                $q->where(function ($inner) use ($term) {
                    $inner->where('sku', 'ILIKE', "%$term%")
                          ->orWhere('model', 'ILIKE', "%$term%")
                          ->orWhere('part_name', 'ILIKE', "%$term%")
                          ->orWhere('category', 'ILIKE', "%$term%")
                          ->orWhere('brand', 'ILIKE', "%$term%");
                });
            }
        });
    }
}