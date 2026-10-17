<?php

use App\Models\InventoryItem;
use App\Models\Motorcycle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function rbOwner(): User
{
    return User::factory()->create(['role' => 'owner']);
}

function rbPart(string $name, array $extra = []): InventoryItem
{
    return InventoryItem::create(array_merge([
        'category' => 'Parts',
        'part_name' => $name,
        'cost_price' => 100,
        'price' => 200,
        'quantity' => 5,
        'minimum_stock' => 1,
        'low_stock_threshold' => 1,
    ], $extra));
}

function rbBike(string $brand, string $model, ?string $year = null): Motorcycle
{
    return Motorcycle::create(['brand' => $brand, 'model' => $model, 'year' => $year]);
}

function rbFit(InventoryItem $part, Motorcycle $bike): void
{
    $part->motorcycles()->attach($bike->id);
}

function rbSearch(array $q): array
{
    return test()->getJson(route('compatibility.check', $q))->assertOk()->json();
}

function rbNames(array $rows): array
{
    return collect($rows)->pluck('part_name')->sort()->values()->all();
}

/** Shared fixture: Honda TMX 125 + TMX 155, Yamaha Sniper; four parts. */
function rbCatalogue(): array
{
    $tmx125 = rbBike('Honda', 'TMX 125');
    $tmx155 = rbBike('Honda', 'TMX 155');
    $sniper = rbBike('Yamaha', 'Sniper 155');

    $pad = rbPart('Brake Pad');
    $chain = rbPart('Chain Set');
    $plug = rbPart('Spark Plug');
    $orphan = rbPart('Brake Lever'); // never linked to any motorcycle

    rbFit($pad, $tmx155);
    rbFit($pad, $sniper);
    rbFit($chain, $tmx125);
    rbFit($chain, $tmx155);
    rbFit($plug, $sniper);

    return compact('tmx125', 'tmx155', 'sniper', 'pad', 'chain', 'plug', 'orphan');
}

// ---------------------------------------------------------------------------
// Search combinations (strict, owner-confirmed fitments only)
// ---------------------------------------------------------------------------

test('brand + model returns only parts confirmed for that motorcycle', function () {
    rbCatalogue();

    expect(rbNames(rbSearch(['make' => 'Honda', 'model' => 'TMX 125'])))->toBe(['Chain Set']);
    expect(rbNames(rbSearch(['make' => 'Honda', 'model' => 'TMX 155'])))->toBe(['Brake Pad', 'Chain Set']);
});

test('brand + model + part name narrows to matching confirmed parts', function () {
    rbCatalogue();

    expect(rbNames(rbSearch(['make' => 'Honda', 'model' => 'TMX 155', 'part' => 'brake'])))->toBe(['Brake Pad']);
    // Brake Lever exists but is not confirmed for this bike, so it never appears.
    expect(rbNames(rbSearch(['make' => 'Honda', 'model' => 'TMX 155', 'part' => 'Brake Lever'])))->toBe([]);
    expect(rbSearch(['make' => 'Honda', 'model' => 'TMX 125', 'part' => 'Brake']))->toBe([]);
});

test('brand only returns parts linked to any model of that brand, once each', function () {
    rbCatalogue();

    $rows = rbSearch(['make' => 'honda']);

    expect(rbNames($rows))->toBe(['Brake Pad', 'Chain Set']);
    expect(collect($rows)->every(fn ($r) => $r['compatibility_status'] === 'confirmed'))->toBeTrue();

    $chain = collect($rows)->firstWhere('part_name', 'Chain Set');
    expect($chain['matched_fitment'])->toBe(['Honda TMX 125', 'Honda TMX 155']);

    expect(rbNames(rbSearch(['make' => 'Yamaha'])))->toBe(['Brake Pad', 'Spark Plug']);
});

test('part name only returns matching products across models without any compatibility claim', function () {
    rbCatalogue();

    $rows = rbSearch(['part' => 'brake']);

    expect(rbNames($rows))->toBe(['Brake Lever', 'Brake Pad']); // includes the never-linked part
    foreach ($rows as $row) {
        expect($row)->not->toHaveKey('compatibility_status');
        expect($row)->not->toHaveKey('matched_fitment');
    }
});

