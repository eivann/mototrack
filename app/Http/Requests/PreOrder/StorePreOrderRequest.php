<?php

namespace App\Http\Requests\PreOrder;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Http;

class StorePreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $skipRecaptcha = app()->runningUnitTests()
            || $this->ip() === config('services.recaptcha.skip_ip');

        $recaptchaRules = $skipRecaptcha
            ? ['nullable']
            : ['required', function ($attribute, $value, $fail) {
                $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret'   => config('services.recaptcha.secret_key'),
                    'response' => $value,
                    'remoteip' => $this->ip(),
                ]);
                if (! $response->json('success')) {
                    $fail('The reCAPTCHA validation failed.');
                }
            }];

        return [
            'inventory_item_id'    => 'nullable|integer|exists:inventory_items,id',
            'part_name'            => 'required_without:inventory_item_id|nullable|string|max:255',
            'model'                => 'nullable|string|max:255',
            'quantity'             => 'required|integer|min:1|max:9999',
            'down_payment'         => 'nullable|numeric|min:0',
            'g-recaptcha-response' => $recaptchaRules,
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->filled('inventory_item_id')) {
                $item = \App\Models\InventoryItem::find($this->inventory_item_id);
                if ($item && $item->quantity > 0) {
                    $validator->errors()->add('inventory_item_id', 'This item is currently in stock. Please reserve it directly from the catalog.');
                }
            }
        });
    }
}
