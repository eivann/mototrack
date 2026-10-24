<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

const GUEST_LAYOUT = 'resources/views/layouts/guest.blade.php';
const GOOGLE_INTER = 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap';

function guestLayoutSource(): string
{
    return file_get_contents(base_path(GUEST_LAYOUT));
}

test('guest layout loads Inter from Google Fonts, same link as the app layout', function () {
    $guest = guestLayoutSource();
    $app = file_get_contents(base_path('resources/views/layouts/app.blade.php'));

    expect($guest)->toContain('<link rel="preconnect" href="https://fonts.googleapis.com">')
        ->and($guest)->toContain('<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>')
        ->and($guest)->toContain(GOOGLE_INTER)
        ->and($app)->toContain(GOOGLE_INTER);
});

test('guest layout no longer references Bunny Fonts or Figtree', function () {
    $guest = strtolower(guestLayoutSource());

    expect($guest)->not->toContain('bunny')->and($guest)->not->toContain('figtree');
});

test('guest layout keeps its CRLF line endings', function () {
    $raw = guestLayoutSource();

    expect(substr_count($raw, "\n"))->toBe(substr_count($raw, "\r\n"));
});

test('every external font host in the guest layout is permitted by the existing CSP', function () {
    $csp = $this->get('/login')->headers->get('Content-Security-Policy');
    preg_match('/style-src ([^;]*)/', $csp, $style);
    preg_match('/font-src ([^;]*)/', $csp, $font);

    expect($style[1])->toContain('https://fonts.googleapis.com')
        ->and($font[1])->toContain('https://fonts.gstatic.com');

    preg_match_all('#https://[a-z0-9.\-]+#i', guestLayoutSource(), $m);
    $fontHosts = array_unique(array_filter($m[0], fn ($h) => str_contains($h, 'fonts.')));
    sort($fontHosts);

    expect(array_values($fontHosts))->toBe(['https://fonts.googleapis.com', 'https://fonts.gstatic.com']);
});

test('rendered guest-layout pages request Inter and no Bunny font', function () {
    $user = User::factory()->create(['role' => 'customer']);

    $otp = $this->actingAs($user)->withSession(['otp_verified' => false])->get(route('otp.verify'));
    $otp->assertOk()->assertSee(GOOGLE_INTER, false)->assertDontSee('fonts.bunny.net', false);

    $confirm = $this->actingAs($user)->get('/confirm-password');
    $confirm->assertOk()->assertSee(GOOGLE_INTER, false)->assertDontSee('fonts.bunny.net', false);
});

test('authentication behaviour is unaffected', function () {
    $this->get('/login')->assertOk();

    $user = User::factory()->create();
    $this->actingAs($user)->post('/confirm-password', ['password' => 'password'])
        ->assertRedirect()->assertSessionHasNoErrors();
    $this->actingAs($user)->post('/confirm-password', ['password' => 'wrong'])
        ->assertSessionHasErrors('password');
});

test('PDF receipt keeps DejaVu Sans', function () {
    $pdf = file_get_contents(base_path('resources/views/sales/pdf_receipt.blade.php'));

    expect($pdf)->toContain("font-family: 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif;");
});