test('model or year without a brand returns nothing and signals that a brand is required', function () {
    rbCatalogue();

    $this->getJson(route('compatibility.check', ['model' => 'TMX 155']))
        ->assertOk()
        ->assertExactJson([])
        ->assertHeader('X-Compat-Notice', 'brand-required');

    $this->getJson(route('compatibility.check', ['year' => '2020']))
        ->assertOk()->assertExactJson([])->assertHeader('X-Compat-Notice', 'brand-required');

    // Even with a part name: a model alone never produces compatibility results.
    expect(rbSearch(['model' => 'TMX 155', 'part' => 'Brake']))->toBe([]);
});

test('an empty search returns nothing instead of the whole catalogue', function () {
    rbCatalogue();

    expect(rbSearch([]))->toBe([]);
    expect(rbSearch(['make' => '', 'model' => '', 'year' => '', 'part' => '   ']))->toBe([]);
});

test('model names are matched exactly (TMX 15 never matches TMX 155) with case and spacing ignored', function () {
    rbCatalogue();

    expect(rbSearch(['make' => 'Honda', 'model' => 'TMX 15']))->toBe([]);
    expect(rbSearch(['make' => 'Honda', 'model' => 'TMX 1555']))->toBe([]);
    expect(rbNames(rbSearch(['make' => ' HONDA ', 'model' => '  tmx   155'])))->toBe(['Brake Pad', 'Chain Set']);
});

test('free-text brand, model and Universal labels on inventory never create compatibility', function () {
    rbPart('Texty Mirror', ['brand' => 'Honda', 'model' => 'TMX 155']);
    rbPart('Universal Grip', ['brand' => 'Honda', 'model' => 'Universal']);
    $linked = rbPart('Linked Lever');
    rbFit($linked, rbBike('Honda', 'TMX 155'));

    expect(rbNames(rbSearch(['make' => 'Honda', 'model' => 'TMX 155'])))->toBe(['Linked Lever']);
    expect(rbNames(rbSearch(['make' => 'Honda'])))->toBe(['Linked Lever']);
});

test('a brand with no recorded motorcycles returns nothing', function () {
    rbCatalogue();

    expect(rbSearch(['make' => 'Suzuki']))->toBe([]);
    expect(rbSearch(['make' => 'Suzuki', 'part' => 'Brake']))->toBe([]);
});

test('free-text q is a part-name search and never stands in for a motorcycle model', function () {
    rbCatalogue();

    expect(rbNames(rbSearch(['q' => 'chain'])))->toBe(['Chain Set']);
    expect(rbSearch(['q' => 'TMX 155']))->toBe([]); // no part is named that
});

test('year filter still works with a brand and stays inclusive', function () {
    $part = rbPart('Ranged Part');
    rbFit($part, rbBike('Honda', 'Beat', '2018-2020'));

    expect(rbNames(rbSearch(['make' => 'Honda', 'model' => 'Beat', 'year' => '2019'])))->toBe(['Ranged Part']);
    expect(rbSearch(['make' => 'Honda', 'model' => 'Beat', 'year' => '2021']))->toBe([]);
});

// ---------------------------------------------------------------------------
// Dependent dropdown data and the conditional Year filter
// ---------------------------------------------------------------------------

test('brand and model options come only from fitment records and are scoped by brand', function () {
    rbCatalogue();
    rbPart('Texty', ['brand' => 'Suzuki', 'model' => 'Raider']);

    $makes = $this->getJson(route('compatibility.options', ['type' => 'makes']))->json();
    expect($makes)->toBe(['Honda', 'Yamaha']);

    expect($this->getJson(route('compatibility.options', ['type' => 'models', 'make' => 'Honda']))->json())
        ->toBe(['TMX 125', 'TMX 155']);
    expect($this->getJson(route('compatibility.options', ['type' => 'models', 'make' => 'Yamaha']))->json())
        ->toBe(['Sniper 155']);
});

test('the year option list is empty when no fitment carries a year', function () {
    rbCatalogue();

    expect($this->getJson(route('compatibility.options', ['type' => 'years']))->json())->toBe([]);
});

