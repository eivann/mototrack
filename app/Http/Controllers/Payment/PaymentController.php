<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\PaymentClaim;
use App\Services\PaymentClaimService;
use App\Services\PaymentException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** RD2-R2 customer side: view the QR + amount due, submit a payment claim, see its status. */
class PaymentController extends Controller
{
    public function __construct(private PaymentClaimService $payments)
    {
    }

    private function customer(): Customer
    {
        $user = auth()->user();

        return Customer::firstOrCreate(['user_id' => $user->id], ['name' => $user->name, 'email' => $user->email]);
    }

    /** The supplied QR image, byte-for-byte, only while the gate is open and only for signed-in users. */
    public function qr()
    {
        abort_unless($this->payments->enabled() && is_file(config('payments.gcash_qr.qr_path')), 404);

        return response()->file(config('payments.gcash_qr.qr_path'), [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function show(string $type, string $key)
    {
        abort_unless($this->payments->enabled() && in_array($type, PaymentClaim::TYPES, true), 404);
        $customer = $this->customer();

        $claim = PaymentClaim::query()->where('payable_type', $type)->where('payable_key', $key)
            ->where('customer_id', $customer->id)->latest('id')->first();
        $payable = $this->payments->payable($type, $key, $customer->id);
        abort_if(! $claim && ! $payable['ok'], 404);

        return view('payments.show', [
            'type' => $type, 'key' => $key, 'claim' => $claim, 'payable' => $payable,
            'amount' => $claim && $claim->isActive() ? (float) $claim->expected_amount : $payable['amount'],
            'label' => config('payments.gcash_qr.label'),
        ]);
    }

    public function submit(Request $request, string $type, string $key)
    {
        abort_unless($this->payments->enabled() && in_array($type, PaymentClaim::TYPES, true), 404);

        $data = $request->validate([
            'reference_number' => ['nullable', 'string', 'max:40', 'regex:/^[A-Za-z0-9 \-]{6,40}$/'],
            'declared_amount' => ['required', 'numeric', 'min:0.01', 'max:9999999'],
            'proof' => ['nullable', 'file', 'max:'.(int) config('payments.gcash_qr.max_proof_kb')],
        ], [
            'reference_number.regex' => 'The reference number may contain only letters, numbers, spaces and dashes (6–40 characters).',
            'declared_amount.required' => 'Enter the amount you sent.',
        ]);

        try {
            $this->payments->submit($type, $key, $this->customer(), $data['reference_number'] ?? null,
                (float) $data['declared_amount'], $request->file('proof'));
        } catch (PaymentException $e) {
            if ($e->httpStatus === 403) {
                abort(403, $e->getMessage());
            }

            return back()->withInput($request->except('proof'))->with('error', $e->getMessage());
        }

        return redirect()->route('payments.show', [$type, $key])
            ->with('success', 'Payment submitted. It is pending verification — the shop will confirm it once they see it in their account.');
    }

    /** Private proof: the owner, or the customer who submitted it. Never cached, never sniffed. */
    public function proof(PaymentClaim $claim)
    {
        $user = auth()->user();
        $allowed = $user->role === 'owner' || ($claim->customer_id !== null && $claim->customer_id === $user->customer?->id);
        abort_unless($allowed && $claim->proof_path && Storage::disk(PaymentClaimService::PROOF_DISK)->exists($claim->proof_path), 404);

        return Storage::disk(PaymentClaimService::PROOF_DISK)->response($claim->proof_path, 'payment-proof-'.$claim->id, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; img-src 'self'; sandbox",
        ]);
    }
}
