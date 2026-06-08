# Online Checkout Low-Stock Alert Integration Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Integrate low-stock SMS alerts for online web shop checkouts inside `CheckoutService` when stock counts fall below threshold limits.

**Architecture:** Queue a low-stock check job (`CheckLowStockAlertJob`) asynchronously for each item purchased online via the web shop checkout flow immediately after decrementing inventory levels. This mirrors the existing POS walk-in transaction check pattern and avoids blocking web request cycles.

**Tech Stack:** Laravel 13.x, Pest PHP, PHPUnit

---

### Task 1: Add Failing Test for Online Checkout Low-Stock Alerts

**Files:**
- Modify: `tests/Feature/Shop/CheckoutGatewayTest.php`

- [ ] **Step 1: Add imports and the failing test at the end of the test file**

Open `tests/Feature/Shop/CheckoutGatewayTest.php` and append the test checking that `CheckLowStockAlertJob` is queued.

In `tests/Feature/Shop/CheckoutGatewayTest.php` (at the top of the file, around line 11):
```php
use Illuminate\Support\Facades\Queue;
use App\Jobs\CheckLowStockAlertJob;
```

At the end of `tests/Feature/Shop/CheckoutGatewayTest.php`:
```php
test('checkout dispatches CheckLowStockAlertJob when item quantity drops below threshold', function () {
    Queue::fake();

    $user = User::factory()->create();
    $item = InventoryItem::create([
        'part_name' => 'Low Stock Tire',
        'price' => 1000.00,
        'quantity' => 5, // Starts at threshold
        'low_stock_threshold' => 5,
        'low_stock_notified' => false,
    ]);

    Session::put('cart', [
        $item->id => ['part_name' => 'Low Stock Tire', 'model' => 'Michelin', 'price' => 1000.00, 'quantity' => 1] // Decrements quantity to 4 (below threshold)
    ]);

    $this->mock(PaymentService::class, function (MockInterface $mock) {
        $mock->shouldReceive('createRcpayUrl')->andReturn('https://mock-rcpay.com/pay');
    });

    $response = $this->actingAs($user)->post('/checkout', [
        'payment_method' => 'rcpay',
        'bank_code' => 'QRPH'
    ]);

    $response->assertRedirect('https://mock-rcpay.com/pay');

    // Assert that the job was dispatched for this specific inventory item
    Queue::assertDispatched(CheckLowStockAlertJob::class, function ($job) use ($item) {
        return $job->inventoryItemId === $item->id;
    });
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter="checkout dispatches CheckLowStockAlertJob when item quantity drops below threshold"`
Expected: FAIL with `The expected [App\Jobs\CheckLowStockAlertJob] job was not pushed.`

- [ ] **Step 3: Commit the test additions**

Run:
```bash
git add tests/Feature/Shop/CheckoutGatewayTest.php
git commit -m "test: add test for online checkout low-stock alert dispatch"
```

---

### Task 2: Implement Job Dispatch in CheckoutService

**Files:**
- Modify: `app/Services/CheckoutService.php`

- [ ] **Step 1: Import CheckLowStockAlertJob and dispatch the job inside processTransaction**

In `app/Services/CheckoutService.php` (add import at the top of the file around line 8):
```php
use App\Jobs\CheckLowStockAlertJob;
```

In `app/Services/CheckoutService.php` (inside `processTransaction`'s second pass loop, around line 58):
```php
            // Pass 2: Generate sales records using the accurate grand total
            foreach ($cart as $id => $item) {
                $inventory = $lockedItems[$id];
                $total = $inventory->price * $item['quantity'];

                Sale::create([
                    'inventory_item_id' => $id,
                    'customer_id' => $customerId,
                    'quantity' => $item['quantity'],
                    'price' => $inventory->price,
                    'total_price' => $total,
                    'transaction_reference' => $transactionReference,
                    'amount_tendered' => $isFirstItem ? ($amountTenderedInput ?: $finalGrandTotal) : 0,
                    'change' => $isFirstItem ? (max(0, ($amountTenderedInput ?? 0) - $finalGrandTotal)) : 0,
                ]);

                $inventory->decrement('quantity', $item['quantity']);
                CheckLowStockAlertJob::dispatch($id);
                $isFirstItem = false;
            }
```

- [ ] **Step 2: Run test to verify it passes**

Run: `php artisan test --filter="checkout dispatches CheckLowStockAlertJob when item quantity drops below threshold"`
Expected: PASS

- [ ] **Step 3: Run the entire test suite to ensure zero regressions**

Run: `php artisan test`
Expected: PASS (All 65 tests pass)

- [ ] **Step 4: Commit implementation**

Run:
```bash
git add app/Services/CheckoutService.php
git commit -m "feat: dispatch CheckLowStockAlertJob during shop checkout"
```

---

## Self-Review

**1. Spec coverage:**
- Target requirement: Integrate low-stock SMS alerts for online web shop checkouts inside `CheckoutService`.
- Task 1 tests this requirement. Task 2 implements it. No gaps identified.

**2. Placeholder scan:**
- No "TBD", "TODO", or vague descriptions exist in code snippets or instructions. All references use exact files, paths, and fully-formed PHP blocks.

**3. Type consistency:**
- `CheckLowStockAlertJob::dispatch($id)` receives the integer `$id` (the inventory item ID key from the cart loop), matching the constructor definition of `CheckLowStockAlertJob` (`public int $inventoryItemId`).
