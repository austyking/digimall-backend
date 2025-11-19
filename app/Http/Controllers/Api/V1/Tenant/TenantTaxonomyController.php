<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Tenant;

use App\DTOs\CreateAttributeDTO;
use App\DTOs\CreateBrandDTO;
use App\DTOs\CreateCategoryDTO;
use App\DTOs\CreateTagDTO;
use App\DTOs\UpdateAttributeDTO;
use App\DTOs\UpdateBrandDTO;
use App\DTOs\UpdateCategoryDTO;
use App\DTOs\UpdateTagDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateAttributeRequest;
use App\Http\Requests\CreateBrandRequest;
use App\Http\Requests\CreateCategoryRequest;
use App\Http\Requests\CreateTagRequest;
use App\Http\Requests\GetAttributesRequest;
use App\Http\Requests\GetBrandsRequest;
use App\Http\Requests\GetCategoriesRequest;
use App\Http\Requests\GetTagsRequest;
use App\Http\Requests\UpdateAttributeRequest;
use App\Http\Requests\UpdateBrandRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Requests\UpdateTagRequest;
use App\Http\Resources\AttributeResource;
use App\Http\Resources\BrandResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\TagResource;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Collection;
use App\Models\Tag;
use App\Services\TaxonomyService;
use Illuminate\Http\JsonResponse;

/**
 * Tenant Taxonomy Management Controller
 *
 * Handles CRUD operations for taxonomy entities:
 * - Categories (Collections)
 * - Brands
 * - Attributes
 * - Tags
 */
class TenantTaxonomyController extends Controller
{
    public function __construct(
        private readonly TaxonomyService $taxonomyService
    ) {}

    /**
     * Check if the authenticated user has admin privileges
     */
    private function authorizeAdmin(): void
    {
        if (! auth()->user()?->hasRole('association-administrator')) {
            abort(403, 'Access denied. Admin privileges required.');
        }
    }

    // ==================== CATEGORIES (COLLECTIONS) ====================

    /**
     * Get all categories with pagination and filtering
     */
    public function getCategories(GetCategoriesRequest $request): JsonResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validated();

        $result = $this->taxonomyService->getCategoriesPaginated(
            page: (int) $validated['page'],
            perPage: (int) $validated['per_page'],
            search: $validated['search'],
            sortBy: $validated['sort_by'],
            sortDirection: $validated['sort_direction']
        );

