<?php

use App\Models\InventoryItem;
use App\Models\User;
use App\Services\InventorySearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function spItem(string $name, array $extra = []): InventoryItem
{
    return InventoryItem::create(array_merge([
        'category' => 'Zzz',
        'part_name' => $name,
        'brand' => 'Yyy',
        'model' => 'Qqq',
        'price' => 100,
        'quantity' => 5,
        'low_stock_threshold' => 1,
    ], $extra));
}

function spBrowse(array $q): array
{
    $r = test()->get(route('browse', $q))->assertOk();

    return collect($r->viewData('items')->items())->pluck('part_name')->sort()->values()->all();
}

function spAdmin(array $q): array
{
    $owner = User::factory()->create(['role' => 'owner']);
    $r = test()->actingAs($owner)->withSession(['otp_verified' => true])->get(route('inventory.index', $q))->assertOk();

    return collect($r->viewData('items')->items())->pluck('part_name')->sort()->values()->all();
}

/** Both surfaces share the search service: they must return the same set. */
function spBoth(array $q): array
{
    $browse = spBrowse($q);
    expect(spAdmin($q))->toBe($browse);

    return $browse;
}

test('exact and partial search, case-insensitive', function () {
    spItem('Brake Pad');
    spItem('Brake Shoe');
    spItem('Chain Set');

    expect(spBoth(['search' => 'Brake Pad']))->toBe(['Brake Pad']);
    expect(spBoth(['search' => 'brak']))->toBe(['Brake Pad', 'Brake Shoe']);
    expect(spBoth(['search' => 'BRAKE pAd']))->toBe(['Brake Pad']);
    expect(spBoth(['search' => 'zzz']))->toBe(['Brake Pad', 'Brake Shoe', 'Chain Set']); // category column
});

test('multiple terms are ANDed and extra whitespace or tabs are ignored', function () {
    spItem('Brake Pad');
    spItem('Brake Shoe');

    expect(spBoth(['search' => "  brake \t  pad  "]))->toBe(['Brake Pad']);
    expect(spBoth(['search' => 'pad shoe']))->toBe([]);
});

test('single-character numeric terms are kept', function () {
    foreach (['Part 1', 'Part 2', 'Part 12', 'Part 21'] as $n) {
        spItem($n);
    }

    expect(spBoth(['search' => 'part 1']))->toBe(['Part 1', 'Part 12', 'Part 21']); // "1" now narrows
    expect(spBoth(['search' => 'part 2']))->toBe(['Part 12', 'Part 2', 'Part 21']);
    expect(spBoth(['search' => '1']))->toBe(['Part 1', 'Part 12', 'Part 21']);
});

test('single-character alphabetic terms are kept, including "a"', function () {
    spItem('Spec A');
    spItem('Spec B');

    expect(spBoth(['search' => 'spec b']))->toBe(['Spec B']);
    expect(spBoth(['search' => 'spec a']))->toBe(['Spec A']);
});

test('filler words are ignored only when another term remains', function () {
    spItem('Pad Kit');
    spItem('The Thing');

    expect(spBoth(['search' => 'pad for the kit']))->toBe(['Pad Kit']);
    expect(spBoth(['search' => 'the']))->toBe(['The Thing']);
});

test('percent, underscore and backslash are matched literally', function () {
    spItem('Oil 10% Synthetic');
    spItem('Oil 10W');
    spItem('Brake_Pad');
    spItem('BrakeXPad');
    spItem('Back\\Slash');
    spItem('Backslash');
    spItem('Wow!Bang');
    spItem('WowBang');

    expect(spBoth(['search' => '10%']))->toBe(['Oil 10% Synthetic']);
    expect(spBoth(['search' => '%']))->toBe(['Oil 10% Synthetic']);
    expect(spBoth(['search' => '_']))->toBe(['Brake_Pad']);
    expect(spBoth(['search' => 'brake_pad']))->toBe(['Brake_Pad']);
    expect(spBoth(['search' => '\\']))->toBe(['Back\\Slash']);
    expect(spBoth(['search' => 'back\\s']))->toBe(['Back\\Slash']);
    expect(spBoth(['search' => 'w!b']))->toBe(['Wow!Bang']);
    expect(spBoth(['search' => '%%']))->toBe([]);
});

test('escapeLike escapes the wildcard set and the escape character', function () {
    expect(InventorySearchService::escapeLike('a%b_c!d\\e'))->toBe('a!%b!_c!!d\\e');
    expect(InventorySearchService::terms('  A   %  '))->toBe(['a', '%']);
    expect(InventorySearchService::terms('   '))->toBe([]);
    expect(InventorySearchService::terms(null))->toBe([]);
});

