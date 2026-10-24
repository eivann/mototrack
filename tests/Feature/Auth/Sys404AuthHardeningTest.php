<?php

use App\Http\Controllers\Auth\OtpController;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();
});

function s4Fail(User $user, int $times): void
{
    for ($i = 0; $i < $times; $i++) {
        test()->post('/login', ['email' => $user->email, 'password' => 'wrong-password']);
    }
}

function s4Key(User $user): string
{
    return Str::transliterate(Str::lower($user->email) . '|127.0.0.1');
}

function s4Otp(string $code = '123456', ?int $minutes = 10): User
{
    return User::factory()->create([
        'role' => 'customer',
        'otp_code' => Hash::make($code),
        'otp_expires_at' => now()->addMinutes($minutes),
    ]);
}

function s4WrongOtp(User $user, int $times)
{
    $last = null;
    for ($i = 0; $i < $times; $i++) {
        $last = test()->actingAs($user)->withSession(['otp_verified' => false])
            ->post(route('otp.verify.submit'), ['otp_code' => '000000']);
    }

    return $last;
}

/* ---------------------------------------------------------------- login lockout */

test('valid login still works and sends the user to OTP', function () {
    $user = User::factory()->create();
    $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/otp/verify');
    $this->assertAuthenticated();
    expect(session('otp_verified'))->toBeFalse();
});

test('invalid login is rejected with the standard message and stays a guest', function () {
    $user = User::factory()->create();
    $this->post('/login', ['email' => $user->email, 'password' => 'nope'])
        ->assertSessionHasErrors(['email' => trans('auth.failed')]);
    $this->assertGuest();
});

test('the failure window is 120 seconds and the threshold is still five', function () {
    expect(LoginRequest::LOCKOUT_DECAY_SECONDS)->toBe(120);
    $user = User::factory()->create();

    s4Fail($user, 4);
    $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/otp/verify');
});

test('five failures lock out even the correct password, with a throttle message', function () {
    $user = User::factory()->create();
    s4Fail($user, 5);

    $r = $this->post('/login', ['email' => $user->email, 'password' => 'password']);
    $r->assertSessionHasErrors('email');
    expect(session('errors')->first('email'))->toContain('Too many login attempts');
    $this->assertGuest();
    expect(RateLimiter::availableIn(s4Key($user)))->toBeGreaterThan(30)->toBeLessThanOrEqual(120);
});

test('the lockout expires after 120 seconds and login works again', function () {
    $user = User::factory()->create();
    s4Fail($user, 5);
    $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');

    $this->travel(119)->seconds();
    $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');

    $this->travel(3)->seconds();
    $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/otp/verify');
    $this->assertAuthenticated();
});

test('a successful login clears the failure counter', function () {
    $user = User::factory()->create();
    s4Fail($user, 4);
    expect(RateLimiter::attempts(s4Key($user)))->toBe(4);

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/otp/verify');
    expect(RateLimiter::attempts(s4Key($user)))->toBe(0);
});

test('lockout is per email, so other accounts are not locked', function () {
    $a = User::factory()->create();
    $b = User::factory()->create();
    s4Fail($a, 5);
    $this->post('/login', ['email' => $b->email, 'password' => 'password'])->assertRedirect('/otp/verify');
});

/* ---------------------------------------------------------------- per-route throttles */

test('public auth POST routes return 429 after 20 requests per minute', function () {
    $routes = [
        ['post', '/login', ['email' => 'x@y.test', 'password' => 'x']],
        ['post', '/register', ['name' => '']],
        ['post', '/forgot-password', ['email' => 'nobody@example.test']],
        ['post', '/reset-password', ['token' => 't', 'email' => 'nobody@example.test']],
    ];
    foreach ($routes as [$method, $uri, $data]) {
        for ($i = 0; $i < 20; $i++) {
            $this->$method($uri, $data)->assertStatus(302);
        }
        $this->$method($uri, $data)->assertStatus(429);
    }
});

test('throttle buckets are separate per route', function () {
    for ($i = 0; $i < 21; $i++) {
        $this->post('/login', ['email' => 'x@y.test', 'password' => 'x']);
    }
    $this->post('/forgot-password', ['email' => 'nobody@example.test'])->assertStatus(302);
});

test('sensitive authenticated password routes return 429 after 6 requests per minute', function () {
    $user = User::factory()->create(['role' => 'customer']);
    $actions = [
        ['post', '/confirm-password', ['password' => 'wrong']],
        ['put', '/password', ['current_password' => 'wrong', 'password' => 'a', 'password_confirmation' => 'b']],
        ['post', '/password/change', ['password' => 'a']],
    ];
    foreach ($actions as [$method, $uri, $data]) {
        for ($i = 0; $i < 6; $i++) {
            $this->actingAs($user)->withSession(['otp_verified' => true])->$method($uri, $data)->assertStatus(302);
        }
        $this->actingAs($user)->withSession(['otp_verified' => true])->$method($uri, $data)->assertStatus(429);
    }
});

