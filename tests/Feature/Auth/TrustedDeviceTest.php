<?php

use App\Mail\OtpMail;
use App\Models\TrustedDevice;
use App\Models\User;
use App\Services\TrustedDeviceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Socialite\Facades\Socialite;

uses(RefreshDatabase::class);

/**
 * RD2-R4: remembered ("trusted") devices. Test data only: Mail is faked, no real OTP is ever sent.
 * The test client replays the real Set-Cookie value like a browser would.
 */

beforeEach(function () {
    Mail::fake();
    config(['trusted_devices.enabled' => true, 'trusted_devices.days' => 30]);
    RateLimiter::clear('x');
});

function tdUser(array $over = []): User
{
    return User::factory()->create(array_merge(['role' => 'customer', 'password' => Hash::make('Secret-pass-1')], $over));
}

/** Password login. Returns the response. */
function tdLogin(User $user, ?string $cookie = null, string $password = 'Secret-pass-1')
{
    $t = test();
    if ($cookie !== null) {
        $t = $t->withCookie('mt_trusted_device', $cookie);
    }

    return $t->post('/login', ['email' => $user->email, 'password' => $password]);
}

/** Complete the OTP step for the signed-in session; optionally tick "remember". Returns the response. */
function tdVerify(User $user, bool $remember): \Illuminate\Testing\TestResponse
{
    $user->forceFill(['otp_code' => Hash::make('123456'), 'otp_expires_at' => now()->addMinutes(10)])->save();
    auth()->guard('web')->forgetUser();   // a real browser's next request reloads the user from the session

    return test()->post('/otp/verify', array_filter(['otp_code' => '123456', 'remember_device' => $remember ? '1' : null]));
}

/** Full first-time trust: login -> OTP with "remember". Returns the plain device token the browser holds. */
function tdTrust(User $user): string
{
    tdLogin($user)->assertRedirect('/otp/verify');
    $response = tdVerify($user, true);
    $token = $response->getCookie('mt_trusted_device');
    expect($token)->not->toBeNull();
    auth()->logout();

    return $token->getValue();
}

function tdFreshBrowser(): void
{
    test()->flushSession();
    auth()->logout();
    \Illuminate\Support\Facades\Cookie::unqueue('mt_trusted_device');
}

test('ticking "remember this device" after a correct OTP sets a protected cookie and stores only a hash', function () {
    $user = tdUser();
    tdLogin($user)->assertRedirect('/otp/verify');
    $response = tdVerify($user, true);
    $response->assertRedirect(route('customer.dashboard'));

    $raw = $response->getCookie('mt_trusted_device', false);          // as sent on the wire (encrypted)
    $token = $response->getCookie('mt_trusted_device')->getValue();   // what the browser can present
    expect($raw->isHttpOnly())->toBeTrue()
        ->and(strtolower((string) $raw->getSameSite()))->toBe('lax')
        ->and($raw->getPath())->toBe('/')
        ->and($raw->getExpiresTime())->toBeGreaterThan(now()->addDays(29)->timestamp)->toBeLessThan(now()->addDays(31)->timestamp)
        ->and($raw->getValue())->not->toBe($token)                    // encrypted/signed by Laravel
        ->and(strlen($token))->toBe(43);

    $device = TrustedDevice::first();
    expect(TrustedDevice::count())->toBe(1)
        ->and($device->user_id)->toBe($user->id)
        ->and($device->token_hash)->toBe(hash('sha256', $token))
        ->and($device->token_hash)->not->toContain($token)
        ->and($device->expires_at->between(now()->addDays(29), now()->addDays(31)))->toBeTrue()
        ->and(json_encode($device->toArray()))->not->toContain($device->token_hash)->not->toContain($token);
    expect($response->headers->get('Location'))->not->toContain($token);
    expect($response->getContent())->not->toContain($token);
});

