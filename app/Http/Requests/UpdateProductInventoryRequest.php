<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation for updating product inventory.
 */
final class UpdateProductInventoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'variant_id' => ['required', 'integer'],
            'action' => ['required', 'string', 'in:set,increment,decrement'],
            'quantity' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'variant_id.required' => 'Variant ID is required.',
            'variant_id.integer' => 'Variant ID must be an integer.',
            'action.required' => 'Action is required.',
            'action.in' => 'Action must be one of: set, increment, decrement.',
            'quantity.required' => 'Quantity is required.',
            'quantity.integer' => 'Quantity must be an integer.',
            'quantity.min' => 'Quantity cannot be negative.',
        ];
    }
}
