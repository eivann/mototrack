<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\PaymentClaim;
use App\Models\PreOrder;
use App\Models\Sale;
use App\Models\User;
use App\Notifications\StatusUpdated;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * RD2-R2: manual, owner-verified GCash QR payments for product reservations and catalogue pre-orders.
 *
 * Rules enforced here (not in views or controllers):
 *  - the expected amount always comes from the server's own records, never from the request;
 *  - a claim can never be "paid" by displaying the QR or uploading a screenshot — only an owner decision
 *    moves it to verified, and only with the amount the owner saw in the receiving account;
 *  - payment state is separate from reservation / pre-order state and never touches stock or fulfilment;
 *  - decisions are idempotent and concurrency-safe (row lock + unique "active" markers);
 *  - references and proofs are never written to logs.
 */
class PaymentClaimService
{
    public const PROOF_DISK = 'local'; // storage/app/private — not web-reachable
    private const PROOF_MIMES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    public function enabled(): bool
    {
        return (bool) config('payments.gcash_qr.enabled');
    }

    /** Normalise a payment reference for storage/comparison (case/space/dash insensitive). */
    public static function normaliseReference(?string $reference): ?string
    {
        $clean = strtoupper(preg_replace('/[\s\-]+/', '', (string) $reference));

        return $clean === '' ? null : $clean;
    }

    /**
     * What can be paid, and for how much, according to the server's own records.
     *
     * @return array{ok: bool, amount: float, message: ?string}
     */
    public function payable(string $type, string $key, int $customerId): array
    {
        $fail = fn (string $m) => ['ok' => false, 'amount' => 0.0, 'message' => $m];

        if ($type === PaymentClaim::TYPE_RESERVATION) {
            $lines = Sale::query()->where('transaction_reference', $key)->where('customer_id', $customerId)->get();
            if ($lines->isEmpty()) {
                return $fail('Reservation not found.');
            }
            foreach ($lines as $line) {
                $deadline = $line->expires_at ?? ($line->reserved_at ? $line->reserved_at->copy()->addHours(24) : null);
                if ($line->status !== OrderStatus::SALE_PENDING || $line->payment_confirmed_at !== null
                    || ($deadline && now()->greaterThan($deadline))) {
                    return $fail('This reservation is no longer open for payment.');
                }
            }
            $amount = round((float) $lines->sum('total_price'), 2);
        } elseif ($type === PaymentClaim::TYPE_PREORDER) {
            if (! ctype_digit($key)) {
                return $fail('Pre-order not found.');
            }
            $order = PreOrder::query()->whereKey((int) $key)->where('customer_id', $customerId)->first();
            if (! $order) {
                return $fail('Pre-order not found.');
            }
            // Custom-parts requests carry no catalogue item and no price: QR payment never applies.
            if ($order->inventory_item_id === null) {
                return $fail('GCash QR payment is not available for custom-parts requests.');
            }
            if ($order->status !== OrderStatus::PREORDER_PENDING || $order->is_paid) {
                return $fail('This pre-order is no longer open for payment.');
            }
            $amount = round((float) $order->total, 2);
        } else {
            return $fail('Unknown payment target.');
        }

        if ($amount <= 0) {
            return $fail('There is no payable amount for this order.');
        }

        return ['ok' => true, 'amount' => $amount, 'message' => null];
    }

    /** Record the customer's choice of GCash QR (idempotent: returns the existing active claim). */
    public function open(string $type, string $key, Customer $customer): PaymentClaim
    {
        if (! $this->enabled()) {
            throw new PaymentException('GCash QR payment is not available yet.');
        }
        $payable = $this->payable($type, $key, $customer->id);
        if (! $payable['ok']) {
            throw new PaymentException($payable['message']);
        }

        return DB::transaction(function () use ($type, $key, $customer, $payable) {
            $existing = PaymentClaim::query()->where('active_payable_key', "$type:$key")->lockForUpdate()->first();
            if ($existing) {
                return $existing;
            }
            try {
                // The INSERT runs in its own savepoint: on PostgreSQL a failed statement aborts the whole
                // surrounding transaction, so the fallback SELECT below would otherwise fail (SQLSTATE 25P02)
                // when two requests open the same payment at once. SQLite does not show this difference.
                return DB::transaction(fn () => PaymentClaim::create([
                    'payable_type' => $type, 'payable_key' => $key, 'customer_id' => $customer->id,
                    'method' => PaymentClaim::METHOD_GCASH_QR, 'status' => PaymentClaim::AWAITING,
                    'expected_amount' => $payable['amount'], 'currency' => 'PHP',
                    'active_payable_key' => "$type:$key",
                ]));
            } catch (UniqueConstraintViolationException) {
                return PaymentClaim::query()->where('active_payable_key', "$type:$key")->firstOrFail();
            }
        });
    }

