<?php

/**
 * RD2-R5: guards for defects found in the R5 light/dark browser review of the R2–R4 pages.
 * Source-level checks only (no browser): they fail if a known defect pattern comes back.
 */

function r5Views(): array
{
    $base = resource_path('views');
    $files = array_merge(
        glob("$base/payments/*.blade.php"),
        glob("$base/payments/partials/*.blade.php"),
        glob("$base/repair_reservations/*.blade.php"),
        glob("$base/repair_reservations/partials/*.blade.php"),
        ["$base/profile/trusted-devices.blade.php", "$base/terms.blade.php", "$base/shop/partials/terms-reservation.blade.php", "$base/preorders/form.blade.php"],
    );

    return array_values(array_filter($files, 'is_file'));
}

test('no R2-R4 class attribute sets two competing plain text colours (the later one silently loses)', function () {
    foreach (r5Views() as $file) {
        preg_match_all('/class="([^"]*)"/', file_get_contents($file), $m);
        foreach ($m[1] as $classes) {
            if (str_contains($classes, '{{') || str_contains($classes, ' ? ')) {
                continue; // a Blade or Alpine ternary picks one of two alternatives at render time
            }
            $plain = array_filter(preg_split('/\s+/', trim($classes)), fn ($c) => preg_match('/^text-(gray|red|green|amber|blue|indigo|white|black)(-\d+)?$/', $c));
            expect(count($plain) <= 1)->toBeTrue(basename($file).': '.$classes);
        }
    }
});

test('muted grey labels on the R2-R4 dark surfaces always have a dark variant', function () {
    foreach (['payments/show', 'payments/owner_index', 'repair_reservations/show', 'repair_reservations/owner_index', 'profile/trusted-devices'] as $view) {
        $src = file_get_contents(resource_path("views/$view.blade.php"));
        preg_match_all('/class="([^"]*\btext-gray-500\b[^"]*)"/', $src, $m);
        foreach ($m[1] as $classes) {
            expect(str_contains($classes, 'dark:text-'))->toBeTrue("$view: $classes");
        }
    }
});

test('the owner Accept button keeps enough contrast: bold white text on the existing green-600 class', function () {
    $src = file_get_contents(resource_path('views/repair_reservations/owner_index.blade.php'));

    expect($src)->toContain('bg-green-600 text-white text-sm font-bold">Accept')
        ->and($src)->not->toContain('bg-green-700 text-white'); // bg-green-700 is not in the compiled stylesheet
});
