<?php

namespace App\Services;

use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * RD2-R4: trusted-device ("remember this device") handling.
 *
 *  - The token is 256 bits from random_bytes(), shown to the browser once (encrypted, HttpOnly cookie);
 *    only its SHA-256 hash is stored.
 *  - Trust is bound to one user, expires absolutely, and is revoked by password/email change or by the user.
 *  - IP address and user agent are NEVER used as proof; the user agent only produces a display label.
 *  - Nothing here logs or returns the token.
 */
class TrustedDeviceService
{
    public function enabled(): bool
    {
        return (bool) config('trusted_devices.enabled', true);
    }

    public function days(): int
    {
        return max(1, (int) config('trusted_devices.days', 30));
    }

    public function cookieName(): string
    {
        return (string) config('trusted_devices.cookie', 'mt_trusted_device');
    }

    /** Google-created accounts have an unusable password, so a password-login shortcut is pointless for them. */
    public function offeredTo(User $user): bool
    {
        return $this->enabled() && ! $user->google_id && ! $user->requires_password_change;
    }

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    /** Only well-formed tokens ever reach the database. */
    private function wellFormed(?string $token): bool
    {
        return is_string($token) && preg_match('/^[A-Za-z0-9_-]{43}$/', $token) === 1;
    }

    /** Create a trusted device for $user and return the cookie to queue. */
    public function issue(User $user, Request $request): Cookie
    {
        $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');

        TrustedDevice::create([
            'user_id' => $user->id,
            'token_hash' => self::hash($token),
            'label' => $this->label((string) $request->userAgent()),
            'expires_at' => now()->addDays($this->days()),
            'last_used_at' => now(),
        ]);

        // Keep only the newest N active devices.
        $keep = (int) config('trusted_devices.max_per_user', 10);
        $surplus = TrustedDevice::where('user_id', $user->id)->active()->orderByDesc('id')->skip($keep)->take(1000)->pluck('id');
        if ($surplus->isNotEmpty()) {
            TrustedDevice::whereIn('id', $surplus)->update(['revoked_at' => now()]);
        }

        return $this->cookie($token, $request, $this->days() * 24 * 60);
    }

    /** The device row for this token if it is valid for $user right now (does not change anything). */
    public function find(User $user, ?string $token): ?TrustedDevice
    {
        if (! $this->enabled() || ! $this->wellFormed($token)) {
            return null;
        }

        return TrustedDevice::query()->where('user_id', $user->id)->where('token_hash', self::hash($token))->active()->first();
    }

    /**
     * Use the device for a login: true only if it was valid and is still valid at the moment of the update,
     * so a concurrent revocation always wins.
     */
    public function consume(User $user, ?string $token): bool
    {
        $device = $this->find($user, $token);
        if (! $device) {
            return false;
        }

        return TrustedDevice::query()->whereKey($device->id)->active()->update(['last_used_at' => now()]) === 1;
    }

    public function revoke(TrustedDevice $device): void
    {
        $device->forceFill(['revoked_at' => $device->revoked_at ?? now()])->save();
    }

    public function revokeAll(User $user): int
    {
        return TrustedDevice::where('user_id', $user->id)->whereNull('revoked_at')->update(['revoked_at' => now()]);
    }

    /** An expired cookie that removes the browser's token. */
    public function forgetCookie(Request $request): Cookie
    {
        return $this->cookie('', $request, -2628000);
    }

    private function cookie(string $value, Request $request, int $minutes): Cookie
    {
        // Encrypted by Laravel's EncryptCookies middleware (not excepted); HttpOnly; Strict because the only
        // consumer is the same-site login POST; Secure in production or on any HTTPS request.
        return cookie($this->cookieName(), $value, $minutes, '/', null,
            app()->isProduction() || $request->isSecure() || (bool) config('session.secure'), true, false, 'strict');
    }

    /** Display-only description. Never used for trust decisions. */
    private function label(string $ua): string
    {
        $browser = match (true) {
            str_contains($ua, 'Edg/') => 'Edge',
            str_contains($ua, 'OPR/') || str_contains($ua, 'Opera') => 'Opera',
            str_contains($ua, 'Firefox/') => 'Firefox',
            str_contains($ua, 'Chrome/') => 'Chrome',
            str_contains($ua, 'Safari/') => 'Safari',
            default => 'Browser',
        };
        $os = match (true) {
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'iPhone') || str_contains($ua, 'iPad') => 'iOS',
            str_contains($ua, 'Mac OS') => 'macOS',
            str_contains($ua, 'Linux') => 'Linux',
            default => 'unknown device',
        };

        return Str::limit("$browser on $os", 120, '');
    }
}
