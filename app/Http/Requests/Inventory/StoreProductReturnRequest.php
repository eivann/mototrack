<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (!$this->has('action_taken') && $this->has('condition')) {
            $this->merge([
                'action_taken' => $this->input('condition') === 'resellable' ? 'restocked' : 'discarded',
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'sale_id'           => 'nullable|exists:sales,id',
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'customer_id'       => 'nullable|exists:customers,id',
            'quantity'          => 'required|integer|min:1',
            'refund_amount'     => 'nullable|numeric|min:0|max:999999.00',
            'action_taken'      => 'required|string|in:restocked,discarded',
            'condition'         => 'nullable|string|in:resellable,damaged',
            'notes'             => 'nullable|string|max:1000',
        ];
    }
}
