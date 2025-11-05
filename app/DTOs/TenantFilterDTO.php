<?php

declare(strict_types=1);

namespace App\DTOs;

use Illuminate\Http\Request;

final readonly class TenantFilterDTO
{
    public function __construct(
        public ?string $status = null,
        public ?string $search = null,
        public ?string $sortBy = 'created_at',
        public ?string $sortDirection = 'desc',
        public ?int $perPage = 15,
    ) {}

    /**
     * Create DTO from request data.
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            status: $request->input('status'),
            search: $request->input('search'),
            sortBy: $request->input('sort_by', 'created_at'),
            sortDirection: $request->input('sort_direction', 'desc'),
            perPage: (int) $request->input('per_page', 15),
        );
    }

    /**
     * Convert DTO to array.
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'search' => $this->search,
            'sort_by' => $this->sortBy,
            'sort_direction' => $this->sortDirection,
            'per_page' => $this->perPage,
        ];
    }
}
