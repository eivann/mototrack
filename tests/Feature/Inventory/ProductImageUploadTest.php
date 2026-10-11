<?php

namespace Tests\Feature\Inventory;

use App\Models\User;
use App\Models\InventoryItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_product_image_during_creation()
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'owner']);
        
        $file = UploadedFile::fake()->image('part.jpg');

        $response = $this->actingAs($admin)->post(route('inventory.store'), [
            'sku' => 'TEST-001',
            'category' => 'Engine',
            'part_name' => 'Piston Ring',
            'brand' => 'Yamaha',
            'model' => 'Mio',
            'quantity' => 10,
            'price' => 500.00,
            'image' => $file,
        ]);

        $response->assertRedirect(route('inventory.index'));
        
        $item = InventoryItem::where('sku', 'TEST-001')->first();
        $this->assertNotNull($item->image_path);
        
        // Assert the file was actually stored on the faked public disk
        Storage::disk('public')->assertExists($item->image_path);
    }

    public function test_admin_can_update_product_image_and_old_image_is_deleted()
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'owner']);
        
        // Create initial item with old image
        $oldFile = UploadedFile::fake()->image('old.jpg');
        $oldPath = $oldFile->store('products', 'public');
        
        $item = InventoryItem::create([
            'sku' => 'TEST-002',
            'category' => 'Brakes',
            'part_name' => 'Brake Pad',
            'brand' => 'Honda',
            'model' => 'Click',
            'quantity' => 5,
            'price' => 200.00,
            'image_path' => $oldPath
        ]);

        Storage::disk('public')->assertExists($oldPath);

        // Upload new image
        $newFile = UploadedFile::fake()->image('new.jpg');
        
        $response = $this->actingAs($admin)->put(route('inventory.update', $item->id), [
            'sku' => 'TEST-002',
            'category' => 'Brakes',
            'part_name' => 'Brake Pad Updated',
            'brand' => 'Honda',
            'model' => 'Click',
            'quantity' => 5,
            'expected_quantity' => 5,
            'price' => 200.00,
            'image' => $newFile,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('inventory.index'));

        $item->refresh();
        
        $this->assertNotNull($item->image_path);
        $this->assertNotEquals($oldPath, $item->image_path);
        
        // Assert new file exists and old file is deleted
        Storage::disk('public')->assertExists($item->image_path);
        Storage::disk('public')->assertMissing($oldPath);
    }
}
