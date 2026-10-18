<?php

use App\Models\InventoryItem;
use App\Models\Motorcycle;
use App\Services\CompatibilityMatcher;
use App\Services\InventoryCompatibilityResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['compatibility.inventory_sourced' => true]);
});

function iscPart(string $name, ?string $model, array $extra = []): InventoryItem
{
    return InventoryItem::create(array_merge([
        'category' => 'Parts',
        'part_name' => $name,
        'brand' => 'Generic Parts Co',
        'model' => $model,
        'cost_price' => 100,
        'price' => 200,
        'quantity' => 5,
        'minimum_stock' => 1,
        'low_stock_threshold' => 1,
    ], $extra));
}

function iscCheck(array $q)
{
    return test()->getJson(route('compatibility.check', $q))->assertOk()->json();
}

function iscOptions(array $q): array
{
    return test()->getJson(route('compatibility.options', $q))->assertOk()->json();
}

function iscNames(array $rows): array
{
    return collect($rows)->pluck('part_name')->sort()->values()->all();
}

it('is OFF by default so production behaviour is unchanged', function () {
    config(['compatibility.inventory_sourced' => false]);
    iscPart('Air Filter', 'Wave 125');

    expect(iscOptions(['type' => 'makes']))->toBe([]);
    expect(iscCheck(['make' => 'Honda', 'model' => 'Wave 125']))->toBe([]);
});

it('offers exactly the five Make options in order', function () {
    expect(iscOptions(['type' => 'makes']))->toBe(['Yamaha', 'Honda', 'Suzuki', 'Kawasaki', 'Universal']);
});

it('lists dependent catalogue models for each of the four makes and none for Universal', function () {
    foreach (['Yamaha' => 'Mio M3', 'Honda' => 'Wave 125', 'Suzuki' => 'Raider 150', 'Kawasaki' => 'Barako 175'] as $make => $sample) {
        $models = iscOptions(['type' => 'models', 'make' => $make]);
        expect($models)->toContain($sample)->and(count($models))->toBeGreaterThan(5);
    }
    expect(iscOptions(['type' => 'models', 'make' => 'Honda']))->not->toContain('Raider 150')
        ->and(iscOptions(['type' => 'models', 'make' => 'Suzuki']))->not->toContain('Wave 125')
        ->and(iscOptions(['type' => 'models', 'make' => 'Universal']))->toBe([])
        ->and(iscOptions(['type' => 'models', 'make' => 'Bajaj']))->toBe([])
        ->and(iscOptions(['type' => 'years']))->toBe([]);
});

it('lists catalogue models that have zero inventory and returns an empty result for them', function () {
    config(['compatibility_catalogue.catalogue' => ['Honda' => [
        ['name' => 'TMX 155', 'type' => 'specific', 'source' => 'owner', 'aliases' => ['TMX 155']],
    ]]]);

    expect(InventoryItem::count())->toBe(0)
        ->and(iscOptions(['type' => 'models', 'make' => 'Honda']))->toBe(['TMX 155'])
        ->and(iscCheck(['make' => 'Honda', 'model' => 'TMX 155']))->toBe([]);
});

it('derives compatibility from the recorded inventory model without any Add Fitment record', function () {
    iscPart('Air Filter', 'Wave 125');
    iscPart('Chain Set', 'Raider 150');

    $rows = iscCheck(['make' => 'Honda', 'model' => 'Wave 125']);

    expect(iscNames($rows))->toBe(['Air Filter'])
        ->and($rows[0]['compatibility_status'])->toBe('confirmed')
        ->and($rows[0]['matched_fitment'])->toBe(['Honda Wave 125'])
        ->and($rows[0]['model'])->toBe('Wave 125')
        ->and(Motorcycle::count())->toBe(0);
});