test('the year option list appears once any fitment is year-specific', function () {
    rbBike('Honda', 'Beat', '2019-2020');

    expect($this->getJson(route('compatibility.options', ['type' => 'years']))->json())->toBe(['2020', '2019']);
});

test('customer page ships a disabled model select, a hidden year block and an empty-data note', function () {
    $html = $this->get(route('compatibility'))->assertOk()->getContent();

    expect($html)
        ->toContain('id="modelSelect" name="model" disabled')
        ->toContain('Select a brand first')
        ->toContain('id="yearWrap"')
        ->toContain('id="noFitmentNote"')
        ->toContain('You can still search by part name');
});

// ---------------------------------------------------------------------------
// Owner tool: multi-model linking, idempotency, canonical spelling
// ---------------------------------------------------------------------------

test('one product can be linked to several models in a single save', function () {
    $part = rbPart('Air Filter');

    $this->actingAs(rbOwner())->post(route('compatibility.store', $part), [
        'brand' => 'Honda', 'model' => 'TMX 125, TMX 155 ;  Wave 110', 'year' => '',
    ])->assertSessionHasNoErrors()->assertSessionHas('success');

    expect($part->motorcycles()->orderBy('model')->pluck('model')->all())
        ->toBe(['TMX 125', 'TMX 155', 'Wave 110']);
    expect(Motorcycle::count())->toBe(3);
});

test('repeated and blank entries in the model list are ignored', function () {
    $part = rbPart('Air Filter');

    $this->actingAs(rbOwner())->post(route('compatibility.store', $part), [
        'brand' => 'Honda', 'model' => ' TMX 125 , tmx  125,, ',
    ])->assertSessionHasNoErrors();

    expect($part->motorcycles()->count())->toBe(1);
});

test('brand and model spellings reuse existing records regardless of case and spacing', function () {
    $a = rbPart('Air Filter');
    $b = rbPart('Oil Filter');
    $owner = rbOwner();

    $this->actingAs($owner)->post(route('compatibility.store', $a), ['brand' => 'Honda', 'model' => 'TMX 125']);
    $this->actingAs($owner)->post(route('compatibility.store', $b), ['brand' => '  honda ', 'model' => 'tmx   125']);

    expect(Motorcycle::count())->toBe(1); // one shared motorcycle record
    expect($b->motorcycles()->first()->brand)->toBe('Honda');
    expect($a->motorcycles()->first()->id)->toBe($b->motorcycles()->first()->id);
});

test('a different year is a different fitment record', function () {
    $part = rbPart('Air Filter');
    $owner = rbOwner();

    $this->actingAs($owner)->post(route('compatibility.store', $part), ['brand' => 'Honda', 'model' => 'Beat', 'year' => '2019']);
    $this->actingAs($owner)->post(route('compatibility.store', $part), ['brand' => 'Honda', 'model' => 'Beat', 'year' => '2020']);
    $this->actingAs($owner)->post(route('compatibility.store', $part), ['brand' => 'Honda', 'model' => 'Beat']);

    expect($part->motorcycles()->count())->toBe(3);
});

test('submitting the same fitment twice (double click or second tab) links it once', function () {
    $part = rbPart('Air Filter');
    $owner = rbOwner();
    $payload = ['brand' => 'Honda', 'model' => 'TMX 125, TMX 155'];

    $this->actingAs($owner)->post(route('compatibility.store', $part), $payload)->assertSessionHasNoErrors();
    $this->actingAs($owner)->post(route('compatibility.store', $part), $payload)->assertSessionHasErrors('fitment');

    expect($part->motorcycles()->count())->toBe(2);
    expect(\DB::table('part_compatibility')->where('inventory_item_id', $part->id)->count())->toBe(2);
    expect(Motorcycle::count())->toBe(2);
});

test('a partly duplicate list adds the new models and reports the skipped one', function () {
    $part = rbPart('Air Filter');
    $owner = rbOwner();
    $this->actingAs($owner)->post(route('compatibility.store', $part), ['brand' => 'Honda', 'model' => 'TMX 125']);

    $this->actingAs($owner)->post(route('compatibility.store', $part), ['brand' => 'Honda', 'model' => 'TMX 125, TMX 155'])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success', fn ($m) => str_contains($m, 'TMX 155') && str_contains($m, 'skipped'));

    expect($part->motorcycles()->count())->toBe(2);
});

