<?php

use App\Http\Requests\PreOrder\StorePreOrderRequest;
use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * SYS4-05 C1: the reCAPTCHA script must load only on pages that render the widget.
 */
function rcScripts(string $html): int
{
    return substr_count($html, 'recaptcha/api.js');
}

function rcCustomer(): User
{
    return User::factory()->create(['role' => 'customer']);
}

function rcGet(string $uri)
{
    return test()->actingAs(rcCustomer())->withSession(['otp_verified' => true])->get($uri);
}

test('both pre-order forms render the widget and load api.js exactly once, after the widget', function () {
    $item = InventoryItem::create(['part_name' => 'Brake Pad', 'price' => 500, 'quantity' => 0]);

    foreach ([route('preorder.form', $item->id), route('preorder.custom')] as $uri) {
        $html = rcGet($uri)->assertOk()->getContent();
        expect(substr_count($html, 'class="g-recaptcha"'))->toBe(1, $uri)
            ->and(rcScripts($html))->toBe(1, $uri)
            ->and(strpos($html, 'recaptcha/api.js'))->toBeGreaterThan(strpos($html, 'class="g-recaptcha"'));
    }
});

test('login and register still render the widget and load api.js exactly once', function () {
    foreach (['/login', '/register'] as $uri) {
        $html = $this->get($uri)->assertOk()->getContent();
        expect(str_contains($html, 'g-recaptcha'))->toBeTrue($uri)
            ->and(rcScripts($html))->toBe(1, $uri);
    }
});

test('pages without a CAPTCHA widget no longer load the script', function () {
    foreach (['/browse', '/customer/dashboard', '/notifications'] as $uri) {
        $html = rcGet($uri)->assertOk()->getContent();
        expect(rcScripts($html))->toBe(0, $uri)->and($html)->not->toContain('g-recaptcha');
    }

    $this->get('/faq')->assertOk()->assertDontSee('recaptcha/api.js', false);
    $this->get('/browse')->assertOk()->assertDontSee('recaptcha/api.js', false);
});

test('guest-layout pages no longer load the script', function () {
    $user = rcCustomer();

    $otp = $this->actingAs($user)->withSession(['otp_verified' => false])->get('/otp/verify')->assertOk()->getContent();
    $confirm = $this->actingAs($user)->withSession(['otp_verified' => true])->get('/confirm-password')->assertOk()->getContent();

    expect($otp)->toContain('<html')->and(rcScripts($otp))->toBe(0)
        ->and($confirm)->toContain('<html')->and(rcScripts($confirm))->toBe(0);
});

test('neither shared layout references reCAPTCHA', function () {
    foreach (['app', 'guest'] as $layout) {
        expect(file_get_contents(resource_path("views/layouts/{$layout}.blade.php")))->not->toContain('recaptcha');
    }
});

test('only the four widget views reference the api.js script, once each', function () {
    $found = [];
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('views'))) as $f) {
        if ($f->isFile() && ($n = substr_count(file_get_contents($f->getPathname()), 'recaptcha/api.js'))) {
            $found[str_replace('\\', '/', substr($f->getPathname(), strlen(resource_path('views')) + 1))] = $n;
        }
    }
    ksort($found);
    expect($found)->toBe([
        'auth/login.blade.php' => 1,
        'auth/register.blade.php' => 1,
        'preorders/custom.blade.php' => 1,
        'preorders/form.blade.php' => 1,
    ]);
});

test('pre-order CAPTCHA server rules are unchanged (bypass is testing-only)', function () {
    // Tests run with the testing bypass, so this cannot prove production enforcement;
    // it pins the rule shape: nullable under bypass, and the verification closure is
    // present in the request source for every non-bypass request.
    $req = new StorePreOrderRequest();
    expect($req->rules()['g-recaptcha-response'])->toBe(['nullable']);

    $src = file_get_contents(app_path('Http/Requests/PreOrder/StorePreOrderRequest.php'));
    expect($src)->toContain("['required', function")
        ->and($src)->toContain('https://www.google.com/recaptcha/api/siteverify')
        ->and($src)->toContain("app()->runningUnitTests()");
});
