<?php

declare(strict_types=1);

namespace App\DTOs;

use Illuminate\Http\Request;

/**
 * Data Transfer Object for creating a tag
 */
final readonly class CreateTagDTO
{
    public function __construct(
        public string $value,
    ) {}

    /**
     * Create DTO from request
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            value: $request->input('value'),
        );
    }

    /**
     * Convert to array for repository
     */
    public function toArray(): array
    {
        return [
            'value' => $this->value,
        ];
    }
}
