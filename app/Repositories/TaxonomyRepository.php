<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Repositories\Contracts\TaxonomyRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Lunar\Models\Attribute;
use Lunar\Models\Brand;
use Lunar\Models\Collection;
use Lunar\Models\Tag;

/**
 * Taxonomy Repository Implementation
 */
class TaxonomyRepository implements TaxonomyRepositoryInterface
{
    // ==================== CATEGORIES (COLLECTIONS) ====================

    public function getCategoriesPaginated(
        int $page = 1,
        int $perPage = 15,
        ?string $search = null,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc'
    ): LengthAwarePaginator {
        $query = Collection::query()
            ->with(['parent', 'children']);

        if ($search) {
            // Search in attribute_data JSON for translated text fields
            $query->where(function ($q) use ($search) {
                $q->where('attribute_data', 'like', '%"en":"%'.$search.'%"%')
                    ->orWhere('attribute_data', 'like', '%"'.$search.'"%');
            });
        }

        return $query->orderBy($sortBy, $sortDirection)
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function findCategory(int $id): ?Collection
    {
        return Collection::with(['parent', 'children'])->find($id);
    }

    public function createCategory(array $data): Collection
    {
        // Ensure tenant_id is set for multi-tenant models
        if (! isset($data['tenant_id']) && tenancy()->initialized) {
            $data['tenant_id'] = tenant()->getTenantKey();
        }

        return Collection::create($data);
    }

    public function updateCategory(Collection $category, array $data): Collection
    {
        $category->update($data);

        return $category->fresh(['parent', 'children']);
    }

    public function deleteCategory(Collection $category): bool
    {
        return $category->delete();
    }

    public function getCategoryTree(): EloquentCollection
    {
        return Collection::whereNull('parent_id')
            ->with(['children' => function ($query) {
                $query->with('children');
            }])
            ->orderBy('created_at')
            ->get();
    }

    // ==================== BRANDS ====================

    public function getBrandsPaginated(
        int $page = 1,
        int $perPage = 15,
        ?string $search = null,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc'
    ): LengthAwarePaginator {
        $query = Brand::query();

        if ($search) {
            // Use Scout search for brands since they use the Searchable trait
            $searchResults = Brand::search($search)->get();
            $ids = $searchResults->pluck('id')->toArray();
            $query->whereIn('id', $ids);
        }

        return $query->orderBy($sortBy, $sortDirection)
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function findBrand(int $id): ?Brand
    {
        return Brand::find($id);
    }

    public function createBrand(array $data): Brand
    {
        // Ensure tenant_id is set for multi-tenant models
        if (! isset($data['tenant_id']) && tenancy()->initialized) {
            $data['tenant_id'] = tenant()->getTenantKey();
        }

        return Brand::create($data);
    }

    public function updateBrand(Brand $brand, array $data): Brand
    {
        if (isset($data['name'])) {
            $brand->name = $data['name'];
        }
        if (isset($data['attribute_data'])) {
            $brand->attribute_data = $data['attribute_data'];
        }
        $brand->save();

        return $brand;
    }

    public function deleteBrand(Brand $brand): bool
    {
        return $brand->delete();
    }

    // ==================== ATTRIBUTES ====================

    public function getAttributesPaginated(
        int $page = 1,
        int $perPage = 15,
        ?string $search = null,
        ?string $type = null,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc'
    ): LengthAwarePaginator {
        $query = Attribute::query()
            ->with('attributeGroup');

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($type) {
            $query->where('type', $type);
        }

        return $query->orderBy($sortBy, $sortDirection)
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function findAttribute(int $id): ?Attribute
    {
        return Attribute::with('attributeGroup')->find($id);
    }

    public function createAttribute(array $data): Attribute
    {
        // Ensure tenant_id is set for multi-tenant models
        if (! isset($data['tenant_id']) && tenancy()->initialized) {
            $data['tenant_id'] = tenant()->getTenantKey();
        }

        $attribute = new Attribute;
        foreach ($data as $key => $value) {
            $attribute->{$key} = $value;
        }
        $attribute->save();

        return $attribute;
    }

    public function updateAttribute(Attribute $attribute, array $data): Attribute
    {
        foreach ($data as $key => $value) {
            $attribute->{$key} = $value;
        }
        $attribute->save();

        return $attribute->fresh('attributeGroup');
    }

    public function deleteAttribute(Attribute $attribute): bool
    {
        return $attribute->delete();
    }

    public function isAttributeUsed(Attribute $attribute): bool
    {
        // Check if attribute is used by any attributables
        return $attribute->attributable()->exists();
    }

    // ==================== TAGS ====================

    public function getTagsPaginated(
        int $page = 1,
        int $perPage = 15,
        ?string $search = null,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc'
    ): LengthAwarePaginator {
        $query = Tag::query();

        if ($search) {
            $query->where('value', 'like', "%{$search}%");
        }

        return $query->orderBy($sortBy, $sortDirection)
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function findTag(int $id): ?Tag
    {
        return Tag::find($id);
    }

    public function createTag(array $data): Tag
    {
        // Ensure tenant_id is set for multi-tenant models
        if (! isset($data['tenant_id']) && tenancy()->initialized) {
            $data['tenant_id'] = tenant()->getTenantKey();
        }

        return Tag::create($data);
    }

    public function updateTag(Tag $tag, array $data): Tag
    {
        $tag->update($data);

        return $tag->fresh();
    }

    public function deleteTag(Tag $tag): bool
    {
        return $tag->delete();
    }

    public function isTagUsed(Tag $tag): bool
    {
        // Check if tag is used by any taggables
        return $tag->taggable()->exists();
    }
}