test('throttle ignores spoofed forwarding headers', function () {
    for ($i = 0; $i < 20; $i++) {
        $this->withHeader('X-Forwarded-For', "10.0.0.$i")->post('/forgot-password', ['email' => 'n@example.test']);
    }
    $this->withHeader('X-Forwarded-For', '203.0.113.9')->post('/forgot-password', ['email' => 'n@example.test'])
        ->assertStatus(429);
});

test('OAuth redirect and callback routes carry no throttle middleware', function () {
    foreach (['auth.google', 'auth.google.callback'] as $name) {
        $mw = collect(app('router')->getRoutes()->getByName($name)->gatherMiddleware())->implode(',');
        expect($mw)->not->toContain('throttle');
    }
});

test('existing OTP throttles are unchanged', function () {
    $routes = app('router')->getRoutes();
    expect(implode(',', $routes->getByName('otp.verify.submit')->gatherMiddleware()))->toContain('throttle:6,1');
    expect(implode(',', $routes->getByName('otp.resend')->gatherMiddleware()))->toContain('throttle:3,1');
    expect(implode(',', $routes->getByName('otp.update-email')->gatherMiddleware()))->toContain('throttle:3,1');
});

test('OTP verify endpoint answers 429 on the 7th request in a minute', function () {
    $user = s4Otp();
    $r = s4WrongOtp($user, 7);
    $r->assertStatus(429);
});

test('OTP resend endpoint answers 429 on the 4th request in a minute', function () {
    $user = s4Otp();
    for ($i = 0; $i < 3; $i++) {
        $this->actingAs($user)->withSession(['otp_verified' => false])->post(route('otp.resend'))->assertSessionHas('status');
    }
    $this->actingAs($user)->withSession(['otp_verified' => false])->post(route('otp.resend'))->assertStatus(429);
});

/* ---------------------------------------------------------------- OTP wrong-attempt cap */

test('five wrong guesses invalidate the OTP and the right code no longer works', function () {
    $user = s4Otp('123456');
    s4WrongOtp($user, 4);
    expect($user->fresh()->otp_code)->not->toBeNull();

    s4WrongOtp($user, 1);
    $fresh = $user->fresh();
    expect($fresh->otp_code)->toBeNull()->and($fresh->otp_expires_at)->toBeNull();

    $this->actingAs($user)->withSession(['otp_verified' => false])
        ->post(route('otp.verify.submit'), ['otp_code' => '123456'])
        ->assertSessionHasErrors('otp_code');
    expect(session('otp_verified'))->not->toBeTrue();
});

test('correct OTP before the cap verifies and resets the counter', function () {
    $user = s4Otp('123456');
    s4WrongOtp($user, 4);

    $this->actingAs($user)->withSession(['otp_verified' => false])
        ->post(route('otp.verify.submit'), ['otp_code' => '123456'])
        ->assertRedirect(route('customer.dashboard'));
    expect(session('otp_verified'))->toBeTrue();
    expect(Cache::get('otp-attempts:' . $user->id . ':' . sha1((string) $user->otp_code)))->toBeNull();
    expect($user->fresh()->otp_code)->toBeNull();
});

test('a newly issued OTP gets a fresh set of five guesses', function () {
    $user = s4Otp('123456');
    s4WrongOtp($user, 4);

    // Resend issues a new OTP (hash changes, so the counter starts again).
    $this->travel(61)->seconds();
    $this->actingAs($user)->withSession(['otp_verified' => false])->post(route('otp.resend'))->assertSessionHas('status');
    expect($user->fresh()->otp_code)->not->toBeNull();
    $user->forceFill(['otp_code' => Hash::make('654321'), 'otp_expires_at' => now()->addMinutes(10)])->save();

    s4WrongOtp($user->fresh(), 4);
    expect($user->fresh()->otp_code)->not->toBeNull();
    $this->travel(61)->seconds();
    $this->actingAs($user->fresh())->withSession(['otp_verified' => false])
        ->post(route('otp.verify.submit'), ['otp_code' => '654321'])
        ->assertRedirect(route('customer.dashboard'));
});

test('an expired OTP cannot authenticate even with the right code', function () {
    $user = s4Otp('123456', 10);
    $this->travel(11)->minutes();
    $this->actingAs($user)->withSession(['otp_verified' => false])
        ->post(route('otp.verify.submit'), ['otp_code' => '123456'])
        ->assertSessionHasErrors('otp_code');
    expect(session('otp_verified'))->not->toBeTrue();
});

test('a user with no OTP issued cannot verify', function () {
    $user = User::factory()->create(['otp_code' => null, 'otp_expires_at' => null]);
    $this->actingAs($user)->withSession(['otp_verified' => false])
        ->post(route('otp.verify.submit'), ['otp_code' => '123456'])
        ->assertSessionHasErrors('otp_code');
});

