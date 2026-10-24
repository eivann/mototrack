<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** SYS4-05 C2a: deferred Chart.js on the owner dashboard and lazy notification sound. */
function alOwnerDashboard(): string
{
    $owner = User::factory()->create(['role' => 'owner']);

    return test()->actingAs($owner)->get('/dashboard')->assertOk()->getContent();
}

test('owner dashboard loads Chart.js once, deferred, before the init script', function () {
    $html = alOwnerDashboard();

    expect(substr_count($html, 'chart.umd.min.js'))->toBe(1);
    expect($html)->toContain('<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js" defer></script>');

    // Chart.js comes before the script that creates the chart.
    expect(strpos($html, 'chart.umd.min.js'))->toBeLessThan(strpos($html, 'new Chart('));
});

test('every Chart usage runs inside DOMContentLoaded so the deferred global exists', function () {
    $html = alOwnerDashboard();

    expect(substr_count($html, 'new Chart('))->toBe(1)
        ->and(substr_count($html, 'DOMContentLoaded'))->toBeGreaterThanOrEqual(1);

    $dom = strpos($html, "document.addEventListener('DOMContentLoaded'");
    expect($dom)->not->toBeFalse()
        ->and($dom)->toBeLessThan(strpos($html, 'new Chart('))
        ->and($html)->toContain('<canvas id="revenueChart"></canvas>');

    // No other script on the page touches the Chart global outside that handler.
    preg_match_all('/\bChart\b[.(]/', $html, $m);
    expect(count($m[0]))->toBe(substr_count($html, 'new Chart(') + substr_count($html, 'Chart.defaults'));
});

test('dashboard chart data and options are unchanged', function () {
    $src = file_get_contents(resource_path('views/admin/dashboard.blade.php'));

    expect($src)->toContain('@json($chartLabels)')
        ->and($src)->toContain('@json($chartData)')
        ->and($src)->toContain("type: 'bar'");
});

test('notification sound is lazy-loaded but still present and played by the toast code', function () {
    $user = User::factory()->create(['role' => 'customer']);
    $html = $this->actingAs($user)->get('/browse')->assertOk()->getContent();

    expect($html)->toContain('<audio id="notif-sound" src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" preload="none"></audio>')
        ->and($html)->not->toContain('preload="auto"')
        ->and(substr_count($html, 'id="notif-sound"'))->toBe(1)
        // Playback logic is unchanged.
        ->and($html)->toContain("document.getElementById('notif-sound')")
        ->and($html)->toContain('sound.currentTime = 0;')
        ->and($html)->toContain("sound.play().catch(e => console.log('Autoplay blocked'));");
});

test('guests do not get the notification sound element', function () {
    $this->get('/browse')->assertOk()->assertDontSee('notif-sound', false);
});