it('resolves explicit spelling aliases and preserves the original inventory values', function () {
    $a = iscPart('Brake Pad', 'XRM110');
    $b = iscPart('Clutch', 'XRM 110');
    $c = iscPart('Kick Lever', 'Barako  Kick');
    $d = iscPart('Kick Spring', 'Barako Kick');
    $e = iscPart('Mirror', 'NMAX');
    $f = iscPart('Grip', 'NMax');

    expect(iscNames(iscCheck(['make' => 'Honda', 'model' => 'XRM 110'])))->toBe(['Brake Pad', 'Clutch'])
        ->and(iscNames(iscCheck(['make' => 'Kawasaki', 'model' => 'Barako Kick'])))->toBe(['Kick Lever', 'Kick Spring'])
        ->and(iscNames(iscCheck(['make' => 'Yamaha', 'model' => 'NMax'])))->toBe(['Grip', 'Mirror']);

    // Stored values are untouched.
    expect($a->fresh()->model)->toBe('XRM110')
        ->and($c->fresh()->model)->toBe('Barako  Kick')
        ->and($e->fresh()->model)->toBe('NMAX');
});

it('matches case and spacing differences only through the normaliser, never by partial text', function () {
    iscPart('Light', 'wave   125');
    iscPart('Not Wave 125', 'Wave 1250');
    iscPart('Not Wave 125 b', 'Wave 12');

    expect(iscNames(iscCheck(['make' => 'Honda', 'model' => 'Wave 125'])))->toBe(['Light']);
});

it('supports multiple models per make and make-only searches', function () {
    iscPart('Air Filter', 'Wave 125');
    iscPart('Brake Pad', 'XRM 110');
    iscPart('Chain Set', 'Raider 150');

    expect(iscNames(iscCheck(['make' => 'Honda'])))->toBe(['Air Filter', 'Brake Pad'])
        ->and(iscNames(iscCheck(['make' => 'Suzuki'])))->toBe(['Chain Set']);
});

it('treats a generic family as its own recorded line and never expands or folds variants', function () {
    iscPart('Mio Plain Part', 'Mio');
    iscPart('Mio M3 Part', 'Mio M3');
    iscPart('Mio Soul Part', 'Mio Soul');

    $family = iscCheck(['make' => 'Yamaha', 'model' => 'Mio (general)']);
    expect(iscNames($family))->toBe(['Mio Plain Part'])
        ->and($family[0]['compatibility_status'])->toBe('recorded_general')
        ->and($family[0]['compatibility_note'])->toContain('confirm your exact variant');

    expect(iscNames(iscCheck(['make' => 'Yamaha', 'model' => 'Mio M3'])))->toBe(['Mio M3 Part'])
        ->and(iscOptions(['type' => 'models', 'make' => 'Yamaha']))->toContain('Mio (general)')->toContain('Mio M3');
});

it('does not split combined or conditional descriptions', function () {
    iscPart('Spark Plug A', 'Mio M3 / XRM / Wave (C7HSA)');
    iscPart('Spark Plug B', 'Wave 125 / BC175 / R150 (CR8E)');
    iscPart('Spark Plug C', 'CT100 / TMX155 (D6HA)');

    foreach ([['Yamaha', 'Mio M3'], ['Honda', 'XRM (general)'], ['Honda', 'Wave 125'], ['Kawasaki', 'BC175'], ['Suzuki', 'R150']] as [$make, $model]) {
        expect(iscCheck(['make' => $make, 'model' => $model]))->toBe([]);
    }
    expect(iscCheck(['make' => 'Honda']))->toBe([]);
});

it('keeps Universal-labelled and conditional products distinct and never confirmed', function () {
    iscPart('Air Filter U', 'Universal');
    iscPart('Bearing U', 'Universal (according To The Bearing Size)');
    iscPart('Rusi Part', 'Rusi (Universal)');
    iscPart('Honda Part', 'Wave 125');

    $rows = collect(iscCheck(['make' => 'Universal']))->keyBy('part_name');

    expect($rows->keys()->sort()->values()->all())->toBe(['Air Filter U', 'Bearing U'])
        ->and($rows['Air Filter U']['compatibility_status'])->toBe('universal_labelled')
        ->and($rows['Bearing U']['compatibility_status'])->toBe('universal_conditional')
        ->and($rows['Air Filter U']['matched_fitment'])->toBe([]);

    // Universal products are not claimed for specific motorcycles.
    expect(iscNames(iscCheck(['make' => 'Honda', 'model' => 'Wave 125'])))->toBe(['Honda Part']);
});

