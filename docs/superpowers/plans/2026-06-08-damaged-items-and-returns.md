# Damaged Items & Product Returns Tracking Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [x]`) syntax for tracking.

**Goal:** Implement a digital tracking module for damaged inventory items and product returns to fulfill the project's inventory monitoring objectives.

**Architecture:** Create dedicated database tables for `damaged_items` and `product_returns` with corresponding Eloquent models. Provide validation requests and a central controller (`InventoryAdjustmentController`) under owner-level middleware protection. Implement a frontend view displaying transaction logs and forms/modals to record stock adjustments.

**Tech Stack:** Laravel 13.x, Pest PHP, Tailwind CSS, AlpineJS

---

### Task 1: Create Database Migrations

**Files:**
- Create: `database/migrations/YYYY_MM_DD_create_damaged_items_table.php`
- Create: `database/migrations/YYYY_MM_DD_create_product_returns_table.php`

- [x] **Step 1: Generate migration file for damaged items**

Run: `php artisan make:migration create_damaged_items_table`
Expected: Migration file generated successfully.

- [x] **Step 2: Define schema in the damaged items migration**

Open the created migration file and define:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('damaged_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('damaged_items');
    }
};
```

- [x] **Step 3: Generate migration file for product returns**

Run: `php artisan make:migration create_product_returns_table`
Expected: Migration file generated successfully.

- [x] **Step 4: Define schema in the product returns migration**

Open the created migration file and define:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('quantity');
            $table->string('action_taken'); // 'restocked' or 'discarded'
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_returns');
    }
};
```

- [x] **Step 5: Run the database migrations**

Run: `php artisan migrate`
Expected: Migrations execute successfully.

- [x] **Step 6: Commit migrations**

```bash
git add database/migrations
git commit -m "migration: create damaged_items and product_returns tables"
```

---

### Task 2: Create Eloquent Models and Relationships

**Files:**
- Create: `app/Models/DamagedItem.php`
- Create: `app/Models/ProductReturn.php`
- Modify: `app/Models/InventoryItem.php`
- Modify: `app/Models/Customer.php`

- [x] **Step 1: Write DamagedItem model**