test('OTP errors are the same generic message for wrong, capped and expired codes', function () {
    $msg = 'The OTP code is invalid or has expired.';
    $verify = fn ($u, $code = '000000') => test()->actingAs($u)->withSession(['otp_verified' => false])
        ->post(route('otp.verify.submit'), ['otp_code' => $code]);

    $verify(s4Otp('123456'))->assertSessionHasErrors(['otp_code' => $msg]);

    $capped = s4Otp('123456');
    s4WrongOtp($capped, 5);
    $this->travel(61)->seconds();
    $verify($capped, '123456')->assertSessionHasErrors(['otp_code' => $msg]);

    $expired = s4Otp('123456', 1);
    $this->travel(2)->minutes();
    $verify($expired, '123456')->assertSessionHasErrors(['otp_code' => $msg]);

    expect(OtpController::MAX_OTP_ATTEMPTS)->toBe(5);
});

test('login issues an OTP which is then protected by the cap', function () {
    $user = User::factory()->create(['role' => 'customer']);
    $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/otp/verify');
    expect($user->fresh()->otp_code)->not->toBeNull();

    // Same session: five wrong guesses (first window of the verify throttle is 6/min).
    for ($i = 0; $i < 5; $i++) {
        $this->post(route('otp.verify.submit'), ['otp_code' => '000000'])->assertSessionHasErrors('otp_code');
    }
    expect($user->fresh()->otp_code)->toBeNull();
    $this->get(route('customer.dashboard'))->assertRedirect(route('otp.verify'));
});

/* ---------------------------------------------------------------- logout / registration / Google */

test('logout ends the session and protected pages need login again', function () {
    $user = User::factory()->create(['role' => 'customer']);
    $this->post('/login', ['email' => $user->email, 'password' => 'password']);
    $this->withSession(['otp_verified' => true])->post('/logout')->assertRedirect('/');
    $this->assertGuest();
    expect(session('otp_verified'))->toBeNull();
    $this->get(route('customer.dashboard'))->assertRedirect('/login');
});

test('registration still lands on OTP with an unverified session', function () {
    $this->post('/register', [
        'name' => 'Ana Reyes',
        'email' => 'ana.reyes@gmail.com',
        'phone' => '09171234567',
        'password' => 'Str0ng!Passw0rd#9',
        'password_confirmation' => 'Str0ng!Passw0rd#9',
    ])->assertRedirect('/otp/verify');
    $this->assertAuthenticated();
    expect(session('otp_verified'))->toBeFalse();
});

function s4Google(string $email): void
{
    $g = Mockery::mock('Laravel\Socialite\Two\User');
    $g->shouldReceive('getEmail')->andReturn($email);
    $g->shouldReceive('getName')->andReturn('Gina Google');
    $g->shouldReceive('getId')->andReturn('g-123');
    $driver = Mockery::mock('Laravel\Socialite\Contracts\Provider');
    $driver->shouldReceive('user')->andReturn($g);
    Socialite::shouldReceive('driver')->with('google')->andReturn($driver);
}

test('Google login requires OTP, regenerates the session and sets no remember cookie', function () {
    s4Google('gina@gmail.com');
    $this->startSession();
    $before = session()->getId();

    $r = $this->get(route('auth.google.callback'));
    $r->assertRedirect(route('otp.verify'));
    $this->assertAuthenticated();
    expect(session('otp_verified'))->toBeFalse();
    expect(session()->getId())->not->toBe($before);

    $names = collect($r->headers->getCookies())->map->getName()->all();
    expect(collect($names)->filter(fn ($n) => str_starts_with($n, 'remember_web_'))->all())->toBe([]);

    $user = User::where('email', 'gina@gmail.com')->first();
    expect($user->remember_token)->toBeNull();
});

test('Google login for an existing user also skips remember-me and still needs OTP', function () {
    $user = User::factory()->create(['email' => 'old@gmail.com', 'role' => 'customer']);
    s4Google('old@gmail.com');

    $r = $this->get(route('auth.google.callback'));
    $r->assertRedirect(route('otp.verify'));
    expect(collect($r->headers->getCookies())->filter(fn ($c) => str_starts_with($c->getName(), 'remember_web_'))->count())->toBe(0);
    $this->get(route('customer.dashboard'))->assertRedirect(route('otp.verify'));
});

test('password login with remember checked still issues the cookie only for password logins', function () {
    $user = User::factory()->create();
    $r = $this->post('/login', ['email' => $user->email, 'password' => 'password', 'remember' => 'on']);
    expect(collect($r->headers->getCookies())->filter(fn ($c) => str_starts_with($c->getName(), 'remember_web_'))->count())->toBe(1);
});

/* ---------------------------------------------------------------- password flows / protected routes */

test('password confirmation and reset-link request still work under the new throttles', function () {
    $user = User::factory()->create(['role' => 'customer']);
    $this->actingAs($user)->withSession(['otp_verified' => true])
        ->post('/confirm-password', ['password' => 'password'])->assertRedirect();
    $this->post('/logout');

    $this->post('/forgot-password', ['email' => $user->email])->assertSessionHas('status');
});

test('verified sessions still reach protected routes', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $this->actingAs($owner)->withSession(['otp_verified' => true])->get(route('dashboard'))->assertOk();
});
