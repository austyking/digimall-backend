<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Repositories\Contracts\UrlRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Lunar\Models\Url;

final class UrlRepository implements UrlRepositoryInterface
{
    /**
     * Find a URL by ID.
     */
    public function find(int $id): ?Url
    {
        return Url::query()->find($id);
    }

    /**
     * Get all URLs for an element (Product).
     */
    public function getByElement(string $elementType, string $elementId): Collection
    {
        return Url::query()
            ->where('element_type', $elementType)
            ->where('element_id', $elementId)
            ->with('language')
            ->get();
    }

    /**
     * Get default URL for an element and language.
     */
    public function getDefaultForElement(string $elementType, string $elementId, int $languageId): ?Url
    {
        return Url::query()
            ->where('element_type', $elementType)
            ->where('element_id', $elementId)
            ->where('language_id', $languageId)
            ->where('default', true)
            ->first();
    }

    /**
     * Create a new URL.
     */
    public function create(array $data): Url
    {
        return Url::query()->create($data);
    }

    /**
     * Update a URL.
     */
    public function update(int $id, array $data): Url
    {
        $url = $this->find($id);

        if (! $url) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException('URL not found');
        }

        $url->update($data);

        return $url->fresh(['language']);
    }

    /**
     * Delete a URL.
     */
    public function delete(int $id): bool
    {
        $url = $this->find($id);

        if (! $url) {
            return false;
        }

        return (bool) $url->delete();
    }

    /**
     * Check if slug exists for a language.
     */
    public function slugExists(string $slug, int $languageId, ?int $excludeId = null): bool
    {
        $query = Url::query()
            ->where('slug', $slug)
            ->where('language_id', $languageId);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Set URL as default for its language and element.
     */
    public function setAsDefault(int $id): Url
    {
        $url = $this->find($id);

        if (! $url) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException('URL not found');
        }

        // Unset all other URLs as default for this element and language
        Url::query()
            ->where('element_type', $url->element_type)
            ->where('element_id', $url->element_id)
            ->where('language_id', $url->language_id)
            ->where('id', '!=', $id)
            ->update(['default' => false]);

        // Set this URL as default
        $url->update(['default' => true]);

        return $url->fresh(['language']);
    }

    /**
     * Generate unique slug from name.
     */
    public function generateUniqueSlug(string $name, int $languageId): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while ($this->slugExists($slug, $languageId)) {
            $slug = $originalSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