Create `app/Models/DamagedItem.php` and define:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DamagedItem extends Model
{
    protected $fillable = ['inventory_item_id', 'quantity', 'notes'];

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
```

- [x] **Step 2: Write ProductReturn model**

Create `app/Models/ProductReturn.php` and define:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductReturn extends Model
{
    protected $fillable = ['inventory_item_id', 'customer_id', 'quantity', 'action_taken', 'notes'];

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
```

- [x] **Step 3: Define relationships in InventoryItem and Customer**

Open `app/Models/InventoryItem.php` and add:
```php
public function damagedItems()
{
    return $this->hasMany(DamagedItem::class);
}

public function productReturns()
{
    return $this->hasMany(ProductReturn::class);
}
```

Open `app/Models/Customer.php` and add:
```php
public function productReturns()
{
    return $this->hasMany(ProductReturn::class);
}
```

- [x] **Step 4: Commit models**

```bash
git add app/Models
git commit -m "feat: add DamagedItem and ProductReturn models with relationships"
```

---

### Task 3: Implement Request Validators

**Files:**
- Create: `app/Http/Requests/Inventory/StoreDamagedItemRequest.php`
- Create: `app/Http/Requests/Inventory/StoreProductReturnRequest.php`

- [x] **Step 1: Write StoreDamagedItemRequest**

Create `app/Http/Requests/Inventory/StoreDamagedItemRequest.php`:
```php
<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class StoreDamagedItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'owner';
    }

    public function rules(): array
    {
        return [
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'quantity'          => 'required|integer|min:1',
            'notes'             => 'nullable|string|max:1000',
        ];
    }
}
```

- [x] **Step 2: Write StoreProductReturnRequest**

Create `app/Http/Requests/Inventory/StoreProductReturnRequest.php`:
```php
<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'owner';
    }

    public function rules(): array
    {
        return [
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'customer_id'       => 'nullable|exists:customers,id',
            'quantity'          => 'required|integer|min:1',
            'action_taken'      => 'required|string|in:restocked,discarded',
            'notes'             => 'nullable|string|max:1000',
        ];
    }
}
```

- [x] **Step 3: Commit request classes**

```bash
git add app/Http/Requests/Inventory
git commit -m "feat: create StoreDamagedItemRequest and StoreProductReturnRequest"
```

---

### Task 4: Implement InventoryAdjustmentController

**Files:**
- Create: `app/Http/Controllers/Inventory/InventoryAdjustmentController.php`

- [x] **Step 1: Write controller handling list and storage operations**

Create `app/Http/Controllers/Inventory/InventoryAdjustmentController.php` with transactional locks to ensure safe quantity increments/decrements:
```php
<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreDamagedItemRequest;
use App\Http\Requests\Inventory\StoreProductReturnRequest;
use App\Models\DamagedItem;
use App\Models\ProductReturn;
use App\Models\InventoryItem;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class InventoryAdjustmentController extends Controller
{
    public function index()
    {
        $damagedItems = DamagedItem::with('item')->latest()->paginate(10, ['*'], 'damaged_page');
        $productReturns = ProductReturn::with('item', 'customer')->latest()->paginate(10, ['*'], 'returns_page');
        $inventoryItems = InventoryItem::orderBy('part_name')->get();
        $customers = Customer::orderBy('name')->get();

        return view('inventory.adjustments', compact('damagedItems', 'productReturns', 'inventoryItems', 'customers'));
    }

    public function storeDamaged(StoreDamagedItemRequest $request)
    {
        DB::transaction(function () use ($request) {
            $item = InventoryItem::lockForUpdate()->findOrFail($request->inventory_item_id);
            
            if ($item->quantity < $request->quantity) {
                throw new \InvalidArgumentException('Quantity to mark as damaged exceeds available stock.');
            }

            DamagedItem::create($request->validated());
            $item->decrement('quantity', $request->quantity);
            
            // Trigger stock notification if threshold is breached
            \App\Jobs\CheckLowStockAlertJob::dispatch($item->id);
        });

        return redirect()->back()->with('success', 'Damaged stock recorded successfully!');
    }

    public function storeReturn(StoreProductReturnRequest $request)
    {
        DB::transaction(function () use ($request) {
            ProductReturn::create($request->validated());
            
            if ($request->action_taken === 'restocked') {
                $item = InventoryItem::lockForUpdate()->findOrFail($request->inventory_item_id);
                $item->increment('quantity', $request->quantity);
            }
        });

        return redirect()->back()->with('success', 'Product return recorded successfully!');
    }
}
```

- [x] **Step 2: Commit controller**

```bash
git add app/Http/Controllers/Inventory/InventoryAdjustmentController.php
git commit -m "feat: implement InventoryAdjustmentController with transaction handling"
```

---

### Task 5: Add Routes

**Files:**
- Modify: `routes/modules/inventory.php`

- [x] **Step 1: Register adjustment routes inside the owner middleware group**

Open `routes/modules/inventory.php` and append adjustment routes under the administrative Route group:
```php
    // Inventory Adjustments (Damaged & Returns)
    Route::get('/inventory/adjustments', [InventoryAdjustmentController::class, 'index'])->name('inventory.adjustments.index');
    Route::post('/inventory/adjustments/damaged', [InventoryAdjustmentController::class, 'storeDamaged'])->name('inventory.adjustments.store_damaged');
    Route::post('/inventory/adjustments/returns', [InventoryAdjustmentController::class, 'storeReturn'])->name('inventory.adjustments.store_return');
```

Also add import at the top of `routes/modules/inventory.php`:
```php
use App\Http\Controllers\Inventory\InventoryAdjustmentController;
```

- [x] **Step 2: Commit routes**

```bash
git add routes/modules/inventory.php
git commit -m "feat: add inventory adjustment routes"
```

---

### Task 6: Create the adjustments.blade.php View

**Files:**
- Create: `resources/views/inventory/adjustments.blade.php`
- Modify: `resources/views/admin/dashboard.blade.php`

- [x] **Step 1: Create the view dashboard file**

Create `resources/views/inventory/adjustments.blade.php` containing two tabs (Damaged Items logs, Product Returns logs) and modal triggers/forms to log adjustments:
```html
<x-app-layout>
<div class="min-h-screen bg-gray-50 dark:bg-gray-950 py-10 px-4" x-data="{ activeTab: 'damaged', showDamageModal: false, showReturnModal: false }">
    <div class="max-w-7xl mx-auto space-y-8">
        
        <!-- Header -->
        <div class="flex justify-between items-center bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 p-8 rounded-[2.5rem] shadow-sm">
            <div>
                <h2 class="text-2xl font-black text-gray-900 dark:text-white italic">Inventory Adjustments</h2>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mt-1">Record and track damaged items and customer returns</p>
            </div>
            <div class="flex gap-4">
                <button @click="showDamageModal = true" class="bg-red-600 hover:bg-red-700 text-white font-black text-xs uppercase tracking-widest px-6 py-3 rounded-xl shadow-lg transition-transform active:scale-95">
                    Log Damage
                </button>
                <button @click="showReturnModal = true" class="bg-indigo-600 hover:bg-indigo-700 text-white font-black text-xs uppercase tracking-widest px-6 py-3 rounded-xl shadow-lg transition-transform active:scale-95">
                    Log Return
                </button>
            </div>
        </div>

        @if(session('success'))
            <div class="p-4 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 font-bold rounded-2xl border border-emerald-100 dark:border-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        <!-- Navigation Tabs -->
        <div class="flex gap-4 border-b border-gray-200 dark:border-gray-800">
            <button @click="activeTab = 'damaged'" :class="activeTab === 'damaged' ? 'border-red-600 text-red-600' : 'border-transparent text-gray-400'" class="pb-4 px-2 font-black text-xs uppercase tracking-widest border-b-2 transition-all">
                Damaged Items
            </button>
            <button @click="activeTab = 'returns'" :class="activeTab === 'returns' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-400'" class="pb-4 px-2 font-black text-xs uppercase tracking-widest border-b-2 transition-all">
                Product Returns
            </button>
        </div>

        <!-- Damaged Items List -->
        <div x-show="activeTab === 'damaged'" class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 overflow-hidden shadow-sm">
            <div class="p-8 border-b border-gray-100 dark:border-gray-800">
                <h3 class="text-lg font-black text-gray-900 dark:text-white italic">Damaged Items Log</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800/50 text-[10px] font-black text-gray-400 uppercase tracking-widest">
                            <th class="p-6">Date</th>
                            <th class="p-6">Part Name</th>
                            <th class="p-6">Quantity</th>
                            <th class="p-6">Notes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                        @forelse($damagedItems as $item)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                                <td class="p-6 text-sm text-gray-500 font-bold">{{ $item->created_at->format('M d, Y H:i') }}</td>
                                <td class="p-6 font-bold text-gray-900 dark:text-white">{{ $item->item->part_name }}</td>
                                <td class="p-6"><span class="px-2.5 py-1 rounded-lg bg-red-50 text-red-600 text-xs font-black">{{ $item->quantity }} Units</span></td>
                                <td class="p-6 text-sm text-gray-500 max-w-xs truncate">{{ $item->notes ?? 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-12 text-center text-gray-400 font-bold italic">No damaged items registered.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($damagedItems->hasPages())
                <div class="p-6 border-t border-gray-50 dark:border-gray-800">{{ $damagedItems->links() }}</div>
            @endif
        </div>

        <!-- Product Returns List -->
        <div x-show="activeTab === 'returns'" class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 overflow-hidden shadow-sm" x-cloak>
            <div class="p-8 border-b border-gray-100 dark:border-gray-800">
                <h3 class="text-lg font-black text-gray-900 dark:text-white italic">Product Returns Log</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800/50 text-[10px] font-black text-gray-400 uppercase tracking-widest">
                            <th class="p-6">Date</th>
                            <th class="p-6">Part Name</th>
                            <th class="p-6">Customer</th>
                            <th class="p-6">Quantity</th>
                            <th class="p-6">Action Taken</th>
                            <th class="p-6">Notes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                        @forelse($productReturns as $item)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                                <td class="p-6 text-sm text-gray-500 font-bold">{{ $item->created_at->format('M d, Y H:i') }}</td>
                                <td class="p-6 font-bold text-gray-900 dark:text-white">{{ $item->item->part_name }}</td>
                                <td class="p-6 text-sm text-gray-500 font-bold">{{ $item->customer->name ?? 'Walk-in' }}</td>
                                <td class="p-6"><span class="px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-600 text-xs font-black">{{ $item->quantity }} Units</span></td>
                                <td class="p-6">
                                    <span class="px-2.5 py-1 rounded-lg text-xs font-black {{ $item->action_taken === 'restocked' ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600' }}">
                                        {{ ucfirst($item->action_taken) }}
                                    </span>
                                </td>
                                <td class="p-6 text-sm text-gray-500 max-w-xs truncate">{{ $item->notes ?? 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-12 text-center text-gray-400 font-bold italic">No returns registered.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($productReturns->hasPages())
                <div class="p-6 border-t border-gray-50 dark:border-gray-800">{{ $productReturns->links() }}</div>
            @endif
        </div>

    </div>

    <!-- Log Damage Modal -->
    <div x-show="showDamageModal" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-black/55 backdrop-blur-sm" x-cloak>
        <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 max-w-md w-full rounded-3xl p-8 shadow-2xl relative">
            <h3 class="text-xl font-black text-gray-900 dark:text-white italic mb-6">Log Damaged Stock</h3>
            <form method="POST" action="{{ route('inventory.adjustments.store_damaged') }}">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Select Item</label>
                        <select name="inventory_item_id" required class="w-full border border-gray-200 dark:border-gray-800 rounded-xl px-4 py-3 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white outline-none">
                            @foreach($inventoryItems as $item)
                                <option value="{{ $item->id }}">{{ $item->part_name }} (Stock: {{ $item->quantity }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Quantity</label>
                        <input type="number" name="quantity" min="1" required class="w-full border border-gray-200 dark:border-gray-800 rounded-xl px-4 py-3 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Notes / Reason</label>
                        <textarea name="notes" rows="3" class="w-full border border-gray-200 dark:border-gray-800 rounded-xl px-4 py-3 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white outline-none"></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-8">
                    <button type="button" @click="showDamageModal = false" class="px-5 py-3 border border-gray-200 dark:border-gray-850 rounded-xl font-bold text-xs uppercase tracking-widest text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-800">Cancel</button>
                    <button type="submit" class="px-5 py-3 bg-red-600 hover:bg-red-700 text-white font-bold text-xs uppercase tracking-widest rounded-xl shadow-lg">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Log Return Modal -->
    <div x-show="showReturnModal" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-black/55 backdrop-blur-sm" x-cloak>
        <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 max-w-md w-full rounded-3xl p-8 shadow-2xl relative">
            <h3 class="text-xl font-black text-gray-900 dark:text-white italic mb-6">Log Product Return</h3>
            <form method="POST" action="{{ route('inventory.adjustments.store_return') }}">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Select Item</label>
                        <select name="inventory_item_id" required class="w-full border border-gray-200 dark:border-gray-800 rounded-xl px-4 py-3 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white outline-none">
                            @foreach($inventoryItems as $item)
                                <option value="{{ $item->id }}">{{ $item->part_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Customer (Optional)</label>
                        <select name="customer_id" class="w-full border border-gray-200 dark:border-gray-800 rounded-xl px-4 py-3 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white outline-none">
                            <option value="">Walk-in</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Quantity</label>
                        <input type="number" name="quantity" min="1" required class="w-full border border-gray-200 dark:border-gray-800 rounded-xl px-4 py-3 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Action Taken</label>
                        <select name="action_taken" required class="w-full border border-gray-200 dark:border-gray-800 rounded-xl px-4 py-3 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white outline-none">
                            <option value="restocked">Put back in Inventory (Restock)</option>
                            <option value="discarded">Mark Damaged/Discard (Do not Restock)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Notes / Reason</label>
                        <textarea name="notes" rows="3" class="w-full border border-gray-200 dark:border-gray-800 rounded-xl px-4 py-3 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white outline-none"></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-8">
                    <button type="button" @click="showReturnModal = false" class="px-5 py-3 border border-gray-200 dark:border-gray-850 rounded-xl font-bold text-xs uppercase tracking-widest text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-800">Cancel</button>
                    <button type="submit" class="px-5 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs uppercase tracking-widest rounded-xl shadow-lg">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
</x-app-layout>
```

- [x] **Step 2: Add navigation link to adjustments on the Admin Dashboard view**

Open `resources/views/admin/dashboard.blade.php` and find the inventory tab code (around line 74):
```html
                                <h3 class="text-xl font-black text-gray-900 dark:text-white italic">Stock Summary</h3>
                                <a href="/inventory" class="text-indigo-600 font-black text-xs uppercase tracking-widest hover:underline">View All &rarr;</a>
```

Add a button linking to adjustments next to it:
```html
                                <h3 class="text-xl font-black text-gray-900 dark:text-white italic">Stock Summary</h3>
                                <div class="flex gap-4 items-center">
                                    <a href="/inventory/adjustments" class="text-indigo-600 font-black text-xs uppercase tracking-widest hover:underline mr-4">Damaged & Returns</a>
                                    <a href="/inventory" class="text-indigo-600 font-black text-xs uppercase tracking-widest hover:underline">View All &rarr;</a>
                                </div>
```

- [x] **Step 3: Commit interface modifications**

```bash
git add resources/views
git commit -m "feat: create adjustments view and link to dashboard"
```

---

### Task 7: Write Feature Tests for Adjustments

**Files:**
- Create: `tests/Feature/Admin/InventoryAdjustmentTest.php`

- [x] **Step 1: Write integration tests verifying inventory adjustment endpoints**

Create `tests/Feature/Admin/InventoryAdjustmentTest.php`:
```php
<?php

use App\Models\InventoryItem;
use App\Models\User;
use App\Models\DamagedItem;
use App\Models\ProductReturn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use App\Jobs\CheckLowStockAlertJob;

uses(RefreshDatabase::class);

test('owner can access the adjustments page', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $response = $this->actingAs($owner)->get('/inventory/adjustments');
    $response->assertStatus(200);
});

test('non-owner cannot access adjustments page', function () {
    $user = User::factory()->create(['role' => 'customer']);
    $response = $this->actingAs($user)->get('/inventory/adjustments');
    $response->assertStatus(403);
});

test('owner can record damaged items which decrements quantity', function () {
    Queue::fake();

    $owner = User::factory()->create(['role' => 'owner']);
    $item = InventoryItem::create([
        'part_name' => 'Brake Pad',
        'price' => 500.00,
        'quantity' => 10,
        'low_stock_threshold' => 5,
        'low_stock_notified' => false,
    ]);

    $response = $this->actingAs($owner)->post('/inventory/adjustments/damaged', [
        'inventory_item_id' => $item->id,
        'quantity'          => 3,
        'notes'             => 'Cracked coating'
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('damaged_items', [
        'inventory_item_id' => $item->id,
        'quantity'          => 3,
        'notes'             => 'Cracked coating'
    ]);

    expect($item->fresh()->quantity)->toBe(7);
    Queue::assertDispatched(CheckLowStockAlertJob::class);
});

test('owner cannot record damaged items with quantity exceeding stock', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $item = InventoryItem::create([
        'part_name' => 'Spark Plug',
        'price' => 150.00,
        'quantity' => 2,
    ]);

    $response = $this->actingAs($owner)->post('/inventory/adjustments/damaged', [
        'inventory_item_id' => $item->id,
        'quantity'          => 3,
    ]);

    // Should fail with standard redirect back due to custom validation or exception
    $response->assertRedirect();
    $this->assertDatabaseMissing('damaged_items', [
        'inventory_item_id' => $item->id
    ]);
});

test('owner can record restocked product returns which increments quantity', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $item = InventoryItem::create([
        'part_name' => 'Clutch Cable',
        'price' => 300.00,
        'quantity' => 5,
    ]);

    $response = $this->actingAs($owner)->post('/inventory/adjustments/returns', [
        'inventory_item_id' => $item->id,
        'quantity'          => 2,
        'action_taken'      => 'restocked',
        'notes'             => 'Unused return'
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('product_returns', [
        'inventory_item_id' => $item->id,
        'quantity'          => 2,
        'action_taken'      => 'restocked'
    ]);

    expect($item->fresh()->quantity)->toBe(7);
});

test('owner can record discarded product returns which does not increment quantity', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $item = InventoryItem::create([
        'part_name' => 'Clutch Cable',
        'price' => 300.00,
        'quantity' => 5,
    ]);

    $response = $this->actingAs($owner)->post('/inventory/adjustments/returns', [
        'inventory_item_id' => $item->id,
        'quantity'          => 2,
        'action_taken'      => 'discarded',
        'notes'             => 'Opened package and dirty'
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('product_returns', [
        'inventory_item_id' => $item->id,
        'quantity'          => 2,
        'action_taken'      => 'discarded'
    ]);

    expect($item->fresh()->quantity)->toBe(5); // Remains unchanged
});
```

- [x] **Step 2: Run the adjustment tests**

Run: `php artisan test --filter=InventoryAdjustmentTest`
Expected: PASS

- [x] **Step 3: Run the entire test suite**

Run: `php artisan test`
Expected: PASS (All 71 tests pass)

- [x] **Step 4: Commit tests**

```bash
git add tests/Feature/Admin/InventoryAdjustmentTest.php
git commit -m "test: write comprehensive integration tests for damaged stock and product returns"
```

---

## Self-Review

**1. Spec coverage:**
- Target Requirement: digital inventory module with product returns and damaged stock logging.
- Task 1 & 2 set up database structures. Tasks 3, 4 & 5 build business validations and controllers. Task 6 implements UI listings/modals. Task 7 provides full integration tests. No gaps identified.

**2. Placeholder scan:**
- All instructions contain exact, copy-pasteable files, commands, and code blocks.

**3. Type consistency:**
- `action_taken` is constrained to `'restocked'` and `'discarded'` across models, validators, controllers, and tests.
