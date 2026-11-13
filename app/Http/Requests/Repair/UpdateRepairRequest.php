<?php

namespace App\Http\Requests\Repair;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRepairRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'owner';
    }

    public function rules(): array
    {
        return [
            'status'            => 'required|string|in:pending,ongoing,done,completed',
            'estimated_minutes' => 'nullable|integer|min:5|max:1440',
            'customer_id'       => 'nullable|exists:customers,id',
            'customer_name'     => 'sometimes|required_without:customer_id|nullable|string|max:255',
            'bike_model'        => 'sometimes|required|string|max:255',
            'issue'             => 'sometimes|required|string|max:1000',
            'cost'              => 'nullable|numeric|min:0|max:9999999.99',
        ];
    }
}
