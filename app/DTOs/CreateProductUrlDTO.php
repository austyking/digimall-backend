<?php

declare(strict_types=1);

namespace App\DTOs;

class CreateProductUrlDTO
{
    /**
     * Create a new DTO instance.
     */
    public function __construct(
        public readonly string $slug,
        public readonly int $languageId,
        public readonly bool $default = false
    ) {}

    /**
     * Convert the DTO to an array.
     */
    public function toArray(): array
    {
        return [
            'slug' => $this->slug,
            'language_id' => $this->languageId,
            'default' => $this->default,
        ];
    }

    /**
     * Create DTO from request data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            slug: $data['slug'],
            languageId: (int) $data['language_id'],
            default: (bool) ($data['default'] ?? false)
        );
    }
}
