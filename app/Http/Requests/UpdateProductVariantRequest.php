<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation for updating a product variant.
 */
final class UpdateProductVariantRequest extends FormRequest
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
        $variantId = $this->route('variantId');

        return [
            'sku' => ['sometimes', 'string', 'max:255', 'unique:lunar_product_variants,sku,'.$variantId],
            'stock' => ['sometimes', 'integer', 'min:0'],
            'purchasable' => ['sometimes', 'boolean'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'unit_quantity' => ['sometimes', 'integer', 'min:1'],
            'tax_class_id' => ['sometimes', 'string', 'exists:lunar_tax_classes,id'],
            'backorder' => ['sometimes', 'boolean'],
            'values' => ['sometimes', 'array'], // Option values for variant
            'values.*' => ['sometimes', 'string'],
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
            'sku.unique' => 'This SKU is already in use.',
            'stock.integer' => 'Stock must be a whole number.',
            'stock.min' => 'Stock cannot be negative.',
            'price.numeric' => 'Price must be a number.',
            'price.min' => 'Price cannot be negative.',
            'unit_quantity.min' => 'Unit quantity must be at least 1.',
            'tax_class_id.exists' => 'Invalid tax class.',
        ];
    }
}