    /**
     * Customer submits a reference and/or proof. The claim becomes Pending Verification — never paid.
     */
    public function submit(string $type, string $key, Customer $customer, ?string $reference, float $declaredAmount, ?UploadedFile $proof): PaymentClaim
    {
        $reference = self::normaliseReference($reference);
        if ($reference === null && $proof === null) {
            throw new PaymentException('Enter the GCash reference number or upload a screenshot of the payment.');
        }

        $proofHash = null;
        $ext = null;
        if ($proof !== null) {
            [$ext, $proofHash] = $this->inspectProof($proof);
        }

        $claim = $this->open($type, $key, $customer); // also re-validates payable + gate

        $stored = null;
        try {
            $claim = DB::transaction(function () use ($claim, $customer, $reference, $declaredAmount, $proof, $ext, $proofHash, &$stored) {
                $locked = PaymentClaim::query()->whereKey($claim->id)->lockForUpdate()->firstOrFail();
                if ($locked->customer_id !== $customer->id) {
                    throw new PaymentException('Payment not found.', 403);
                }
                if ($locked->status !== PaymentClaim::AWAITING) {
                    throw new PaymentException($locked->status === PaymentClaim::PENDING
                        ? 'Your payment is already waiting for verification.'
                        : 'This payment has already been decided.', 409);
                }

                $fingerprint = $reference !== null ? hash('sha256', $reference) : null;
                $duplicate = PaymentClaim::query()->where('id', '!=', $locked->id)
                    ->where(fn ($q) => $q
                        ->when($fingerprint, fn ($w) => $w->orWhere('active_reference_fingerprint', $fingerprint))
                        ->when($proofHash, fn ($w) => $w->orWhere('active_proof_hash', $proofHash)))
                    ->exists();
                if (($fingerprint || $proofHash) && $duplicate) {
                    throw new PaymentException('This payment reference or screenshot has already been submitted for another order.', 409);
                }

                if ($proof !== null) {
                    $stored = $proof->storeAs("payments/proofs/{$locked->id}", Str::random(40).'.'.$ext, self::PROOF_DISK);
                }

                $locked->update([
                    'status' => PaymentClaim::PENDING,
                    'reference_number' => $reference,
                    'declared_amount' => round($declaredAmount, 2),
                    'proof_path' => $stored,
                    'active_reference_fingerprint' => $fingerprint,
                    'active_proof_hash' => $proofHash,
                    'submitted_at' => now(),
                ]);

                return $locked;
            });
        } catch (UniqueConstraintViolationException) {
            $this->discardProof($stored);
            throw new PaymentException('This payment reference or screenshot has already been submitted for another order.', 409);
        } catch (\Throwable $e) {
            $this->discardProof($stored);
            throw $e;
        }

        $this->audit($claim, 'payment_claim.submitted', null);
        $this->notifySubmitted($claim, $customer);

        return $claim;
    }

    /**
     * Owner decision. Idempotent: repeating the same decision changes nothing; the opposite decision on an
     * already-decided claim is refused.
     *
     * @param  float|null  $receivedAmount  amount the owner saw in the receiving account (required to approve)
     */
    public function decide(PaymentClaim $claim, User $owner, string $decision, ?float $receivedAmount, ?string $note): PaymentClaim
    {
        if ($owner->role !== 'owner') {
            throw new PaymentException('Only the owner can verify payments.', 403);
        }
        if (! in_array($decision, ['approve', 'reject'], true)) {
            throw new PaymentException('Unknown decision.', 422);
        }
        $note = $note !== null ? Str::limit(trim($note), 255, '') : null;
        if ($decision === 'reject' && ($note === null || $note === '')) {
            throw new PaymentException('Give the customer a reason for rejecting the payment.', 422);
        }

        $changed = false;
        $locked = DB::transaction(function () use ($claim, $owner, $decision, $receivedAmount, $note, &$changed) {
            $locked = PaymentClaim::query()->whereKey($claim->id)->lockForUpdate()->firstOrFail();
            $target = $decision === 'approve' ? PaymentClaim::VERIFIED : PaymentClaim::REJECTED;

            if ($locked->status === $target) {
                return $locked; // idempotent repeat — no second write, no second notification
            }
            if ($locked->status !== PaymentClaim::PENDING) {
                throw new PaymentException('This payment is not waiting for verification.', 409);
            }

            if ($decision === 'approve') {
                if ($receivedAmount === null || abs($receivedAmount - (float) $locked->expected_amount) > 0.004) {
                    throw new PaymentException('The amount received must equal the amount due (₱'.number_format((float) $locked->expected_amount, 2).') before the payment can be verified.', 422);
                }
                $locked->update([
                    'status' => PaymentClaim::VERIFIED, 'verified_amount' => round($receivedAmount, 2),
                    'decided_at' => now(), 'decided_by' => $owner->id, 'decision_note' => $note,
                ]);
            } else {
                // Release the unique markers so the customer may submit a corrected payment.
                $locked->update([
                    'status' => PaymentClaim::REJECTED, 'decided_at' => now(), 'decided_by' => $owner->id,
                    'decision_note' => $note, 'active_payable_key' => null,
                    'active_reference_fingerprint' => null, 'active_proof_hash' => null,
                ]);
            }
            $changed = true;

            return $locked;
        });

        if ($changed) {
            $this->audit($locked, $decision === 'approve' ? 'payment_claim.verified' : 'payment_claim.rejected', $owner);
            $this->notifyDecision($locked);
        }

        return $locked;
    }

