<?php

use App\Actions\CommitOrder;
use App\Models\InventoryItem;
use App\Models\Sale;
use App\Models\User;
use App\Services\ReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

const BC_OLD_KEY = 'inventory_in_stock_parent_items_count_v2';

function bcItem(int $n, int $qty = 5, array $o = []): InventoryItem
{
    return InventoryItem::create(array_merge([
        'part_name' => sprintf('Part %03d', $n),
        'sku' => 'BC-' . $n,
        'price' => 100,
        'quantity' => $qty,
        'category' => 'Cat',
        'brand' => 'Brand',
        'model' => 'Model',
    ], $o));
}

function bcMany(int $count): array
{
    return array_map(fn ($i) => bcItem($i), range(1, $count));
}

function bcBrowse(string $qs = ''): array
{
    $r = test()->get('/browse' . $qs)->assertOk();
    $items = $r->viewData('items');

    return [$items, $r->getContent()];
}

/** Assert Browse reflects $total in-stock items across $pages pages. */
function bcExpect(int $total, int $pages, string $qs = ''): void
{
    [$items, $html] = bcBrowse($qs);
    expect($items->total())->toBe($total)->and($items->lastPage())->toBe($pages);
    expect(str_contains($html, "of {$total} items"))->toBeTrue();
    // A next-page link exists only when another page really exists.
    expect(str_contains($html, 'page=2'))->toBe($pages > 1);
}

function bcSell(InventoryItem $item, int $qty, string $status = 'completed', string $ref = 'BC-REF-1'): void
{
    app(CommitOrder::class)->execute(
        [['id' => $item->id, 'quantity' => $qty]],
        $ref,
        null,
        'Out of stock: ',
        fn ($first, $total) => $first ? (float) $total : 0.0,
        fn ($first, $total) => 0.0,
        false,
        $status
    );
}

function bcOwnerSetQuantity(InventoryItem $item, int $new): void
{
    $owner = User::factory()->create(['role' => 'owner']);
    test()->actingAs($owner)->withSession(['otp_verified' => true])
        ->put(route('inventory.update', $item->id), [
            'part_name' => $item->part_name, 'sku' => $item->sku, 'category' => 'Cat', 'brand' => 'Brand',
            'model' => 'Model', 'price' => 100, 'quantity' => $new, 'expected_quantity' => $item->fresh()->quantity,
        ])->assertSessionHasNoErrors();
    auth()->logout();
}

test('13 eligible items dropping to 12 gives one page and no second-page link', function () {
    $items = bcMany(13);
    bcExpect(13, 2); // warms any count cache

    $items[0]->update(['quantity' => 0]);

    bcExpect(12, 1);
    [$p2] = bcBrowse('?page=2');
    expect($p2->count())->toBe(0)->and($p2->total())->toBe(12);
});

test('12 eligible items rising to 13 gives two pages and the 13th item is reachable', function () {
    $items = bcMany(12);
    $extra = bcItem(99, 0);
    bcExpect(12, 1); // warms any count cache

    $extra->increment('quantity', 3);

    bcExpect(13, 2);
    [$p2, $html] = bcBrowse('?page=2');
    expect($p2->pluck('id')->all())->toBe([$extra->id])
        ->and($html)->toContain('Part 099')
        ->and($items)->toHaveCount(12);
});

test('a bogus value in the old cache key has no effect on Browse', function () {
    bcMany(5);
    Cache::put(BC_OLD_KEY, 999, now()->addMinutes(10));

    [$items, $html] = bcBrowse();
    expect($items->total())->toBe(5)->and($items->lastPage())->toBe(1)
        ->and($html)->toContain('of 5 items')->and($html)->not->toContain('of 999 items');
});

test('Browse never reads or writes the old count cache key', function () {
    bcMany(3);
    Cache::forget(BC_OLD_KEY);

    bcBrowse();

    expect(Cache::has(BC_OLD_KEY))->toBeFalse();
});

