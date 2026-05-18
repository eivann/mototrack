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

    protected $with = ['customer'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
