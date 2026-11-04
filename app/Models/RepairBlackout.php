<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** RD2-R3 Phase 4: a period (half-open [starts_at, ends_at)) in which no repair reservation may be requested or accepted. */
class RepairBlackout extends Model
{
    public const SLOT = 'slot';
    public const RANGE = 'range';
    public const DAY = 'day';

    protected $fillable = ['starts_at', 'ends_at', 'kind', 'reason', 'created_by'];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    /** "Mon, Nov 2 · 10:00 AM – 12:00 PM" / "Mon, Nov 2 · whole day" in the business timezone. */
    public function label(): string
    {
        $tz = config('repair_reservations.timezone');
        $s = $this->starts_at->copy()->timezone($tz);
        $e = $this->ends_at->copy()->timezone($tz);
        if ($this->kind === self::DAY) {
            return $s->format('D, M j, Y').' · whole day';
        }

        return $s->format('D, M j, Y').' · '.$s->format('g:i A').' – '.$e->format('g:i A');
    }
}