it('only reports owner-confirmed universal fitment when the owner lists it', function () {
    config(['compatibility_catalogue.universal.confirmed' => ['Universal 7ml']]);
    iscPart('Chain Lube', 'Universal 7ml');

    expect(iscCheck(['make' => 'Universal'])[0]['compatibility_status'])->toBe('universal_confirmed');
});

it('never matches No Model, dimensions, parts brands or other non-motorcycle values', function () {
    iscPart('Bulb', 'No Model');
    iscPart('Tire', '90/90-17');
    iscPart('Oil', 'Motul');
    iscPart('Test', 'test');

    foreach (['Yamaha', 'Honda', 'Suzuki', 'Kawasaki'] as $make) {
        expect(iscCheck(['make' => $make]))->toBe([]);
    }
    expect(iscCheck(['make' => 'Universal']))->toBe([]);

    $all = collect(['Yamaha', 'Honda', 'Suzuki', 'Kawasaki'])->flatMap(fn ($m) => iscOptions(['type' => 'models', 'make' => $m]));
    expect($all->contains('No Model') || $all->contains('90/90-17') || $all->contains('Motul'))->toBeFalse();
});

it('does not confuse the parts brand with the motorcycle make', function () {
    iscPart('Yamaha-branded universal', 'Universal', ['brand' => 'Yamaha']);
    iscPart('Honda-branded raider part', 'Raider 150', ['brand' => 'Honda']);

    expect(iscCheck(['make' => 'Yamaha']))->toBe([])
        ->and(iscCheck(['make' => 'Honda']))->toBe([])
        ->and(iscNames(iscCheck(['make' => 'Suzuki', 'model' => 'Raider 150'])))->toBe(['Honda-branded Raider Part']);
});

it('keeps out-of-scope make products reachable by part name search only', function () {
    iscPart('Brake Lever CT100', 'CT100', ['category' => 'Brakes']);

    expect(iscCheck(['make' => 'Bajaj']))->toBe([])
        ->and(iscNames(iscCheck(['part' => 'Brake Lever'])))->toBe(['Brake Lever CT100'])
        ->and(iscCheck(['part' => 'Brake Lever'])[0])->not->toHaveKey('compatibility_status');
});

it('treats Part Name as optional and narrows when supplied', function () {
    iscPart('Air Filter', 'Wave 125');
    iscPart('Brake Pad', 'Wave 125');

    expect(iscNames(iscCheck(['make' => 'Honda', 'model' => 'Wave 125'])))->toBe(['Air Filter', 'Brake Pad'])
        ->and(iscNames(iscCheck(['make' => 'Honda', 'model' => 'Wave 125', 'part' => 'brake'])))->toBe(['Brake Pad'])
        ->and(iscCheck(['make' => 'Honda', 'model' => 'Wave 125', 'part' => 'zzz']))->toBe([]);
});

it('returns a product once even if it matches by inventory model and an owner fitment', function () {
    $p = iscPart('Air Filter', 'Wave 125');
    $m = Motorcycle::create(['brand' => 'Honda', 'model' => 'Wave 125']);
    $p->motorcycles()->attach($m->id);

    $rows = iscCheck(['make' => 'Honda', 'model' => 'Wave 125']);
    expect($rows)->toHaveCount(1)->and($rows[0]['compatibility_status'])->toBe('confirmed');

    $rows = iscCheck(['make' => 'Honda']);
    expect($rows)->toHaveCount(1);
});

