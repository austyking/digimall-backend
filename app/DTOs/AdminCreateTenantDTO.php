<?php

declare(strict_types=1);

namespace App\DTOs;

use Illuminate\Http\Request;

final readonly class AdminCreateTenantDTO
{
    public function __construct(
        public string $name,
        public string $displayName,
        public ?string $description = null,
        public bool $active = true,
        public array $settings = [],
        public ?string $createdBy = null,
    ) {}

    /**
     * Create DTO from HTTP request.
     */
    public static function fromRequest(Request $request): self
    {
        $user = $request->user();
        $userId = $user?->id;

        return new self(
            name: $request->input('name'),
            displayName: $request->input('display_name'),
            description: $request->input('description'),
            active: $request->boolean('active', true),
            settings: $request->input('settings', []),
            createdBy: $userId,
        );
    }

    /**
     * Convert DTO to array for model creation.
     */
    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'display_name' => $this->displayName,
            'description' => $this->description,
            'status' => $this->active ? 'active' : 'inactive',
            'settings' => $this->settings,
        ];

        // Add audit trail
        if ($this->createdBy) {
            $data['settings']['created_by'] = [
                'user_id' => $this->createdBy,
                'at' => now()->toISOString(),
            ];
        }

        return $data;
    }
}
