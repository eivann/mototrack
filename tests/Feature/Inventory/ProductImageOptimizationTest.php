<?php

namespace Tests\Feature\Inventory;

use App\Models\InventoryItem;
use App\Models\User;
use App\Services\ProductImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** RD2-05: new product pictures are shrunk, WebP-encoded and stored as 320/640/full files; legacy pictures are untouched. */
class ProductImageOptimizationTest extends TestCase
{
    use RefreshDatabase;

    private function noisyPng(int $w, int $h, bool $alpha = false): UploadedFile
    {
        $im = imagecreatetruecolor($w, $h);
        imagealphablending($im, false);
        imagesavealpha($im, true);
        imagefill($im, 0, 0, imagecolorallocatealpha($im, 0, 0, 0, 127)); // fully transparent background
        mt_srand(7);
        for ($i = 0; $i < 400; $i++) {
            $c = imagecolorallocate($im, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
            imagefilledellipse($im, mt_rand((int) ($w * .2), (int) ($w * .8)), mt_rand((int) ($h * .2), (int) ($h * .8)), mt_rand(8, 120), mt_rand(8, 120), $c);
        }
        $path = tempnam(sys_get_temp_dir(), 'pi').'.png';
        imagepng($im, $path, 9);
        imagedestroy($im);

        return new UploadedFile($path, 'part.png', 'image/png', null, true);
    }

    private function jpegWithOrientation(int $w, int $h, int $orientation): UploadedFile
    {
        $im = imagecreatetruecolor($w, $h);
        imagefill($im, 0, 0, imagecolorallocate($im, 240, 240, 240));
        imagefilledrectangle($im, 0, 0, (int) ($w / 2), $h, imagecolorallocate($im, 200, 0, 0)); // left half red
        ob_start();
        imagejpeg($im, null, 90);
        $jpeg = ob_get_clean();
        imagedestroy($im);
        // Minimal Exif APP1 segment carrying only the Orientation tag (little-endian TIFF).
        $tiff = "II\x2A\x00\x08\x00\x00\x00"."\x01\x00"."\x12\x01\x03\x00\x01\x00\x00\x00".pack('v', $orientation)."\x00\x00"."\x00\x00\x00\x00";
        $app1 = "\xFF\xE1".pack('n', 2 + 6 + strlen($tiff))."Exif\x00\x00".$tiff;
        $jpeg = substr($jpeg, 0, 2).$app1.substr($jpeg, 2);
        $path = tempnam(sys_get_temp_dir(), 'pj').'.jpg';
        file_put_contents($path, $jpeg);

        return new UploadedFile($path, 'part.jpg', 'image/jpeg', null, true);
    }

    public function test_large_transparent_png_is_resized_converted_to_webp_and_keeps_alpha_and_aspect(): void
    {
        $this->assertTrue(ProductImageService::canOptimize());
        Storage::fake('public');
        $file = $this->noisyPng(1600, 1200);
        $originalBytes = filesize($file->getRealPath());

        $path = app(ProductImageService::class)->store($file);

        $this->assertMatchesRegularExpression(ProductImageService::PATH_PATTERN, $path);
        $this->assertSame('products/', substr($path, 0, 9));
        [$w, $h, $type] = getimagesizefromstring(Storage::disk('public')->get($path));
        $this->assertSame(IMAGETYPE_WEBP, $type);
        $this->assertSame(960, $w);
        $this->assertEqualsWithDelta(1200 * 960 / 1600, $h, 1);

        $stem = preg_replace('/-\d+\.webp$/', '', $path);
        foreach ([320, 640] as $vw) {
            $this->assertTrue(Storage::disk('public')->exists("{$stem}-{$vw}.webp"), "{$vw}w variant exists");
            [$vw2, $vh2] = getimagesizefromstring(Storage::disk('public')->get("{$stem}-{$vw}.webp"));
            $this->assertSame($vw, $vw2);
            $this->assertEqualsWithDelta(1200 * $vw / 1600, $vh2, 1);
        }
        $this->assertLessThan($originalBytes, Storage::disk('public')->size($path));

        // The transparent background survives re-encoding.
        $im = imagecreatefromstring(Storage::disk('public')->get($path));
        $this->assertGreaterThan(100, (imagecolorat($im, 2, 2) >> 24) & 0x7F, 'corner pixel is still (mostly) transparent');
    }

    public function test_small_images_are_not_enlarged_and_get_no_variants(): void
    {
        Storage::fake('public');
        $path = app(ProductImageService::class)->store($this->noisyPng(250, 200));

        [$w, $h] = getimagesizefromstring(Storage::disk('public')->get($path));
        $this->assertSame([250, 200], [$w, $h]);
        $this->assertCount(1, Storage::disk('public')->files('products'));
        $this->assertCount(1, app(ProductImageService::class)->variants($path));
    }

    public function test_exif_orientation_is_applied(): void
    {
        Storage::fake('public');
        $path = app(ProductImageService::class)->store($this->jpegWithOrientation(400, 200, 6)); // needs a 90° turn

        [$w, $h] = getimagesizefromstring(Storage::disk('public')->get($path));
        $this->assertSame([200, 400], [$w, $h]);
    }

    public function test_data_that_is_not_a_picture_is_refused_and_nothing_is_stored(): void
    {
        Storage::fake('public');
        $junk = UploadedFile::fake()->createWithContent('part.jpg', 'this is not an image');

        try {
            app(ProductImageService::class)->store($junk);
            $this->fail('expected a validation error');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('image', $e->errors());
        }
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_admin_upload_stores_the_optimised_set_and_replacing_removes_old_set_only(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create(['role' => 'owner']);
        $body = ['category' => 'Engine', 'part_name' => 'Piston Ring', 'brand' => 'Yamaha', 'model' => 'Mio', 'quantity' => 10, 'price' => 500];

        $this->actingAs($owner)->post(route('inventory.store'), $body + ['sku' => 'IMG-1', 'image' => $this->noisyPng(1100, 1100)])->assertSessionHasNoErrors();
        $item = InventoryItem::where('sku', 'IMG-1')->firstOrFail();
        $this->assertMatchesRegularExpression(ProductImageService::PATH_PATTERN, $item->image_path);
        $this->assertCount(3, Storage::disk('public')->files('products'));

        // An unrelated file in the same folder must survive a replacement.
        Storage::disk('public')->put('products/other-legacy.jpg', 'legacy');
        $old = $item->image_path;

        $this->actingAs($owner)->put(route('inventory.update', $item->id), $body + ['sku' => 'IMG-1', 'expected_quantity' => 10, 'image' => $this->noisyPng(500, 500)])->assertSessionHasNoErrors();

        $item->refresh();
        $this->assertNotSame($old, $item->image_path);
        Storage::disk('public')->assertMissing($old);
        $this->assertCount(3, Storage::disk('public')->files('products'), 'new 320w + 500w only, plus the unrelated file');
        Storage::disk('public')->assertExists('products/other-legacy.jpg');
    }

    public function test_legacy_paths_keep_working_with_no_srcset_and_no_rewrites(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/legacy photo.jpg', 'x');
        $legacy = InventoryItem::create(['sku' => 'L1', 'category' => 'A', 'part_name' => 'Legacy', 'brand' => 'B', 'model' => 'M', 'quantity' => 1, 'price' => 1, 'image_path' => 'products/legacy photo.jpg']);
        $external = InventoryItem::create(['sku' => 'L2', 'category' => 'A', 'part_name' => 'Ext', 'brand' => 'B', 'model' => 'M', 'quantity' => 1, 'price' => 1, 'image_path' => 'https://example.test/a.jpg']);
        $none = InventoryItem::create(['sku' => 'L3', 'category' => 'A', 'part_name' => 'None', 'brand' => 'B', 'model' => 'M', 'quantity' => 1, 'price' => 1]);

        foreach ([$legacy, $external, $none] as $i) {
            $this->assertNull($i->image_srcset);
        }
        $this->assertStringContainsString('products/legacy photo.jpg', $legacy->image_url);
        $this->assertSame('https://example.test/a.jpg', $external->image_url);
        $this->assertStringStartsWith('data:image/svg+xml', $none->image_url);
        $this->assertArrayHasKey('image_srcset', $legacy->toArray());
        Storage::disk('public')->assertExists('products/legacy photo.jpg');
    }

    public function test_component_emits_srcset_and_sizes_only_for_optimised_pictures_and_keeps_the_fallback(): void
    {
        Storage::fake('public');
        $opt = InventoryItem::create(['sku' => 'O1', 'category' => 'A', 'part_name' => 'Opt', 'brand' => 'B', 'model' => 'M', 'quantity' => 1, 'price' => 1,
            'image_path' => app(ProductImageService::class)->store($this->noisyPng(1200, 1200))]);
        $leg = InventoryItem::create(['sku' => 'O2', 'category' => 'A', 'part_name' => 'Leg', 'brand' => 'B', 'model' => 'M', 'quantity' => 1, 'price' => 1, 'image_path' => 'products/x.jpg']);

        $html = (string) $this->blade('<x-product-image :item="$i" sizes="48px" />', ['i' => $opt]);
        $this->assertStringContainsString('srcset="', $html);
        $this->assertStringContainsString(' 320w', $html);
        $this->assertStringContainsString(' 640w', $html);
        $this->assertStringContainsString(' 960w', $html);
        $this->assertStringContainsString('sizes="48px"', $html);
        $this->assertStringContainsString('loading="lazy"', $html);
        $this->assertStringContainsString('data-image-fallback', $html);
        $this->assertStringContainsString('onerror=', $html);

        $legacy = (string) $this->blade('<x-product-image :item="$i" :eager="true" />', ['i' => $leg]);
        $this->assertStringNotContainsString('srcset=', $legacy);
        $this->assertStringContainsString('loading="eager"', $legacy);
    }

    public function test_browse_loads_the_first_row_eagerly_and_the_rest_lazily(): void
    {
        Storage::fake('public');
        foreach (range(1, 6) as $n) {
            InventoryItem::create(['sku' => "BR$n", 'category' => 'A', 'part_name' => "Part $n", 'brand' => 'B', 'model' => 'M', 'quantity' => 3, 'price' => 10, 'image_path' => "products/p$n.jpg"]);
        }

        $html = $this->get(route('browse'))->assertOk()->getContent();
        preg_match_all('/<img [^>]*src="[^"]*products\/p\d\.jpg"[^>]*>/', $html, $m);
        $this->assertCount(6, $m[0]);
        $loading = array_map(fn ($t) => preg_match('/loading="(\w+)"/', $t, $x) ? $x[1] : '', $m[0]);
        $this->assertSame(['eager', 'eager', 'eager', 'lazy', 'lazy', 'lazy'], $loading);
    }

    public function test_service_only_writes_inside_public_products(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        app(ProductImageService::class)->store($this->noisyPng(900, 700));

        foreach (Storage::disk('public')->allFiles() as $f) {
            $this->assertStringStartsWith('products/', $f);
        }
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_an_already_lean_small_jpeg_is_kept_instead_of_being_made_bigger(): void
    {
        Storage::fake('public');
        $im = imagecreatetruecolor(100, 100);
        mt_srand(1);
        for ($y = 0; $y < 100; $y++) {
            for ($x = 0; $x < 100; $x++) {
                imagesetpixel($im, $x, $y, imagecolorallocate($im, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255)));
            }
        }
        $tmp = tempnam(sys_get_temp_dir(), 'lj').'.jpg';
        imagejpeg($im, $tmp, 10);
        $orig = file_get_contents($tmp);

        $path = app(ProductImageService::class)->store(new UploadedFile($tmp, 'a.jpg', 'image/jpeg', null, true));

        $this->assertMatchesRegularExpression(ProductImageService::PATH_PATTERN, $path);
        $this->assertLessThanOrEqual(strlen($orig), Storage::disk('public')->size($path));
        $this->assertSame(strlen($orig), Storage::disk('public')->size($path), 'kept byte-for-byte');
        $this->assertStringEndsWith('.jpg', $path);
    }

    public function test_format_is_detected_from_content_not_from_the_file_extension(): void
    {
        Storage::fake('public');
        $im = imagecreatetruecolor(700, 500);
        imagefill($im, 0, 0, imagecolorallocate($im, 30, 90, 160));
        $dir = sys_get_temp_dir();
        ob_start(); imagewebp($im, null, 80); $webp = ob_get_clean();
        ob_start(); imagepng($im); $png = ob_get_clean();
        file_put_contents("$dir/webp-as.jpg", $webp);
        file_put_contents("$dir/png-as.jpg", $png);

        foreach (['webp-as.jpg', 'png-as.jpg'] as $name) {
            $path = app(ProductImageService::class)->store(new UploadedFile("$dir/$name", $name, 'image/jpeg', null, true));
            $this->assertMatchesRegularExpression(ProductImageService::PATH_PATTERN, $path, $name);
            [$w, $h, $type] = getimagesizefromstring(Storage::disk('public')->get($path));
            $this->assertSame([700, 500], [$w, $h]);
        }

        // ... and the real upload endpoint accepts a mislabelled WebP.
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner)->post(route('inventory.store'), ['sku' => 'MIS-1', 'category' => 'A', 'part_name' => 'Mislabelled', 'brand' => 'B', 'model' => 'M', 'quantity' => 1, 'price' => 1,
            'image' => new UploadedFile("$dir/webp-as.jpg", 'webp-as.jpg', 'image/jpeg', null, true)])->assertSessionHasNoErrors();
        $this->assertNotNull(InventoryItem::where('sku', 'MIS-1')->value('image_path'));
    }

    public function test_indexed_png_with_a_transparent_colour_keeps_its_transparency(): void
    {
        Storage::fake('public');
        $im = imagecreate(300, 300);
        $clear = imagecolorallocate($im, 255, 255, 255);
        $red = imagecolorallocate($im, 220, 0, 0);
        imagecolortransparent($im, $clear);
        imagefilledellipse($im, 150, 150, 160, 160, $red);
        $f = sys_get_temp_dir().'/indexed.png';
        imagepng($im, $f);

        $path = app(ProductImageService::class)->store(new UploadedFile($f, 'indexed.png', 'image/png', null, true));

        $out = imagecreatefromstring(Storage::disk('public')->get($path));
        if (!imageistruecolor($out)) { // kept-original palette PNG: imagecolorat() would return an index
            imagepalettetotruecolor($out);
        }
        $this->assertGreaterThan(100, (imagecolorat($out, 3, 3) >> 24) & 0x7F, 'corner stays transparent');
        $this->assertSame(0, (imagecolorat($out, 150, 150) >> 24) & 0x7F, 'the product itself stays opaque');
    }

    public function test_every_srcset_entry_points_at_an_existing_file_of_exactly_the_declared_width(): void
    {
        Storage::fake('public');
        $item = InventoryItem::create(['sku' => 'W1', 'category' => 'A', 'part_name' => 'W', 'brand' => 'B', 'model' => 'M', 'quantity' => 1, 'price' => 1,
            'image_path' => app(ProductImageService::class)->store($this->noisyPng(1100, 700))]);

        $entries = array_map('trim', explode(',', $item->image_srcset));
        $this->assertCount(3, $entries);
        foreach ($entries as $entry) {
            [$url, $w] = preg_split('/\s+/', $entry);
            $path = 'products/'.basename($url);
            Storage::disk('public')->assertExists($path);
            $this->assertSame((int) rtrim($w, 'w'), getimagesizefromstring(Storage::disk('public')->get($path))[0], $entry);
        }
        $this->assertStringContainsString($item->image_url, $item->image_srcset, 'src is one of the srcset candidates');
    }

    public function test_missing_variants_are_left_out_and_a_missing_main_file_advertises_nothing(): void
    {
        Storage::fake('public');
        $path = app(ProductImageService::class)->store($this->noisyPng(1100, 1100));
        $item = InventoryItem::create(['sku' => 'M1', 'category' => 'A', 'part_name' => 'M', 'brand' => 'B', 'model' => 'M', 'quantity' => 1, 'price' => 1, 'image_path' => $path]);
        $stem = preg_replace('/-\d+\.webp$/', '', $path);

        Storage::disk('public')->delete("{$stem}-640.webp");
        $this->assertStringNotContainsString(' 640w', $item->fresh()->image_srcset);
        $this->assertStringContainsString(' 320w', $item->fresh()->image_srcset);

        Storage::disk('public')->delete($path);
        $this->assertNull($item->fresh()->image_srcset);
        // The picture URL is still produced, so the page falls back to the placeholder through onerror.
        $html = (string) $this->blade('<x-product-image :item="$i" />', ['i' => $item->fresh()]);
        $this->assertStringNotContainsString('srcset=', $html);
        $this->assertStringContainsString('data-image-fallback', $html);
    }

    public function test_new_uploads_over_the_pixel_cap_are_refused_with_a_clear_message_and_nothing_is_stored(): void
    {
        Storage::fake('public');
        $im = imagecreatetruecolor(3400, 3400); // 11.6 MP > 10 MP cap
        imagefill($im, 0, 0, imagecolorallocate($im, 10, 120, 10));
        $f = sys_get_temp_dir().'/huge.png';
        imagepng($im, $f, 9);
        imagedestroy($im);
        $this->assertLessThan(2 * 1024 * 1024, filesize($f), 'under the 2 MB upload limit, yet far too many pixels');

        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner)->post(route('inventory.store'), $this->itemData('BIG-1') + ['image' => new UploadedFile($f, 'huge.png', 'image/png', null, true)])
            ->assertSessionHasErrors('image');
        $this->assertStringContainsString('megapixels', session('errors')->first('image'));

        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertDatabaseMissing('inventory_items', ['sku' => 'BIG-1']);
    }

