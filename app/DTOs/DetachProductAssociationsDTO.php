<?php

declare(strict_types=1);

namespace App\DTOs;

class DetachProductAssociationsDTO
{
    /**
     * Create a new DTO instance.
     */
    public function __construct(
        public readonly array $productIds,
        public readonly ?string $type = null
    ) {}

    /**
     * Convert the DTO to an array.
     */
    public function toArray(): array
    {
        $data = [
            'product_ids' => $this->productIds,
        ];

        if ($this->type !== null) {
            $data['type'] = $this->type;
        }

        return $data;
    }

    /**
     * Create DTO from request data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            productIds: array_map('intval', $data['product_ids']),
            type: $data['type'] ?? null
        );
    }
}
