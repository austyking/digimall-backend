<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class UpdateProductInventoryDTO
{
    public function __construct(
        public int $variantId,
        public string $action,
        public int $quantity,
    ) {}

    /**
     * Create DTO from request data.
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            variantId: (int) $data['variant_id'],
            action: $data['action'],
            quantity: $data['quantity'],
        );
    }

    /**
     * Convert DTO to array.
     */
    public function toArray(): array
    {
        return [
            'variant_id' => $this->variantId,
            'action' => $this->action,
            'quantity' => $this->quantity,
        ];
    }
}
