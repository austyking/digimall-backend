<?php

declare(strict_types=1);

namespace App\DTOs;

use Illuminate\Http\Request;

final readonly class UpdateTenantDTO
{
    public function __construct(
        public readonly ?string $displayName = null,
        public readonly ?string $description = null,
        public readonly ?bool $active = null,
    ) {}

    /**
     * Create DTO from HTTP request.
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            displayName: $request->input('display_name'),
            description: $request->input('description'),
            active: $request->has('active') ? $request->boolean('active') : null,
        );
    }

    /**
     * Convert DTO to array for model update.
     */
    public function toArray(): array
    {
        return array_filter([
            'display_name' => $this->displayName,
            'description' => $this->description,
            'active' => $this->active,
        ], fn ($value) => $value !== null);
    }
}
