<?php

declare(strict_types=1);

namespace App\DTOs;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final readonly class UpdateProductVariantDTO
{
    public function __construct(
        public ?string $sku = null,
        public ?int $stock = null,
        public ?string $purchasable = null,
        public ?float $price = null,
        public ?int $unitQuantity = null,
        public ?int $taxClassId = null,
        public ?int $backorder = null,
        public ?array $values = null,
    ) {}

    /**
     * Create DTO from request data.
     */
    public static function fromRequest(array $data): self
    {
        $validator = Validator::make($data, [
            'sku' => 'sometimes|required|string|max:255',
            'stock' => 'sometimes|required|integer|min:0',
            'purchasable' => 'sometimes|required|string|in:always,in_stock,backorder',
            'price' => 'sometimes|required|numeric|min:0',
            'unit_quantity' => 'nullable|integer|min:1',
            'tax_class_id' => 'nullable|integer|exists:lunar_tax_classes,id',
            'backorder' => 'nullable|integer|min:0',
            'values' => 'nullable|array',
            'values.*' => 'integer|exists:lunar_product_option_values,id',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();

        return new self(
            sku: $validated['sku'] ?? null,
            stock: $validated['stock'] ?? null,
            purchasable: $validated['purchasable'] ?? null,
            price: isset($validated['price']) ? (float) $validated['price'] : null,
            unitQuantity: $validated['unit_quantity'] ?? null,
            taxClassId: $validated['tax_class_id'] ?? null,
            backorder: $validated['backorder'] ?? null,
            values: $validated['values'] ?? null,
        );
    }

    /**
     * Convert DTO to array (only non-null values).
     */
    public function toArray(): array
    {
        return array_filter([
            'sku' => $this->sku,
            'stock' => $this->stock,
            'purchasable' => $this->purchasable,
            'price' => $this->price,
            'unit_quantity' => $this->unitQuantity,
            'tax_class_id' => $this->taxClassId,
            'backorder' => $this->backorder,
            'values' => $this->values,
        ], fn ($value) => $value !== null);
    }
}
