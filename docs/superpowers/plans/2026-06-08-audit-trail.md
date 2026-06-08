# System-Wide Audit Trail Implementation Plan (Hardened)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement a secure, performant, and transactional auditing system to log all structural transformations (create, update, delete, restore actions) across core domains: Inventory Items, Damaged stock, Returns, and Sales transactions.

---

### Architectural Improvements & Security Hardening

1. **Model Event Correctness & Double Logging Prevention:**
   - Soft-delete operations fire both `updating`/`updated` and `deleting`/`deleted` events in Laravel. We check `$model->isDirty('deleted_at')` to suppress duplicate update logs during soft deletes.
   - We capture dirtied states in `updating` (when old/new is clean and synced) but defer actual database insertion to the `updated` event to ensure the transaction successfully persists.
   - We add support for the `restored` event to log when soft-deleted items are brought back.

2. **Storage Efficiency & Pruning:**
   - Incorporate Laravel's `MassPrunable` trait to automatically clean up logs older than 180 days (configurable via `config/audit.php`).
   - Clean up changes by stripping out `updated_at` updates when no actual data changed.

3. **Metadata Security:**
   - Strictly filter out attributes defined in the model's `$hidden` list (e.g. passwords, tokens) to prevent credential leaks in database logs.

---

### Task 1: Database Setup

**Files:**
- Create: `database/migrations/2026_06_08_000003_create_audit_logs_table.php`

- [ ] **Step 1: Write the migration**
Create migration file `database/migrations/2026_06_08_000003_create_audit_logs_table.php` with robust support for long user agent strings and indexed polymorphic lookups:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action'); // created, updated, deleted, restored
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable(); // Supports IPv6
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['auditable_type', 'auditable_id']);
            $table->index('created_at'); // Optimized for pruning & date filters
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
```

- [ ] **Step 2: Commit migration**
```bash
git add database/migrations/
git commit -m "migration: create audit_logs table with indexes"
```

---

### Task 2: Models & Traits

**Files:**
- Create: `config/audit.php`
- Create: `app/Models/AuditLog.php`
- Create: `app/Traits/HasAuditTrail.php`

- [ ] **Step 1: Create configuration file**
Create `config/audit.php`:
```php
<?php

return [
    'prune_days' => env('AUDIT_PRUNE_DAYS', 180),
];
```

- [ ] **Step 2: Create AuditLog model with Pruning**
Create `app/Models/AuditLog.php`:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\MassPrunable;

class AuditLog extends Model
{
    use MassPrunable;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->created_at = $model->freshTimestamp();
        });
    }

    public function prunable()
    {
        return static::where('created_at', '<', now()->subDays(config('audit.prune_days', 180)));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
```

