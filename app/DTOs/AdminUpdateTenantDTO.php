<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class AdminUpdateTenantDTO
{
    public function __construct(
        public ?string $displayName = null,
        public ?string $subdomain = null,
        public ?string $description = null,
        public ?bool $active = null,
        public ?array $settings = null,
        public ?string $updatedBy = null,
    ) {}

    /**
     * Create DTO from array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            displayName: $data['display_name'] ?? null,
            subdomain: $data['subdomain'] ?? null,
            description: $data['description'] ?? null,
            active: isset($data['active']) ? (bool) $data['active'] : null,
            settings: $data['settings'] ?? null,
            updatedBy: $data['updated_by'] ?? null,
        );
    }

    /**
     * Convert DTO to array, excluding null values.
     */
    public function toArray(): array
    {
        return array_filter([
            'display_name' => $this->displayName,
            'subdomain' => $this->subdomain,
            'description' => $this->description,
            'active' => $this->active,
            'settings' => $this->settings,
            'updated_by' => $this->updatedBy,
        ], fn ($value) => $value !== null);
    }
}
