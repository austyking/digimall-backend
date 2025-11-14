<?php

declare(strict_types=1);

namespace App\DTOs;

class AttachProductAssociationsDTO
{
    /**
     * Create a new DTO instance.
     */
    public function __construct(
        public readonly array $productIds,
        public readonly string $type
    ) {}

    /**
     * Convert the DTO to an array.
     */
    public function toArray(): array
    {
        return [
            'product_ids' => $this->productIds,
            'type' => $this->type,
        ];
    }

    /**
     * Create DTO from request data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            productIds: array_map('intval', $data['product_ids']),
            type: $data['type']
        );
    }
}
