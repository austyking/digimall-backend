<?php

declare(strict_types=1);

namespace App\DTOs;

use Lunar\FieldTypes\Text;

/**
 * Data Transfer Object for updating a product.
 *
 * Encapsulates all optional data for updating an existing product.
 * Only non-null fields will be updated.
 */
final readonly class UpdateProductDTO
{
    public function __construct(
        // Optional update fields
        public ?int $productTypeId = null,
        public ?string $name = null,
        public ?string $status = null,
        public ?int $brandId = null,
        public ?string $description = null,
        public ?array $attributeData = null,
        public ?array $tags = null,
        public ?array $images = null,
        public ?string $sku = null,
        public ?float $price = null,
        public ?int $stock = null,
        public ?array $metadata = null,
    ) {}

    /**
     * Create DTO from request data.
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            productTypeId: isset($data['product_type_id']) ? (int) $data['product_type_id'] : null,
            name: $data['name'] ?? null,
            status: $data['status'] ?? null,
            brandId: isset($data['brand_id']) ? (int) $data['brand_id'] : null,
            description: $data['description'] ?? null,
            attributeData: $data['attribute_data'] ?? null,
            tags: $data['tags'] ?? null,
            images: $data['images'] ?? null,
            sku: $data['sku'] ?? null,
            price: isset($data['price']) ? (float) $data['price'] : null,
            stock: isset($data['stock']) ? (int) $data['stock'] : null,
            metadata: $data['metadata'] ?? null,
        );
    }

    /**
     * Convert DTO to array, excluding null values.
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->productTypeId !== null) {
            $data['product_type_id'] = $this->productTypeId;
        }

        if ($this->status !== null) {
            $data['status'] = $this->status;
        }

        if ($this->brandId !== null) {
            $data['brand_id'] = $this->brandId;
        }

        // Build attribute_data updates with FieldType objects
        $attributeData = [];

        if ($this->name !== null) {
            $attributeData['name'] = new Text($this->name);
        }

        if ($this->description !== null) {
            $attributeData['description'] = new Text($this->description);
        }

        if ($this->sku !== null) {
            $attributeData['sku'] = new Text($this->sku);
        }

        // Merge with provided attribute_data (convert to FieldType objects)
        if ($this->attributeData) {
            foreach ($this->attributeData as $key => $value) {
                if (is_string($value)) {
                    $attributeData[$key] = new Text($value);
                } else {
                    $attributeData[$key] = $value;
                }
            }
        }

        if (! empty($attributeData)) {
            $data['attribute_data'] = $attributeData;
        }

        return $data;
    }

    /**
     * Check if DTO has any data to update.
     */
    public function hasData(): bool
    {
        return ! empty($this->toArray());
    }

    /**
     * Validate the DTO data.
     */
    public function validate(): bool
    {
        if ($this->status !== null && ! in_array($this->status, ['draft', 'published', 'archived'])) {
            return false;
        }

        return true;
    }
}
