<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Data Transfer Object for creating a new product.
 *
 * Encapsulates all required and optional data for creating a new product.
 * Following the DTO pattern for type-safe data passing between layers.
 */
final readonly class CreateProductDTO
{
    public function __construct(
        // Required fields
        public int $productTypeId,
        public string $name,
        public string $status,
        public string $vendorId,

        // Optional fields
        public ?int $brandId = null,
        public ?string $description = null,
        public ?array $attributeData = null,
        public ?array $tags = null,
        public ?array $images = null,
        public ?string $sku = null,

        // Pricing and inventory (handled by variants, but can be set here for simple products)
        public ?float $price = null,
        public ?int $stock = null,

        // Metadata
        public ?array $metadata = null,
    ) {}

    /**
     * Create DTO from request data.
     */
    public static function fromRequest(array $data, string $vendorId): self
    {
        return new self(
            productTypeId: (int) $data['product_type_id'],
            name: $data['name'],
            status: $data['status'] ?? 'draft',
            vendorId: $vendorId,
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
     * Convert DTO to array for repository.
     */
    public function toArray(): array
    {
        $data = [
            'product_type_id' => $this->productTypeId,
            'status' => $this->status,
            'vendor_id' => $this->vendorId,
        ];

        // Build attribute_data for Lunar
        $attributeData = [
            'name' => $this->name,
        ];

        if ($this->description) {
            $attributeData['description'] = $this->description;
        }

        if ($this->sku) {
            $attributeData['sku'] = $this->sku;
        }

        // Merge with provided attribute_data
        if ($this->attributeData) {
            $attributeData = array_merge($attributeData, $this->attributeData);
        }

        $data['attribute_data'] = $attributeData;

        if ($this->brandId) {
            $data['brand_id'] = $this->brandId;
        }

        return $data;
    }

    /**
     * Validate the DTO data.
     */
    public function validate(): bool
    {
        return ! empty($this->productTypeId) &&
               ! empty($this->name) &&
               in_array($this->status, ['draft', 'published', 'archived']);
    }
}