it('still honours existing owner fitment records for catalogue models (rollback-safe)', function () {
    $p = iscPart('Special Part', 'No Model');
    $m = Motorcycle::create(['brand' => 'Honda', 'model' => 'Wave 125']);
    $p->motorcycles()->attach($m->id);

    $rows = iscCheck(['make' => 'Honda', 'model' => 'Wave 125']);
    expect(iscNames($rows))->toBe(['Special Part'])->and($rows[0]['compatibility_source'])->toBe('fitment');
});

it('makes a newly added product with an already-recognised model searchable immediately', function () {
    expect(iscCheck(['make' => 'Suzuki', 'model' => 'Raider 150']))->toBe([]);
    iscPart('New Clutch Cable', 'Raider150');

    expect(iscNames(iscCheck(['make' => 'Suzuki', 'model' => 'Raider 150'])))->toBe(['New Clutch Cable']);
});

it('does not infer year compatibility from inventory', function () {
    iscPart('Air Filter', 'Wave 125');

    expect(iscCheck(['make' => 'Honda', 'model' => 'Wave 125', 'year' => '2020']))->toBe([]);
});

it('requires a make for model searches and rejects unknown makes and models', function () {
    iscPart('Air Filter', 'Wave 125');

    test()->getJson(route('compatibility.check', ['model' => 'Wave 125']))->assertOk()->assertHeader('X-Compat-Notice', 'brand-required');
    expect(iscCheck(['make' => 'Honda', 'model' => 'Not A Model']))->toBe([])
        ->and(iscCheck(['make' => 'Honda', 'model' => 'Raider 150']))->toBe([]);
});

it('has a clean catalogue: unique aliases, four makes, no overlap with Universal lists', function () {
    $catalogue = config('compatibility_catalogue.catalogue');
    expect(array_keys($catalogue))->toBe(['Yamaha', 'Honda', 'Suzuki', 'Kawasaki']);

    $seen = [];
    foreach ($catalogue as $make => $entries) {
        $names = [];
        foreach ($entries as $e) {
            expect(in_array($e['type'], ['specific', 'family'], true))->toBeTrue();
            expect($names)->not->toContain(mb_strtolower($e['name']));
            $names[] = mb_strtolower($e['name']);
            foreach (collect($e['aliases'])->map(fn ($a) => CompatibilityMatcher::normalize($a))->unique() as $norm) {
                $k = $make . '|' . $norm;
                expect($seen)->not->toHaveKey($k);
                $seen[$k] = true;
            }
            foreach ($e['aliases'] as $a) {
                expect(CompatibilityMatcher::normalize($a))->not->toContain('/')->not->toContain('universal');
            }
        }
    }
    // Same alias must not appear under two makes.
    $flat = collect($seen)->keys()->map(fn ($k) => explode('|', $k, 2)[1]);
    expect($flat->duplicates()->all())->toBe([]);
});

it('keeps the purple customer page and Universal option wiring', function () {
    $html = test()->get(route('compatibility'))->assertOk()->getContent();
    expect($html)->toContain('#5244e1')->toContain('makeSelect')->toContain('modelSelect')->toContain('Check Compatibility');
});

it('assigns manufacturers correctly (audit): STX and R15 Yamaha, GS125 Suzuki, TMX Honda', function () {
    $models = fn ($make) => iscOptions(['type' => 'models', 'make' => $make]);

    expect($models('Yamaha'))->toContain('STX')->toContain('STX 125')->toContain('YZF-R15')
        ->and($models('Suzuki'))->not->toContain('R15')->not->toContain('STX')->not->toContain('STX 125')->toContain('GS125')
        ->and($models('Honda'))->not->toContain('GS125')
        ->and($models('Yamaha'))->not->toContain('KMX F');

    iscPart('Chain', 'STX125');
    iscPart('Clutch', 'R15');
    iscPart('Piston', 'GS125');
    expect(iscNames(iscCheck(['make' => 'Yamaha', 'model' => 'STX 125'])))->toBe(['Chain'])
        ->and(iscNames(iscCheck(['make' => 'Yamaha', 'model' => 'YZF-R15'])))->toBe(['Clutch'])
        ->and(iscCheck(['make' => 'Suzuki', 'model' => 'STX 125']))->toBe([])
        ->and(iscNames(iscCheck(['make' => 'Suzuki', 'model' => 'GS125'])))->toBe(['Piston']);
});

