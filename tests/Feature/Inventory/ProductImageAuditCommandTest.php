<?php

namespace Tests\Feature\Inventory;

use App\Models\InventoryItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ProductImageAuditCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_measures_legacy_pictures_and_changes_nothing(): void
    {
        $dir = sys_get_temp_dir().'/audit_'.uniqid();
        mkdir($dir.'/images/products', 0777, true);
        $this->app->usePublicPath($dir);

        $im = imagecreatetruecolor(1400, 1400);
        mt_srand(2);
        for ($i = 0; $i < 200; $i++) {
            imagefilledellipse($im, mt_rand(0, 1400), mt_rand(0, 1400), mt_rand(20, 300), mt_rand(20, 300), imagecolorallocate($im, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255)));
        }
        imagepng($im, $dir.'/images/products/big.png', 6);
        $before = filesize($dir.'/images/products/big.png');

        $item = InventoryItem::create(['sku' => 'A1', 'category' => 'A', 'part_name' => 'Big', 'brand' => 'B', 'model' => 'M', 'quantity' => 1, 'price' => 1, 'image_path' => 'products/big.png']);
        InventoryItem::create(['sku' => 'A2', 'category' => 'A', 'part_name' => 'Missing', 'brand' => 'B', 'model' => 'M', 'quantity' => 1, 'price' => 1, 'image_path' => 'products/missing.jpg']);
        $stamp = $item->fresh()->updated_at;
        $mtime = filemtime($dir.'/images/products/big.png');

        Artisan::call('products:images-audit', ['--json' => true]);
        $out = json_decode(Artisan::output(), true);

        $this->assertSame(1, $out['summary']['pictures_measured']);
        $this->assertSame(1, $out['summary']['file_not_found']);
        $this->assertLessThan($before, $out['summary']['bytes_estimated']);
        $this->assertSame(960, $out['rows'][0]['est_w']);

        // Read-only: file, row and folder are exactly as before.
        $this->assertSame($before, filesize($dir.'/images/products/big.png'));
        $this->assertSame($mtime, filemtime($dir.'/images/products/big.png'));
        $this->assertSame(['big.png'], array_values(array_diff(scandir($dir.'/images/products'), ['.', '..'])));
        $this->assertSame('products/big.png', $item->fresh()->image_path);
        $this->assertEquals($stamp, $item->fresh()->updated_at);
    }
}
