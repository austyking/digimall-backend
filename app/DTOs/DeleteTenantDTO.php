<?php

declare(strict_types=1);

namespace App\DTOs;

use Illuminate\Http\Request;

final readonly class DeleteTenantDTO
{
    public function __construct(
        public string $tenantId,
        public string $reason,
        public bool $force = false,
        public ?string $deletedBy = null,
    ) {}

    /**
     * Create DTO from HTTP request.
     */
    public static function fromRequest(Request $request, string $tenantId): self
    {
        $user = $request->user();
        $userId = $user?->id;

        return new self(
            tenantId: $tenantId,
            reason: $request->input('reason'),
            force: $request->boolean('force', false),
            deletedBy: $userId,
        );
    }

    /**
     * Convert DTO to array for audit logging.
     */
    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'reason' => $this->reason,
            'force' => $this->force,
            'deleted_by' => $this->deletedBy,
            'deleted_at' => now()->toISOString(),
        ];
    }
}