test('the Secure flag is set on HTTPS requests and in production', function () {
    $user = tdUser();
    $this->call('POST', 'https://localhost/login', ['email' => $user->email, 'password' => 'Secret-pass-1'])->assertRedirect();
    $user->forceFill(['otp_code' => Hash::make('123456'), 'otp_expires_at' => now()->addMinutes(10)])->save();
    auth()->guard('web')->forgetUser();
    $response = $this->call('POST', 'https://localhost/otp/verify', ['otp_code' => '123456', 'remember_device' => '1']);
    $c = $response->getCookie('mt_trusted_device', false);
    expect($c->isSecure())->toBeTrue();
});

test('without ticking the box nothing is remembered and the next login still needs the OTP', function () {
    $user = tdUser();
    tdLogin($user)->assertRedirect('/otp/verify');
    $response = tdVerify($user, false);
    expect($response->getCookie('mt_trusted_device'))->toBeNull()->and(TrustedDevice::count())->toBe(0);

    tdFreshBrowser();
    tdLogin($user)->assertRedirect('/otp/verify');
});

test('a later password login from the trusted browser skips the OTP, keeps the absolute expiry and regenerates the session', function () {
    $user = tdUser();
    $token = tdTrust($user);
    $device = TrustedDevice::first();
    $expires = $device->expires_at->copy();

    $this->travel(3)->days();
    Mail::fake();
    $sessionBefore = session()->getId();
    tdLogin($user, $token)->assertRedirect(route('customer.dashboard'));

    expect(session('otp_verified'))->toBeTrue()->and(auth()->id())->toBe($user->id)
        ->and(session()->getId())->not->toBe($sessionBefore);
    Mail::assertNothingSent();
    $this->get(route('customer.dashboard'))->assertOk();

    $device->refresh();
    expect($device->last_used_at->isToday())->toBeTrue()
        ->and($device->expires_at->equalTo($expires))->toBeTrue();      // never extended by use
    expect(session('otp_verified'))->toBeTrue();
});

test('logout then relogin on the trusted device still skips the OTP; the session itself is gone after logout', function () {
    $user = tdUser();
    $token = tdTrust($user);

    tdLogin($user, $token)->assertRedirect(route('customer.dashboard'));
    $this->withCookie('mt_trusted_device', $token)->post('/logout')->assertRedirect('/');
    $this->get(route('customer.dashboard'))->assertRedirect('/login');

    tdLogin($user, $token)->assertRedirect(route('customer.dashboard'));
    expect(TrustedDevice::first()->revoked_at)->toBeNull();
});

test('a browser without the token (new device) must complete the OTP and an OTP mail is sent', function () {
    $user = tdUser();
    tdTrust($user);
    tdFreshBrowser();

    Mail::fake();   // forget the OTP mail of the first-time trust step
    tdLogin($user)->assertRedirect('/otp/verify');
    expect(session('otp_verified'))->toBeFalse();
    Mail::assertSent(OtpMail::class, 1);
    $this->get(route('customer.dashboard'))->assertRedirect(route('otp.verify'));
});

test('the password is always required: wrong password with a valid token is refused', function () {
    $user = tdUser();
    $token = tdTrust($user);
    tdFreshBrowser();

    tdLogin($user, $token, 'wrong-password')->assertSessionHasErrors('email');
    expect(auth()->check())->toBeFalse();
    $this->get(route('customer.dashboard'))->assertRedirect('/login');
    expect(TrustedDevice::first()->last_used_at->isToday())->toBeTrue(); // untouched by the failed attempt: created today, not re-stamped later
});

test('the login lockout still applies to a trusted browser', function () {
    $user = tdUser();
    $token = tdTrust($user);
    tdFreshBrowser();

    for ($i = 0; $i < 5; $i++) {
        tdLogin($user, $token, 'wrong-password');
    }
    tdLogin($user, $token)->assertSessionHasErrors('email');
    expect(auth()->check())->toBeFalse();
});

test('an expired device needs the OTP again and the stale cookie is removed', function () {
    $user = tdUser();
    $token = tdTrust($user);
    tdFreshBrowser();

    $this->travel(31)->days();
    $response = tdLogin($user, $token);
    $response->assertRedirect('/otp/verify');
    expect(session('otp_verified'))->toBeFalse();
    $cleared = $response->getCookie('mt_trusted_device', false);
    expect($cleared)->not->toBeNull()->and($cleared->getExpiresTime())->toBeLessThan(time());
});

