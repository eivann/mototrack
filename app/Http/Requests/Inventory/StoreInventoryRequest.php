<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'owner';
    }

    public function rules(): array
    {
        return [
            'sku'       => 'nullable|unique:inventory_items,sku',
            'category'  => 'required|string|max:255',
            'part_name' => 'required|string|max:255',
            'brand'     => 'required|string|max:255',
            'model'     => 'required|string|max:255',
            'quantity'  => 'required|integer|min:0',
            'price'     => 'required|numeric|min:0',
            'notes'     => 'nullable|string|max:1000',
            'image'     => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ];
    }
}
