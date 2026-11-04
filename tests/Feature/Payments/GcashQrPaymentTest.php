<?php

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\PaymentClaim;
use App\Models\PreOrder;
use App\Models\Sale;
use App\Models\User;
use App\Notifications\StatusUpdated;
use App\Services\PaymentClaimService;
use App\Services\ReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/**
 * RD2-R2: owner-verified GCash QR payments for product reservations and catalogue pre-orders.
 * Test data only: no gateway, no real payment, notifications and storage are faked.
 */

beforeEach(function () {
    Notification::fake();
    Storage::fake('local');
    Storage::fake('public');   // Phase 7: isolate the public disk too (the real one holds a .gitignore placeholder)
    config(['payments.gcash_qr.enabled' => true]);
});

function gqCustomer(): array
{
    $user = User::factory()->create(['role' => 'customer']);
    $customer = Customer::create(['user_id' => $user->id, 'name' => $user->name, 'email' => $user->email]);

    return [$user, $customer];
}

function gqOwner(): User
{
    $owner = User::factory()->create(['role' => 'owner']);
    \Illuminate\Support\Facades\Cache::forget('users_role_owner_ids'); // User::owners() caches ids for 5 minutes

    return $owner;
}

/** Reserve ₱1,120.00 of stock (2 × ₱560) through the real checkout. Returns [user, customer, trx, item]. */
function gqReserve(string $method = 'gcash_qr', ?array $who = null): array
{
    [$user, $customer] = $who ?? gqCustomer();
    static $n = 0;
    $n++;
    $item = InventoryItem::create(['part_name' => "Brake Pad $n", 'sku' => "GQ-$n", 'price' => 560, 'quantity' => 10]);

    test()->actingAs($user)->post("/cart/add/{$item->id}", [], ['Accept' => 'application/json']);
    test()->actingAs($user)->post("/cart/increase/{$item->id}", [], ['Accept' => 'application/json']);
    $response = test()->actingAs($user)->post('/checkout', ['payment_method' => $method]);
    $trx = Sale::where('customer_id', $customer->id)->latest('id')->value('transaction_reference');

    return [$user, $customer, $trx, $item, $response];
}

function gqProof(string $name = 'proof.png'): UploadedFile
{
    return UploadedFile::fake()->image($name, 120, 120);
}

function gqSubmit(User $user, string $type, string $key, array $data = [])
{
    return test()->actingAs($user)->post("/payments/$type/$key", $data + ['declared_amount' => '1120.00', 'reference_number' => '1234 567 890123']);
}

function gqClaim(string $type, string $key): ?PaymentClaim
{
    return PaymentClaim::where('payable_type', $type)->where('payable_key', $key)->latest('id')->first();
}

test('cash reservation keeps working with the gate closed or open and creates no payment claim', function () {
    foreach ([false, true] as $enabled) {
        config(['payments.gcash_qr.enabled' => $enabled]);
        foreach (['cash', 'on_pickup'] as $method) {
            [, $customer, $trx, $item, $response] = gqReserve($method);

            $response->assertRedirect('/checkout/receipt');
            expect(Sale::where('transaction_reference', $trx)->pluck('status')->unique()->all())->toBe(['pending'])
                ->and((float) Sale::where('transaction_reference', $trx)->sum('total_price'))->toBe(1120.0)
                ->and($item->fresh()->quantity)->toBe(8)
                ->and(PaymentClaim::count())->toBe(0);
        }
    }
});

test('with the gate closed the checkout page is the legacy cash page, QR routes are hidden and gcash is refused', function () {
    config(['payments.gcash_qr.enabled' => false]);
    [$user] = gqCustomer();
    $item = InventoryItem::create(['part_name' => 'Chain', 'sku' => 'GQ-C', 'price' => 100, 'quantity' => 5]);
    $this->actingAs($user)->post("/cart/add/{$item->id}", [], ['Accept' => 'application/json']);

    $page = $this->actingAs($user)->get('/checkout')->assertOk()->getContent();
    expect($page)->toContain('name="payment_method" value="on_pickup"')->and($page)->not->toContain('GCash');

    $this->actingAs($user)->post('/checkout', ['payment_method' => 'gcash_qr'])->assertSessionHasErrors('payment_method');
    expect(Sale::count())->toBe(0)->and(PaymentClaim::count())->toBe(0);
    $this->actingAs($user)->get('/payments/gcash-qr')->assertNotFound();
    $this->actingAs($user)->get('/payments/reservation/TRX-X')->assertNotFound();
});

