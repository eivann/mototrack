<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** RD2-R4: a browser the user chose to trust after OTP. Holds only a hash of the device token. */
class TrustedDevice extends Model
{
    protected $fillable = ['user_id', 'token_hash', 'label', 'expires_at', 'last_used_at', 'revoked_at'];

    /** Never serialised by accident. */
    protected $hidden = ['token_hash'];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('revoked_at')->where('expires_at', '>', now());
    }
}
