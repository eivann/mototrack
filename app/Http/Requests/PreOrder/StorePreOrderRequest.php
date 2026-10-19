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
            'part_name'            => 'required_without:inventory_item_id|nullable|string|min:3|max:255|regex:/[a-zA-Z0-9]/',
            'model'                => 'required_without:inventory_item_id|nullable|string|min:2|max:255|regex:/[a-zA-Z0-9]/',
            'quantity'             => 'required|integer|min:1|max:100',
            'down_payment'         => 'nullable|numeric|min:0',
            'g-recaptcha-response' => $recaptchaRules,
        ];
    }

    public function messages(): array
    {
        return [
            'part_name.required_without' => 'Please enter the specific part name or description you wish to request.',
            'part_name.min'              => 'The part name must be at least 3 characters long.',
            'part_name.regex'            => 'The part name must contain alphanumeric characters.',
            'model.required_without'     => 'Please specify the target motorcycle make and model (e.g. Yamaha R6 2018 or Universal).',
            'model.min'                  => 'The motorcycle model must be at least 2 characters long.',
            'model.regex'                => 'The motorcycle model must contain alphanumeric characters.',
            'quantity.required'          => 'Please specify the desired quantity.',
            'quantity.min'               => 'Pre-order quantity must be at least 1 unit.',
            'quantity.max'               => 'Pre-order quantity cannot exceed 100 units per request.',
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