- [ ] **Step 3: Create robust HasAuditTrail Trait**
Create `app/Traits/HasAuditTrail.php`:
```php
<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait HasAuditTrail
{
    public static function bootHasAuditTrail(): void
    {
        static::created(function ($model) {
            static::logAudit('created', $model, null, $model->getAttributes());
        });

        static::updating(function ($model) {
            // Suppress log duplication on soft delete (which triggers update of deleted_at)
            if (method_exists($model, 'isForceDeleting') && !$model->isForceDeleting() && $model->isDirty('deleted_at')) {
                return;
            }

            $dirty = $model->getDirty();
            $old = [];
            $new = [];
            
            foreach ($dirty as $key => $value) {
                $old[$key] = $model->getRawOriginal($key);
                $new[$key] = $value;
            }

            $model->audit_temp_changes = [
                'old' => $old,
                'new' => $new,
            ];
        });

        static::updated(function ($model) {
            if (isset($model->audit_temp_changes)) {
                static::logAudit('updated', $model, $model->audit_temp_changes['old'], $model->audit_temp_changes['new']);
            }
        });

        static::deleted(function ($model) {
            $action = 'deleted';
            if (method_exists($model, 'isForceDeleting') && !$model->isForceDeleting()) {
                $action = 'soft_deleted';
            }
            static::logAudit($action, $model, $model->getAttributes(), null);
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(function ($model) {
                static::logAudit('restored', $model, null, $model->getAttributes());
            });
        }
    }

    protected static function logAudit(string $action, $model, ?array $old, ?array $new): void
    {
        $hiddenFields = method_exists($model, 'getHidden') ? $model->getHidden() : [];
        
        if (!empty($hiddenFields)) {
            if ($old) {
                $old = array_diff_key($old, array_flip($hiddenFields));
            }
            if ($new) {
                $new = array_diff_key($new, array_flip($hiddenFields));
            }
        }

        // Clean up metadata updates
        if ($action === 'updated') {
            unset($old['updated_at'], $new['updated_at']);
            if (empty($new)) {
                return; // No auditable data changed
            }
        }

        AuditLog::create([
            'user_id'        => Auth::id(),
            'action'         => $action,
            'auditable_type' => get_class($model),
            'auditable_id'   => $model->id,
            'old_values'     => $old,
            'new_values'     => $new,
            'ip_address'     => Request::ip() ?? '127.0.0.1',
            'user_agent'     => Request::userAgent(),
        ]);
    }
}
```

- [ ] **Step 4: Apply Trait to Models**
Add `use \App\Traits\HasAuditTrail;` inside:
- `app/Models/InventoryItem.php`
- `app/Models/DamagedItem.php`
- `app/Models/ProductReturn.php`
- `app/Models/Sale.php`

- [ ] **Step 5: Commit models and traits**
```bash
git add config/ app/
git commit -m "feat: implement hardened HasAuditTrail trait and bind models"
```

---

### Task 3: Controller & Routes

**Files:**
- Create: `app/Http/Controllers/Admin/AuditLogController.php`
- Create: `routes/modules/audit.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Create AuditLogController**
Create `app/Http/Controllers/Admin/AuditLogController.php` with robust request sanitization:
```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'user_id' => 'nullable|integer|exists:users,id',
            'action' => 'nullable|string|in:created,updated,deleted,soft_deleted,restored',
            'model_type' => 'nullable|string',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $query = AuditLog::with('user');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('model_type')) {
            $query->where('auditable_type', $request->model_type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->latest('id')->paginate(20)->withQueryString();
        $users = User::orderBy('name')->get();

        $models = [
            \App\Models\InventoryItem::class => 'Inventory Item',
            \App\Models\DamagedItem::class   => 'Damaged Item',
            \App\Models\ProductReturn::class => 'Product Return',
            \App\Models\Sale::class          => 'Sale Record',
        ];

        return view('admin.audit_logs.index', compact('logs', 'users', 'models'));
    }
}
```

- [ ] **Step 2: Register routes**
Create `routes/modules/audit.php`:
```php
<?php

