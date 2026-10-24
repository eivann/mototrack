<?php

use App\Mail\OtpMail;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/**
 * UI pass: pinned dashboard sidebars, viewport-sized pages, MotoTrack branding (OTP page + e-mail +
 * receipts), product pictures with fallbacks, landing-page header state and lighter pages.
 * Layout behaviour itself is browser-side; these tests pin the server-rendered contract it relies on.
 */

beforeEach(fn () => Notification::fake());

function upOwner(): User
{
    return User::factory()->create(['role' => 'owner']);
}

function upCustomer(): User
{
    $user = User::factory()->create(['role' => 'customer']);
    Customer::create(['user_id' => $user->id, 'name' => $user->name, 'email' => $user->email]);

    return $user;
}

function upGet(User $user, string $uri)
{
    return test()->actingAs($user)->withSession(['otp_verified' => true])->get($uri);
}

test('both dashboards render the pinned sidebar contract and the measured chrome hooks', function () {
    foreach ([[upOwner(), '/dashboard'], [upCustomer(), '/customer/dashboard']] as [$user, $uri]) {
        $html = upGet($user, $uri)->assertOk()->getContent();

        expect($html)->toContain('<nav aria-label="Dashboard sections" class="mt-dash-sidebar')
            ->and($html)->toContain('data-mt-subheader')
            ->and($html)->toContain('data-mt-nav')
            ->and($html)->toContain('<main class="mt-main">')
            ->and($html)->toContain('.mt-dash-sidebar {')
            // The old fixed offset (which did not match the real header heights) is gone.
            ->and($html)->not->toContain('md:top-[176px]');
    }
});

test('full-height pages size themselves below the sticky header instead of adding a phantom scroll', function () {
    $html = upGet(upOwner(), '/sales/history')->assertOk()->getContent();

    expect($html)->toContain('main.mt-main .min-h-screen')
        ->and($html)->toContain('calc(100dvh - var(--mt-main-top))');
});

test('landing header binds section state with object syntax so Home is not stuck active', function () {
    $html = $this->get('/')->assertOk()->getContent();

    // Object syntax lets Alpine REMOVE the server-rendered active classes; the string form only adds.
    expect($html)->toContain(":class=\"{ &#039;text-indigo-600 dark:text-indigo-400&#039;: activeSection === &#039;home&#039;")
        ->and($html)->toContain('@click="setSection(\'services\')"')
        ->and($html)->toContain('@click="setSection(\'contact\')"')
        ->and($html)->not->toContain("activeSection === 'home' ? 'text-indigo-600")
        ->and($html)->toContain("['home', 'services', 'about', 'preorder', 'contact']");
});

test('off the landing page the header marks only the current route', function () {
    $html = upGet(upCustomer(), '/browse')->assertOk()->getContent();

    // Browse Parts is the current page; no section item is wired to the landing-page tracker here.
    expect(substr_count($html, 'aria-current="page"'))->toBeGreaterThanOrEqual(1)
        ->and($html)->not->toContain('@click="setSection(');
});

test('the OTP page, its e-mail and receipts carry the MotoTrack logo', function () {
    $user = upCustomer();
    $otpPage = $this->actingAs($user)->withSession(['otp_verified' => false])->get('/otp/verify')->assertOk()->getContent();
    expect($otpPage)->toContain('brand/mototrack-em.png')->and($otpPage)->toContain('MOTOTRACK');

    $mail = (new OtpMail('123456'))->render();
    // Absolute URL so mail clients can fetch it (asset() resolves against the app URL / request host).
    expect($mail)->toContain('src="'.asset('brand/mototrack-em.png').'"')
        ->and($mail)->toMatch('#src="https?://[^"]+/brand/mototrack-em\.png"#')
        ->and($mail)->toContain('123456');

    $item = InventoryItem::create(['part_name' => 'Logo Test Part', 'sku' => 'UI-1', 'price' => 10, 'quantity' => 5]);
    $owner = upOwner();
    $this->actingAs($owner)->post('/sales/store', ['cart_data' => json_encode([['id' => $item->id, 'quantity' => 1]]), 'amount_tendered' => '10']);
    $ref = DB::table('sale_headers')->value('transaction_reference');
    expect(upGet($owner, route('sales.receipt', $ref))->assertOk()->getContent())->toContain('brand/mototrack-em.png');
});

test('product pictures use the real image_path, keep proportions and fall back cleanly', function () {
    $user = upCustomer();
    $pictured = InventoryItem::create(['part_name' => 'Pictured Out', 'sku' => 'UI-P', 'price' => 50, 'quantity' => 0, 'image_path' => 'products/some-part.jpg']);

    // The pre-order card used a non-existent `image` attribute, so the picture never showed.
    $html = upGet($user, route('preorder.form', $pictured->id))->assertOk()->getContent();
    expect($html)->toContain('images/products/some-part.jpg')
        ->and($html)->toContain('loading="lazy" decoding="async"')
        ->and($html)->toContain('object-contain')
        ->and($html)->toContain('data-image-fallback');

    InventoryItem::create(['part_name' => 'No Picture', 'sku' => 'UI-N', 'price' => 50, 'quantity' => 4]);
    $browse = upGet($user, '/browse')->assertOk()->getContent();
    expect($browse)->toContain('No Picture: no picture available')
        ->and($browse)->not->toContain('object-cover group-hover:scale-105');
});

test('the customer dashboard no longer loads every unread notification', function () {
    $user = upCustomer();
    $rows = [];
    for ($i = 0; $i < 120; $i++) {
        $rows[] = ['id' => (string) Str::uuid(), 'type' => 'X', 'notifiable_type' => User::class, 'notifiable_id' => $user->id,
            'data' => json_encode(['title' => 'T'.$i, 'message' => 'm', 'link' => '#']), 'read_at' => null,
            'created_at' => now()->subMinutes($i), 'updated_at' => now()];
    }
    DB::table('notifications')->insert($rows);

    $hydrated = 0;
    Event::listen('eloquent.retrieved: '.DatabaseNotification::class, function () use (&$hydrated) {
        $hydrated++;
    });

    $html = upGet($user, '/customer/dashboard')->assertOk()->getContent();

    expect($hydrated)->toBeLessThanOrEqual(10)   // the bell's 10 latest, shared by every widget
        ->and($html)->toContain('120 NEW');
});

test('the cash pickup dialog uses the shared POS calculation and stays inside the viewport', function () {
    $html = view('components.cash-pickup-modal', ['action' => '/x', 'lines' => [['name' => 'Part', 'qty' => 1, 'subtotal' => 99.9]], 'total' => 99.9])->render();

    expect($html)->toContain('js/pos-totals.js')
        ->and($html)->toContain("totalText: '99.90'")
        ->and($html)->toContain('max-height: calc(100dvh - 2rem)');
});
