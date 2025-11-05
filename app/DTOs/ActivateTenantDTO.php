<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class ActivateTenantDTO
{
    public function __construct(
        public string $reason,
        public ?string $activatedBy = null,
    ) {}

    /**
     * Create DTO from array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            reason: $data['reason'] ?? 'Activated by administrator',
            activatedBy: $data['activated_by'] ?? null,
        );
    }

    /**
     * Convert DTO to array.
     */
    public function toArray(): array
    {
        return [
            'reason' => $this->reason,
            'activated_by' => $this->activatedBy,
        ];
    }
}
