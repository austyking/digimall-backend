<?php

declare(strict_types=1);

namespace App\DTOs;

use Illuminate\Http\Request;

/**
 * Data Transfer Object for creating a brand
 */
final readonly class CreateBrandDTO
{
    public function __construct(
        public string $name,
    ) {}

    /**
     * Create DTO from request
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->input('name'),
        );
    }

    /**
     * Convert to array for repository
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'attribute_data' => [
                'name' => new \Lunar\FieldTypes\Text($this->name),
            ],
        ];
    }
}
