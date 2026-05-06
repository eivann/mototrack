<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Repair extends Model
{
   protected $fillable = [
    'customer_name',
    'customer_id',
    'bike_model',
    'issue',
    'status',
    'cost',
    'is_paid',
];

public function customer()
{
    return $this->belongsTo(Customer::class);
}
}