        return response()->json([
            'data' => CategoryResource::collection($result->items()),
            'meta' => [
                'current_page' => $result->currentPage(),
                'last_page' => $result->lastPage(),
                'per_page' => $result->perPage(),
                'total' => $result->total(),
                'from' => $result->firstItem(),
                'to' => $result->lastItem(),
            ],
            'links' => [
                'first' => $result->url(1),
                'last' => $result->url($result->lastPage()),
                'prev' => $result->previousPageUrl(),
                'next' => $result->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Create a new category
     */
    public function createCategory(CreateCategoryRequest $request): JsonResponse
    {
        $dto = CreateCategoryDTO::fromRequest($request);
        $category = $this->taxonomyService->createCategory($dto);

        return response()->json([
            'data' => new CategoryResource($category),
            'message' => 'Category created successfully',
        ], 201);
    }

    /**
     * Get a specific category
     */
    public function getCategory(Collection $category): JsonResponse
    {
        $category->load(['parent', 'children']);

        return response()->json([
            'data' => new CategoryResource($category),
        ]);
    }

    /**
     * Update a category
     */
    public function updateCategory(UpdateCategoryRequest $request, Collection $category): JsonResponse
    {
        $dto = UpdateCategoryDTO::fromRequest($request);
        $updatedCategory = $this->taxonomyService->updateCategory($category, $dto);

        return response()->json([
            'data' => new CategoryResource($updatedCategory),
            'message' => 'Category updated successfully',
        ]);
    }

    /**
     * Delete a category
     */
    public function deleteCategory(Collection $category): JsonResponse
    {
        $this->taxonomyService->deleteCategory($category);

        return response()->json([
            'message' => 'Category deleted successfully',
        ]);
    }

    /**
     * Get category tree structure
     */
    public function getCategoryTree(): JsonResponse
    {
        $tree = $this->taxonomyService->getCategoryTree();

        return response()->json([
            'data' => CategoryResource::collection($tree),
        ]);
    }

    // ==================== BRANDS ====================

    /**
     * Get all brands with pagination and filtering
     */
    public function getBrands(GetBrandsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->taxonomyService->getBrandsPaginated(
            page: (int) $validated['page'],
            perPage: (int) $validated['per_page'],
            search: $validated['search'],
            sortBy: $validated['sort_by'],
            sortDirection: $validated['sort_direction']
        );

        return response()->json([
            'data' => BrandResource::collection($result->items()),
            'meta' => [
                'current_page' => $result->currentPage(),
                'last_page' => $result->lastPage(),
                'per_page' => $result->perPage(),
                'total' => $result->total(),
                'from' => $result->firstItem(),
                'to' => $result->lastItem(),
            ],
            'links' => [
                'first' => $result->url(1),
                'last' => $result->url($result->lastPage()),
                'prev' => $result->previousPageUrl(),
                'next' => $result->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Create a new brand
     */
    public function createBrand(CreateBrandRequest $request): JsonResponse
    {
        $dto = CreateBrandDTO::fromRequest($request);
        $brand = $this->taxonomyService->createBrand($dto);

        return response()->json([
            'data' => new BrandResource($brand),
            'message' => 'Brand created successfully',
        ], 201);
    }

    /**
     * Get a specific brand
     */
    public function getBrand(Brand $brand): JsonResponse
    {
        return response()->json([
            'data' => new BrandResource($brand),
        ]);
    }

    /**
     * Update a brand
     */
    public function updateBrand(UpdateBrandRequest $request, Brand $brand): JsonResponse
    {
        $dto = UpdateBrandDTO::fromRequest($request);
        $updatedBrand = $this->taxonomyService->updateBrand($brand, $dto);

        return response()->json([
            'data' => new BrandResource($updatedBrand),
            'message' => 'Brand updated successfully',
        ]);
    }

    /**
     * Delete a brand
     */
    public function deleteBrand(Brand $brand): JsonResponse
    {
        $this->taxonomyService->deleteBrand($brand);

        return response()->json([
            'message' => 'Brand deleted successfully',
        ]);
    }

    // ==================== ATTRIBUTES ====================

    /**
     * Get all attributes with pagination and filtering
     */
    public function getAttributes(GetAttributesRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->taxonomyService->getAttributesPaginated(
            page: (int) $validated['page'],
            perPage: (int) $validated['per_page'],
            search: $validated['search'],
            type: $validated['type'],
            sortBy: $validated['sort_by'],
            sortDirection: $validated['sort_direction']
        );

        return response()->json([
            'data' => AttributeResource::collection($result->items()),
            'meta' => [
                'current_page' => $result->currentPage(),
                'last_page' => $result->lastPage(),
                'per_page' => $result->perPage(),
                'total' => $result->total(),
                'from' => $result->firstItem(),
                'to' => $result->lastItem(),
            ],
            'links' => [
                'first' => $result->url(1),
                'last' => $result->url($result->lastPage()),
                'prev' => $result->previousPageUrl(),
                'next' => $result->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Create a new attribute
     */
    public function createAttribute(CreateAttributeRequest $request): JsonResponse
    {
        $dto = CreateAttributeDTO::fromRequest($request);
        $attribute = $this->taxonomyService->createAttribute($dto);

        return response()->json([
            'data' => new AttributeResource($attribute),
            'message' => 'Attribute created successfully',
        ], 201);
    }

    /**
     * Get a specific attribute
     */
    public function getAttribute(Attribute $attribute): JsonResponse
    {
        $attribute->load('attributeGroup');

        return response()->json([
            'data' => new AttributeResource($attribute),
        ]);
    }

    /**
     * Update an attribute
     */
    public function updateAttribute(UpdateAttributeRequest $request, Attribute $attribute): JsonResponse
    {
        $dto = UpdateAttributeDTO::fromRequest($request);
        $updatedAttribute = $this->taxonomyService->updateAttribute($attribute, $dto);

        return response()->json([
            'data' => new AttributeResource($updatedAttribute),
            'message' => 'Attribute updated successfully',
        ]);
    }

    /**
     * Delete an attribute
     */
    public function deleteAttribute(Attribute $attribute): JsonResponse
    {
        $this->taxonomyService->deleteAttribute($attribute);

        return response()->json([
            'message' => 'Attribute deleted successfully',
        ]);
    }

    // ==================== TAGS ====================

    /**
     * Get all tags with pagination and filtering
     */
    public function getTags(GetTagsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->taxonomyService->getTagsPaginated(
            page: (int) $validated['page'],
            perPage: (int) $validated['per_page'],
            search: $validated['search'],
            sortBy: $validated['sort_by'],
            sortDirection: $validated['sort_direction']
        );

        return response()->json([
            'data' => TagResource::collection($result->items()),
            'meta' => [
                'current_page' => $result->currentPage(),
                'last_page' => $result->lastPage(),
                'per_page' => $result->perPage(),
                'total' => $result->total(),
                'from' => $result->firstItem(),
                'to' => $result->lastItem(),
            ],
            'links' => [
                'first' => $result->url(1),
                'last' => $result->url($result->lastPage()),
                'prev' => $result->previousPageUrl(),
                'next' => $result->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Create a new tag
     */
    public function createTag(CreateTagRequest $request): JsonResponse
    {
        $dto = CreateTagDTO::fromRequest($request);
        $tag = $this->taxonomyService->createTag($dto);

        return response()->json([
            'data' => new TagResource($tag),
            'message' => 'Tag created successfully',
        ], 201);
    }

    /**
     * Get a specific tag
     */
    public function getTag(Tag $tag): JsonResponse
    {
        return response()->json([
            'data' => new TagResource($tag),
        ]);
    }

    /**
     * Update a tag
     */
    public function updateTag(UpdateTagRequest $request, Tag $tag): JsonResponse
    {
        $dto = UpdateTagDTO::fromRequest($request);
        $updatedTag = $this->taxonomyService->updateTag($tag, $dto);

        return response()->json([
            'data' => new TagResource($updatedTag),
            'message' => 'Tag updated successfully',
        ]);
    }

    /**
     * Delete a tag
     */
    public function deleteTag(Tag $tag): JsonResponse
    {
        $this->taxonomyService->deleteTag($tag);

        return response()->json([
            'message' => 'Tag deleted successfully',
        ]);
    }
}