test('gcash reservation payment page shows the QR, the amount due, and never claims it is paid', function () {
    [$user, , $trx] = gqReserve('gcash_qr');

    $page = $this->actingAs($user)->get(route('payments.show', ['reservation', $trx]))->assertOk()->getContent();

    expect($page)->toContain('₱1,120.00')
        ->and($page)->toContain('src="'.route('payments.qr').'"')
        ->and($page)->toContain('Submit payment for verification')
        ->and($page)->not->toContain('Payment verified');
});

test('the QR is served unchanged to signed-in users only', function () {
    [$user] = gqCustomer();

    $this->get('/payments/gcash-qr')->assertRedirect('/login');
    $response = $this->actingAs($user)->get('/payments/gcash-qr')->assertOk();

    expect($response->headers->get('Content-Type'))->toBe('image/png')
        ->and(hash_file('sha256', $response->baseResponse->getFile()->getPathname()))->toBe(hash_file('sha256', resource_path('payments/gcash-qr.png')))
        ->and(hash_file('sha256', resource_path('payments/gcash-qr.png')))->toBe('501be743a6f2adad12a79104f5706632b6afe2c97ef8eb6d720736215c43069d');
});

test('gcash is available for catalogue pre-orders using the server-side total', function () {
    [$user, $customer] = gqCustomer();
    $item = InventoryItem::create(['part_name' => 'Out Of Stock Fork', 'sku' => 'GQ-PO', 'price' => 560, 'quantity' => 0]);

    $response = $this->actingAs($user)->post('/preorder/store', ['inventory_item_id' => $item->id, 'quantity' => 2, 'payment_method' => 'gcash_qr']);

    $preorder = PreOrder::where('customer_id', $customer->id)->firstOrFail();
    $response->assertRedirect(route('payments.show', ['preorder', $preorder->id]));
    $claim = gqClaim('preorder', (string) $preorder->id);
    expect($claim->status)->toBe('awaiting_payment')->and((float) $claim->expected_amount)->toBe(1120.0);

    $this->actingAs($user)->get(route('payments.show', ['preorder', $preorder->id]))->assertOk()->assertSee('₱1,120.00');
    // The pre-order form offers the choice.
    $this->actingAs($user)->get(route('preorder.form', $item->id))->assertOk()->assertSee('GCash QR');
});

test('custom-parts requests never get QR payment', function () {
    [$user, $customer] = gqCustomer();

    $this->actingAs($user)->post('/preorder/store', ['part_name' => 'Custom Exhaust', 'model' => 'Universal', 'quantity' => 1, 'payment_method' => 'gcash_qr'])
        ->assertSessionHasErrors('payment_method');
    expect(PreOrder::count())->toBe(0)->and(PaymentClaim::count())->toBe(0);

    // Even a custom request created without a method cannot be paid by QR later.
    $custom = PreOrder::create(['customer_id' => $customer->id, 'customer_name' => 'X', 'part_name' => 'Custom', 'model' => 'U', 'quantity' => 1, 'price' => 0, 'total' => 0, 'down_payment' => 0, 'status' => 'pending', 'is_paid' => false]);
    gqSubmit($user, 'preorder', (string) $custom->id)->assertSessionHas('error');
    expect(PaymentClaim::count())->toBe(0);
    $this->flushSession(); // drop the flashed refusal message; the form itself must not offer QR
    $this->actingAs($user)->get(route('preorder.custom'))->assertOk()->assertDontSee('GCash');
});

test('submitting a reference and screenshot records a pending claim, privately, without marking anything paid', function () {
    [$user, $customer, $trx, $item] = gqReserve('gcash_qr');
    $owner = gqOwner();

    gqSubmit($user, 'reservation', $trx, ['proof' => gqProof()])->assertRedirect(route('payments.show', ['reservation', $trx]))->assertSessionHas('success');

    $claim = gqClaim('reservation', $trx);
    expect($claim->status)->toBe('pending_verification')
        ->and($claim->reference_number)->toBe('1234567890123')
        ->and((float) $claim->declared_amount)->toBe(1120.0)
        ->and($claim->submitted_at)->not->toBeNull()
        ->and($claim->proof_path)->toStartWith("payments/proofs/{$claim->id}/");
    Storage::disk('local')->assertExists($claim->proof_path);
    Storage::disk('public')->assertDirectoryEmpty('/');

    // Nothing about the reservation or stock moved.
    expect(Sale::where('transaction_reference', $trx)->pluck('status')->unique()->all())->toBe(['pending'])
        ->and(Sale::where('transaction_reference', $trx)->whereNotNull('payment_confirmed_at')->count())->toBe(0)
        ->and($item->fresh()->quantity)->toBe(8);

    Notification::assertSentTo($user, StatusUpdated::class, fn ($n) => $n->title === 'Payment submitted');
    Notification::assertSentTo($owner, StatusUpdated::class, fn ($n) => $n->title === 'GCash payment awaiting verification');

    // The reference is never serialised or written into the audit trail.
    expect(json_encode($claim))->not->toContain('1234567890123');
    expect(AuditLog::where('auditable_type', PaymentClaim::class)->get()->toJson())->not->toContain('1234567890123')->not->toContain('proofs');
});

