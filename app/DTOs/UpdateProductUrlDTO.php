<?php

declare(strict_types=1);

namespace App\DTOs;

class UpdateProductUrlDTO
{
    /**
     * Create a new DTO instance.
     */
    public function __construct(
        public readonly ?string $slug = null,
        public readonly ?bool $default = null
    ) {}

    /**
     * Convert the DTO to an array (only non-null values).
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->slug !== null) {
            $data['slug'] = $this->slug;
        }

        if ($this->default !== null) {
            $data['default'] = $this->default;
        }

        return $data;
    }

    /**
     * Create DTO from request data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            slug: $data['slug'] ?? null,
            default: isset($data['default']) ? (bool) $data['default'] : null
        );
    }
}
