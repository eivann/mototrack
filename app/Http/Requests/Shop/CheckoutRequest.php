<?php

namespace App\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // "on_pickup" is the legacy hidden value for cash at the counter.
        return [
            'payment_method' => ['nullable', 'string', \Illuminate\Validation\Rule::in(['cash', 'on_pickup', 'gcash_qr'])],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // RD2-R2: GCash QR stays unavailable until the owner-approved config gate is open.
            if ($this->input('payment_method') === 'gcash_qr' && ! app(\App\Services\PaymentClaimService::class)->enabled()) {
                $validator->errors()->add('payment_method', 'GCash QR payment is not available yet. Please choose cash at the counter.');
            }
        });
    }

    public function wantsGcashQr(): bool
    {
        return $this->input('payment_method') === 'gcash_qr';
    }
}
