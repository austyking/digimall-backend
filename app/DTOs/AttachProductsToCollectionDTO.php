<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class AttachProductsToCollectionDTO
{
    public function __construct(
        public string $collectionId,
        public array $productIds,
    ) {}

    /**
     * Create DTO from request data.
     */
    public static function fromRequest(string $collectionId, array $data): self
    {
        return new self(
            collectionId: $collectionId,
            productIds: $data['product_ids'] ?? [],
        );
    }

    /**
     * Convert DTO to array.
     */
    public function toArray(): array
    {
        return [
            'collection_id' => $this->collectionId,
            'product_ids' => $this->productIds,
        ];
    }
}
