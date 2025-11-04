<?php

declare(strict_types=1);

namespace App\DTOs;

use Illuminate\Http\Request;

final readonly class UpdateTenantSettingsDTO
{
    public function __construct(
        public readonly array $settings,
    ) {}

    /**
     * Create DTO from HTTP request.
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            settings: $request->input('settings', []),
        );
    }

    /**
     * Get settings array.
     */
    public function toArray(): array
    {
        return $this->settings;
    }
}
