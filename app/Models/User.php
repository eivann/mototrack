<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token', 'otp_code'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the customer profile associated with the user.
     */
    public function customer(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Customer::class);
    }

    /**
     * Get all users with the 'owner' role, cached for 5 minutes.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, static>
     */
    public static function owners()
    {
        // Cache plain IDs only. Caching the Eloquent collection itself breaks on any
        // serializing cache store (file/database/redis): with `serializable_classes`
        // disabled it comes back as __PHP_Incomplete_Class and notifications to
        // owners silently failed. Models are always hydrated fresh and the role is
        // re-checked so a demoted owner is never notified from a stale list.
        $ids = Cache::remember('users_role_owner_ids', 300, function () {
            return static::where('role', 'owner')->pluck('id')->all();
        });

        return static::whereIn('id', $ids)->where('role', 'owner')->get();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'requires_password_change' => 'boolean',
            'otp_expires_at' => 'datetime',
        ];
    }
}
