<?php

declare(strict_types=1);

namespace App\DTOs;

use Illuminate\Http\Request;

final readonly class CreateTenantDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $displayName,
        public readonly ?string $description = null,
        public readonly bool $active = true,
        public readonly array $settings = [],
    ) {}

    /**
     * Create DTO from HTTP request.
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->input('name'),
            displayName: $request->input('display_name'),
            description: $request->input('description'),
            active: $request->boolean('active', true),
            settings: $request->input('settings', []),
        );
    }

    /**
     * Convert DTO to array for model creation.
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'display_name' => $this->displayName,
            'description' => $this->description,
            'active' => $this->active,
            'settings' => $this->settings,
        ];
    }
}
