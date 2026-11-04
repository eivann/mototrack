<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\PaymentClaim;
use App\Services\PaymentClaimService;
use App\Services\PaymentException;
use Illuminate\Http\Request;

/** RD2-R2 owner side (route group is auth + owner): review and decide GCash payment claims. */
class OwnerPaymentController extends Controller
{
    public function __construct(private PaymentClaimService $payments)
    {
    }

    public function index(Request $request)
    {
        $tab = in_array($request->query('tab'), ['pending', 'review', 'verified', 'rejected', 'awaiting'], true) ? $request->query('tab') : 'pending';

        $query = PaymentClaim::query()->with('customer')->latest('submitted_at')->latest('id');
        match ($tab) {
            'review' => $query->where('needs_owner_review', true),
            'verified' => $query->where('status', PaymentClaim::VERIFIED),
            'rejected' => $query->where('status', PaymentClaim::REJECTED),
            'awaiting' => $query->where('status', PaymentClaim::AWAITING),
            default => $query->where('status', PaymentClaim::PENDING),
        };

        $counts = [
            'pending' => PaymentClaim::where('status', PaymentClaim::PENDING)->count(),
            'review' => PaymentClaim::where('needs_owner_review', true)->count(),
        ];

        return view('payments.owner_index', [
            'claims' => $query->paginate(15)->withQueryString(), 'tab' => $tab, 'counts' => $counts,
            'enabled' => $this->payments->enabled(),
        ]);
    }

    public function approve(Request $request, PaymentClaim $claim)
    {
        $data = $request->validate([
            'received_amount' => ['required', 'numeric', 'min:0.01', 'max:9999999'],
            'note' => ['nullable', 'string', 'max:255'],
        ], ['received_amount.required' => 'Enter the amount you received in the GCash account.']);

        return $this->run(fn () => $this->payments->decide($claim, $request->user(), 'approve', (float) $data['received_amount'], $data['note'] ?? null),
            'Payment verified. The customer has been notified.');
    }

    public function reject(Request $request, PaymentClaim $claim)
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:255']], ['note.required' => 'Give the customer a reason for rejecting the payment.']);

        return $this->run(fn () => $this->payments->decide($claim, $request->user(), 'reject', null, $data['note']),
            'Payment rejected. The customer has been notified.');
    }

    private function run(callable $action, string $success)
    {
        try {
            $action();
        } catch (PaymentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $success);
    }
}
