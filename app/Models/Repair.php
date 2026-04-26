<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Repair extends Model
{
   protected $fillable = [
    'customer_name',
    'bike_model',
    'issue',
    'status',
    'cost',
    'is_paid',
];
}
