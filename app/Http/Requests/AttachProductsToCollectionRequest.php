<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation for attaching/detaching products to/from collections.
 */
final class AttachProductsToCollectionRequest extends FormRequest
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
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['required', 'string', 'exists:products,id'],
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
            'product_ids.required' => 'At least one product ID is required.',
            'product_ids.array' => 'Product IDs must be an array.',
            'product_ids.min' => 'At least one product ID is required.',
            'product_ids.*.required' => 'Each product ID is required.',
            'product_ids.*.string' => 'Each product ID must be a string.',
            'product_ids.*.exists' => 'One or more product IDs are invalid.',
        ];
    }
}