    public function test_an_extreme_dimension_png_is_refused_from_its_header_without_decoding_it(): void
    {
        Storage::fake('public');
        $w = $h = 20000; // 400 MP, a few hundred KB when compressed
        $chunk = fn (string $t, string $d) => pack('N', strlen($d)).$t.$d.pack('N', crc32($t.$d));
        $z = deflate_init(ZLIB_ENCODING_DEFLATE, ['level' => 9]);
        $row = "\0".str_repeat("\x80", $w);
        $body = '';
        for ($i = 0; $i < $h; $i++) {
            $body .= deflate_add($z, $row, ZLIB_NO_FLUSH);
        }
        $body .= deflate_add($z, '', ZLIB_FINISH);
        $f = sys_get_temp_dir().'/bomb.png';
        file_put_contents($f, "\x89PNG\r\n\x1a\n".$chunk('IHDR', pack('NNCCCCC', $w, $h, 8, 0, 0, 0, 0)).$chunk('IDAT', $body).$chunk('IEND', ''));
        $before = memory_get_peak_usage(true);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        try {
            app(ProductImageService::class)->store(new UploadedFile($f, 'bomb.png', 'image/png', null, true));
        } finally {
            $this->assertSame([], Storage::disk('public')->allFiles());
            $this->assertLessThan(60 * 1024 * 1024, memory_get_peak_usage(true) - $before, 'never decoded');
        }
    }