test('the trust period is configurable', function () {
    config(['trusted_devices.days' => 7]);
    $user = tdUser();
    $token = tdTrust($user);
    tdFreshBrowser();

    $this->travel(6)->days();
    tdLogin($user, $token)->assertRedirect(route('customer.dashboard'));
    tdFreshBrowser();
    $this->travel(2)->days();
    tdLogin($user, $token)->assertRedirect('/otp/verify');
});

test('a revoked device needs the OTP again', function () {
    $user = tdUser();
    $token = tdTrust($user);
    tdFreshBrowser();

    TrustedDevice::first()->update(['revoked_at' => now()]);
    tdLogin($user, $token)->assertRedirect('/otp/verify');
});

test('malformed, forged and foreign tokens never skip the OTP', function () {
    $alice = tdUser();
    $bob = tdUser();
    $aliceToken = tdTrust($alice);

    $bad = ['', 'x', str_repeat('A', 43), str_repeat('A', 44), "' OR 1=1 --", '../../etc/passwd', str_repeat('a', 5000), 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA/', $aliceToken.'x', substr($aliceToken, 1)];
    foreach ($bad as $value) {
        tdFreshBrowser();
        tdLogin($alice, $value)->assertRedirect('/otp/verify');
        expect(session('otp_verified'))->toBeFalse();
    }

    // Alice's valid token presented while Bob signs in (bound to one user).
    tdFreshBrowser();
    tdLogin($bob, $aliceToken)->assertRedirect('/otp/verify');
    expect(session('otp_verified'))->toBeFalse();

    // A cookie that was not produced by this app's encryption (plaintext tampering) is ignored.
    tdFreshBrowser();
    $this->withUnencryptedCookie('mt_trusted_device', $aliceToken)->post('/login', ['email' => $alice->email, 'password' => 'Secret-pass-1'])->assertRedirect('/otp/verify');
});

test('clearing the cookie means the OTP is required again', function () {
    $user = tdUser();
    tdTrust($user);
    tdFreshBrowser();
    tdLogin($user, null)->assertRedirect('/otp/verify');
    expect(TrustedDevice::first()->revoked_at)->toBeNull();   // the server-side record remains until it expires or is revoked
});

test('changing the password revokes every remembered device', function () {
    $user = tdUser();
    $t1 = tdTrust($user);
    tdFreshBrowser();
    $t2 = tdTrust($user);
    tdFreshBrowser();
    expect(TrustedDevice::whereNull('revoked_at')->count())->toBe(2);

    // Through the real profile password form.
    $this->actingAs($user)->put(route('password.update'), ['current_password' => 'Secret-pass-1', 'password' => 'Brand-new-pass-9', 'password_confirmation' => 'Brand-new-pass-9'])->assertSessionHasNoErrors();
    expect(TrustedDevice::whereNull('revoked_at')->count())->toBe(0);

    tdFreshBrowser();
    tdLogin($user->fresh(), $t1, 'Brand-new-pass-9')->assertRedirect('/otp/verify');
    tdFreshBrowser();
    tdLogin($user->fresh(), $t2, 'Brand-new-pass-9')->assertRedirect('/otp/verify');
});

test('a password reset (account recovery) and an email change also revoke trust, a name change does not', function () {
    $user = tdUser();
    $token = tdTrust($user);
    $user->update(['name' => 'Renamed Person']);
    expect(TrustedDevice::first()->revoked_at)->toBeNull();

    $user->update(['password' => 'Recovered-pass-3']);              // what the reset flow does
    expect(TrustedDevice::first()->revoked_at)->not->toBeNull();

    $user2 = tdUser();
    tdTrust($user2);
    $user2->update(['email' => 'changed-'.uniqid().'@gmail.com']);
    expect(TrustedDevice::where('user_id', $user2->id)->first()->revoked_at)->not->toBeNull();
});

test('only the signed-in user can see or revoke their own devices, and revoking needs the password', function () {
    $alice = tdUser();
    $bob = tdUser();
    $tokenA = tdTrust($alice);
    tdFreshBrowser();
    tdTrust($bob);
    tdFreshBrowser();
    $aliceDevice = TrustedDevice::where('user_id', $alice->id)->first();
    $bobDevice = TrustedDevice::where('user_id', $bob->id)->first();

    $this->get(route('profile.devices'))->assertRedirect('/login');
    $this->delete(route('profile.devices.destroy', $aliceDevice->id), ['current_password' => 'Secret-pass-1'])->assertRedirect('/login');

    $this->actingAs($alice)->get(route('profile.devices'))->assertOk()->assertSee('Remembered devices')
        ->assertSee($aliceDevice->label)->assertDontSee($bobDevice->token_hash)->assertDontSee($aliceDevice->token_hash);
    expect(substr_count($this->actingAs($alice)->get(route('profile.devices'))->getContent(), 'id="device-'))->toBe(1);

    // wrong / missing password: nothing is revoked
    $this->actingAs($alice)->delete(route('profile.devices.destroy', $aliceDevice->id), ['current_password' => 'nope'])->assertSessionHasErrorsIn('trustedDevices', 'current_password');
    $this->actingAs($alice)->delete(route('profile.devices.destroy', $aliceDevice->id))->assertSessionHasErrorsIn('trustedDevices', 'current_password');
    expect($aliceDevice->fresh()->revoked_at)->toBeNull();

    // someone else's device is "not found"
    $this->actingAs($alice)->delete(route('profile.devices.destroy', $bobDevice->id), ['current_password' => 'Secret-pass-1'])->assertNotFound();
    expect($bobDevice->fresh()->revoked_at)->toBeNull();

    $this->actingAs($alice)->withCookie('mt_trusted_device', $tokenA)
        ->delete(route('profile.devices.destroy', $aliceDevice->id), ['current_password' => 'Secret-pass-1'])->assertRedirect(route('profile.devices'));
    expect($aliceDevice->fresh()->revoked_at)->not->toBeNull();
    tdFreshBrowser();
    tdLogin($alice, $tokenA)->assertRedirect('/otp/verify');
});

test('"remove all" needs the password, revokes every device and clears the cookie', function () {
    $user = tdUser();
    tdTrust($user); tdFreshBrowser();
    $token = tdTrust($user); tdFreshBrowser();

    $this->actingAs($user)->delete(route('profile.devices.destroy-all'), ['current_password' => 'bad'])->assertSessionHasErrorsIn('trustedDevices', 'current_password');
    expect(TrustedDevice::whereNull('revoked_at')->count())->toBe(2);

    $response = $this->actingAs($user)->delete(route('profile.devices.destroy-all'), ['current_password' => 'Secret-pass-1']);
    $response->assertRedirect(route('profile.devices'));
    expect(TrustedDevice::whereNull('revoked_at')->count())->toBe(0)
        ->and($response->getCookie('mt_trusted_device', false)->getExpiresTime())->toBeLessThan(time());
});

test('the feature can be switched off: no checkbox, no cookie, no skipping', function () {
    $user = tdUser();
    $token = tdTrust($user);
    tdFreshBrowser();

    config(['trusted_devices.enabled' => false]);
    tdLogin($user, $token)->assertRedirect('/otp/verify');
    $this->get('/otp/verify')->assertOk()->assertDontSee('Remember this device');

    $response = tdVerify($user, true);
    $cookie = $response->getCookie('mt_trusted_device');
    expect($cookie === null || $cookie->getValue() === '')->toBeTrue();   // at most an expiring "forget" cookie, never a token
    expect(TrustedDevice::count())->toBe(1);
});

test('the checkbox is offered to password and Google accounts but not forced password changes', function () {
    $user = tdUser();
    tdLogin($user)->assertRedirect('/otp/verify');
    $this->get('/otp/verify')->assertOk()->assertSee('Remember this device for 30 days');

    $google = tdUser(['google_id' => 'g-123']);
    tdFreshBrowser();
    tdLogin($google)->assertRedirect('/otp/verify');
    $this->get('/otp/verify')->assertOk()->assertSee('Remember this device for 30 days');

    $response = tdVerify($google, true);
    expect($response->getCookie('mt_trusted_device'))->not->toBeNull()
        ->and(TrustedDevice::where('user_id', $google->id)->count())->toBe(1);

    $forced = tdUser(['requires_password_change' => true]);
    tdFreshBrowser();
    tdLogin($forced)->assertRedirect('/otp/verify');
    $response = tdVerify($forced, true);
    $response->assertRedirect(route('password.change.view'));
    expect($response->getCookie('mt_trusted_device'))->toBeNull()
        ->and(TrustedDevice::where('user_id', $forced->id)->count())->toBe(0);
});

test('Google login on a trusted device skips OTP but still authenticates with Google', function () {
    $user = tdUser(['google_id' => 'g-123']);

    $cookie = app(TrustedDeviceService::class)->issue(
        $user,
        \Illuminate\Http\Request::create('/')
    );

    $token = $cookie->getValue();

    $googleUser = Mockery::mock('Laravel\Socialite\Two\User');
    $googleUser->shouldReceive('getEmail')->andReturn($user->email);
    $googleUser->shouldReceive('getName')->andReturn($user->name);
    $googleUser->shouldReceive('getId')->andReturn('g-123');

    $driver = Mockery::mock('Laravel\Socialite\Contracts\Provider');
    $driver->shouldReceive('user')->once()->andReturn($googleUser);

    Socialite::shouldReceive('driver')
        ->with('google')
        ->andReturn($driver);

    Mail::fake();

    $response = $this->withCookie('mt_trusted_device', $token)
        ->get(route('auth.google.callback'));

    $response->assertRedirect(route('customer.dashboard'));
    $this->assertAuthenticatedAs($user);

    expect(session('otp_verified'))->toBeTrue()
        ->and($user->fresh()->otp_code)->toBeNull()
        ->and($user->fresh()->otp_expires_at)->toBeNull();

    Mail::assertNothingSent();
});

test('a wrong OTP does not create trust, even when the box is ticked', function () {
    $user = tdUser();
    tdLogin($user)->assertRedirect('/otp/verify');
    $user->forceFill(['otp_code' => Hash::make('123456'), 'otp_expires_at' => now()->addMinutes(10)])->save();
    auth()->guard('web')->forgetUser();
    $response = $this->post('/otp/verify', ['otp_code' => '000000', 'remember_device' => '1']);
    $response->assertSessionHasErrors('otp_code');
    expect($response->getCookie('mt_trusted_device'))->toBeNull()->and(TrustedDevice::count())->toBe(0);
});

test('consumption loses to a concurrent revocation, and old devices are pruned beyond the per-user cap', function () {
    $svc = app(TrustedDeviceService::class);
    $user = tdUser();
    $token = tdTrust($user);
    $device = TrustedDevice::first();

    expect($svc->find($user, $token))->not->toBeNull();
    $stale = TrustedDevice::find($device->id);
    $svc->revoke($device);                                   // revocation lands between "find" and "use"
    expect($svc->consume($user, $token))->toBeFalse();
    $svc->revoke($stale);                                    // double revoke is harmless
    expect(TrustedDevice::first()->revoked_at)->not->toBeNull();

    config(['trusted_devices.max_per_user' => 2]);
    $request = \Illuminate\Http\Request::create('/otp/verify', 'POST');
    for ($i = 0; $i < 4; $i++) {
        $svc->issue($user, $request);
    }
    expect(TrustedDevice::where('user_id', $user->id)->active()->count())->toBe(2);
});

test('trust tokens never appear in logs', function () {
    $logFile = storage_path('logs/laravel.log');
    $before = is_file($logFile) ? filesize($logFile) : 0;
    $user = tdUser();
    $token = tdTrust($user);
    tdFreshBrowser();
    tdLogin($user, $token);
    clearstatcache();
    $new = is_file($logFile) ? substr(file_get_contents($logFile), $before) : '';
    expect($new)->not->toContain($token);
});
