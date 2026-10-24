<?php

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'owner';
    }

    public function rules(): array
    {
        return [
            'cart_data'       => 'required|json',
            'amount_tendered' => ['required', 'numeric', 'min:0', 'max:999999', 'regex:/^\d+(\.\d{1,2})?$/'],
            // Total shown on the POS screen; optional so other callers keep working unchanged.
            'expected_total'  => ['nullable', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,2})?$/'],
        ];
    }
}
