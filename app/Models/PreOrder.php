<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreOrder extends Model
{
   protected $fillable = [
    'customer_name',
    'part_name',
    'model',
    'quantity',
    'down_payment',
    'status',
    'price',
    'total',
];
}
