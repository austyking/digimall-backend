<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // TODO: Add proper authorization logic based on user roles
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $productId = $this->route('product');

        return [
            'product_type_id' => ['sometimes', 'string', 'exists:product_types,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', 'string', 'in:draft,published'],
            'brand_id' => ['nullable', 'string', 'exists:brands,id'],
            'attribute_data' => ['sometimes', 'array'],
            'attribute_data.name' => ['sometimes', 'string', 'max:255'],
            'attribute_data.description' => ['nullable', 'string'],
            'attribute_data.short_description' => ['nullable', 'string'],
            'attribute_data.sku' => ['sometimes', 'string', 'unique:products,sku,'.$productId],
            'attribute_data.images' => ['nullable', 'array'],
            'attribute_data.images.*' => ['string', 'url'],
            'variants' => ['nullable', 'array'],
            'variants.*.name' => ['sometimes', 'string'],
            'variants.*.sku' => ['sometimes', 'string', 'unique:product_variants,sku'],
            'variants.*.price' => ['sometimes', 'numeric', 'min:0'],
            'variants.*.stock' => ['sometimes', 'integer', 'min:0'],
            'variants.*.purchasable' => ['string', 'in:always,in_stock,backorder'],
            'variants.*.backorder' => ['integer', 'min:0'],
            'collections' => ['nullable', 'array'],
            'collections.*' => ['string', 'exists:collections,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'exists:tags,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'product_type_id.exists' => 'Selected product type does not exist.',
            'name.max' => 'Product name must not exceed 255 characters.',
            'status.in' => 'Product status must be either draft or published.',
            'brand_id.exists' => 'Selected brand does not exist.',
            'attribute_data.name.max' => 'Product name in attribute data must not exceed 255 characters.',
            'attribute_data.sku.unique' => 'Product SKU must be unique.',
            'variants.*.name.required' => 'Variant name is required.',
            'variants.*.sku.unique' => 'Variant SKU must be unique.',
            'variants.*.price.min' => 'Variant price must be greater than or equal to 0.',
            'variants.*.stock.min' => 'Variant stock must be greater than or equal to 0.',
            'collections.*.exists' => 'Selected collection does not exist.',
            'tags.*.exists' => 'Selected tag does not exist.',
        ];
    }
}
