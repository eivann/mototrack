<?php

namespace App\Http\Controllers\PreOrder;

use App\Http\Controllers\Controller;
use App\Http\Concerns\HandlesExceptions;
use App\Http\Requests\PreOrder\StorePreOrderRequest;
use App\Models\InventoryItem;
use App\Models\PreOrder;
use App\Models\User;
use App\Models\Customer;
use App\Notifications\StatusUpdated;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class CustomerPreOrderController extends Controller
{
    use HandlesExceptions;

    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function parts()
    {
        $items = InventoryItem::where('quantity', 0)->get();
        return view('preorders.parts', compact('items'));
    }

    public function customForm()
    {
        return view('preorders.custom');
    }

    public function form($id)
    {
        $item = InventoryItem::findOrFail($id);
        return view('preorders.form', compact('item'));
    }

    public function store(StorePreOrderRequest $request)
    {

        $user = auth()->user();
        $customer = Customer::firstOrCreate(
            ['user_id' => $user->id],
            ['name' => $user->name, 'email' => $user->email]
        );

        $price = $request->price ?? 0;
        $total = $price * $request->quantity;
        $downPayment = $request->down_payment ?? 0;

        $preorder = PreOrder::create([
            'customer_id' => $customer->id,
            'customer_name' => ucwords($user->name),
            'part_name' => ucwords($request->part_name),
            'model' => $request->model ? ucwords($request->model) : null,
            'quantity' => $request->quantity,
            'price' => $price,
            'total' => $total,
            'down_payment' => $downPayment,
            'status' => 'pending',
            'is_paid' => false,
        ]);

        if ($downPayment <= 0) {
            $owners = User::owners();
            Notification::send($owners, new StatusUpdated(
                'New Custom Pre-order Request',
                "Customer {$user->name} has requested a custom pre-order for {$request->part_name}.",
                "/preorders"
            ));
            return redirect('/customer/dashboard')->with('success', 'Custom pre-order request submitted!');
        }

        $paymentMethod = $request->input('payment_method', 'gcash');

        if ($paymentMethod === 'rcpay') {
            return $this->attempt(function () use ($preorder, $downPayment, $request) {
                $externalId = 'PRE-' . $preorder->id . '-' . time();
                $bankCode = $request->input('bank_code', 'PMP');
                $redirectUrl = $this->paymentService->createRcpayUrl($externalId, $downPayment, $bankCode);

                $preorder->update([
                    'xendit_invoice_id' => $externalId,
                    'xendit_invoice_url' => $redirectUrl,
                ]);

                return redirect($redirectUrl);
            }, 'Pre-order saved but RCPAY payment could not be initiated. Please contact support.');
        }

        return $this->attempt(function () use ($preorder, $downPayment, $request) {
            $externalId = 'PRE-' . $preorder->id . '-' . time();
            $result = $this->paymentService->createInvoice(
                $externalId,
                $downPayment,
                'Down Payment for Pre-order: ' . $request->part_name,
                route('preorder.verify', ['id' => $preorder->id]),
                url('/customer/dashboard?error=preorder_failed&id=' . $preorder->id)
            );

            $preorder->update([
                'xendit_invoice_id' => $result['id'],
                'xendit_invoice_url' => $result['invoice_url'],
            ]);

            return redirect($result['invoice_url']);
        }, 'Pre-order saved but payment could not be initiated. Please contact support.');
    }

    public function verify($id)
    {
        $preorder = PreOrder::findOrFail($id);

        // F-7: Ownership gate — only the customer who placed this pre-order
        // may trigger payment verification. Without this check, any
        // authenticated user could verify (and mark as paid) another
        // user's pre-order by guessing the ID.
        if ($preorder->customer?->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        if ($preorder->is_paid) {
            return redirect()->route('customer.dashboard')->with('success', 'Payment already confirmed.');
        }

        return $this->attempt(function () use ($preorder) {
            $invoice = $this->paymentService->getInvoiceStatus($preorder->xendit_invoice_id);

            if ($invoice['status'] === 'SETTLED' || $invoice['status'] === 'PAID') {
                $preorder->update(['is_paid' => true]);

                $owners = User::owners();
                Notification::send($owners, new StatusUpdated(
                    'Pre-order Payment Received',
                    "Customer " . auth()->user()->name . " has completed the down payment for {$preorder->part_name}.",
                    "/preorders"
                ));

                return redirect()->route('customer.dashboard')->with('success', 'Payment confirmed!');
            }

            return redirect()->route('customer.dashboard')->with('info', 'Payment status: ' . $invoice['status']);
        }, 'Payment verification failed. Please try again or contact support.');
    }
}