    /**
     * A reservation / pre-order was cancelled or expired. Claims that never reached the owner are closed;
     * claims with money possibly in flight are flagged for owner review. Nothing is refunded or reopened.
     */
    public function payableClosed(string $type, string $key, string $why): void
    {
        try {
            $claims = PaymentClaim::query()->where('payable_type', $type)->where('payable_key', $key)
                ->whereIn('status', PaymentClaim::ACTIVE)->get();

            foreach ($claims as $claim) {
                if ($claim->status === PaymentClaim::AWAITING) {
                    $claim->update(['status' => PaymentClaim::CANCELLED, 'active_payable_key' => null,
                        'review_reason' => Str::limit("Order {$why} before any payment was submitted.", 250, '')]);

                    continue;
                }
                $claim->update(['needs_owner_review' => true,
                    'review_reason' => Str::limit("Order was {$why} while this payment was ".strtolower($claim->label()).'. Check the receiving account; refund (if due) is handled manually by the owner.', 250, '')]);
                app(NotificationDispatcher::class)->send(User::owners(), new StatusUpdated(
                    'Payment needs review',
                    $claim->payableLabel()." was {$why} but has a GCash payment on file. Please review it.",
                    route('owner.payments.index')
                ));
            }
        } catch (\Throwable $e) {
            // Never let a payment-side problem undo an already committed cancellation/expiry.
            Log::warning("Payment claim review flag failed for {$type} {$key}: ".get_class($e));
        }
    }

    /** Validate an uploaded proof by actual content, returning [extension, sha256]. */
    private function inspectProof(UploadedFile $file): array
    {
        $maxBytes = (int) config('payments.gcash_qr.max_proof_kb') * 1024;
        if (! $file->isValid() || $file->getSize() === false || $file->getSize() > $maxBytes) {
            throw new PaymentException('The screenshot must be an image of at most '.((int) config('payments.gcash_qr.max_proof_kb') / 1024).' MB.');
        }
        $path = $file->getRealPath();
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        $info = @getimagesize($path);
        if (! isset(self::PROOF_MIMES[$mime]) || $info === false || ($info[0] ?? 0) < 1 || ($info[1] ?? 0) < 1 || ($info['mime'] ?? null) !== $mime) {
            throw new PaymentException('Upload a JPG, PNG or WebP screenshot.');
        }

        return [self::PROOF_MIMES[$mime], hash_file('sha256', $path)];
    }

    private function discardProof(?string $path): void
    {
        if ($path) {
            Storage::disk(self::PROOF_DISK)->delete($path);
        }
    }

    private function audit(PaymentClaim $claim, string $action, ?User $actor): void
    {
        try {
            AuditLog::create([
                'user_id' => $actor?->id ?? auth()->id(),
                'action' => $action,
                'auditable_type' => PaymentClaim::class,
                'auditable_id' => $claim->id,
                'old_values' => null,
                // Deliberately no reference number, proof path or hash.
                'new_values' => ['status' => $claim->status, 'payable' => $claim->payable_type.':'.$claim->payable_key,
                    'expected_amount' => (string) $claim->expected_amount, 'verified_amount' => $claim->verified_amount !== null ? (string) $claim->verified_amount : null],
                'ip_address' => request()->ip() ?? '127.0.0.1',
                'user_agent' => request()->userAgent(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Payment audit row could not be written: '.get_class($e));
        }
    }

    private function notifySubmitted(PaymentClaim $claim, Customer $customer): void
    {
        $dispatcher = app(NotificationDispatcher::class);
        $dispatcher->send($customer->user, new StatusUpdated(
            'Payment submitted',
            $claim->payableLabel().': your GCash payment is pending verification by the shop. It is not confirmed until the owner verifies it.',
            $claim->customerUrl()
        ));
        $dispatcher->send(User::owners(), new StatusUpdated(
            'GCash payment awaiting verification',
            $claim->payableLabel().' — ₱'.number_format((float) $claim->expected_amount, 2).' pending your check.',
            route('owner.payments.index')
        ));
    }

    private function notifyDecision(PaymentClaim $claim): void
    {
        $user = $claim->customer?->user;
        if (! $user) {
            return;
        }
        $verified = $claim->status === PaymentClaim::VERIFIED;
        app(NotificationDispatcher::class)->send($user, new StatusUpdated(
            $verified ? 'Payment verified' : 'Payment rejected',
            $verified
                ? $claim->payableLabel().': your GCash payment was verified. Please follow the pickup instructions for your order.'
                : $claim->payableLabel().': your GCash payment could not be verified. Reason: '.rtrim((string) $claim->decision_note, '.').'. You may submit a corrected payment if the order is still open.',
            $claim->customerUrl()
        ));
    }
}
