<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\User;
use App\Models\InventoryItem;
use App\Models\Customer;
use App\Notifications\StatusUpdated;
use App\Services\CartService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class CheckoutController extends Controller
{
    protected $cartService;
    protected $paymentService;

    public function __construct(CartService $cartService, PaymentService $paymentService)
    {
        $this->cartService = $cartService;
        $this->paymentService = $paymentService;
    }

    public function index()
    {
        $cart = $this->cartService->getCart();
        return view('shop.checkout', compact('cart'));
    }

    public function process(Request $request)
    {
        $cart = $this->cartService->getCart();

        if (empty($cart)) {
            return back()->with('error', 'Cart is empty!');
        }

        $transactionReference = 'TRX-' . strtoupper(uniqid());
        $paymentMethod = $request->input('payment_method', 'gcash');

        // Find or create customer profile
        $customerId = null;
        if (auth()->check()) {
            $user = auth()->user();
            $customer = $user->customer ?: Customer::create([
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]);
            $customerId = $customer->id;
        }

        try {
            $checkoutService = app(\App\Services\CheckoutService::class);
            $grandTotal = $checkoutService->processTransaction(
                $cart,
                $transactionReference,
                $customerId,
                $request->input('amount_tendered')
            );
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->cartService->clear();
        session()->put('last_trx', $transactionReference);

        // Notifications
        if (auth()->check()) {
            auth()->user()->notify(new StatusUpdated(
                'Order Placed',
                "Your order #{$transactionReference} has been placed successfully.",
                '/customer/dashboard'
            ));
        }

        $owners = User::where('role', 'owner')->get();
        Notification::send($owners, new StatusUpdated(
            'New Order Received',
            "Customer " . auth()->user()->name . " has placed a new order #{$transactionReference}.",
            "/sales/{$transactionReference}/receipt"
        ));

        if ($paymentMethod === 'gcash') {
            try {
                $paymentAmount = $request->input('amount_tendered') ?: $grandTotal;
                $result = $this->paymentService->createInvoice(
                    $transactionReference,
                    $paymentAmount,
                    'MotoTrack Order ' . $transactionReference,
                    url('/shop/receipt?trx=' . $transactionReference),
                    url('/shop/cart')
                );
                return redirect($result['invoice_url']);
            } catch (\Exception $e) {
                return redirect('/shop/receipt')->with('error', 'Order saved but GCash failed: ' . $e->getMessage());
            }
        }

        if ($paymentMethod === 'rcpay') {
            try {
                $paymentAmount = $request->input('amount_tendered') ?: $grandTotal;
                $bankCode = $request->input('bank_code', 'PMP');
                $redirectUrl = $this->paymentService->createRcpayUrl(
                    $transactionReference,
                    $paymentAmount,
                    $bankCode
                );
                return redirect($redirectUrl);
            } catch (\Exception $e) {
                return redirect('/shop/receipt')->with('error', 'Order saved but RCPAY payment failed: ' . $e->getMessage());
            }
        }

        return redirect('/shop/receipt')->with('success', 'Order placed successfully!');
    }

    public function receipt(Request $request)
    {
        $trx = $request->query('trx') ?? session('last_trx');

        if (!$trx) {
            return redirect('/customer/dashboard')->with('error', 'No recent receipt found.');
        }

        $orders = Sale::with('item')->where('transaction_reference', $trx)->get();

        if ($orders->isEmpty()) {
            return redirect('/customer/dashboard')->with('error', 'Receipt not found.');
        }

        return view('shop.receipt', compact('orders', 'trx'));
    }
}