test('empty or blank search lists everything on both surfaces', function () {
    spItem('One');
    spItem('Two');

    expect(spBoth([]))->toBe(['One', 'Two']);
    expect(spBoth(['search' => '']))->toBe(['One', 'Two']);
    expect(spBoth(['search' => '    ']))->toBe(['One', 'Two']);
});

test('suggestions treat the typed prefix literally', function () {
    spItem('_Under Part');
    spItem('Plain Part');
    spItem('50% Off Part');

    $names = fn ($q) => collect($this->getJson(route('inventory.suggestions', ['field' => 'part_name', 'q' => $q]))->json())->pluck('value')->all();

    expect($names('_'))->toBe(['_Under Part']);
    expect($names('%'))->toBe([]);
    expect($names('50%'))->toBe(['50% Off Part']);
    expect($names('pla'))->toBe(['Plain Part']);
});

test('browse paging is deterministic with equal prices and survives stock updates between pages', function () {
    foreach (range(1, 30) as $i) {
        spItem("Item $i", ['price' => 100]);
    }

    foreach ([[], ['sort' => 'low_high'], ['sort' => 'high_low']] as $params) {
        $seen = [];
        foreach ([1, 2, 3] as $page) {
            $r = $this->get(route('browse', $params + ['page' => $page]))->assertOk();
            foreach ($r->viewData('items')->items() as $it) {
                $seen[] = $it->id;
            }
            if ($page === 1) {
                // Touch a row already shown (a stock change) before page 2 is requested.
                InventoryItem::where('id', $seen[0])->update(['quantity' => 4]);
            }
        }
        expect($seen)->toHaveCount(30)->and(array_unique($seen))->toHaveCount(30);
        expect($seen)->toBe(collect($seen)->sort()->values()->all()); // id order within equal prices
    }
});

test('filters and sort order survive pagination links on browse and admin', function () {
    config(['compatibility.inventory_sourced' => true]);
    foreach (range(1, 14) as $i) {
        spItem("Click Pad $i", ['category' => 'Brakes', 'model' => 'Click 125', 'price' => $i]);
    }

    $browse = $this->get(route('browse', ['search' => 'pad', 'model' => 'Click 125', 'category' => 'Brakes', 'sort' => 'high_low']))->assertOk();
    $items = $browse->viewData('items');
    expect($items->total())->toBe(14)->and($items->items()[0]->part_name)->toBe('Click Pad 14');
    $html = $browse->getContent();
    foreach (['search=pad', 'model=Click%20125', 'category=Brakes', 'sort=high_low', 'page=2'] as $needle) {
        expect($html)->toContain($needle);
    }

    $owner = User::factory()->create(['role' => 'owner']);
    $admin = $this->actingAs($owner)->withSession(['otp_verified' => true])
        ->get(route('inventory.index', ['search' => 'pad', 'category' => 'Brakes']))->assertOk()->getContent();
    foreach (['search=pad', 'category=Brakes', 'page=2'] as $needle) {
        expect($admin)->toContain($needle);
    }
});

test('admin ordering is stable for equal part names', function () {
    foreach (range(1, 15) as $i) {
        spItem('Same Name', ['sku' => "SKU-$i"]);
    }
    $owner = User::factory()->create(['role' => 'owner']);
    $ids = [];
    foreach ([1, 2] as $page) {
        $r = $this->actingAs($owner)->withSession(['otp_verified' => true])->get(route('inventory.index', ['page' => $page]))->assertOk();
        foreach ($r->viewData('items')->items() as $it) {
            $ids[] = $it->id;
        }
    }
    expect($ids)->toHaveCount(15)->and(array_unique($ids))->toHaveCount(15);
});

test('browse always ends its ORDER BY with the id tiebreaker', function () {
    spItem('Any');
    $last = function (array $q): string {
        $sqls = [];
        \Illuminate\Support\Facades\DB::listen(function ($e) use (&$sqls) {
            if (str_contains($e->sql, 'order by') && str_contains($e->sql, 'inventory_items') && str_contains($e->sql, 'limit')) {
                $sqls[] = $e->sql;
            }
        });
        test()->get(route('browse', $q))->assertOk();

        return (string) collect($sqls)->first(fn ($s) => str_contains($s, 'parent_id'));
    };

    expect($last([]))->toContain('order by "id" asc');
    expect($last(['sort' => 'low_high']))->toContain('order by "price" asc, "id" asc');
    expect($last(['sort' => 'high_low']))->toContain('order by "price" desc, "id" asc');
});
