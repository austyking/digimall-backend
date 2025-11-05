<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class DeactivateTenantDTO
{
    public function __construct(
        public string $reason,
        public ?string $deactivatedBy = null,
    ) {}

    /**
     * Create DTO from array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            reason: $data['reason'] ?? 'Deactivated by administrator',
            deactivatedBy: $data['deactivated_by'] ?? null,
        );
    }

    /**
     * Convert DTO to array.
     */
    public function toArray(): array
    {
        return [
            'reason' => $this->reason,
            'deactivated_by' => $this->deactivatedBy,
        ];
    }
}
