<?php

use App\Mail\OtpMail;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

/**
 * D6 corrections: special characters can no longer break scripts or inject markup, the terms modals
 * cannot trap the checkbox, the About picture is valid markup, the Browse filter panel follows
 * breakpoints, and the e-mail header is always MotoTrack-branded. Browser behaviour is pinned through
 * the server-rendered contract it depends on.
 */

beforeEach(fn () => Notification::fake());

function srCustomer(): User
{
    $user = User::factory()->create(['role' => 'customer']);
    Customer::create(['user_id' => $user->id, 'name' => $user->name, 'email' => $user->email]);

    return $user;
}

test('inventory edit serialises category, brand and SKU as safe JavaScript strings', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $item = InventoryItem::create([
        'part_name' => 'Seat Cover', 'sku' => "RID-O'N-1", 'price' => 10, 'quantity' => 2,
        'category' => "Rider's Accessories", 'brand' => 'O\'Neal "Pro" </script><b>x</b>',
    ]);

    $html = $this->actingAs($owner)->get(route('inventory.edit', $item))->assertOk()->getContent();
    $start = strpos($html, 'category: ');
    $data = substr($html, $start, strpos($html, 'loading: false,', $start) - $start);

    // Quotes, angle brackets and the closing script tag are escaped inside the JavaScript literals.
    expect($data)->toContain("category: 'Rider\\u0027s Accessories'")
        ->and($data)->toContain('sku: \'RID-O\\u0027N-1\'')
        ->and($data)->toContain('\\u003C\\/script\\u003E')
        ->and($data)->not->toContain("'Rider's")
        ->and($data)->not->toContain('</script>')
        ->and($data)->not->toContain('<b>');
});

test('the pre-order and checkout terms modals unlock the checkbox when the terms fit without scrolling', function () {
    $user = srCustomer();
    $item = InventoryItem::create(['part_name' => 'Out Part', 'sku' => 'SR-OUT', 'price' => 10, 'quantity' => 0]);

    $preorder = $this->actingAs($user)->get(route('preorder.form', $item->id))->assertOk()->getContent();
    expect($preorder)->toContain('@click="openTerms()"')
        ->and($preorder)->toContain('@scroll="checkTermsRead()"')
        ->and($preorder)->toContain('@resize.window.debounce.150ms="checkTermsRead()"')
        ->and($preorder)->toContain('el.scrollHeight <= el.clientHeight + 2')
        // Agreement is still explicit: the checkbox stays bound to `agreed` and gated on the terms.
        ->and($preorder)->toContain('x-model="agreed" :disabled="!scrolledToBottom"');

    $stocked = InventoryItem::create(['part_name' => 'In Part', 'sku' => 'SR-IN', 'price' => 10, 'quantity' => 5]);
    $this->actingAs($user)->post("/cart/add/{$stocked->id}", [], ['Accept' => 'application/json']);
    $checkout = $this->actingAs($user)->get('/checkout')->assertOk()->getContent();
    expect($checkout)->toContain('@click="openTerms()"')
        ->and($checkout)->toContain('@scroll="checkTermsRead()"')
        ->and($checkout)->toContain('x-model="agreed" :disabled="!scrolledToBottom"');
});

test('landing search renders results and toasts as text and escapes regex terms', function () {
    $html = $this->get('/')->assertOk()->getContent();
    preg_match('#// ── Safe rendering helpers.*?form\.addEventListener#s', $html, $m);
    $js = $m[0] ?? '';

    expect($js)->toContain('function escapeRegExp(value)')
        ->and($js)->toContain('.map(escapeRegExp)')
        ->and($js)->toContain("el('span', 'font-medium text-sm', message)")
        ->and($js)->toContain('function safeImageUrl(url)')
        ->and($js)->toContain('add.dataset.id = String(id)')
        // No dynamic value is interpolated into HTML any more.
        ->and($js)->not->toContain('${message}')
        ->and($js)->not->toContain('${item.')
        ->and($js)->not->toContain('highlightText(')
        ->and($js)->not->toMatch('/innerHTML\s*=\s*`[^`]*\$\{/');
});

test('the compatibility page toast inserts its message as text', function () {
    $html = $this->get('/compatibility')->assertOk()->getContent();

    expect($html)->toContain("text.textContent = String(message ?? '');")
        ->and($html)->not->toContain('<span class="font-medium">${message}</span>');
});

test('the About image is a single valid picture element with a WebP source and JPEG fallback', function () {
    $html = $this->get('/')->assertOk()->getContent();
    $about = substr($html, strpos($html, 'id="about"'), 3000);
    $about = substr($about, 0, strpos($about, '</picture>') + 10);

    expect(substr_count($about, '<picture>'))->toBe(1)
        ->and($about)->toContain('images/about.webp')
        ->and($about)->toContain('type="image/webp"')
        ->and($about)->toContain('images/about.jpg')
        ->and($about)->toContain('loading="lazy"')
        ->and($about)->toContain('width="1200" height="1600"');
});

test('browse filters follow the breakpoint and sit below the sticky search bar', function () {
    $html = $this->actingAs(srCustomer())->get('/browse')->assertOk()->getContent();

    expect($html)->toContain('x-show="showFilters || isDesktop"')
        ->and($html)->toContain("window.matchMedia('(min-width: 1024px)')")
        ->and($html)->toContain('x-ref="browseHeader"')
        ->and($html)->toContain('top: calc(var(--mt-nav-h, 89px) + var(--browse-header-h, 90px) + 1rem);')
        ->and($html)->not->toContain('lg:top-28')
        ->and($html)->not->toContain('window.innerWidth >= 1024');
});

test('the e-mail header is MotoTrack-branded even when the app name is Laravel', function () {
    config(['app.name' => 'Laravel']);

    $mail = (new OtpMail('654321'))->render();

    expect($mail)->not->toContain('laravel.com/img/notification-logo')
        ->and($mail)->toContain('alt="MotoTrack logo"')
        ->and($mail)->toContain('>MotoTrack</span>')
        ->and($mail)->toContain('src="'.asset('brand/mototrack-em.png').'"')
        ->and($mail)->toMatch('#src="https?://[^"/]+/brand/mototrack-em\.png"#');

    config(['app.name' => 'MotoTrack']);
    expect((new OtpMail('654321'))->render())->toContain('alt="MotoTrack logo"');
});
