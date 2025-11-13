<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\LanguageRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\UrlRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Lunar\Models\Product;
use Lunar\Models\Url;

final readonly class ProductUrlService
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private UrlRepositoryInterface $urlRepository,
        private LanguageRepositoryInterface $languageRepository
    ) {}

    /**
     * Get all URLs for a product.
     */
    public function getProductUrls(string $productId): Collection
    {
        return $this->urlRepository->getByElement(Product::class, $productId);
    }

    /**
     * Get default URL for a product and language.
     */
    public function getDefaultUrl(string $productId, string $languageCode): ?Url
    {
        $language = $this->languageRepository->findByCode($languageCode);

        if (! $language) {
            return null;
        }

        return $this->urlRepository->getDefaultForElement(Product::class, $productId, $language->id);
    }

    /**
     * Create a new URL for a product.
     */
    public function createUrl(string $productId, array $data): Url
    {
        $product = $this->productRepository->find($productId);

        if (! $product) {
            throw new ModelNotFoundException('Product not found');
        }

        // Check if slug exists
        if ($this->urlRepository->slugExists($data['slug'], $data['language_id'])) {
            throw new \InvalidArgumentException('Slug already exists for this language');
        }

        // If this is set as default, unset other defaults
        if ($data['default'] ?? false) {
            $this->unsetDefaultUrls($productId, $data['language_id']);
        }

        return $this->urlRepository->create([
            'element_type' => Product::class,
            'element_id' => $productId,
            'slug' => $data['slug'],
            'language_id' => $data['language_id'],
            'default' => $data['default'] ?? false,
        ]);
    }

    /**
     * Update a URL.
     */
    public function updateUrl(int $urlId, array $data): Url
    {
        $url = $this->urlRepository->find($urlId);

        if (! $url) {
            throw new ModelNotFoundException('URL not found');
        }

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

        $deleted = $this->urlRepository->delete($urlId);

        // If the deleted URL was default, promote another URL
        if ($deleted && $wasDefault) {
            $this->promoteNextUrlToDefault($elementId, $languageId);
        }

        return $deleted;
    }

    /**
     * Set a URL as default.
     */
    public function setAsDefault(int $urlId): Url
    {
        return $this->urlRepository->setAsDefault($urlId);
    }

    /**
     * Generate unique slug for a product.
     */
    public function generateSlug(string $name, int $languageId): string
    {
        return $this->urlRepository->generateUniqueSlug($name, $languageId);
    }

    /**
     * Unset all default URLs for a product and language.
     */
    private function unsetDefaultUrls(string $productId, int $languageId): void
    {
        $urls = $this->urlRepository->getByElement(Product::class, $productId);

        foreach ($urls as $url) {
            if ($url->language_id === $languageId && $url->default) {
                $this->urlRepository->update($url->id, ['default' => false]);
            }
        }
    }

    /**
     * Promote the next URL to default after deletion.
     */
    private function promoteNextUrlToDefault(string $elementId, int $languageId): void
    {
        $urls = $this->urlRepository->getByElement(Product::class, $elementId);

        $nextUrl = $urls->first(fn ($url) => $url->language_id === $languageId);

        if ($nextUrl) {
            $this->urlRepository->update($nextUrl->id, ['default' => true]);
        }
    }
}