test('over-long model names and empty lists are rejected without creating records', function () {
    $part = rbPart('Air Filter');
    $owner = rbOwner();

    $this->actingAs($owner)->post(route('compatibility.store', $part), ['brand' => 'Honda', 'model' => str_repeat('A', 101)])
        ->assertSessionHasErrors('model');
    $this->actingAs($owner)->post(route('compatibility.store', $part), ['brand' => 'Honda', 'model' => ' , ; '])
        ->assertSessionHasErrors('model');

    expect(Motorcycle::count())->toBe(0);
    expect($part->motorcycles()->count())->toBe(0);
});

test('saving a fitment never touches the free-text brand or model on the inventory item', function () {
    $part = rbPart('Air Filter', ['brand' => 'Acme', 'model' => 'Universal']);

    $this->actingAs(rbOwner())->post(route('compatibility.store', $part), ['brand' => 'Honda', 'model' => 'TMX 125']);

    $part->refresh();
    expect($part->brand)->toBe('Acme')->and($part->model)->toBe('Universal');
});

test('a failed save redirects back with the typed values so the form can reopen', function () {
    $part = rbPart('Air Filter');

    $response = $this->actingAs(rbOwner())
        ->from(route('compatibility.manage'))
        ->post(route('compatibility.store', $part), [
            '_item' => $part->id, 'brand' => 'Yamaha', 'model' => 'Sniper', 'year' => 'abc',
        ]);

    $response->assertRedirect(route('compatibility.manage'))->assertSessionHasErrors('year');

    $html = $this->actingAs(rbOwner())->withSession(['_old_input' => [
        '_item' => (string) $part->id, 'brand' => 'Yamaha', 'model' => 'Sniper', 'year' => 'abc',
    ]])->get(route('compatibility.manage'))->getContent();

    expect($html)->toContain('itemId\\u0022:'.$part->id)->toContain('Sniper');
});

// ---------------------------------------------------------------------------
// Product selector endpoint
// ---------------------------------------------------------------------------

test('the product selector searches name, SKU and brand and needs two characters', function () {
    $owner = rbOwner();
    rbPart('Brake Pad', ['sku' => 'BRK-001', 'brand' => 'Acme']);
    rbPart('Chain Set', ['sku' => 'CHN-777', 'brand' => 'Zeta']);

    $url = fn ($q) => route('compatibility.manage.parts', ['q' => $q]);

    expect($this->actingAs($owner)->getJson($url('b'))->assertOk()->json())->toBe([]);
    expect(collect($this->actingAs($owner)->getJson($url('brake'))->json())->pluck('part_name')->all())->toBe(['Brake Pad']);
    expect(collect($this->actingAs($owner)->getJson($url('CHN-7'))->json())->pluck('part_name')->all())->toBe(['Chain Set']);
    expect(collect($this->actingAs($owner)->getJson($url('zeta'))->json())->pluck('part_name')->all())->toBe(['Chain Set']);

    $row = $this->actingAs($owner)->getJson($url('brake'))->json()[0];
    expect($row)->toHaveKeys(['id', 'part_name', 'sku', 'brand']);
});

test('the product selector returns at most fifteen results', function () {
    foreach (range(1, 20) as $i) {
        rbPart("Gasket {$i}");
    }

    expect($this->actingAs(rbOwner())->getJson(route('compatibility.manage.parts', ['q' => 'Gasket']))->json())->toHaveCount(15);
});

// ---------------------------------------------------------------------------
// Permissions: only the owner can maintain fitments
// ---------------------------------------------------------------------------