test('a parent with an in-stock variation counts, a parent with nothing in stock does not', function () {
    $plain = bcItem(1, 4);
    $viaVariation = bcItem(2, 0);
    bcItem(3, 0, ['parent_id' => $viaVariation->id, 'variation_name' => 'Red', 'sku' => 'BC-3']);
    $variationStock = $viaVariation->variations()->first();
    $variationStock->update(['quantity' => 6]);
    $empty = bcItem(4, 0);
    bcItem(5, 0, ['parent_id' => $empty->id, 'variation_name' => 'Blue', 'sku' => 'BC-5']);

    [$items] = bcBrowse();
    expect($items->total())->toBe(2)
        ->and($items->pluck('id')->sort()->values()->all())->toBe([$plain->id, $viaVariation->id]);

    // Selling out the variation removes its parent from the count.
    $variationStock->update(['quantity' => 0]);
    expect(bcBrowse()[0]->total())->toBe(1);
});

test('search and category filters keep accurate totals and pagination', function () {
    foreach (range(1, 14) as $i) {
        bcItem($i, 5, ['category' => $i <= 13 ? 'Brakes' : 'Chains', 'part_name' => ($i <= 13 ? 'Brake ' : 'Chain ') . $i]);
    }
    bcExpect(14, 2);

    expect(bcBrowse('?category=Brakes')[0]->total())->toBe(13);
    expect(bcBrowse('?search=Chain')[0]->total())->toBe(1);

    InventoryItem::where('part_name', 'Brake 1')->update(['quantity' => 0]);
    [$items, $html] = bcBrowse('?category=Brakes');
    expect($items->total())->toBe(12)->and($items->lastPage())->toBe(1)
        ->and($html)->not->toContain('page=2');
    bcExpect(13, 2);
});

test('page size, id tie-break ordering and price sorting are unchanged', function () {
    foreach (range(1, 15) as $i) {
        bcItem($i, 5, ['price' => $i % 2 ? 50 : 70]);
    }

    [$p1] = bcBrowse();
    [$p2] = bcBrowse('?page=2');
    expect($p1->perPage())->toBe(12)->and($p1->count())->toBe(12)->and($p2->count())->toBe(3);
    expect($p1->pluck('id')->all())->toBe($p1->pluck('id')->sort()->values()->all());

    [$asc] = bcBrowse('?sort=low_high');
    $prices = $asc->pluck('price')->map(fn ($p) => (float) $p)->all();
    expect($prices)->toBe(collect($prices)->sort()->values()->all());
    // Equal prices keep ascending id order.
    expect($asc->where('price', 50)->pluck('id')->all())->toBe($asc->where('price', 50)->pluck('id')->sort()->values()->all());

    // No duplicates or gaps across pages.
    expect($p1->pluck('id')->merge($p2->pluck('id'))->unique()->count())->toBe(15);
});

test('sell-out, owner edit, restock and reservation release keep the count accurate', function () {
    $items = bcMany(13);
    bcExpect(13, 2);

    // Sale that sells an item out.
    bcSell($items[0], 5, 'completed', 'BC-SALE');
    bcExpect(12, 1);

    // Owner edits an item to zero, then restores it.
    bcOwnerSetQuantity($items[1], 0);
    bcExpect(11, 1);
    bcOwnerSetQuantity($items[1], 5);
    bcExpect(12, 1);

    // Reservation takes the last units, then is cancelled (stock restored).
    bcSell($items[2], 5, 'pending', 'BC-RES');
    bcExpect(11, 1);
    $sale = Sale::where('transaction_reference', 'BC-RES')->firstOrFail();
    expect(app(ReservationService::class)->releaseGroup($sale, 'test'))->toBeTrue();
    bcExpect(12, 1);

    // Restocking the sold-out item brings the second page back.
    $items[0]->increment('quantity', 2);
    bcExpect(13, 2);
});

test('an unfiltered Browse request runs exactly one inventory count query', function () {
    bcMany(14);

    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->get('/browse')->assertOk();
    $counts = collect(DB::getQueryLog())->pluck('query')
        ->filter(fn ($q) => str_contains($q, 'from "inventory_items"') && str_contains($q, 'count(*)'));
    DB::disableQueryLog();

    expect($counts)->toHaveCount(1);
});
