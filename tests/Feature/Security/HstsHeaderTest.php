<?php

use App\Http\Middleware\SecurityHeaders;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

/**
 * SYS4-07: Strict-Transport-Security is sent in production only.
 * Laravel builds a fresh application for every test, so the environment switch below
 * cannot leak into other tests.
 */
const HSTS_VALUE = 'max-age=31536000; includeSubDomains';

function hstsEnv(string $env): void
{
    app()['env'] = $env;
    expect(app()->environment($env))->toBeTrue();
}

function hstsAll($response): array
{
    return $response->headers->all('strict-transport-security');
}

test('production responses carry exactly the requested HSTS value', function () {
    hstsEnv('production');

    $response = $this->get('/login')->assertOk();

    expect(hstsAll($response))->toBe([HSTS_VALUE])
        ->and($response->headers->get('Strict-Transport-Security'))->toBe('max-age=31536000; includeSubDomains');
});

test('HSTS is never sent outside production', function () {
    foreach (['testing', 'local', 'staging'] as $env) {
        hstsEnv($env);
        expect(hstsAll($this->get('/login')->assertOk()))->toBe([], $env);
    }
});

test('the default test environment sends no HSTS', function () {
    expect(app()->environment('testing'))->toBeTrue();
    expect(hstsAll($this->get('/login')))->toBe([]);
});

test('no preload directive and no extra directives', function () {
    hstsEnv('production');
    $value = $this->get('/login')->headers->get('Strict-Transport-Security');

    expect(strtolower($value))->not->toContain('preload')
        ->and(explode(';', $value))->toHaveCount(2)
        ->and(file_get_contents(app_path('Http/Middleware/SecurityHeaders.php')))->not->toContain('preload;')
        ->and(file_get_contents(app_path('Http/Middleware/SecurityHeaders.php')))->not->toContain('; preload');
});

test('existing security headers and the CSP are unchanged in both environments', function () {
    $csp = "default-src 'self'; " .
        "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://unpkg.com https://www.google.com https://www.gstatic.com https://cdn.jsdelivr.net; " .
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
        "font-src 'self' https://fonts.gstatic.com; " .
        "img-src 'self' data: https:; " .
        "frame-src https://www.google.com https://www.recaptcha.net; " .
        "media-src 'self' https://assets.mixkit.co; " .
        "connect-src 'self' wss: https:;";

    foreach (['testing', 'production'] as $env) {
        hstsEnv($env);
        $r = $this->get('/login');
        expect($r->headers->get('X-Frame-Options'))->toBe('SAMEORIGIN', $env)
            ->and($r->headers->get('X-Content-Type-Options'))->toBe('nosniff')
            ->and($r->headers->get('Referrer-Policy'))->toBe('strict-origin-when-cross-origin')
            ->and($r->headers->get('Permissions-Policy'))->toBe('camera=(), microphone=(), geolocation=()')
            ->and($r->headers->get('Content-Security-Policy'))->toBe($csp);
    }
});

test('production redirects, errors, JSON and the health route all receive HSTS once', function () {
    hstsEnv('production');

    $responses = [
        'redirect' => $this->get('/dashboard')->assertRedirect(),   // guest bounced to login
        'not found' => $this->get('/definitely-not-a-page')->assertNotFound(),
        'json' => $this->getJson('/inventory-suggestions?field=search&q=a')->assertOk(),
        'health' => $this->get('/up')->assertOk(),
        'authenticated page' => $this->actingAs(User::factory()->create(['role' => 'customer']))
            ->withSession(['otp_verified' => true])->get('/browse')->assertOk(),
    ];

    foreach ($responses as $name => $response) {
        expect(hstsAll($response))->toBe([HSTS_VALUE], $name);
    }
});

test('a duplicate HSTS set by a route is replaced, never duplicated', function () {
    hstsEnv('production');
    Route::get('/_hsts-dup', fn () => response('x')->header('Strict-Transport-Security', 'max-age=1'));

    expect(hstsAll($this->get('/_hsts-dup')))->toBe([HSTS_VALUE]);
});

test('file responses that cannot take headers are untouched and do not error', function () {
    hstsEnv('production');
    Route::get('/_hsts-file', fn () => response()->download(base_path('composer.json')));

    $response = $this->get('/_hsts-file');
    $response->assertOk();
    // BinaryFileResponse has no header() helper, so the middleware skips it (existing behaviour).
    expect(hstsAll($response))->toBe([])
        ->and($response->headers->get('X-Frame-Options'))->toBeNull();
});

test('the middleware still runs globally and only adds one conditional header', function () {
    $src = file_get_contents(base_path('bootstrap/app.php'));
    expect($src)->toContain('SecurityHeaders::class');

    $mw = file_get_contents(app_path('Http/Middleware/SecurityHeaders.php'));
    expect(substr_count($mw, 'Strict-Transport-Security'))->toBe(1)
        ->and($mw)->toContain("app()->environment('production')");
    expect(class_exists(SecurityHeaders::class))->toBeTrue();
});