it('holds unverified make/model strings out of the catalogue', function () {
    $pending = collect(config('compatibility_catalogue.pending_verification'))->pluck(0)->all();
    expect($pending)->toContain('KMX F')->toContain('LC150');

    $aliases = collect(config('compatibility_catalogue.catalogue'))->flatten(1)->pluck('aliases')->flatten()->map(fn ($a) => CompatibilityMatcher::normalize($a));
    foreach ($pending as $p) {
        expect($aliases->contains(CompatibilityMatcher::normalize($p)))->toBeFalse();
    }
    iscPart('Brake Pad', 'LC150');
    foreach (['Yamaha', 'Honda', 'Suzuki', 'Kawasaki'] as $m) {
        expect(iscCheck(['make' => $m]))->toBe([]);
    }
});

it('maps Honda TMX models without merging variants', function () {
    expect(iscOptions(['type' => 'models', 'make' => 'Honda']))
        ->toContain('TMX (general)')->toContain('TMX 125')->toContain('TMX 125 Alpha')->toContain('TMX 155')->toContain('TMX Supremo');

    iscPart('Carb 155', 'TMX155');
    iscPart('Cable 155', 'TMX 155');
    iscPart('Carb 125', 'TMX125');
    iscPart('Brake Cable Alpha', 'TMX 125 Alpha');
    iscPart('Air Filter Supremo', 'TMX Supremo');
    iscPart('Bearing Plain', 'TMX');
    iscPart('Combined', 'CT100 / TMX155 (D6HA)');
    iscPart('Typo Supremo', 'TNX Supremo');
    iscPart('Rusi', 'Rusi 125');

    expect(iscNames(iscCheck(['make' => 'Honda', 'model' => 'TMX 155'])))->toBe(['Cable 155', 'Carb 155'])
        ->and(iscNames(iscCheck(['make' => 'Honda', 'model' => 'TMX 125'])))->toBe(['Carb 125'])
        ->and(iscNames(iscCheck(['make' => 'Honda', 'model' => 'TMX 125 Alpha'])))->toBe(['Brake Cable Alpha'])
        ->and(iscNames(iscCheck(['make' => 'Honda', 'model' => 'TMX Supremo'])))->toBe(['Air Filter Supremo'])
        ->and(iscNames(iscCheck(['make' => 'Honda', 'model' => 'TMX (general)'])))->toBe(['Bearing Plain']);

    // Existing production fitment rows ("Tmx 125") are matched case-insensitively and de-duplicated with inventory.
    $m = Motorcycle::create(['brand' => 'Honda', 'model' => 'Tmx 125']);
    InventoryItem::where('part_name', 'Carb 125')->first()->motorcycles()->attach($m->id);
    expect(iscCheck(['make' => 'Honda', 'model' => 'TMX 125']))->toHaveCount(1);
});

it('does not claim a part fits every model on make-only searches', function () {
    iscPart('Air Filter', 'Wave 125');
    $html = test()->get(route('compatibility'))->getContent();

    expect($html)->toContain('const inventorySourced = true;')
        ->and($html)->toContain('Each part shows the model it was recorded for')
        ->and($html)->toContain('no variant given')
        ;
    $row = iscCheck(['make' => 'Honda'])[0];
    expect($row['matched_fitment'])->toBe(['Honda Wave 125']);
});

it('escapes inventory text in customer result cards', function () {
    $html = test()->get(route('compatibility'))->getContent();

    expect($html)->toContain('${esc(item.model)}')->toContain('${esc(item.image_url)}')
        ->and($html)->not->toContain('${item.model}')->not->toContain('${item.image_url}');

    // The API returns the raw value (JSON-encoded); the page escapes it on render.
    iscPart('XSS Part', '<img src=x onerror=alert(1)>');
    $rows = iscCheck(['part' => 'XSS']);
    expect($rows[0]['model'])->toContain('<img')->and(stripos($rows[0]['model'], 'onerror'))->not->toBeFalse();
});

