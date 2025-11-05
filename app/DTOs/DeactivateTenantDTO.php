<?php

declare(strict_types=1);

namespace App\DTOs;

use Illuminate\Http\Request;

final readonly class DeactivateTenantDTO
{
    public function __construct(
        public string $tenantId,
        public string $reason,
        public ?string $deactivatedBy = null,
    ) {}

    /**
     * Create DTO from request.
     */
    public static function fromRequest(Request $request, string $tenantId): self
    {
        return new self(
            tenantId: $tenantId,
            reason: $request->input('reason', 'Deactivated by administrator'),
            deactivatedBy: $request->user()?->id,
        );
    }

    /**
     * Create DTO from array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: $data['tenant_id'],
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