test('a reference alone or a screenshot alone is enough, but neither is not', function () {
    [$user, , $trx] = gqReserve('gcash_qr');

    gqSubmit($user, 'reservation', $trx, ['reference_number' => ''])->assertSessionHas('error');
    expect(gqClaim('reservation', $trx)->status)->toBe('awaiting_payment');

    gqSubmit($user, 'reservation', $trx, ['reference_number' => '', 'proof' => gqProof()])->assertSessionHas('success');
    expect(gqClaim('reservation', $trx)->status)->toBe('pending_verification');
});

test('proofs must be real images within the size limit and references must look like references', function () {
    [$user, , $trx] = gqReserve('gcash_qr');

    gqSubmit($user, 'reservation', $trx, ['reference_number' => '', 'proof' => UploadedFile::fake()->createWithContent('proof.png', '<?php echo 1;')])->assertSessionHas('error');
    gqSubmit($user, 'reservation', $trx, ['reference_number' => '', 'proof' => UploadedFile::fake()->create('proof.pdf', 10, 'application/pdf')])->assertSessionHas('error');
    gqSubmit($user, 'reservation', $trx, ['reference_number' => '', 'proof' => UploadedFile::fake()->image('big.png')->size(5000)])->assertSessionHasErrors('proof');
    gqSubmit($user, 'reservation', $trx, ['reference_number' => 'no!'])->assertSessionHasErrors('reference_number');
    gqSubmit($user, 'reservation', $trx, ['declared_amount' => 'abc'])->assertSessionHasErrors('declared_amount');

    expect(gqClaim('reservation', $trx)->status)->toBe('awaiting_payment')->and(Storage::disk('local')->allFiles())->toBe([]);
});

test('the owner verifies only with the exact amount received, and verification never fulfils the order', function () {
    [$user, $customer, $trx, $item] = gqReserve('gcash_qr');
    $owner = gqOwner();
    gqSubmit($user, 'reservation', $trx, ['proof' => gqProof()]);
    $claim = gqClaim('reservation', $trx);

    $this->actingAs($owner)->post(route('owner.payments.approve', $claim), ['received_amount' => '1000.00'])->assertSessionHas('error');
    expect($claim->fresh()->status)->toBe('pending_verification');

    $this->actingAs($owner)->post(route('owner.payments.approve', $claim), ['received_amount' => '1120.00', 'note' => 'seen in account'])->assertSessionHas('success');

    $claim->refresh();
    expect($claim->status)->toBe('verified')
        ->and($claim->decided_by)->toBe($owner->id)->and($claim->decided_at)->not->toBeNull()
        ->and((float) $claim->verified_amount)->toBe(1120.0);
    // Payment state is separate: the reservation is still a pending reservation, stock untouched, no sale completed.
    expect(Sale::where('transaction_reference', $trx)->pluck('status')->unique()->all())->toBe(['pending'])
        ->and(Sale::where('transaction_reference', $trx)->whereNotNull('payment_confirmed_at')->count())->toBe(0)
        ->and($item->fresh()->quantity)->toBe(8);
    Notification::assertSentTo($user, StatusUpdated::class, fn ($n) => $n->title === 'Payment verified');
    expect(AuditLog::where('action', 'payment_claim.verified')->where('auditable_id', $claim->id)->exists())->toBeTrue();
});

