<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RD2-R4: "remember this device" so a previously OTP-verified browser can skip the emailed OTP.
 *
 * LOCAL MIGRATION ONLY — not applied to any live/production database by this change.
 *
 * Only a SHA-256 hash of the random device token is stored (never the token). A device row is bound to one
 * user, has an absolute expiry (no sliding extension) and can be revoked. `label` is a display-only
 * description ("Chrome on Windows"); it is never used as proof of trust.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trusted_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->string('label', 120)->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'revoked_at', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trusted_devices');
    }
};
