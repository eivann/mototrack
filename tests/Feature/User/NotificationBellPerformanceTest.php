<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function nbSeed(User $user, int $count, int $unreadEvery = 1, string $prefix = 'N'): void
{
    $rows = [];
    for ($i = 0; $i < $count; $i++) {
        $rows[] = [
            'id' => (string) Str::uuid(),
            'type' => 'App\Notifications\OrderUpdate',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode(['title' => "{$prefix}-title-" . str_pad((string) $i, 3, '0', STR_PAD_LEFT), 'message' => 'm', 'link' => '#']),
            // $i = 0 is the newest.
            'read_at' => ($i % $unreadEvery === 0) ? null : now(),
            'created_at' => now()->subMinutes($i),
            'updated_at' => now()->subMinutes($i),
        ];
    }
    DB::table('notifications')->insert($rows);
}

function nbGet(User $user, string $uri = '/customer/dashboard')
{
    return test()->actingAs($user)->withSession(['otp_verified' => true])->get($uri);
}

/** Notification rows hydrated and notification SELECT statements while rendering a page. */
function nbMeasure(User $user, string $uri = '/browse'): array
{
    $hydrated = 0;
    Event::listen('eloquent.retrieved: ' . DatabaseNotification::class, function () use (&$hydrated) {
        $hydrated++;
    });
    DB::flushQueryLog();
    DB::enableQueryLog();
    $response = nbGet($user, $uri)->assertOk();
    $queries = collect(DB::getQueryLog())->pluck('query')->filter(fn ($q) => str_contains($q, 'from "notifications"'))->values();
    DB::disableQueryLog();

    return [$hydrated, $queries, $response];
}

test('with 200 notifications the bell fetches at most 10 + 3 rows and never all of them', function () {
    $user = User::factory()->create(['role' => 'customer']);
    nbSeed($user, 200);

    [$hydrated, $queries] = nbMeasure($user, '/browse');

    expect($hydrated)->toBeLessThanOrEqual(13)
        ->and($queries->filter(fn ($q) => str_contains($q, 'limit 10'))->count())->toBe(1)
        ->and($queries->filter(fn ($q) => str_contains($q, 'limit 3'))->count())->toBe(1)
        ->and($queries->filter(fn ($q) => str_contains($q, 'count(*)'))->count())->toBe(1);
    // No notification SELECT may be unbounded.
    $unbounded = $queries->filter(fn ($q) => str_starts_with($q, 'select *') && ! str_contains($q, 'limit'));
    expect($unbounded->count())->toBe(0);
});

test('hydrated rows stay constant as the notification count grows', function () {
    $small = User::factory()->create(['role' => 'customer']);
    nbSeed($small, 15);
    [$h15] = nbMeasure($small, '/browse');

    $big = User::factory()->create(['role' => 'customer']);
    nbSeed($big, 250);
    [$h250] = nbMeasure($big, '/browse');

    expect($h250)->toBe($h15);
});

test('the bell lists the latest 10 newest-first and the unread badge shows the true total', function () {
    $user = User::factory()->create(['role' => 'customer']);
    nbSeed($user, 200); // all unread

    $html = nbGet($user, '/browse')->assertOk()->getContent();

    expect($html)->toContain('200 NEW');
    preg_match_all('/N-title-(\d{3})/', $html, $m);
    // Bell titles appear newest first; the 10 latest are 000..009 and 010+ never render.
    $bell = array_values(array_unique($m[1]));
    expect(array_slice($bell, 0, 10))->toBe(['000', '001', '002', '003', '004', '005', '006', '007', '008', '009']);
    expect($html)->not->toContain('N-title-010');
});

test('unread preview JSON contains at most 3 and only unread records', function () {
    $user = User::factory()->create(['role' => 'customer']);
    nbSeed($user, 40, 2); // even index unread, odd read

    $html = nbGet($user, '/browse')->assertOk()->getContent();
    $m = [];
    preg_match('/const rawNotifs = (\[.*?\]);/s', $html, $m);
    $json = json_decode($m[1], true);

    expect($json)->toHaveCount(3);
    foreach ($json as $n) {
        expect($n['read_at'])->toBeNull();
    }
    expect(collect($json)->pluck('data.title')->all())->toBe(['N-title-000', 'N-title-002', 'N-title-004']);
});