test('approval is idempotent: repeating it changes and notifies nothing, and the opposite decision is refused', function () {
    [$user, , $trx] = gqReserve('gcash_qr');
    $owner = gqOwner();
    gqSubmit($user, 'reservation', $trx, ['proof' => gqProof()]);
    $claim = gqClaim('reservation', $trx);

    $this->actingAs($owner)->post(route('owner.payments.approve', $claim), ['received_amount' => '1120.00']);
    $decidedAt = $claim->fresh()->decided_at;
    $this->travel(5)->minutes();
    $this->actingAs($owner)->post(route('owner.payments.approve', $claim), ['received_amount' => '1120.00'])->assertSessionHas('success');

    expect($claim->fresh()->decided_at->equalTo($decidedAt))->toBeTrue();
    Notification::assertSentTo($user, StatusUpdated::class, fn ($n) => $n->title === 'Payment verified');
    expect(Notification::sent($user, StatusUpdated::class)->filter(fn ($n) => $n->title === 'Payment verified'))->toHaveCount(1);
    expect(AuditLog::where('action', 'payment_claim.verified')->count())->toBe(1);

    $this->actingAs($owner)->post(route('owner.payments.reject', $claim), ['note' => 'changed my mind'])->assertSessionHas('error');
    expect($claim->fresh()->status)->toBe('verified');
});

test('rejection needs a reason, notifies the customer, and lets them submit a corrected payment', function () {
    [$user, , $trx] = gqReserve('gcash_qr');
    $owner = gqOwner();
    gqSubmit($user, 'reservation', $trx);
    $claim = gqClaim('reservation', $trx);

    $this->actingAs($owner)->post(route('owner.payments.reject', $claim), ['note' => ''])->assertSessionHasErrors('note');
    $this->actingAs($owner)->post(route('owner.payments.reject', $claim), ['note' => 'No matching payment found'])->assertSessionHas('success');

    expect($claim->fresh()->status)->toBe('rejected')->and($claim->fresh()->decision_note)->toBe('No matching payment found');
    Notification::assertSentTo($user, StatusUpdated::class, fn ($n) => $n->title === 'Payment rejected' && str_contains($n->message, 'No matching payment found'));
    expect(Sale::where('transaction_reference', $trx)->pluck('status')->unique()->all())->toBe(['pending']);

    // Same reference is usable again (it was never paid), via a fresh claim.
    gqSubmit($user, 'reservation', $trx, ['reference_number' => '1234567890123'])->assertSessionHas('success');
    expect(PaymentClaim::where('payable_key', $trx)->count())->toBe(2)->and(gqClaim('reservation', $trx)->status)->toBe('pending_verification');
});

test('only the owner can verify, and only the right customer can see a payment or its proof', function () {
    [$user, , $trx] = gqReserve('gcash_qr');
    [$other] = gqCustomer();
    gqSubmit($user, 'reservation', $trx, ['proof' => gqProof()]);
    $claim = gqClaim('reservation', $trx);

    // Guests are sent to login; customers (including the claim's own) cannot decide.
    $this->app['auth']->forgetGuards();
    $this->post(route('owner.payments.approve', $claim), ['received_amount' => '1120'])->assertRedirect('/login');
    $this->actingAs($user)->post(route('owner.payments.approve', $claim), ['received_amount' => '1120'])->assertForbidden();
    $this->actingAs($other)->post(route('owner.payments.reject', $claim), ['note' => 'x'])->assertForbidden();
    $this->actingAs($user)->get(route('owner.payments.index'))->assertForbidden();
    expect($claim->fresh()->status)->toBe('pending_verification');

    // Proof: owner and submitting customer only.
    $this->actingAs($user)->get(route('payments.proof', $claim))->assertOk();
    $this->actingAs(gqOwner())->get(route('payments.proof', $claim))->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff');
    $this->actingAs($other)->get(route('payments.proof', $claim))->assertNotFound();
    $this->app['auth']->forgetGuards();
    $this->get(route('payments.proof', $claim))->assertRedirect('/login');

    // Another customer can neither open nor submit against someone else's reservation.
    $this->actingAs($other)->get(route('payments.show', ['reservation', $trx]))->assertNotFound();
    gqSubmit($other, 'reservation', $trx)->assertSessionHas('error');
    expect(PaymentClaim::count())->toBe(1);
});

test('the owner verification page lists the claim with amount, reference, proof link and actions', function () {
    [$user, , $trx] = gqReserve('gcash_qr');
    gqSubmit($user, 'reservation', $trx, ['proof' => gqProof()]);

    $page = $this->actingAs(gqOwner())->get(route('owner.payments.index'))->assertOk()->getContent();

    expect($page)->toContain($trx)->toContain('₱1,120.00')->toContain('1234567890123')
        ->toContain('View proof')->toContain('Verify payment')->toContain('Reject payment');
});