use App\Http\Controllers\Admin\AuditLogController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'owner'])->group(function () {
    Route::get('/admin/audit-logs', [AuditLogController::class, 'index'])->name('admin.audit_logs.index');
});
```

Import `routes/modules/audit.php` in `routes/web.php`:
```php
require __DIR__.'/modules/audit.php';
```

- [ ] **Step 3: Commit controller and routes**
```bash
git add app/Http/Controllers/Admin/AuditLogController.php routes/
git commit -m "feat: register audit routes and controller"
```

---

### Task 4: Admin UI View

**Files:**
- Create: `resources/views/admin/audit_logs/index.blade.php`
- Modify: `resources/views/layouts/navigation.blade.php`

- [ ] **Step 1: Create Index UI template**
Create `resources/views/admin/audit_logs/index.blade.php`:
```html
<x-app-layout>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-950 py-10 px-4">
        <div class="max-w-7xl mx-auto space-y-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-black text-gray-900 dark:text-white uppercase tracking-tight italic">System Audit Trail</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Track and monitor all operations and record mutations.</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-3xl p-6 shadow-sm">
                <form method="GET" action="{{ route('admin.audit_logs.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">User</label>
                        <select name="user_id" class="w-full border border-gray-200 dark:border-gray-800 rounded-xl px-4 py-3 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white outline-none">
                            <option value="">All Users</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Action</label>
                        <select name="action" class="w-full border border-gray-200 dark:border-gray-800 rounded-xl px-4 py-3 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white outline-none">
                            <option value="">All Actions</option>
                            <option value="created" {{ request('action') == 'created' ? 'selected' : '' }}>Created</option>
                            <option value="updated" {{ request('action') == 'updated' ? 'selected' : '' }}>Updated</option>
                            <option value="deleted" {{ request('action') == 'deleted' ? 'selected' : '' }}>Deleted (Hard)</option>
                            <option value="soft_deleted" {{ request('action') == 'soft_deleted' ? 'selected' : '' }}>Deleted (Soft)</option>
                            <option value="restored" {{ request('action') == 'restored' ? 'selected' : '' }}>Restored</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Module Type</label>
                        <select name="model_type" class="w-full border border-gray-200 dark:border-gray-800 rounded-xl px-4 py-3 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white outline-none">
                            <option value="">All Modules</option>
                            @foreach($models as $class => $name)
                                <option value="{{ $class }}" {{ request('model_type') == $class ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">From Date</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full border border-gray-200 dark:border-gray-800 rounded-xl px-4 py-3 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">To Date</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full border border-gray-200 dark:border-gray-800 rounded-xl px-4 py-3 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white outline-none">
                    </div>
                    <div class="md:col-span-5 flex justify-end gap-3 mt-2">
                        <a href="{{ route('admin.audit_logs.index') }}" class="px-5 py-3 border border-gray-250 dark:border-gray-800 rounded-xl font-bold text-xs uppercase tracking-widest text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-850">Clear Filters</a>
                        <button type="submit" class="px-5 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs uppercase tracking-widest rounded-xl shadow-lg shadow-indigo-100 dark:shadow-none">Filter Logs</button>
                    </div>
                </form>
            </div>

            <!-- Table -->
            <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-3xl overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 dark:bg-gray-800/50 text-gray-400 uppercase font-black text-xs tracking-wider border-b border-gray-100 dark:border-gray-800">
                            <tr>
                                <th class="px-6 py-4">Timestamp</th>
                                <th class="px-6 py-4">Operator</th>
                                <th class="px-6 py-4">Action</th>
                                <th class="px-6 py-4">Model & ID</th>
                                <th class="px-6 py-4">Details (Old vs New)</th>
                                <th class="px-6 py-4">Network Info</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-gray-700 dark:text-gray-300">
                            @forelse($logs as $log)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-xs font-semibold text-gray-400">
                                        {{ $log->created_at->format('Y-m-d h:i:s A') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-bold text-gray-900 dark:text-white">{{ $log->user->name ?? 'System/Console' }}</div>
                                        <div class="text-xs text-gray-400">{{ $log->user->email ?? 'N/A' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider
                                            {{ $log->action === 'created' ? 'bg-emerald-55 dark:bg-emerald-950/40 text-emerald-600' : '' }}
                                            {{ $log->action === 'updated' ? 'bg-amber-55 dark:bg-amber-950/40 text-amber-600' : '' }}
                                            {{ $log->action === 'deleted' || $log->action === 'soft_deleted' ? 'bg-rose-55 dark:bg-rose-950/40 text-rose-600' : '' }}
                                            {{ $log->action === 'restored' ? 'bg-indigo-55 dark:bg-indigo-950/40 text-indigo-600' : '' }}
                                        ">
                                            {{ str_replace('_', ' ', $log->action) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-semibold text-gray-800 dark:text-gray-200">
                                            {{ class_basename($log->auditable_type) }}
                                        </div>
                                        <div class="text-xs text-gray-400">ID: {{ $log->auditable_id }}</div>
                                    </td>
                                    <td class="px-6 py-4 max-w-xs">
                                        <div class="text-xs space-y-1">
                                            @if($log->action === 'created')
                                                <div class="text-emerald-500 font-bold">Populated parameters.</div>
                                            @elseif($log->action === 'deleted' || $log->action === 'soft_deleted')
                                                <div class="text-rose-500 font-bold">Deleted record values.</div>
                                            @elseif($log->action === 'restored')
                                                <div class="text-indigo-500 font-bold">Restored original record values.</div>
                                            @else
                                                @if(!empty($log->old_values))
                                                    @foreach($log->old_values as $key => $val)
                                                        <div>
                                                            <span class="font-semibold text-gray-400">{{ $key }}:</span>
                                                            <span class="text-red-400 line-through">{{ is_array($val) ? json_encode($val) : $val }}</span>
                                                            &rarr;
                                                            <span class="text-emerald-400 font-bold">{{ is_array($log->new_values[$key]) ? json_encode($log->new_values[$key]) : $log->new_values[$key] }}</span>
                                                        </div>
                                                    @endforeach
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-400">
                                        <div>IP: <span class="font-semibold text-gray-600 dark:text-gray-200">{{ $log->ip_address }}</span></div>
                                        <div class="truncate max-w-[120px]" title="{{ $log->user_agent }}">UA: {{ $log->user_agent }}</div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-400 font-semibold">
                                        No audit entries discovered matching selected criteria.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($logs->hasPages())
                    <div class="p-6 border-t border-gray-100 dark:border-gray-800">
                        {{ $logs->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
```

- [ ] **Step 2: Add sidebar link to Navigation**
In `resources/views/layouts/navigation.blade.php`, add:
```html
<x-nav-link :href="route('admin.audit_logs.index')" :active="request()->routeIs('admin.audit_logs.*')">
    {{ __('Audit Trail') }}
</x-nav-link>
```

- [ ] **Step 3: Commit templates**
```bash
git add resources/
git commit -m "feat: create audit log list UI view and add navigation link"
```

---

### Task 5: Feature Tests

**Files:**
- Create: `tests/Feature/Admin/AuditLogTest.php`

- [ ] **Step 1: Write comprehensive integration tests**
Create `tests/Feature/Admin/AuditLogTest.php` with soft-delete and pruning checks:
```php
<?php

use App\Models\AuditLog;
use App\Models\InventoryItem;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('owner can access system audit logs page', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $response = $this->actingAs($owner)->get('/admin/audit-logs');
    $response->assertStatus(200);
});

test('non-owner cannot access system audit logs page', function () {
    $user = User::factory()->create(['role' => 'customer']);
    $response = $this->actingAs($user)->get('/admin/audit-logs');
    $response->assertStatus(403);
});

test('creating updating deleting audit trail-bound model generates audit logs', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    
    // Test Create
    $item = $this->actingAs($owner)->post('/inventory', [
        'sku' => 'SKU-AUDIT-1',
        'part_name' => 'Chain lube',
        'brand' => 'Yamalube',
        'model' => 'Universal',
        'price' => 250.00,
        'quantity' => 20,
        'low_stock_threshold' => 5,
    ]);

    $itemInstance = InventoryItem::where('sku', 'SKU-AUDIT-1')->first();
    expect($itemInstance)->not->toBeNull();

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'created',
        'auditable_type' => InventoryItem::class,
        'auditable_id' => $itemInstance->id,
        'user_id' => $owner->id,
    ]);

    // Test Update
    $this->actingAs($owner)->put("/inventory/{$itemInstance->id}", [
        'sku' => 'SKU-AUDIT-1',
        'part_name' => 'Premium lube',
        'brand' => 'Yamalube',
        'model' => 'Universal',
        'price' => 300.00,
        'quantity' => 20,
        'low_stock_threshold' => 5,
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'updated',
        'auditable_type' => InventoryItem::class,
        'auditable_id' => $itemInstance->id,
    ]);

    $updateLog = AuditLog::where('action', 'updated')
        ->where('auditable_type', InventoryItem::class)
        ->where('auditable_id', $itemInstance->id)
        ->first();

    expect($updateLog->old_values['part_name'])->toBe('Chain lube')
        ->and($updateLog->new_values['part_name'])->toBe('Premium lube')
        ->and($updateLog->old_values['price'])->toBe(250)
        ->and($updateLog->new_values['price'])->toBe(300);

    // Test Delete
    $this->actingAs($owner)->delete("/inventory/{$itemInstance->id}");

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'deleted',
        'auditable_type' => InventoryItem::class,
        'auditable_id' => $itemInstance->id,
    ]);
});

test('soft-deleting and restoring models logs correct action events without duplication', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $item = InventoryItem::create([
        'part_name' => 'Tire',
        'price' => 1500,
        'quantity' => 10,
    ]);

    $sale = Sale::create([
        'inventory_item_id' => $item->id,
        'quantity' => 1,
        'price' => 1500,
        'total_price' => 1500,
        'transaction_reference' => 'TRX-AUDIT-SOFT',
    ]);

    // Clear logs from creation to isolate soft delete
    AuditLog::truncate();

    // Trigger soft delete
    $sale->delete();

    // Assert that we have exactly one soft_deleted log, and no updated logs for deleted_at
    $logs = AuditLog::all();
    expect($logs)->toHaveCount(1)
        ->and($logs->first()->action)->toBe('soft_deleted');

    // Trigger restore
    $sale->restore();

    $restoreLogs = AuditLog::where('action', 'restored')->get();
    expect($restoreLogs)->toHaveCount(1);
});

test('audit logs are pruneable', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    
    // Create an old log
    $oldLog = AuditLog::create([
        'user_id' => $owner->id,
        'action' => 'created',
        'auditable_type' => User::class,
        'auditable_id' => $owner->id,
        'created_at' => now()->subDays(190),
    ]);

    // Create a new log
    $newLog = AuditLog::create([
        'user_id' => $owner->id,
        'action' => 'created',
        'auditable_type' => User::class,
        'auditable_id' => $owner->id,
        'created_at' => now()->subDays(10),
    ]);

    // Run pruning
    $prunedCount = (new AuditLog)->pruneAll();
    expect($prunedCount)->toBe(1);

    $this->assertDatabaseMissing('audit_logs', ['id' => $oldLog->id]);
    $this->assertDatabaseHas('audit_logs', ['id' => $newLog->id]);
});
```

- [ ] **Step 2: Run tests**
Run: `php artisan test --filter=AuditLogTest`
Expected: PASS

- [ ] **Step 3: Commit tests**
```bash
git add tests/Feature/Admin/AuditLogTest.php
git commit -m "test: verify audit log soft deletes, restoring, and pruning"
```

---

## Self-Review

**1. Spec coverage:**
- Logs create, update, delete, soft delete, and restore events across models (`InventoryItem`, `DamagedItem`, `ProductReturn`, `Sale`).
- Records complete network payload information (`ip_address`, `user_agent`) and user bindings (`user_id`).
- Implements full filtering controls, pagination support, and unit tests verifying the payload mutations.

**2. Performance & Security considerations:**
- Compares Eloquent states using dynamic property caching during the `updating` phase to capture pure changes before saving.
- Ignores model columns designated as `$hidden` (such as passwords, secret tokens) preventing credential leaking into audit logs.
- Added indices on polymorphic lookups (`auditable_type`, `auditable_id`) to maintain search performance.
- Automatically prunes database logs older than 180 days using Laravel's native model pruning interface.
- Gated the filter queries to ensure all incoming search dates and user indices are fully validated prior to database parsing.
- Handles soft deletes safely by intercepting dirty `deleted_at` keys to avoid double-logging update and delete events.