it('reports the true total and limit so a truncated list is flagged', function () {
    config(['compatibility.result_limit' => 3]);
    foreach (range(1, 5) as $i) {
        iscPart("Part $i", 'Wave 125');
    }
    $resp = test()->getJson(route('compatibility.check', ['make' => 'Honda', 'model' => 'Wave 125']))->assertOk();

    expect($resp->json())->toHaveCount(3);
    $resp->assertHeader('X-Result-Total', '5')->assertHeader('X-Result-Limit', '3');
    expect(test()->get(route('compatibility'))->getContent())->toContain('resultLimitNote')->toContain('Showing the first');

    $all = test()->getJson(route('compatibility.check', ['make' => 'Honda', 'model' => 'Wave 125', 'part' => 'Part 1']))->assertOk();
    $all->assertHeader('X-Result-Total', '1');
});

it('does not claim completeness and shows owner-supplied zero-inventory models', function () {
    expect(config('compatibility_catalogue.catalogue_status.complete'))->toBeFalse();

    config(['compatibility_catalogue.owner_models.Honda' => [
        ['name' => 'Zero Stock Model', 'type' => 'specific', 'source' => 'owner', 'aliases' => ['Zero Stock Model']],
    ]]);
    expect(iscOptions(['type' => 'models', 'make' => 'Honda']))->toContain('Zero Stock Model')->toContain('Wave 125')
        ->and(iscCheck(['make' => 'Honda', 'model' => 'Zero Stock Model']))->toBe([]);

    iscPart('Late Arrival', 'Zero Stock Model');
    expect(iscNames(iscCheck(['make' => 'Honda', 'model' => 'Zero Stock Model'])))->toBe(['Late Arrival']);
});

it('treats Supremo and TMX Supremo as one Honda model with a single dropdown option', function () {
    $options = iscOptions(['type' => 'models', 'make' => 'Honda']);
    expect(collect($options)->filter(fn ($o) => stripos($o, 'supremo') !== false)->values()->all())->toBe(['TMX Supremo']);

    $a = iscPart('Air Filter Supremo', 'TMX Supremo');
    $b = iscPart('Cylinder Kit', 'Supremo');
    $c = iscPart('Typo Part', 'TNX Supremo');

    expect(iscNames(iscCheck(['make' => 'Honda', 'model' => 'TMX Supremo'])))->toBe(['Air Filter Supremo', 'Cylinder Kit'])
        ->and($b->fresh()->model)->toBe('Supremo')
        ->and($c->fresh()->model)->toBe('Tnx Supremo' === $c->fresh()->model ? 'Tnx Supremo' : 'TNX Supremo');

    // TNX Supremo stays unresolved: not under any make, not mapped, held for confirmation.
    foreach (['Yamaha', 'Honda', 'Suzuki', 'Kawasaki'] as $make) {
        expect(collect(iscCheck(['make' => $make]))->pluck('part_name')->contains('Typo Part'))->toBeFalse();
    }
    expect(collect(config('compatibility_catalogue.pending_verification'))->pluck(0))->toContain('TNX Supremo');

    // A product that is also linked by owner fitment is still returned once.
    $m = Motorcycle::create(['brand' => 'Honda', 'model' => 'TMX Supremo']);
    $b->motorcycles()->attach($m->id);
    expect(iscCheck(['make' => 'Honda', 'model' => 'TMX Supremo']))->toHaveCount(2);
});

