<?php

declare(strict_types=1);

namespace App\DTOs;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final readonly class CreateProductVariantDTO
{
    public function __construct(
        public string $sku,
        public int $stock,
        public string $purchasable,
        public float $price,
        public ?int $unitQuantity = 1,
        public ?int $taxClassId = null,
        public ?int $backorder = 0,
        public ?array $values = null,
    ) {}

    /**
     * Create DTO from request data.
     */
    public static function fromRequest(array $data): self
    {
        $validator = Validator::make($data, [
            'sku' => 'required|string|max:255',
            'stock' => 'required|integer|min:0',
            'purchasable' => 'required|string|in:always,in_stock,backorder',
            'price' => 'required|numeric|min:0',
            'unit_quantity' => 'nullable|integer|min:1',
            'tax_class_id' => 'nullable|integer|exists:tax_classes,id',
            'backorder' => 'nullable|integer|min:0',
            'values' => 'nullable|array',
            'values.*' => 'integer|exists:product_option_values,id',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();

        return new self(
            sku: $validated['sku'],
            stock: $validated['stock'],
            purchasable: $validated['purchasable'],
            price: (float) $validated['price'],
            unitQuantity: $validated['unit_quantity'] ?? 1,
            taxClassId: $validated['tax_class_id'] ?? null,
            backorder: $validated['backorder'] ?? 0,
            values: $validated['values'] ?? null,
        );
    }

    /**
     * Convert DTO to array.
     */
    public function toArray(): array
    {
        return [
            'sku' => $this->sku,
            'stock' => $this->stock,
            'purchasable' => $this->purchasable,
            'price' => $this->price,
            'unit_quantity' => $this->unitQuantity,
            'tax_class_id' => $this->taxClassId,
            'backorder' => $this->backorder,
            'values' => $this->values,
        ];
    }
}
