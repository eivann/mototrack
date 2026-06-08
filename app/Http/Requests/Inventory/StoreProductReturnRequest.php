<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'owner';
    }

    public function rules(): array
    {
        return [
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'customer_id'       => 'nullable|exists:customers,id',
            'quantity'          => 'required|integer|min:1',
            'action_taken'      => 'required|string|in:restocked,discarded',
            'notes'             => 'nullable|string|max:1000',
        ];
    }
}
