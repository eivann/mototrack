<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('logo SVGs stay lightweight and keep their original geometry', function () {
    foreach (['light', 'dark'] as $mode) {
        $path = public_path("images/logo-{$mode}.svg");
        expect(file_exists($path))->toBeTrue();
        expect(filesize($path))->toBeLessThan(20 * 1024);

        $svg = file_get_contents($path);
        $xml = new DOMDocument();
        expect($xml->loadXML($svg))->toBeTrue();
        // Same canvas as the original artwork, so every layout using it is unchanged.
        expect($svg)->toContain('width="2000"')->toContain('height="2000"')->toContain('viewBox="0 0 1500 1499.999933"');
        // Embedded bitmaps are only the soft shadows, kept small.
        preg_match_all('/data:image\/png;base64,([A-Za-z0-9+\/=]+)/', $svg, $m);
        expect(count($m[1]))->toBe(4);
        foreach ($m[1] as $b64) {
            $info = getimagesizefromstring(base64_decode($b64));
            expect($info[0])->toBeLessThanOrEqual(300)->and($info[1])->toBeLessThanOrEqual(300);
        }
    }
});

test('every logo and brand asset referenced by a view exists', function () {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('views')));
    $refs = [];
    foreach ($files as $f) {
        if ($f->isFile() && str_ends_with($f->getFilename(), '.php')) {
            preg_match_all("/asset\('((?:images\/logo[^']*|brand\/[^']*))'\)/", file_get_contents($f->getPathname()), $m);
            $refs = array_merge($refs, $m[1]);
        }
    }
    $refs = array_unique($refs);
    expect($refs)->toContain('images/logo-light.svg')->toContain('images/logo-dark.svg')->toContain('brand/mototrack-em.png');
    foreach ($refs as $ref) {
        expect(file_exists(public_path($ref)))->toBeTrue("missing public/{$ref}");
    }
});

test('pages still reference both theme logos with class-based light/dark switching', function () {
    $html = $this->get('/')->assertOk()->getContent();
    expect($html)->toContain('images/logo-light.svg')->toContain('images/logo-dark.svg')
        ->toContain('block dark:hidden')->toContain('hidden dark:block');
    $this->get('/login')->assertOk()->assertSee('brand/mototrack-em.png');
    $this->get('/register')->assertOk()->assertSee('brand/mototrack-em.png');
    $this->get('/forgot-password')->assertOk()->assertSee('images/logo-dark.svg');
    $this->get('/browse')->assertOk()->assertSee('images/logo-light.svg');
});
