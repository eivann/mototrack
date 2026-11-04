<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** RD2-R3: append-only status history of a repair reservation. */
class RepairReservationEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['repair_reservation_id', 'from_status', 'to_status', 'actor_role', 'actor_id', 'note', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];
}