test('a payment reference or screenshot cannot be reused on another order, however it is typed', function () {
    [$userA, , $trxA] = gqReserve('gcash_qr');
    [$userB, , $trxB] = gqReserve('gcash_qr');
    $proof = gqProof('same.png');
    gqSubmit($userA, 'reservation', $trxA, ['reference_number' => 'ab-12 34 5678', 'proof' => $proof])->assertSessionHas('success');

    gqSubmit($userB, 'reservation', $trxB, ['reference_number' => 'AB12345678'])->assertSessionHas('error');
    gqSubmit($userB, 'reservation', $trxB, ['reference_number' => '', 'proof' => UploadedFile::fake()->createWithContent('copy.png', file_get_contents($proof->getRealPath()))])->assertSessionHas('error');

    expect(gqClaim('reservation', $trxB)->status)->toBe('awaiting_payment')
        ->and(PaymentClaim::whereNotNull('reference_number')->count())->toBe(1);

    // One active claim per order: a second submission on the same reservation does not duplicate.
    gqSubmit($userA, 'reservation', $trxA, ['reference_number' => 'ZZ99999999'])->assertSessionHas('error');
    expect(PaymentClaim::where('payable_key', $trxA)->count())->toBe(1);
});

test('the database itself refuses two active claims for one order or one reference', function () {
    [, $customer] = gqCustomer();
    $base = ['payable_type' => 'reservation', 'customer_id' => $customer->id, 'expected_amount' => 10, 'status' => 'pending_verification'];
    PaymentClaim::create($base + ['payable_key' => 'A', 'active_payable_key' => 'reservation:A', 'active_reference_fingerprint' => str_repeat('a', 64)]);

    // each attempt runs in a savepoint so PostgreSQL's aborted-transaction state cannot mask the real error
    expect(fn () => \Illuminate\Support\Facades\DB::transaction(fn () => PaymentClaim::create($base + ['payable_key' => 'A', 'active_payable_key' => 'reservation:A'])))->toThrow(\Illuminate\Database\UniqueConstraintViolationException::class);
    expect(fn () => \Illuminate\Support\Facades\DB::transaction(fn () => PaymentClaim::create($base + ['payable_key' => 'B', 'active_payable_key' => 'reservation:B', 'active_reference_fingerprint' => str_repeat('a', 64)])))->toThrow(\Illuminate\Database\UniqueConstraintViolationException::class);
});

test('cancelling or expiring a reservation closes an unpaid claim and flags a paid one for owner review, with no refund or reopening', function () {
    $owner = gqOwner();

    // Awaiting payment (nothing submitted): simply closed.
    [, , $trx1] = gqReserve('gcash_qr');
    $sale = Sale::where('transaction_reference', $trx1)->first();
    expect(app(ReservationService::class)->releaseGroup($sale, 'Customer cancelled', 'cancelled'))->toBeTrue();
    expect(gqClaim('reservation', $trx1)->status)->toBe('cancelled')->and(gqClaim('reservation', $trx1)->active_payable_key)->toBeNull();

    // Pending verification: stays pending, flagged, owner notified, order stays cancelled.
    [$user2, , $trx2] = gqReserve('gcash_qr');
    gqSubmit($user2, 'reservation', $trx2, ['reference_number' => 'REF-EXPIRE-1']);
    $sale2 = Sale::where('transaction_reference', $trx2)->first();
    app(ReservationService::class)->releaseGroup($sale2, 'Automatically expired after 24 hours', 'expired');

    $claim = gqClaim('reservation', $trx2);
    expect($claim->status)->toBe('pending_verification')->and($claim->needs_owner_review)->toBeTrue()
        ->and($claim->review_reason)->toContain('expired')
        ->and(Sale::where('transaction_reference', $trx2)->pluck('status')->unique()->all())->toBe(['expired']);
    Notification::assertSentTo($owner, StatusUpdated::class, fn ($n) => $n->title === 'Payment needs review');

    // The owner can still record what really happened; the order is never reactivated.
    $this->actingAs($owner)->post(route('owner.payments.approve', $claim), ['received_amount' => '1120.00'])->assertSessionHas('success');
    expect($claim->fresh()->status)->toBe('verified')->and($claim->fresh()->needs_owner_review)->toBeTrue()
        ->and(Sale::where('transaction_reference', $trx2)->pluck('status')->unique()->all())->toBe(['expired']);

    // And nothing new can be paid against a closed reservation.
    gqSubmit($user2, 'reservation', $trx2, ['reference_number' => 'REF-LATE-2'])->assertSessionHas('error');
    $this->actingAs($user2)->get(route('payments.show', ['reservation', $trx2]))->assertOk()->assertDontSee('Submit payment for verification');
});

