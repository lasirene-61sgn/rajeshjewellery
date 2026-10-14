<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'craftsman_id' => ['nullable', 'exists:craftsmen,id'],
            'image' => ['nullable', 'image', 'mimes:jpg,svg,png,webp,gif,jpeg|max:5048'],
            'reference_number' => ['nullable', 'string', 'max:250'],
            'product_name' => ['nullable', 'string'],
            'weight' => ['nullable', 'string'],
            'size' => ['nullable', 'string'],
            'length' => ['nullable', 'string'],
            'quantity' => ['nullable', 'string'],
            'order_type' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'created_at' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
        ];
    }
}
