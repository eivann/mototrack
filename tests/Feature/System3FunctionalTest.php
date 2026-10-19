<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\PreOrder;
use App\Models\Repair;
use App\Models\Sale;
use App\Models\User;
use App\Notifications\StatusUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class System3FunctionalTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $customerUser;
    protected Customer $customer;
    protected InventoryItem $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['role' => 'owner']);
        $this->customerUser = User::factory()->create(['role' => 'customer']);
        $this->customer = Customer::create([
            'user_id' => $this->customerUser->id,
            'name' => $this->customerUser->name,
            'email' => $this->customerUser->email,
        ]);

        $this->item = InventoryItem::create([
            'part_name' => 'High Performance Brake Pad',
            'sku' => 'HP-BP-001',
            'brand' => 'Brembo',
            'model' => 'Yamaha R6',
            'price' => 750.00,
            'quantity' => 10,
        ]);
    }

    // ── STEP 2 & 3: NOTIFICATION FOLLOW & MARK-AS-READ ────────────────────────

    public function test_follow_action_marks_notification_as_read_and_redirects(): void
    {
        $this->customerUser->notify(new StatusUpdated(
            'Order Update',
            'Your order has been processed.',
            route('customer.dashboard', ['tab' => 'purchases']) . '#purchase-TRX-123'
        ));

        $notification = $this->customerUser->notifications()->first();
        $this->assertTrue($notification->unread());
        $this->assertEquals(1, $this->customerUser->fresh()->unreadNotifications()->count());

        $response = $this->actingAs($this->customerUser)
            ->post(route('notifications.follow', $notification->id));

        $response->assertRedirect(route('customer.dashboard', ['tab' => 'purchases']) . '#purchase-TRX-123');
        $this->assertFalse($notification->fresh()->unread());
        $this->assertEquals(0, $this->customerUser->fresh()->unreadNotifications()->count());
    }

    public function test_user_cannot_follow_or_read_another_users_notification(): void
    {
        $otherUser = User::factory()->create(['role' => 'customer']);
        $this->customerUser->notify(new StatusUpdated(
            'Private Notice',
            'Confidential customer notice.',
            route('customer.dashboard')
        ));

        $notification = $this->customerUser->notifications()->first();

        // Attempt by other user must return 404
        $response = $this->actingAs($otherUser)
            ->post(route('notifications.follow', $notification->id));

        $response->assertNotFound();
        $this->assertTrue($notification->fresh()->unread());
    }

    public function test_open_redirect_is_prevented_on_follow_action(): void
    {
        // Notification with malicious external URL
        $this->customerUser->notify(new StatusUpdated(
            'Phishing Link',
            'Click here to claim a prize.',
            'https://evil-phishing-site.example.com/steal-login'
        ));

        $notification = $this->customerUser->notifications()->first();

        $response = $this->actingAs($this->customerUser)
            ->post(route('notifications.follow', $notification->id));

        // Must redirect to safe fallback, NOT evil site
        $response->assertRedirect(route('customer.dashboard'));
        $this->assertFalse($notification->fresh()->unread());

        // Dangerous javascript: scheme
        $this->customerUser->notify(new StatusUpdated(
            'XSS Link',
            'Dangerous protocol.',
            'javascript:alert(1)'
        ));
        $xssNotif = $this->customerUser->notifications()->latest()->first();

        $response2 = $this->actingAs($this->customerUser)
            ->post(route('notifications.follow', $xssNotif->id));

        $response2->assertRedirect(route('customer.dashboard'));
    }

    public function test_rendering_notification_index_does_not_clear_unread_count(): void
    {
        $this->customerUser->notify(new StatusUpdated(
            'Notice 1',
            'First update.',
            '/customer/dashboard'
        ));
        $this->customerUser->notify(new StatusUpdated(
            'Notice 2',
            'Second update.',
            '/customer/dashboard'
        ));

        $this->assertEquals(2, $this->customerUser->fresh()->unreadNotifications()->count());

        $this->actingAs($this->customerUser)
            ->get(route('notifications.index'))
            ->assertOk();

        // Count must still be 2
        $this->assertEquals(2, $this->customerUser->fresh()->unreadNotifications()->count());
    }

    // ── STEP 4: RECEIPT ACCURACY (ONLINE VIEW) ───────────────────────────────

    public function test_unpaid_reservation_online_receipt_displays_acknowledgement(): void
    {
        $trx = 'RES-TEST-UNPAID';
        Sale::create([
            'inventory_item_id' => $this->item->id,
            'customer_id' => $this->customer->id,
            'quantity' => 2,
            'price' => 750.00,
            'total_price' => 1500.00,
            'amount_tendered' => null,
            'change' => null,
            'status' => OrderStatus::SALE_PENDING,
            'transaction_reference' => $trx,
            'reserved_at' => now(),
            'expires_at' => now()->addHours(24),
        ]);

        $response = $this->actingAs($this->customerUser)
            ->get(route('checkout.receipt', ['trx' => $trx]));

        $response->assertOk();
        $response->assertSee('Reservation Acknowledgement — No Payment Collected');
        $response->assertSee('Pending Counter Payment');
        $response->assertDontSee('Cash Sales Receipt — Paid in Cash');
    }

    public function test_completed_paid_sale_online_receipt_displays_cash_sales_receipt(): void
    {
        $trx = 'POS-TEST-PAID';
        Sale::create([
            'inventory_item_id' => $this->item->id,
            'customer_id' => $this->customer->id,
            'quantity' => 1,
            'price' => 750.00,
            'total_price' => 750.00,
            'amount_tendered' => 1000.00,
            'change' => 250.00,
            'status' => OrderStatus::SALE_COMPLETED,
            'transaction_reference' => $trx,
            'payment_confirmed_at' => now(),
            'picked_up_at' => now(),
        ]);

        $response = $this->actingAs($this->customerUser)
            ->get(route('checkout.receipt', ['trx' => $trx]));

        $response->assertOk();
        $response->assertSee('Cash Sales Receipt — Paid in Cash');
        $response->assertSee('Amount Tendered:');
        $response->assertSee('₱1,000.00');
        $response->assertSee('₱250.00');
        $response->assertDontSee('No Payment Collected');
    }

    public function test_cancelled_and_expired_reservations_are_never_mislabeled_as_paid(): void
    {
        $trxCancelled = 'RES-TEST-CANCELLED';
        Sale::create([
            'inventory_item_id' => $this->item->id,
            'customer_id' => $this->customer->id,
            'quantity' => 1,
            'price' => 750.00,
            'total_price' => 750.00,
            'amount_tendered' => null,
            'change' => null,
            'status' => OrderStatus::SALE_CANCELLED,
            'transaction_reference' => $trxCancelled,
            'payment_confirmed_at' => null,
        ]);

        $trxExpired = 'RES-TEST-EXPIRED';
        Sale::create([
            'inventory_item_id' => $this->item->id,
            'customer_id' => $this->customer->id,
            'quantity' => 1,
            'price' => 750.00,
            'total_price' => 750.00,
            'amount_tendered' => null,
            'change' => null,
            'status' => OrderStatus::SALE_EXPIRED,
            'transaction_reference' => $trxExpired,
            'payment_confirmed_at' => null,
        ]);

        // Online receipt for cancelled
        $responseCancel = $this->actingAs($this->customerUser)
            ->get(route('checkout.receipt', ['trx' => $trxCancelled]));
        $responseCancel->assertOk();
        $responseCancel->assertDontSee('Cash Sales Receipt — Paid in Cash');
        $responseCancel->assertSee('Reservation Acknowledgement — No Payment Collected');

        // Online receipt for expired
        $responseExpired = $this->actingAs($this->customerUser)
            ->get(route('checkout.receipt', ['trx' => $trxExpired]));
        $responseExpired->assertOk();
        $responseExpired->assertDontSee('Cash Sales Receipt — Paid in Cash');
        $responseExpired->assertSee('Reservation Acknowledgement — No Payment Collected');
    }

    // ── STEP 5: DOWNLOADED PDF RECEIPTS ──────────────────────────────────────

    public function test_unpaid_reservation_pdf_receipt_download_generates_valid_pdf(): void
    {
        $trx = 'RES-PDF-UNPAID';
        Sale::create([
            'inventory_item_id' => $this->item->id,
            'customer_id' => $this->customer->id,
            'quantity' => 1,
            'price' => 750.00,
            'total_price' => 750.00,
            'amount_tendered' => 0,
            'change' => 0,
            'status' => OrderStatus::SALE_PENDING,
            'transaction_reference' => $trx,
            'reserved_at' => now(),
        ]);

        $response = $this->actingAs($this->customerUser)
            ->get(route('checkout.receipt.download', ['transaction' => $trx]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_paid_sale_pdf_receipt_download_generates_valid_pdf(): void
    {
        $trx = 'POS-PDF-PAID';
        Sale::create([
            'inventory_item_id' => $this->item->id,
            'customer_id' => $this->customer->id,
            'quantity' => 2,
            'price' => 750.00,
            'total_price' => 1500.00,
            'amount_tendered' => 2000.00,
            'change' => 500.00,
            'status' => OrderStatus::SALE_COMPLETED,
            'payment_confirmed_at' => now(),
            'transaction_reference' => $trx,
        ]);

        $response = $this->actingAs($this->customerUser)
            ->get(route('checkout.receipt.download', ['transaction' => $trx]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    // ── STEP 6: CUSTOM PREORDER VALIDATION ────────────────────────────────────

    public function test_custom_preorder_requires_part_name_and_model(): void
    {
        $response = $this->actingAs($this->customerUser)
            ->post(route('preorder.store'), [
                'part_name' => '',
                'model' => '',
                'quantity' => 1,
            ]);

        $response->assertSessionHasErrors(['part_name', 'model']);
    }

    public function test_custom_preorder_rejects_whitespace_or_too_short_inputs(): void
    {
        $response = $this->actingAs($this->customerUser)
            ->post(route('preorder.store'), [
                'part_name' => '   ',
                'model' => ' ',
                'quantity' => 1,
            ]);

        $response->assertSessionHasErrors(['part_name', 'model']);

        $response2 = $this->actingAs($this->customerUser)
            ->post(route('preorder.store'), [
                'part_name' => 'ab', // < 3 characters
                'model' => 'a',   // < 2 characters
                'quantity' => 1,
            ]);

        $response2->assertSessionHasErrors(['part_name', 'model']);
    }

    public function test_custom_preorder_rejects_malformed_symbol_only_inputs(): void
    {
        $response = $this->actingAs($this->customerUser)
            ->post(route('preorder.store'), [
                'part_name' => '!@#$%^&*()',
                'model' => '???///',
                'quantity' => 1,
            ]);

        $response->assertSessionHasErrors(['part_name', 'model']);
    }

    public function test_custom_preorder_accepts_normal_historical_and_uncommon_models_and_preserves_biz01_pricing(): void
    {
        // 1. Normal model
        $response1 = $this->actingAs($this->customerUser)
            ->post(route('preorder.store'), [
                'part_name' => 'Drive Belt Standard',
                'model' => 'Honda Click 150i 2021',
                'quantity' => 1,
            ]);
        $response1->assertRedirect('/customer/dashboard');
        $this->assertDatabaseHas('pre_orders', [
            'part_name' => 'Drive Belt Standard',
            'model' => 'Honda Click 150i 2021',
            'price' => 0, // Confirms BIZ-01 zero-price remains preserved and not altered
        ]);

        // 2. Historical model
        $response2 = $this->actingAs($this->customerUser)
            ->post(route('preorder.store'), [
                'part_name' => 'Mikuni VM26 Carburetor Kit',
                'model' => 'Yamaha DT125 1984',
                'quantity' => 1,
            ]);
        $response2->assertRedirect('/customer/dashboard');
        $this->assertDatabaseHas('pre_orders', [
            'part_name' => 'Mikuni VM26 Carburetor Kit',
            'model' => 'Yamaha DT125 1984',
            'price' => 0,
        ]);

        // 3. Uncommon / imported model
        $response3 = $this->actingAs($this->customerUser)
            ->post(route('preorder.store'), [
                'part_name' => 'Polini Variator Kit',
                'model' => 'Vespa Sprint 150 ABS',
                'quantity' => 2,
            ]);
        $response3->assertRedirect('/customer/dashboard');
        $this->assertDatabaseHas('pre_orders', [
            'part_name' => 'Polini Variator Kit',
            'model' => 'Vespa Sprint 150 ABS',
            'quantity' => 2,
            'price' => 0,
        ]);
    }

    public function test_custom_preorder_rejects_invalid_quantities(): void
    {
        $response = $this->actingAs($this->customerUser)
            ->post(route('preorder.store'), [
                'part_name' => 'Exhaust Pipe',
                'model' => 'Honda Beat',
                'quantity' => 0,
            ]);

        $response->assertSessionHasErrors(['quantity']);

        $response2 = $this->actingAs($this->customerUser)
            ->post(route('preorder.store'), [
                'part_name' => 'Exhaust Pipe',
                'model' => 'Honda Beat',
                'quantity' => 101, // exceeds max:100
            ]);

        $response2->assertSessionHasErrors(['quantity']);
    }

    // ── ANCHOR VERIFICATION IN BLADE VIEWS ───────────────────────────────────

    public function test_dashboard_views_contain_verified_record_anchors(): void
    {
        $ref = 'ANCHOR-TRX-001';
        $sale = Sale::create([
            'inventory_item_id' => $this->item->id,
            'customer_id' => $this->customer->id,
            'quantity' => 1,
            'price' => 750.00,
            'total_price' => 750.00,
            'status' => OrderStatus::SALE_PENDING,
            'transaction_reference' => $ref,
            'reserved_at' => now(),
            'expires_at' => now()->addHours(24),
        ]);

        $repair = Repair::create([
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customerUser->name,
            'bike_model' => 'Kawasaki Ninja 400',
            'issue' => 'Brake fluid leak',
            'cost' => 1200.00,
            'status' => 'pending',
            'is_paid' => false,
        ]);

        // Customer dashboard view check
        $customerView = $this->actingAs($this->customerUser)
            ->get(route('customer.dashboard', ['tab' => 'repairs']))
            ->getContent();
        $this->assertStringContainsString('id="repair-' . $repair->id . '"', $customerView);

        $customerPurchasesView = $this->actingAs($this->customerUser)
            ->get(route('customer.dashboard', ['tab' => 'purchases']))
            ->getContent();
        $this->assertStringContainsString('id="purchase-' . $ref . '"', $customerPurchasesView);

        // Admin dashboard view check
        $adminView = $this->actingAs($this->owner)
            ->get(route('dashboard', ['tab' => 'reservations']))
            ->getContent();
        $this->assertStringContainsString('id="reservation-' . $ref . '"', $adminView);

        // Repairs index view check
        $repairsView = $this->actingAs($this->owner)
            ->get(route('repairs.index'))
            ->getContent();
        $this->assertStringContainsString('id="repair-' . $repair->id . '"', $repairsView);
    }
}
