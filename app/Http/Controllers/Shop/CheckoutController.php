<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Concerns\HandlesExceptions;
use App\Http\Requests\Shop\CheckoutRequest;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use App\Notifications\StatusUpdated;
use App\Services\CartService;
use App\Services\CheckoutService;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    use HandlesExceptions;
    public function __construct(
        protected CartService $cartService,
        protected CheckoutService $checkoutService
    ) {}

    public function index()
    {
        $cart = $this->cartService->getCart();
        return view('shop.checkout', compact('cart'));
    }

    public function process(CheckoutRequest $request)
    {
        $cart = $this->cartService->getCart();

        if (empty($cart)) {
            return back()->with('error', 'Cart is empty!');
        }

        // H-6: Use ULID for collision-resistant, non-guessable transaction IDs
        $transactionReference = 'TRX-' . Str::ulid();

        // Find or create customer profile
        $customerId = null;
        if (auth()->check()) {
            $user = auth()->user();
            $customer = Customer::firstOrCreate(
                ['user_id' => $user->id],
                ['name' => $user->name, 'email' => $user->email]
            );
            $customerId = $customer->id;
        }

        $lock = \Illuminate\Support\Facades\Cache::lock('checkout_' . session()->getId(), 10);
        if (!$lock->get()) {
            return back()->with('error', 'Your previous checkout request is still processing. Please wait.');
        }

        // ── Step 1: Commit the order ──────────────────────────────────
        // The DB transaction is isolated from external payment API calls.
        // If this step fails, nothing was committed and the error message
        // is truthful — the cart genuinely was not charged.
        try {
            $grandTotal = $this->checkoutService->processTransaction(
                $cart,
                $transactionReference,
                $customerId,
                0 // zero tender for reservation
            );
        } catch (\Throwable $e) {
            $lock->release();
            report($e);
            $message = app()->hasDebugModeEnabled()
                ? $e->getMessage()
                : 'Checkout failed. Your cart has not been charged. Please try again.';
            return back()->with('error', $message);
        }

        $lock->release();

        // ── Order committed — safe to clear cart and notify ───────────
        $this->cartService->clear();
        session()->put('last_trx', $transactionReference);

        if (auth()->check()) {
            auth()->user()->notify(new StatusUpdated(
                'Order Placed',
                "Your order #{$transactionReference} has been placed successfully.",
                route('customer.dashboard', ['tab' => 'reservations']) . '#reservation-' . $transactionReference
            ));

            $owners = User::owners();
            Notification::send($owners, new StatusUpdated(
                'New Reservation Received',
                "Customer " . auth()->user()->name . " has placed a new reservation #{$transactionReference}.",
                route('dashboard', ['tab' => 'reservations']) . '#reservation-' . $transactionReference
            ));
        }

        // RD2-R2: GCash QR choice is recorded as a payment claim (awaiting payment) and the customer goes
        // straight to the QR / amount screen. The reservation itself is unchanged and is never marked paid.
        if ($request->wantsGcashQr() && $customerId !== null) {
            try {
                app(\App\Services\PaymentClaimService::class)->open(
                    \App\Models\PaymentClaim::TYPE_RESERVATION, $transactionReference, Customer::findOrFail($customerId)
                );

                return redirect()->route('payments.show', ['reservation', $transactionReference])
                    ->with('success', 'Reservation placed! Scan the QR below to pay, then submit your payment details.');
            } catch (\Throwable $e) {
                report($e);

                return redirect('/checkout/receipt')->with('warning', 'Reservation placed, but GCash payment could not be set up. You can still pay in cash at the counter.');
            }
        }

        return redirect('/checkout/receipt')->with('success', 'Reservation placed successfully! Please present this to the staff within 24 hours to pay and pick up.');
    }

    public function receipt(Request $request)
    {
        $trx = $request->query('trx') ?? session('last_trx');

        if (!$trx) {
            return redirect('/customer/dashboard')->with('error', 'No recent receipt found.');
        }

        // C-3 Security: Scope receipts to the authenticated user's customer
        // profile to prevent IDOR. Owners retain unrestricted access for
        // admin workflows.
        $scope = null;
        if (auth()->user()->role !== 'owner') {
            $customerId = auth()->user()->customer?->id;

            // F-3: A null customer_id means the user has no Customer profile
            // yet — they cannot own any receipts. Querying with NULL would
            // match admin POS sales (which also have customer_id = NULL).
            if ($customerId === null) {
                return redirect('/customer/dashboard')->with('error', 'Receipt not found.');
            }

            $scope = fn ($q) => $q->where('customer_id', $customerId);
        }

        // SYS4-06 D4: header-aware, scope applied to every lookup.
        $orders = Sale::transactionByKey($trx, $scope);

        if ($orders->isEmpty()) {
            return redirect('/customer/dashboard')->with('error', 'Receipt not found.');
        }

        return view('shop.receipt', compact('orders', 'trx'));
    }

    public function downloadReceipt(Request $request, $trx)
    {
        $scope = null;
        if (auth()->user()->role !== 'owner') {
            $customerId = auth()->user()->customer?->id;

            if ($customerId === null) {
                abort(404, 'Receipt not found.');
            }

            $scope = fn ($q) => $q->where('customer_id', $customerId);
        }

        $sales = Sale::transactionByKey($trx, $scope);

        if ($sales->isEmpty()) {
            abort(404, 'Receipt not found.');
        }

        $transactionId = $sales->first()->transaction_reference ?? 'SALE-' . $sales->first()->id;
        $date = $sales->first()->created_at->format('F d, Y h:i A');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('sales.pdf_receipt', compact('sales', 'transactionId', 'date'));
        
        return $pdf->download("Receipt_{$transactionId}.pdf");
    }
}