test('guests and customers cannot use any owner compatibility endpoint', function () {
    $part = rbPart('Air Filter');
    $bike = rbBike('Honda', 'TMX 125');
    rbFit($part, $bike);
    $customer = User::factory()->create(['role' => 'customer']);

    $calls = [
        ['get', route('compatibility.manage')],
        ['get', route('compatibility.manage.options')],
        ['get', route('compatibility.manage.parts', ['q' => 'air'])],
        ['post', route('compatibility.store', $part), ['brand' => 'Honda', 'model' => 'Beat']],
        ['patch', route('compatibility.update', [$part, $bike]), ['notes' => 'x']],
        ['delete', route('compatibility.destroy', [$part, $bike])],
    ];

    foreach ($calls as $call) {
        [$method, $url] = $call;
        $data = $call[2] ?? [];
        $guest = $this->{$method}($url, $data);
        expect($guest->status())->toBeIn([302, 401, 403]);

        $this->actingAs($customer)->{$method}($url, $data)->assertForbidden();
        auth()->logout();
    }

    expect(Motorcycle::count())->toBe(1);
    expect($part->motorcycles()->count())->toBe(1);
    expect($part->motorcycles()->first()->pivot->notes)->toBeNull();
});

// ---------------------------------------------------------------------------
// Database relationships
// ---------------------------------------------------------------------------

test('removing a fitment keeps the motorcycle and other parts linked to it', function () {
    $a = rbPart('Air Filter');
    $b = rbPart('Oil Filter');
    $bike = rbBike('Honda', 'TMX 125');
    rbFit($a, $bike);
    rbFit($b, $bike);

    $this->actingAs(rbOwner())->delete(route('compatibility.destroy', [$a, $bike]))->assertSessionHas('success');

    expect(Motorcycle::find($bike->id))->not->toBeNull();
    expect($a->motorcycles()->count())->toBe(0);
    expect($b->motorcycles()->count())->toBe(1);
});

test('deleting a part removes its fitment links but not the motorcycle', function () {
    $part = rbPart('Air Filter');
    $bike = rbBike('Honda', 'TMX 125');
    rbFit($part, $bike);

    \DB::statement('PRAGMA foreign_keys = ON');
    $part->delete();

    expect(\DB::table('part_compatibility')->count())->toBe(0);
    expect(Motorcycle::find($bike->id))->not->toBeNull();
});

test('a note update changes only the pivot note, never make, model or year', function () {
    $part = rbPart('Air Filter');
    $bike = rbBike('Honda', 'TMX 125', '2020');
    rbFit($part, $bike);

    $this->actingAs(rbOwner())->patch(route('compatibility.update', [$part, $bike]), [
        'notes' => 'Caliper only', 'brand' => 'Hacked', 'model' => 'Hacked', 'year' => '1999',
    ])->assertSessionHasNoErrors();

    $bike->refresh();
    expect($bike->brand)->toBe('Honda')->and($bike->model)->toBe('TMX 125')->and($bike->year)->toBe('2020');
    expect($part->motorcycles()->first()->pivot->notes)->toBe('Caliper only');
});

// ---------------------------------------------------------------------------
// Alpine scope (structure). Real in-browser behaviour is exercised by
// tests/Browser/compatibility_release_b.py (headless Chromium).
// ---------------------------------------------------------------------------

test('every fitment control and the modal share one Alpine scope and the null-record defect is gone', function () {
    $part = rbPart('Air Filter');
    $bike = rbBike('Honda', 'TMX 125');
    rbFit($part, $bike);

    $html = $this->actingAs(rbOwner())->get(route('compatibility.manage'))->assertOk()->getContent();

    expect($html)->toContain('x-data="fitmentManager(');
    expect(substr_count($html, 'x-data="fitmentManager('))->toBe(1);

    // Defects of the deployed page: record starting as null and window-level events
    // dispatched from controls that had no Alpine ancestor.
    expect($html)->not->toContain('editRecord: null');
    expect($html)->not->toContain("\$dispatch('open-fitment'");

    $root = strpos($html, 'x-data="fitmentManager(');
    foreach (['openAdd(', 'openEdit(', 'role="dialog"', 'id="quick-part"'] as $needle) {
        expect(strpos($html, $needle))->toBeGreaterThan($root);
    }

    // Closing the root div is the last structural element before the script.
    expect($html)->toContain('function fitmentManager(restore)');
    expect($html)->toContain('close()')->toContain('this.form = blank()');
});
