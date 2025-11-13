<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Data Transfer Object for product filtering and searching.
 *
 * Encapsulates all filter criteria for product queries.
 */
final readonly class ProductFilterDTO
{
    public function __construct(
        public ?string $query = null,
        public ?string $status = null,
        public ?int $brandId = null,
        public ?int $productTypeId = null,
        public ?string $vendorId = null,
        public ?string $collectionId = null,
        public ?array $tags = null,
        public ?float $minPrice = null,
        public ?float $maxPrice = null,
        public ?bool $inStock = null,
        public ?int $limit = null,
        public ?int $offset = null,
        public ?string $sortBy = null,
        public ?string $sortDirection = 'asc',
    ) {}

    /**
     * Create DTO from request data.
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            query: $data['q'] ?? $data['query'] ?? null,
            status: $data['status'] ?? null,
            brandId: isset($data['brand_id']) ? (int) $data['brand_id'] : null,
            productTypeId: isset($data['product_type_id']) ? (int) $data['product_type_id'] : null,
            vendorId: $data['vendor_id'] ?? null,
            collectionId: $data['collection_id'] ?? null,
            tags: $data['tags'] ?? null,
            minPrice: isset($data['min_price']) ? (float) $data['min_price'] : null,
            maxPrice: isset($data['max_price']) ? (float) $data['max_price'] : null,
            inStock: isset($data['in_stock']) ? (bool) $data['in_stock'] : null,
            limit: isset($data['limit']) ? (int) $data['limit'] : null,
            offset: isset($data['offset']) ? (int) $data['offset'] : null,
            sortBy: $data['sort_by'] ?? null,
            sortDirection: in_array(strtolower($data['sort_direction'] ?? ''), ['asc', 'desc'])
                ? strtolower($data['sort_direction'])
                : 'asc',
        );
    }

    /**
     * Check if any filters are applied.
     */
    public function hasFilters(): bool
    {
        return $this->query !== null ||
               $this->status !== null ||
               $this->brandId !== null ||
               $this->productTypeId !== null ||
               $this->vendorId !== null ||
               $this->collectionId !== null ||
               $this->tags !== null ||
               $this->minPrice !== null ||
               $this->maxPrice !== null ||
               $this->inStock !== null;
    }

    /**
     * Validate the filter criteria.
     */
    public function validate(): bool
    {
        if ($this->status !== null && ! in_array($this->status, ['draft', 'published', 'archived'])) {
            return false;
        }

        if ($this->sortDirection !== null && ! in_array($this->sortDirection, ['asc', 'desc'])) {
            return false;
        }

        if ($this->limit !== null && $this->limit < 1) {
            return false;
        }

        if ($this->offset !== null && $this->offset < 0) {
            return false;
        }

        return true;
    }
}