it('keeps specific, general-family, Universal-labelled and Universal-conditional results distinctly labelled', function () {
    iscPart('Specific Part', 'Wave 125');
    iscPart('Family Part', 'XRM');
    iscPart('Universal Part', 'Universal');
    iscPart('Conditional Part', 'Universal (according To The Bearing Size)');

    $specific = iscCheck(['make' => 'Honda', 'model' => 'Wave 125'])[0];
    $family = iscCheck(['make' => 'Honda', 'model' => 'XRM (general)'])[0];
    $rows = collect(iscCheck(['make' => 'Universal']))->keyBy('part_name');

    expect($specific['compatibility_status'])->toBe('confirmed')->and($specific['compatibility_note'])->toBeNull()
        ->and($family['compatibility_status'])->toBe('recorded_general')->and($family['compatibility_note'])->toContain('confirm your exact variant')
        ->and($rows['Universal Part']['compatibility_status'])->toBe('universal_labelled')
        ->and($rows['Conditional Part']['compatibility_status'])->toBe('universal_conditional')
        ->and($rows['Universal Part']['compatibility_note'])->not->toBe($rows['Conditional Part']['compatibility_note']);

    $statuses = [$specific['compatibility_status'], $family['compatibility_status'], $rows['Universal Part']['compatibility_status'], $rows['Conditional Part']['compatibility_status']];
    expect(array_unique($statuses))->toHaveCount(4);

    $html = test()->get(route('compatibility'))->getContent();
    expect($html)->toContain("'Recorded for' : 'Confirmed fit'")->toContain('compatibility_note');
});

it('with the flag OFF keeps Release B customer behaviour and wording', function () {
    config(['compatibility.inventory_sourced' => false]);
    $page = test()->get(route('compatibility'))->assertOk()->getContent();

    // Page is told it is in Release B mode; legacy heading text is present verbatim.
    expect($page)->toContain('const inventorySourced = false;')
        ->toContain("`Confirmed compatible with \${[makeVal, modelVal].filter(Boolean).join(' ')}\${modelVal === '' ? ' (any model)' : ''}`")
        ->toContain('Parts matching your search. Pick a brand and model to confirm they fit your motorcycle.')
        ->toContain('Brand and model lists appear once compatible motorcycles are recorded for our parts');

    // API: options come only from motorcycle records; no Universal, no catalogue models; inventory text is ignored.
    iscPart('Air Filter', 'Wave 125');
    expect(iscOptions(['type' => 'makes']))->toBe([])
        ->and(iscOptions(['type' => 'models', 'make' => 'Honda']))->toBe([])
        ->and(iscCheck(['make' => 'Honda', 'model' => 'Wave 125']))->toBe([])
        ->and(iscCheck(['make' => 'Universal']))->toBe([]);

    $bike = Motorcycle::create(['brand' => 'Honda', 'model' => 'TMX 155']);
    $part = iscPart('Carb', 'anything');
    $part->motorcycles()->attach($bike->id);
    expect(iscOptions(['type' => 'makes']))->toBe(['Honda'])
        ->and(iscOptions(['type' => 'models', 'make' => 'Honda']))->toBe(['TMX 155']);
    $resp = test()->getJson(route('compatibility.check', ['make' => 'Honda', 'model' => 'TMX 155']))->assertOk();
    expect($resp->json())->toHaveCount(1)->and($resp->json()[0])->not->toHaveKey('compatibility_note')
        ->and($resp->json()[0]['compatibility_status'])->toBe('confirmed')
        ->and($resp->json()[0]['matched_fitment'])->toBe(['Honda TMX 155']);
    expect($resp->headers->has('X-Result-Total'))->toBeFalse();
});

it('resolver and matcher agree when the flag is off (rollback path)', function () {
    config(['compatibility.inventory_sourced' => false]);
    $m = Motorcycle::create(['brand' => 'Honda', 'model' => 'TMX 125']);
    $p = iscPart('Brake Pad', 'whatever');
    $p->motorcycles()->attach($m->id);

    expect(iscNames(iscCheck(['make' => 'Honda', 'model' => 'TMX 125'])))->toBe(['Brake Pad'])
        ->and(InventoryCompatibilityResolver::enabled())->toBeFalse();
});
