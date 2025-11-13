<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;
use Lunar\Models\Url;

interface UrlRepositoryInterface
{
    /**
     * Find a URL by ID.
     */
    public function find(int $id): ?Url;

    /**
     * Get all URLs for an element (Product).
     */
    public function getByElement(string $elementType, string $elementId): Collection;

    /**
     * Get default URL for an element and language.
     */
    public function getDefaultForElement(string $elementType, string $elementId, int $languageId): ?Url;

    /**
     * Create a new URL.
     */
    public function create(array $data): Url;

    /**
     * Update a URL.
     */
    public function update(int $id, array $data): Url;

    /**
     * Delete a URL.
     */
    public function delete(int $id): bool;

    /**
     * Check if slug exists for a language.
     */
    public function slugExists(string $slug, int $languageId, ?int $excludeId = null): bool;

    /**
     * Set URL as default for its language and element.
     */
    public function setAsDefault(int $id): Url;

    /**
     * Generate unique slug from name.
     */
    public function generateUniqueSlug(string $name, int $languageId): string;
}
