<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\CreateAttributeDTO;
use App\DTOs\CreateBrandDTO;
use App\DTOs\CreateCategoryDTO;
use App\DTOs\CreateTagDTO;
use App\DTOs\UpdateAttributeDTO;
use App\DTOs\UpdateBrandDTO;
use App\DTOs\UpdateCategoryDTO;
use App\DTOs\UpdateTagDTO;
use App\Repositories\Contracts\TaxonomyRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Lunar\Models\Attribute;
use Lunar\Models\Brand;
use Lunar\Models\Collection;
use Lunar\Models\Tag;

/**
 * Taxonomy Service
 *
 * Handles business logic for taxonomy management:
 * - Categories (Collections)
 * - Brands
 * - Attributes
 * - Tags
 */
class TaxonomyService
{
    public function __construct(
        private TaxonomyRepositoryInterface $repository
    ) {}

    // ==================== CATEGORIES (COLLECTIONS) ====================

    /**
     * Get paginated categories with filtering and sorting
     */
    public function getCategoriesPaginated(
        int $page = 1,
        int $perPage = 15,
        ?string $search = null,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc'
    ): LengthAwarePaginator {
        return $this->repository->getCategoriesPaginated($page, $perPage, $search, $sortBy, $sortDirection);
    }

    /**
     * Create a new category
     */
    public function createCategory(CreateCategoryDTO $dto): Collection
    {
        Log::info('Creating category with data: '.json_encode($dto->toArray()));

        return $this->repository->createCategory($dto->toArray());
    }

    /**
     * Update an existing category
     */
    public function updateCategory(Collection $category, UpdateCategoryDTO $dto): Collection
    {
        return $this->repository->updateCategory($category, $dto->toArray());
    }

    /**
     * Delete a category
     */
    public function deleteCategory(Collection $category): bool
    {
        // Check if category has children
        if ($category->children()->exists()) {
            throw new \Exception('Cannot delete category with child categories. Please reassign or delete child categories first.');
        }

        // Check if category is used by products
        if ($category->products()->exists()) {
            throw new \Exception('Cannot delete category that is assigned to products. Please reassign products first.');
        }

        return $this->repository->deleteCategory($category);
    }

    /**
     * Get category tree structure
     */
    public function getCategoryTree(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->getCategoryTree();
    }

    // ==================== BRANDS ====================

    /**
     * Get paginated brands with filtering and sorting
     */
    public function getBrandsPaginated(
        int $page = 1,
        int $perPage = 15,
        ?string $search = null,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc'
    ): LengthAwarePaginator {
        return $this->repository->getBrandsPaginated($page, $perPage, $search, $sortBy, $sortDirection);
    }

    /**
     * Create a new brand
     */
    public function createBrand(CreateBrandDTO $dto): Brand
    {
        return $this->repository->createBrand($dto->toArray());
    }

    /**
     * Update an existing brand
     */
    public function updateBrand(Brand $brand, UpdateBrandDTO $dto): Brand
    {
        return $this->repository->updateBrand($brand, $dto->toArray());
    }

    /**
     * Delete a brand
     */
    public function deleteBrand(Brand $brand): bool
    {
        // Check if brand is used by products
        if ($brand->products()->exists()) {
            throw new \Exception('Cannot delete brand that is assigned to products. Please reassign products first.');
        }

        return $this->repository->deleteBrand($brand);
    }

    // ==================== ATTRIBUTES ====================

    /**
     * Get paginated attributes with filtering and sorting
     */
    public function getAttributesPaginated(
        int $page = 1,
        int $perPage = 15,
        ?string $search = null,
        ?string $type = null,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc'
    ): LengthAwarePaginator {
        return $this->repository->getAttributesPaginated($page, $perPage, $search, $type, $sortBy, $sortDirection);
    }

    /**
     * Create a new attribute
     */
    public function createAttribute(CreateAttributeDTO $dto): Attribute
    {
        return $this->repository->createAttribute($dto->toArray());
    }

    /**
     * Update an existing attribute
     */
    public function updateAttribute(Attribute $attribute, UpdateAttributeDTO $dto): Attribute
    {
        return $this->repository->updateAttribute($attribute, $dto->toArray());
    }

    /**
     * Delete an attribute
     */
    public function deleteAttribute(Attribute $attribute): bool
    {
        // Check if attribute is used by any attributables via repository
        if ($this->repository->isAttributeUsed($attribute)) {
            throw new \Exception('Cannot delete attribute that is assigned to items. Please remove attribute assignments first.');
        }

        return $this->repository->deleteAttribute($attribute);
    }

    // ==================== TAGS ====================

    /**
     * Get paginated tags with filtering and sorting
     */
    public function getTagsPaginated(
        int $page = 1,
        int $perPage = 15,
        ?string $search = null,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc'
    ): LengthAwarePaginator {
        return $this->repository->getTagsPaginated($page, $perPage, $search, $sortBy, $sortDirection);
    }

    /**
     * Create a new tag
     */
    public function createTag(CreateTagDTO $dto): Tag
    {
        return $this->repository->createTag($dto->toArray());
    }

    /**
     * Update an existing tag
     */
    public function updateTag(Tag $tag, UpdateTagDTO $dto): Tag
    {
        return $this->repository->updateTag($tag, $dto->toArray());
    }

    /**
     * Delete a tag
     */
    public function deleteTag(Tag $tag): bool
    {
        // Check if tag is used by any taggables via repository
        if ($this->repository->isTagUsed($tag)) {
            throw new \Exception('Cannot delete tag that is assigned to items. Please remove tag assignments first.');
        }

        return $this->repository->deleteTag($tag);
    }
}
