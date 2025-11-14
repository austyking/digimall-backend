<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\CreateProductUrlDTO;
use App\DTOs\UpdateProductUrlDTO;
use App\Repositories\Contracts\LanguageRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\UrlRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Lunar\Models\Product;
use Lunar\Models\Url;

final readonly class ProductUrlService
{
    public function __construct(
        private UrlRepositoryInterface $urlRepository,
    ) {}

    /**
     * Get all URLs for a product.
     */
    public function getUrlsForProduct(int $productId): Collection
    {
        return $this->urlRepository->findByElement($productId, Product::class);
    }

    /**
     * Get default URL for a product and language.
     */
    public function getDefaultUrl(int $productId, int $languageId): ?Url
    {
        return $this->urlRepository->getDefaultUrl($productId, Product::class, $languageId);
    }

    /**
     * Create a new URL for a product.
     */
    public function createUrl(int $productId, CreateProductUrlDTO $dto): Url
    {
        // Check if slug exists
        if ($this->urlRepository->slugExists($dto->slug, $dto->languageId)) {
            throw new \InvalidArgumentException('Slug already exists for this language');
        }

        // If this is set as default, unset other defaults
        if ($dto->default) {
            $this->unsetDefaultUrls($productId, $dto->languageId);
        }

        return $this->urlRepository->create([
            'element_type' => Product::class,
            'element_id' => $productId,
            'slug' => $dto->slug,
            'language_id' => $dto->languageId,
            'default' => $dto->default,
        ]);
    }

    /**
     * Update a URL.
     */
    public function updateUrl(int $urlId, UpdateProductUrlDTO $dto): Url
    {
        $url = $this->urlRepository->find($urlId);

        if (! $url) {
            throw new ModelNotFoundException('URL not found');
        }

        $data = $dto->toArray();

        // Check if slug exists (excluding current URL)
        if (isset($data['slug']) && $this->urlRepository->slugExists($data['slug'], $url->language_id, $urlId)) {
            throw new \InvalidArgumentException('Slug already exists for this language');
        }

        // If setting as default, unset other defaults
        if (($data['default'] ?? false) && ! $url->default) {
            $this->unsetDefaultUrls($url->element_id, $url->language_id);
        }

        return $this->urlRepository->update($urlId, $data);
    }

    /**
     * Delete a URL.
     */
    public function deleteUrl(int $urlId): bool
    {
        $url = $this->urlRepository->find($urlId);

        if (! $url) {
            throw new ModelNotFoundException('URL not found');
        }

        $wasDefault = $url->default;
        $elementId = $url->element_id;
        $languageId = $url->language_id;

        // If the URL was default, promote another URL BEFORE deleting
        if ($wasDefault) {
            $this->promoteNextUrlToDefault($urlId, $elementId, $languageId);
        }

        return $this->urlRepository->delete($urlId);
    }

    /**
     * Set a URL as default.
     */
    public function setAsDefault(int $urlId): Url
    {
        $url = $this->urlRepository->find($urlId);

        if (! $url) {
            throw new ModelNotFoundException('URL not found');
        }

        // Unset other defaults for this element and language
        $this->unsetDefaultUrls($url->element_id, $url->language_id);

        // Set this URL as default
        $this->urlRepository->update($urlId, ['default' => true]);
        $url->refresh ();
        return $url;
    }

    /**
     * Generate unique slug for a product.
     */
    public function generateSlug(string $name, int $languageId): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while ($this->urlRepository->slugExists($slug, $languageId)) {
            $slug = $originalSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Check if a slug is unique for a language.
     */
    public function isSlugUnique(string $slug, int $languageId, ?int $excludeId = null): bool
    {
        return ! $this->urlRepository->slugExists($slug, $languageId, $excludeId);
    }

    /**
     * Unset all default URLs for a product and language.
     */
    private function unsetDefaultUrls(int $productId, int $languageId): void
    {
        $urls = $this->urlRepository->findByElementAndLanguage($productId, Product::class, $languageId);

        foreach ($urls as $url) {
            if ($url->default) {
                $this->urlRepository->update($url->id, ['default' => false]);
            }
        }
    }

    /**
     * Promote the next URL to default after deletion.
     */
    private function promoteNextUrlToDefault(int $urlIdToDelete, int $elementId, int $languageId): void
    {
        $urls = $this->urlRepository->findByElementAndLanguage($elementId, Product::class, $languageId);

        // Find the next URL (excluding the one being deleted)
        $nextUrl = $urls->first(fn ($url) => $url->id !== $urlIdToDelete);

        if ($nextUrl) {
            $this->urlRepository->update($nextUrl->id, ['default' => true]);
        }
    }
}
