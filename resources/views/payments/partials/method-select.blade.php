{{-- RD2-R2: payment method choice. While the GCash QR gate is closed this renders exactly the legacy
     hidden "pay at the counter" value, so nothing about the existing flow changes. Expects the parent
     form's Alpine scope to define `payMethod`. --}}
@if (app(\App\Services\PaymentClaimService::class)->enabled())
    <fieldset class="space-y-2">
        <legend class="text-sm font-bold text-gray-900 dark:text-white mb-2">Payment method</legend>
        <label class="flex items-start gap-3 rounded-xl border border-gray-200 dark:border-gray-700 p-3 cursor-pointer" :class="payMethod === 'cash' ? 'ring-2 ring-blue-500' : ''">
            <input type="radio" name="payment_method" value="cash" x-model="payMethod" class="mt-1" @checked(old('payment_method', 'cash') !== 'gcash_qr')>
            <span class="text-sm"><strong class="block text-gray-900 dark:text-white">Cash at the counter</strong>
                <span class="text-gray-600 dark:text-gray-400">Pay in cash when you pick up your order.</span></span>
        </label>
        <label class="flex items-start gap-3 rounded-xl border border-gray-200 dark:border-gray-700 p-3 cursor-pointer" :class="payMethod === 'gcash_qr' ? 'ring-2 ring-blue-500' : ''">
            <input type="radio" name="payment_method" value="gcash_qr" x-model="payMethod" class="mt-1" @checked(old('payment_method') === 'gcash_qr')>
            <span class="text-sm"><strong class="block text-gray-900 dark:text-white">GCash QR</strong>
                <span class="text-gray-600 dark:text-gray-400">After you place the order you will see the QR code and the exact amount to pay. The shop confirms your payment manually &mdash; it is not counted as paid until then.</span></span>
        </label>
        @error('payment_method')<p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
    </fieldset>
@else
    <input type="hidden" name="payment_method" value="on_pickup">
@endif
