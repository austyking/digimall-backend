<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation for updating product availability.
 */
final class UpdateProductAvailabilityRequest extends FormRequest
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
            'purchasable' => ['sometimes', 'string', 'in:always,in_stock,backorder'],
            'stock' => ['sometimes', 'integer', 'min:0'],
            'backorder' => ['sometimes', 'integer', 'min:0'],
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
            'purchasable.in' => 'Purchasable must be one of: always, in_stock, backorder.',
            'stock.integer' => 'Stock must be a whole number.',
            'stock.min' => 'Stock cannot be negative.',
            'backorder.integer' => 'Backorder must be a whole number.',
            'backorder.min' => 'Backorder cannot be negative.',
        ];
    }
}