test('read and unread notifications keep their order and indicators', function () {
    $user = User::factory()->create(['role' => 'customer']);
    nbSeed($user, 6, 2); // 0,2,4 unread; 1,3,5 read

    $html = nbGet($user, '/browse')->assertOk()->getContent();

    expect($html)->toContain('3 NEW');
    // Mark-read form only for unread rows: 3 of the 6.
    expect(substr_count($html, 'action="' . url('/notifications/') . '/'))->toBeGreaterThanOrEqual(3);
    $ids = DatabaseNotification::where('notifiable_id', $user->id)->whereNull('read_at')->pluck('id');
    foreach ($ids as $id) {
        expect($html)->toContain(route('notifications.read', $id));
    }
    $readIds = DatabaseNotification::where('notifiable_id', $user->id)->whereNotNull('read_at')->pluck('id');
    foreach ($readIds as $id) {
        expect($html)->not->toContain(route('notifications.read', $id));
        expect($html)->toContain(route('notifications.follow', $id)); // follow link still present for read items
    }
    // Order within the list is newest first.
    expect(strpos($html, 'N-title-000'))->toBeLessThan(strpos($html, 'N-title-001'));
    expect(strpos($html, 'N-title-001'))->toBeLessThan(strpos($html, 'N-title-005'));
});

test('empty state is unchanged and badge is hidden when there are no notifications', function () {
    $user = User::factory()->create(['role' => 'customer']);
    $html = nbGet($user, '/browse')->assertOk()->getContent();

    expect($html)->toContain('hasNew: false')->and($html)->toContain('0 NEW');
    preg_match('/const rawNotifs = (\[.*?\]);/s', $html, $m);
    expect(json_decode($m[1], true))->toBe([]);
});

test('notifications are isolated per user', function () {
    $a = User::factory()->create(['role' => 'customer']);
    $b = User::factory()->create(['role' => 'customer']);
    nbSeed($a, 30, 1, 'ALICE');
    nbSeed($b, 5, 1, 'BOB');

    $htmlA = nbGet($a, '/browse')->getContent();
    expect($htmlA)->toContain('ALICE-title-000')->and($htmlA)->not->toContain('BOB-title')->and($htmlA)->toContain('30 NEW');

    $htmlB = nbGet($b, '/browse')->getContent();
    expect($htmlB)->toContain('BOB-title-000')->and($htmlB)->not->toContain('ALICE-title')->and($htmlB)->toContain('5 NEW');
});

test('guests render public pages without the bell and without errors', function () {
    $user = User::factory()->create(['role' => 'customer']);
    nbSeed($user, 5);

    $html = $this->get('/browse')->assertOk()->getContent();
    expect($html)->not->toContain('Recent Notifications')->and($html)->not->toContain('rawNotifs');
    $this->get('/')->assertOk();
});

test('notification actions and links still work for the owner of the notification', function () {
    $user = User::factory()->create(['role' => 'customer']);
    $other = User::factory()->create(['role' => 'customer']);
    nbSeed($user, 3);
    nbSeed($other, 1, 1, 'OTHER');
    $mine = DatabaseNotification::where('notifiable_id', $user->id)->first();
    $theirs = DatabaseNotification::where('notifiable_id', $other->id)->first();

    $this->actingAs($user)->withSession(['otp_verified' => true])->post(route('notifications.read', $mine->id))->assertRedirect();
    expect($mine->fresh()->read_at)->not->toBeNull();

    $this->actingAs($user)->withSession(['otp_verified' => true])->post(route('notifications.read', $theirs->id))->assertNotFound();
    expect($theirs->fresh()->read_at)->toBeNull();

    $this->actingAs($user)->withSession(['otp_verified' => true])->post(route('notifications.read_all'))->assertRedirect();
    expect(DatabaseNotification::where('notifiable_id', $user->id)->whereNull('read_at')->count())->toBe(0);
    expect(DatabaseNotification::where('notifiable_id', $other->id)->whereNull('read_at')->count())->toBe(1);
});

test('the notification centre still paginates 15 per page', function () {
    $user = User::factory()->create(['role' => 'customer']);
    nbSeed($user, 40);

    $r = nbGet($user, '/notifications')->assertOk();
    $p = $r->viewData('notifications');
    expect($p->perPage())->toBe(15)->and($p->total())->toBe(40)->and($p->count())->toBe(15);
});
