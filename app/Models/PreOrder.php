<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreOrder extends Model
{
   protected $fillable = [
    'customer_name',
    'customer_id',
    'part_name',
    'model',
    'quantity',
    'down_payment',
    'status',
    'price',
    'total',
    'is_paid',
    'xendit_invoice_id',
    'xendit_invoice_url',
];

    protected $casts = [
        'is_paid' => 'boolean',
    ];

    // Audit #12: no `protected $with` — views render the denormalized
    // `customer_name` column, not the relation, so this eager load was unused.

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