test('cancelling a pre-order with a payment on file flags it for review', function () {
    [$user, $customer] = gqCustomer();
    $item = InventoryItem::create(['part_name' => 'Fork Seal', 'sku' => 'GQ-PC', 'price' => 560, 'quantity' => 0]);
    $this->actingAs($user)->post('/preorder/store', ['inventory_item_id' => $item->id, 'quantity' => 2, 'payment_method' => 'gcash_qr']);
    $preorder = PreOrder::where('customer_id', $customer->id)->first();
    gqSubmit($user, 'preorder', (string) $preorder->id, ['reference_number' => 'PRE-REF-0001'])->assertSessionHas('success');

    $this->actingAs($user)->post("/customer/preorders/{$preorder->id}/cancel", ['reason' => 'changed mind']);

    $claim = gqClaim('preorder', (string) $preorder->id);
    expect($preorder->fresh()->status)->toBe('cancelled')->and($claim->needs_owner_review)->toBeTrue()->and($claim->status)->toBe('pending_verification');
});

test('a gcash reservation still produces the same VAT-inclusive receipt and totals', function () {
    [$user, , $trx] = gqReserve('gcash_qr');
    gqSubmit($user, 'reservation', $trx);

    $this->actingAs($user)->get('/checkout/receipt?trx='.$trx)->assertOk()
        ->assertSee('VAT (12%, included)')->assertSee('1,000.00')->assertSee('120.00')->assertSee('1,120.00');
});

test('payment helpers: reference normalisation and the expected amount comes only from the server', function () {
    expect(PaymentClaimService::normaliseReference(' ab-12 34 '))->toBe('AB1234')->and(PaymentClaimService::normaliseReference('  - '))->toBeNull();

    [$user, , $trx] = gqReserve('gcash_qr');
    // A forged amount in the request cannot change what is due.
    gqSubmit($user, 'reservation', $trx, ['declared_amount' => '1.00', 'expected_amount' => '1.00'])->assertSessionHas('success');
    expect((float) gqClaim('reservation', $trx)->expected_amount)->toBe(1120.0);
});

test('public landing and FAQ mention GCash QR only once the gate is open', function () {
    config(['payments.gcash_qr.enabled' => false]);
    $this->get('/')->assertOk()->assertDontSee('GCash QR');
    $this->get('/faq')->assertOk()->assertDontSee('GCash');

    config(['payments.gcash_qr.enabled' => true]);
    $this->get('/')->assertOk()->assertSee('GCash QR')->assertSee('not custom-parts requests');
    $this->get('/faq')->assertOk()->assertSee('marked paid only after that check');
});

test('terms explain QR payment accurately only while the gate is open, with no refund promise', function () {
    [$user] = gqCustomer();
    config(['payments.gcash_qr.enabled' => false]);
    $this->actingAs($user)->get('/preorder-parts')->assertOk();
    $closed = view('payments.partials.qr-terms')->render();
    expect($closed)->not->toContain('GCash');

    config(['payments.gcash_qr.enabled' => true]);
    $open = view('payments.partials.qr-terms')->render();
    expect($open)->toContain('not confirmation of payment')->toContain('actually received')
        ->toContain('not</em> reactivated')->toContain('not automatically refunded')->toContain('custom-parts')
        ->not->toContain('will be refunded')->not->toContain('forfeit');

    // The rendered checkout and pre-order pages carry the section while open, and not while closed.
    $item = InventoryItem::create(['part_name' => 'Terms Part', 'sku' => 'GQ-TERMS', 'price' => 100, 'quantity' => 5]);
    $this->actingAs($user)->post("/cart/add/{$item->id}", [], ['Accept' => 'application/json']);
    $this->actingAs($user)->get('/checkout')->assertOk()->assertSee('Payment by GCash QR')->assertSee('not confirmation of payment');
    $oos = InventoryItem::create(['part_name' => 'Terms OOS', 'sku' => 'GQ-TERMS-0', 'price' => 100, 'quantity' => 0]);
    $this->actingAs($user)->get("/preorder/{$oos->id}")->assertOk()->assertSee('Payment by GCash QR');

    config(['payments.gcash_qr.enabled' => false]);
    $this->actingAs($user)->get('/checkout')->assertOk()->assertDontSee('Payment by GCash QR');
    $this->actingAs($user)->get("/preorder/{$oos->id}")->assertOk()->assertDontSee('Payment by GCash QR');
});
