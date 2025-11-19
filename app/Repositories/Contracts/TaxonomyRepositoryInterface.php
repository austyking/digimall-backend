<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Lunar\Models\Attribute;
use Lunar\Models\Brand;
use Lunar\Models\Collection;
use Lunar\Models\Tag;

/**
 * Taxonomy Repository Interface
 */
interface TaxonomyRepositoryInterface
{
    // Categories (Collections)
    public function getCategoriesPaginated(int $page = 1, int $perPage = 15, ?string $search = null, string $sortBy = 'created_at', string $sortDirection = 'desc'): LengthAwarePaginator;

    public function findCategory(int $id): ?Collection;

    public function createCategory(array $data): Collection;

    public function updateCategory(Collection $category, array $data): Collection;

    public function deleteCategory(Collection $category): bool;

    public function getCategoryTree(): EloquentCollection;

    // Brands
    public function getBrandsPaginated(int $page = 1, int $perPage = 15, ?string $search = null, string $sortBy = 'created_at', string $sortDirection = 'desc'): LengthAwarePaginator;

    public function findBrand(int $id): ?Brand;

    public function createBrand(array $data): Brand;

    public function updateBrand(Brand $brand, array $data): Brand;

    public function deleteBrand(Brand $brand): bool;

    // Attributes
    public function getAttributesPaginated(int $page = 1, int $perPage = 15, ?string $search = null, ?string $type = null, string $sortBy = 'created_at', string $sortDirection = 'desc'): LengthAwarePaginator;

    public function findAttribute(int $id): ?Attribute;

    public function createAttribute(array $data): Attribute;

    public function updateAttribute(Attribute $attribute, array $data): Attribute;

    public function deleteAttribute(Attribute $attribute): bool;

    public function isAttributeUsed(Attribute $attribute): bool;

    // Tags
    public function getTagsPaginated(int $page = 1, int $perPage = 15, ?string $search = null, string $sortBy = 'created_at', string $sortDirection = 'desc'): LengthAwarePaginator;

    public function findTag(int $id): ?Tag;

    public function createTag(array $data): Tag;

    public function updateTag(Tag $tag, array $data): Tag;

    public function deleteTag(Tag $tag): bool;

    public function isTagUsed(Tag $tag): bool;
}
