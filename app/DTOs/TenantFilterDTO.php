<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class TenantFilterDTO
{
    public function __construct(
        public ?bool $active = null,
        public ?string $search = null,
        public ?string $sortBy = 'created_at',
        public ?string $sortDirection = 'desc',
        public ?int $perPage = 15,
    ) {}

    /**
     * Create DTO from request data.
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            active: isset($data['active']) ? filter_var($data['active'], FILTER_VALIDATE_BOOLEAN) : null,
            search: $data['search'] ?? null,
            sortBy: $data['sort_by'] ?? 'created_at',
            sortDirection: $data['sort_direction'] ?? 'desc',
            perPage: isset($data['per_page']) ? (int) $data['per_page'] : 15,
        );
    }

    /**
     * Convert DTO to array.
     */
    public function toArray(): array
    {
        return [
            'active' => $this->active,
            'search' => $this->search,
            'sort_by' => $this->sortBy,
            'sort_direction' => $this->sortDirection,
            'per_page' => $this->perPage,
        ];
    }
}
