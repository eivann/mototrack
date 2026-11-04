<x-app-layout>
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-6 px-4">
    <div class="max-w-3xl mx-auto">
        <h2 class="text-2xl font-bold mb-1 text-gray-900 dark:text-white">Pay with GCash</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-5">
            {{ $type === 'preorder' ? 'Pre-order #'.$key : 'Reservation '.$key }}
        </p>

        @php($status = $claim?->status)
        @if ($errors->any())
            <div class="mb-4 text-red-600 dark:text-red-400 text-sm"><ul>@foreach ($errors->all() as $e)<li>• {{ $e }}</li>@endforeach</ul></div>
        @endif

        <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-lg rounded-2xl p-5 sm:p-6 space-y-5">

            @if ($status === 'pending_verification')
                <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/30 p-4 text-sm text-amber-900 dark:text-amber-200">
                    <strong>Pending verification.</strong> The shop has not confirmed your payment yet. Your order is <u>not</u> marked as paid until the owner verifies it in their GCash account.
                    <div class="mt-2 text-xs">Submitted {{ $claim->submitted_at?->timezone('Asia/Manila')->format('M d, Y h:i A') }} &middot; Amount due ₱{{ number_format((float) $claim->expected_amount, 2) }}</div>
                </div>
            @elseif ($status === 'verified')
                <div class="rounded-xl border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-950/30 p-4 text-sm text-green-900 dark:text-green-200">
                    <strong>Payment verified</strong> on {{ $claim->decided_at?->timezone('Asia/Manila')->format('M d, Y h:i A') }}. Please follow the pickup instructions for your order.
                </div>
            @elseif ($status === 'rejected')
                <div class="rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-950/30 p-4 text-sm text-red-900 dark:text-red-200">
                    <strong>Payment rejected.</strong> Reason: {{ $claim->decision_note }}
                    @if ($payable['ok']) <div class="mt-1">You can submit a corrected payment below.</div> @endif
                </div>
            @elseif ($status === 'cancelled')
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-sm text-gray-700 dark:text-gray-300">This payment request was closed because the order is no longer open.</div>
            @endif

            @if ($claim?->needs_owner_review)
                <div class="rounded-xl border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950/30 p-4 text-sm text-blue-900 dark:text-blue-200">
                    This order is no longer open but a GCash payment is on file. The shop will review it with you directly &mdash; nothing is refunded or reopened automatically.
                </div>
            @endif

            @if ($payable['ok'] && ! in_array($status, ['pending_verification', 'verified'], true))
                <div class="grid sm:grid-cols-2 gap-5 items-start">
                    <div class="text-center">
                        {{-- Served byte-for-byte through a gated route; width is capped, aspect ratio preserved so it stays scannable. --}}
                        <img src="{{ route('payments.qr') }}" alt="GCash QR code for {{ $label }}" width="720" height="1368"
                             class="mx-auto w-full max-w-[260px] h-auto rounded-xl border border-gray-200 dark:border-gray-700 bg-white" loading="eager" decoding="async">
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Receiving account name should show <strong>{{ $label }}</strong>. If it does not, do not pay.</p>
                    </div>
                    <div class="space-y-3 text-sm text-gray-700 dark:text-gray-300">
                        <div class="rounded-xl bg-gray-50 dark:bg-gray-900/50 p-4">
                            <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Amount to pay (VAT inclusive)</div>
                            <div class="text-3xl font-black text-gray-900 dark:text-white">₱{{ number_format($amount, 2) }}</div>
                        </div>
                        <ol class="list-decimal pl-5 space-y-1">
                            <li>Open GCash, choose <strong>Pay QR</strong> and scan the code.</li>
                            <li>Enter exactly <strong>₱{{ number_format($amount, 2) }}</strong> and pay.</li>
                            <li>Enter the GCash reference number and/or upload your receipt screenshot below.</li>
                        </ol>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Your payment counts only after the shop verifies it. You will be notified.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('payments.submit', [$type, $key]) }}" enctype="multipart/form-data"
                      x-data="{ busy: false }" @submit="busy = true" class="space-y-4 border-t border-gray-100 dark:border-gray-700 pt-5">
                    @csrf
                    <div>
                        <label for="declared_amount" class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">Amount you sent (₱)</label>
                        <input id="declared_amount" name="declared_amount" type="number" step="0.01" min="0.01" required inputmode="decimal"
                               value="{{ old('declared_amount', number_format($amount, 2, '.', '')) }}"
                               class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label for="reference_number" class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">GCash reference number</label>
                        <input id="reference_number" name="reference_number" type="text" maxlength="40" autocomplete="off" value="{{ old('reference_number') }}"
                               class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label for="proof" class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">Screenshot of the payment (JPG, PNG or WebP, up to {{ (int) (config('payments.gcash_qr.max_proof_kb') / 1024) }} MB)</label>
                        <input id="proof" name="proof" type="file" accept="image/jpeg,image/png,image/webp"
                               class="w-full text-sm text-gray-700 dark:text-gray-300">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Provide the reference number, the screenshot, or both.</p>
                    </div>
                    <button type="submit" :disabled="busy" class="w-full bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white font-bold py-3 rounded-xl">
                        <span x-text="busy ? 'Submitting…' : 'Submit payment for verification'">Submit payment for verification</span>
                    </button>
                </form>
            @elseif (! $payable['ok'] && ! in_array($status, ['pending_verification', 'verified'], true))
                <div class="text-sm text-gray-700 dark:text-gray-300">{{ $payable['message'] }}</div>
            @endif

            @if ($claim?->proof_path)
                <div class="text-xs"><a class="text-blue-600 dark:text-blue-400 underline" href="{{ route('payments.proof', $claim) }}" target="_blank" rel="noopener">View the screenshot you submitted</a></div>
            @endif

            <div class="text-xs"><a href="{{ route('customer.dashboard') }}" class="text-gray-600 dark:text-gray-400 hover:underline">← Back to my dashboard</a></div>
        </div>
    </div>
</div>
</x-app-layout>
