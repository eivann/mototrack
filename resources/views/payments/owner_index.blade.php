<x-app-layout>
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-6 px-4">
    <div class="max-w-6xl mx-auto">
        <h2 class="text-2xl font-bold mb-1 text-gray-900 dark:text-white">GCash Payment Verification</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Check each claim against the <strong>actual receiving GCash account</strong> before approving. Nothing here changes stock or marks the order picked up.</p>

        @unless ($enabled)
            <div class="mb-4 rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/30 p-3 text-sm text-amber-900 dark:text-amber-200">
                GCash QR is switched off in this environment, so customers cannot start new payments. Existing claims remain reviewable.
            </div>
        @endunless

        <div class="flex flex-wrap gap-2 mb-4 text-sm">
            @foreach (['pending' => 'Pending ('.$counts['pending'].')', 'review' => 'Needs review ('.$counts['review'].')', 'verified' => 'Verified', 'rejected' => 'Rejected', 'awaiting' => 'Awaiting payment'] as $k => $label)
                <a href="{{ route('owner.payments.index', ['tab' => $k]) }}"
                   class="px-3 py-1.5 rounded-full border {{ $tab === $k ? 'bg-blue-600 text-white border-blue-600' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700' }}">{{ $label }}</a>
            @endforeach
        </div>

        <div class="space-y-3">
            @forelse ($claims as $claim)
                <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl p-4 shadow-sm" id="claim-{{ $claim->id }}">
                    <div class="flex flex-wrap justify-between gap-2">
                        <div>
                            <div class="font-bold text-gray-900 dark:text-white">{{ $claim->payableLabel() }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $claim->customer?->name ?? 'Customer' }} &middot; {{ $claim->label() }}
                                @if ($claim->submitted_at) &middot; submitted {{ $claim->submitted_at->timezone('Asia/Manila')->format('M d, Y h:i A') }} @endif</div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs text-gray-500 dark:text-gray-400">Amount due (VAT incl.)</div>
                            <div class="text-xl font-black text-gray-900 dark:text-white">₱{{ number_format((float) $claim->expected_amount, 2) }}</div>
                        </div>
                    </div>

                    <dl class="grid sm:grid-cols-3 gap-2 text-sm mt-3 text-gray-700 dark:text-gray-300">
                        <div><dt class="text-xs text-gray-500 dark:text-gray-400">Customer says they sent</dt>
                            <dd>{{ $claim->declared_amount !== null ? '₱'.number_format((float) $claim->declared_amount, 2) : '—' }}
                                @if ($claim->declared_amount !== null && abs((float) $claim->declared_amount - (float) $claim->expected_amount) > 0.004)
                                    <span class="ml-1 text-xs font-bold text-red-600">≠ amount due</span>@endif</dd></div>
                        <div><dt class="text-xs text-gray-500 dark:text-gray-400">GCash reference</dt><dd class="font-mono break-all">{{ $claim->reference_number ?? '—' }}</dd></div>
                        <div><dt class="text-xs text-gray-500 dark:text-gray-400">Screenshot</dt>
                            <dd>@if ($claim->proof_path)<a class="text-blue-600 dark:text-blue-400 underline" target="_blank" rel="noopener" href="{{ route('payments.proof', $claim) }}">View proof</a>@else — @endif</dd></div>
                    </dl>

                    @if ($claim->needs_owner_review)
                        <div class="mt-3 rounded-lg bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-800 p-2 text-xs text-red-800 dark:text-red-200">⚠ {{ $claim->review_reason }}</div>
                    @endif

                    @if ($claim->decided_at)
                        <div class="mt-3 text-xs text-gray-600 dark:text-gray-400">
                            {{ $claim->label() }} by {{ $claim->decider?->name ?? 'owner' }} on {{ $claim->decided_at->timezone('Asia/Manila')->format('M d, Y h:i A') }}
                            @if ($claim->verified_amount !== null) &middot; received ₱{{ number_format((float) $claim->verified_amount, 2) }} @endif
                            @if ($claim->decision_note) &middot; “{{ $claim->decision_note }}” @endif
                        </div>
                    @endif

                    @if ($claim->status === 'pending_verification')
                        <div class="grid md:grid-cols-2 gap-3 mt-4">
                            <form method="POST" action="{{ route('owner.payments.approve', $claim) }}" class="space-y-2" x-data="{ busy: false }" @submit="busy = true">
                                @csrf
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">Amount received in the GCash account (₱)</label>
                                <input type="number" name="received_amount" step="0.01" min="0.01" required inputmode="decimal" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                                <input type="text" name="note" maxlength="255" placeholder="Note (optional)" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                                <button type="submit" :disabled="busy" class="w-full bg-green-600 hover:bg-green-700 disabled:opacity-60 text-white font-bold py-2 rounded-lg text-sm">Verify payment</button>
                            </form>
                            <form method="POST" action="{{ route('owner.payments.reject', $claim) }}" class="space-y-2" x-data="{ busy: false }" @submit="busy = true">
                                @csrf
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">Reason shown to the customer</label>
                                <input type="text" name="note" maxlength="255" required placeholder="e.g. No matching payment found" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                                <button type="submit" :disabled="busy" class="w-full bg-red-600 hover:bg-red-700 disabled:opacity-60 text-white font-bold py-2 rounded-lg text-sm">Reject payment</button>
                            </form>
                        </div>
                    @endif
                </div>
            @empty
                <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl p-8 text-center text-gray-500 dark:text-gray-400">No payments in this list.</div>
            @endforelse
        </div>

        <div class="mt-4">{{ $claims->links() }}</div>
    </div>
</div>
</x-app-layout>