    private function itemData(string $sku): array
    {
        return ['sku' => $sku, 'category' => 'A', 'part_name' => 'P '.$sku, 'brand' => 'B', 'model' => 'M', 'quantity' => 1, 'price' => 1];
    }

    private function jpegBytes(bool $progressive = false): string
    {
        $im = imagecreatetruecolor(800, 600);
        mt_srand(11);
        for ($i = 0; $i < 60; $i++) {
            imagefilledellipse($im, mt_rand(0, 800), mt_rand(0, 600), mt_rand(20, 300), mt_rand(20, 300), imagecolorallocate($im, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255)));
        }
        imageinterlace($im, $progressive);
        ob_start();
        imagejpeg($im, null, 85);

        return ob_get_clean();
    }

    public function test_a_truncated_jpeg_is_refused_not_reported_as_a_successful_optimisation(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create(['role' => 'owner']);
        $full = $this->jpegBytes();
        foreach ([0.5, 0.9, 0.99] as $ratio) {
            $f = sys_get_temp_dir().'/cut.jpg';
            file_put_contents($f, substr($full, 0, (int) (strlen($full) * $ratio)));
            $this->actingAs($owner)->post(route('inventory.store'), $this->itemData('CUT-'.$ratio) + ['image' => new UploadedFile($f, 'cut.jpg', 'image/jpeg', null, true)])
                ->assertSessionHasErrors('image');
            $this->assertStringContainsString('incomplete', session('errors')->first('image'));
        }
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertSame(0, InventoryItem::where('sku', 'like', 'CUT-%')->count());
    }

    public function test_a_truncated_png_or_webp_is_refused_too(): void
    {
        Storage::fake('public');
        $im = imagecreatetruecolor(600, 400);
        mt_srand(5);
        for ($i = 0; $i < 80; $i++) {
            imagefilledellipse($im, mt_rand(0, 600), mt_rand(0, 400), mt_rand(10, 200), mt_rand(10, 200), imagecolorallocate($im, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255)));
        }
        ob_start(); imagepng($im); $png = ob_get_clean();
        ob_start(); imagewebp($im, null, 80); $webp = ob_get_clean();
        foreach (['cut.png' => $png, 'cut.webp' => $webp] as $name => $bytes) {
            $f = sys_get_temp_dir().'/'.$name;
            file_put_contents($f, substr($bytes, 0, intdiv(strlen($bytes), 2)));
            try {
                app(ProductImageService::class)->store(new UploadedFile($f, $name, null, null, true));
                $this->fail("$name should have been refused");
            } catch (\Illuminate\Validation\ValidationException $e) {
                $this->assertArrayHasKey('image', $e->errors(), $name);
            }
        }
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_complete_jpegs_are_accepted_including_progressive_and_files_with_trailing_data(): void
    {
        Storage::fake('public');
        foreach (['baseline' => $this->jpegBytes(), 'progressive' => $this->jpegBytes(true), 'trailing' => $this->jpegBytes()."\0\0\0TRAILER-BYTES"] as $label => $bytes) {
            $f = sys_get_temp_dir()."/ok-$label.jpg";
            file_put_contents($f, $bytes);
            $this->assertTrue(ProductImageService::jpegIsComplete($f), $label);
            $path = app(ProductImageService::class)->store(new UploadedFile($f, "ok-$label.jpg", 'image/jpeg', null, true));
            Storage::disk('public')->assertExists($path);
        }
    }

    public function test_a_refused_replacement_keeps_the_existing_picture_and_product(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create(['role' => 'owner']);
        $old = app(ProductImageService::class)->store($this->noisyPng(900, 900));
        $item = InventoryItem::create($this->itemData('KEEP-1') + ['image_path' => $old]);
        $before = Storage::disk('public')->allFiles();
        $f = sys_get_temp_dir().'/cut2.jpg';
        $full = $this->jpegBytes();
        file_put_contents($f, substr($full, 0, intdiv(strlen($full), 2)));

        $this->actingAs($owner)->put(route('inventory.update', $item->id), $this->itemData('KEEP-1') + ['expected_quantity' => 1, 'image' => new UploadedFile($f, 'cut2.jpg', 'image/jpeg', null, true)])
            ->assertSessionHasErrors('image');

        $this->assertSame($old, $item->fresh()->image_path);
        $this->assertEqualsCanonicalizing($before, Storage::disk('public')->allFiles());
    }

    public function test_upload_size_limits_are_unchanged_product_2_mib_and_gcash_proof_4_mib(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner)->post(route('inventory.store'), $this->itemData('SZ-1') + ['image' => UploadedFile::fake()->image('big.jpg', 100, 100)->size(2049)])
            ->assertSessionHasErrors('image');
        $this->actingAs($owner)->post(route('inventory.store'), $this->itemData('SZ-2') + ['image' => UploadedFile::fake()->image('ok.jpg', 100, 100)->size(2048)])
            ->assertSessionHasNoErrors();
        $this->assertSame(4096, (int) config('payments.gcash_qr.max_proof_kb'));
        $this->assertStringContainsString('max:2048', file_get_contents(base_path('app/Http/Requests/Inventory/StoreInventoryRequest.php')));
        $this->assertStringContainsString('max:2048', file_get_contents(base_path('app/Http/Requests/Inventory/UpdateInventoryRequest.php')));
    }

    public function test_a_failed_update_keeps_the_old_picture_and_leaves_no_orphan(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create(['role' => 'owner']);
        $old = app(ProductImageService::class)->store($this->noisyPng(900, 900));
        $item = InventoryItem::create(['sku' => 'F1', 'category' => 'A', 'part_name' => 'F', 'brand' => 'B', 'model' => 'M', 'quantity' => 5, 'price' => 10, 'image_path' => $old]);
        $before = Storage::disk('public')->allFiles();

        $this->actingAs($owner)->put(route('inventory.update', $item->id), ['sku' => 'F1', 'category' => 'A', 'part_name' => 'F', 'brand' => 'B', 'model' => 'M',
            'quantity' => 5, 'expected_quantity' => 99 /* stale */, 'price' => 10, 'image' => $this->noisyPng(900, 900)])->assertSessionHasErrors('quantity');

        $this->assertSame($old, $item->fresh()->image_path);
        $this->assertEqualsCanonicalizing($before, Storage::disk('public')->allFiles());
    }

    public function test_a_picture_still_used_by_another_product_is_not_deleted_on_replace(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create(['role' => 'owner']);
        $shared = app(ProductImageService::class)->store($this->noisyPng(900, 900));
        $a = InventoryItem::create(['sku' => 'S1', 'category' => 'A', 'part_name' => 'A', 'brand' => 'B', 'model' => 'M', 'quantity' => 5, 'price' => 10, 'image_path' => $shared]);
        $b = InventoryItem::create(['sku' => 'S2', 'category' => 'A', 'part_name' => 'B', 'brand' => 'B', 'model' => 'M', 'quantity' => 5, 'price' => 10, 'image_path' => $shared]);

        $this->actingAs($owner)->put(route('inventory.update', $a->id), ['sku' => 'S1', 'category' => 'A', 'part_name' => 'A', 'brand' => 'B', 'model' => 'M',
            'quantity' => 5, 'expected_quantity' => 5, 'price' => 10, 'image' => $this->noisyPng(500, 500)])->assertSessionHasNoErrors();

        $this->assertNotSame($shared, $a->fresh()->image_path);
        Storage::disk('public')->assertExists($shared);
        $this->assertSame($shared, $b->fresh()->image_path);
    }

    public function test_private_payment_files_are_never_touched_by_image_work(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        Storage::disk('local')->put('payments/proofs/1/proof.jpg', 'private-proof');
        $svc = app(ProductImageService::class);

        $path = $svc->store($this->noisyPng(900, 900));
        $svc->delete($path);
        $svc->delete('payments/proofs/1/proof.jpg'); // not a products/ path: only the public disk is ever touched

        Storage::disk('local')->assertExists('payments/proofs/1/proof.jpg');
        $this->assertSame('private-proof', Storage::disk('local')->get('payments/proofs/1/proof.jpg'));
    }

    public function test_a_write_failure_part_way_through_a_set_leaves_no_partial_files_and_still_stores_the_picture(): void
    {
        Storage::fake('public');
        $real = Storage::disk('public');
        $writes = 0;
        $flaky = \Mockery::mock($real)->makePartial();
        $flaky->shouldReceive('put')->andReturnUsing(function (...$args) use ($real, &$writes) {
            if (++$writes === 2) {
                throw new \RuntimeException('disk full');
            }

            return $real->put(...$args);
        });
        Storage::set('public', $flaky);

        $path = app(ProductImageService::class)->store($this->noisyPng(1200, 900)); // would be a 3-file set

        $this->assertDoesNotMatchRegularExpression('#/p_[a-f0-9]{32}-#', implode(',', Storage::disk('public')->allFiles()), 'no half-written optimised set is left behind');
        Storage::disk('public')->assertExists($path); // the upload still succeeded, with the original file
        $this->assertCount(1, Storage::disk('public')->allFiles());
    }

    public function test_the_rejection_message_is_visible_on_the_product_form(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create(['role' => 'owner']);
        $item = InventoryItem::create($this->itemData('VIS-1'));
        $f = sys_get_temp_dir().'/cut3.jpg';
        $full = $this->jpegBytes();
        file_put_contents($f, substr($full, 0, intdiv(strlen($full), 2)));

        $this->actingAs($owner)->from(route('inventory.edit', $item->id))->followingRedirects()
            ->put(route('inventory.update', $item->id), $this->itemData('VIS-1') + ['expected_quantity' => 1, 'image' => new UploadedFile($f, 'cut3.jpg', 'image/jpeg', null, true)])
            ->assertSee('This JPEG is incomplete', false);
    }
}
