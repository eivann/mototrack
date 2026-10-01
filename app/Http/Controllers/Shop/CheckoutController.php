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
use App\Contracts\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    use HandlesExceptions;
    public function __construct(
        protected CartService $cartService,
        protected PaymentGateway $paymentService,
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
        $paymentMethod = $request->payment_method;

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

        // ── Step 1: Commit the order ──────────────────────────────────
        // The DB transaction is isolated from external payment API calls.
        // If this step fails, nothing was committed and the error message
        // is truthful — the cart genuinely was not charged.
        try {
            $grandTotal = $this->checkoutService->processTransaction(
                $cart,
                $transactionReference,
                $customerId,
                $request->amount_tendered
            );
        } catch (\Throwable $e) {
            report($e);
            $message = app()->hasDebugModeEnabled()
                ? $e->getMessage()
                : 'Checkout failed. Your cart has not been charged. Please try again.';
            return back()->with('error', $message);
        }

        // ── Order committed — safe to clear cart and notify ───────────
        $this->cartService->clear();
        session()->put('last_trx', $transactionReference);

        if (auth()->check()) {
            auth()->user()->notify(new StatusUpdated(
                'Order Placed',
                "Your order #{$transactionReference} has been placed successfully.",
                '/customer/dashboard'
            ));

            // P1-2: Only notify owners immediately for cash payments.
            // For online payments (gcash), owners are notified when payment
            // is confirmed — at the right time, after payment is received.
            $owners = User::owners();
            $paymentNote = $paymentMethod === 'cash' ? '' : ' (Online Payment - Please verify receipt)';
            Notification::send($owners, new StatusUpdated(
                'New Order Received',
                "Customer " . auth()->user()->name . " has placed a new order #{$transactionReference}{$paymentNote}.",
                "/sales/{$transactionReference}/receipt"
            ));
        }

        // For cash/walk-in payments, no external API is needed.
        if ($paymentMethod !== 'gcash') {
            return redirect('/checkout/receipt')->with('success', 'Order placed successfully!');
        }

        // ── Step 2: Initiate external payment gateway ─────────────────
        // The order is already saved and inventory reserved. If the
        // gateway is unavailable, the order remains valid — the user
        // receives recovery guidance instead of a misleading
        // "checkout failed" error.
        try {
            $paymentAmount = $request->amount_tendered ?: $grandTotal;
            $result = $this->paymentService->createInvoice(
                $transactionReference,
                $paymentAmount,
                'MotoTrack Order ' . $transactionReference,
                url('/checkout/receipt?trx=' . $transactionReference),
                url('/shop/cart')
            );
            return redirect($result['invoice_url']);
        } catch (\Throwable $e) {
            report($e);

            // Order exists and stock is reserved. Direct the user
            // to the receipt with actionable recovery guidance.
            return redirect('/checkout/receipt')->with('warning',
                'Your order #' . $transactionReference . ' has been saved, but the payment gateway is temporarily unavailable. '
                . 'Please try again from your dashboard or contact support.'
            );
        }
    }

    public function receipt(Request $request)
    {
        $trx = $request->query('trx') ?? session('last_trx');

        if (!$trx) {
            return redirect('/customer/dashboard')->with('error', 'No recent receipt found.');
        }

        $query = Sale::with('item')->where('transaction_reference', $trx);

        // C-3 Security: Scope receipts to the authenticated user's customer
        // profile to prevent IDOR. Owners retain unrestricted access for
        // admin workflows.
        if (auth()->user()->role !== 'owner') {
            $customerId = auth()->user()->customer?->id;

            // F-3: A null customer_id means the user has no Customer profile
            // yet — they cannot own any receipts. Querying with NULL would
            // match admin POS sales (which also have customer_id = NULL).
            if ($customerId === null) {
                return redirect('/customer/dashboard')->with('error', 'Receipt not found.');
            }

            $query->where('customer_id', $customerId);
        }

        $orders = $query->get();

        if ($orders->isEmpty()) {
            return redirect('/customer/dashboard')->with('error', 'Receipt not found.');
        }

        return view('shop.receipt', compact('orders', 'trx'));
    }

    public function downloadReceipt(Request $request, $trx)
    {
        $query = Sale::with('item')->where('transaction_reference', $trx);

        if (auth()->user()->role !== 'owner') {
            $customerId = auth()->user()->customer?->id;

            if ($customerId === null) {
                abort(404, 'Receipt not found.');
            }

            $query->where('customer_id', $customerId);
        }

        $sales = $query->get();

        if ($sales->isEmpty()) {
            abort(404, 'Receipt not found.');
        }

        $transactionId = $sales->first()->transaction_reference ?? 'SALE-' . $sales->first()->id;
        $date = $sales->first()->created_at->format('F d, Y h:i A');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('sales.pdf_receipt', compact('sales', 'transactionId', 'date'));
        
        return $pdf->download("Receipt_{$transactionId}.pdf");
    }
}
