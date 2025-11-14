<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Product;

use App\DTOs\CreateProductDTO;
use App\DTOs\ProductFilterDTO;
use App\DTOs\UpdateProductDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService
    ) {}

    /**
     * Display a listing of products.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filterDTO = ProductFilterDTO::fromRequest($request->all());

        if (! $filterDTO->validate()) {
            abort(400, 'Invalid filter parameters');
        }

        $user = $request->user();

        // For authenticated users (vendors), filter by their vendor_id unless explicitly specified
        if ($user && $user->vendor && ! $filterDTO->vendorId) {
            $filters = array_merge([
                'vendor_id' => $user->vendor->id,
            ], array_filter([
                'query' => $filterDTO->query,
                'status' => $filterDTO->status,
                'brand_id' => $filterDTO->brandId,
                'product_type_id' => $filterDTO->productTypeId,
                'collection_id' => $filterDTO->collectionId,
                'tags' => $filterDTO->tags,
                'min_price' => $filterDTO->minPrice,
                'max_price' => $filterDTO->maxPrice,
                'in_stock' => $filterDTO->inStock,
                'limit' => $filterDTO->limit,
                'offset' => $filterDTO->offset,
                'sort_by' => $filterDTO->sortBy,
                'sort_direction' => $filterDTO->sortDirection,
            ]));
        } else {
            $filters = array_filter([
                'query' => $filterDTO->query,
                'status' => $filterDTO->status,
                'brand_id' => $filterDTO->brandId,
                'product_type_id' => $filterDTO->productTypeId,
                'vendor_id' => $filterDTO->vendorId,
                'collection_id' => $filterDTO->collectionId,
                'tags' => $filterDTO->tags,
                'min_price' => $filterDTO->minPrice,
                'max_price' => $filterDTO->maxPrice,
                'in_stock' => $filterDTO->inStock,
                'limit' => $filterDTO->limit,
                'offset' => $filterDTO->offset,
                'sort_by' => $filterDTO->sortBy,
                'sort_direction' => $filterDTO->sortDirection,
            ]);
        }

        if (! empty($filters)) {
            // If limit/per_page is set, use paginator
            if (! empty($filters['limit']) || ! empty($filters['per_page'])) {
                $perPage = $filters['limit'] ?? $filters['per_page'] ?? 15;
                $products = $this->productService->getPaginatedProducts($perPage, $filters);

                return ProductResource::collection($products);
            } else {
                $products = $this->productService->filterProducts($filters);

                return ProductResource::collection($products);
            }
        } elseif ($filterDTO->limit) {
            $products = $this->productService->getPaginatedProducts($filterDTO->limit);

            return ProductResource::collection($products);
        } else {
            $products = $this->productService->getAllProducts();

            return ProductResource::collection($products);
        }
    }

    /**
     * Store a newly created product.
     */
    public function store(CreateProductRequest $request): JsonResponse
    {
        $user = $request->user();
        $vendor = $user->vendor; // Assuming user has a vendor relationship

        if (! $vendor) {
            abort(403, 'User must be associated with a vendor to create products');
        }

        $dto = CreateProductDTO::fromRequest($request->all(), $vendor->id);
        $product = $this->productService->createProduct($dto);

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified product.
     */
    public function show(Request $request, int $productId): ProductResource
    {
        $product = $this->productService->findById($productId);

        if (! $product) {
            abort(404, 'Product not found');
        }

        // Check if user can access this product (public read, but vendor ownership for management)
        $user = $request->user();
        if ($user && $product->vendor_id !== $user->vendor?->id) {
            // For now, allow public access to products, but this could be restricted based on business rules
        }

        return new ProductResource($product);
    }

    /**
     * Update the specified product.
     */
    public function update(UpdateProductRequest $request, int $productId): ProductResource
    {
        $user = $request->user();
        $vendor = $user->vendor;

        if (! $vendor) {
            abort(403, 'User must be associated with a vendor to update products');
        }

        $product = $this->productService->findById($productId);

        if (! $product) {
            abort(404, 'Product not found');
        }

        // Ensure vendor can only update their own products
        if ($product->vendor_id !== $vendor->id) {
            abort(403, 'You can only update your own products');
        }

        $dto = UpdateProductDTO::fromRequest($request->all());
        $product = $this->productService->updateProduct((int) $productId, $dto);

        return new ProductResource($product);
    }

    /**
     * Remove the specified product.
     */
    public function destroy(Request $request, int $productId): JsonResponse
    {
        $user = $request->user();
        $vendor = $user->vendor;

        if (! $vendor) {
            abort(403, 'User must be associated with a vendor to delete products');
        }

        $product = $this->productService->findById($productId);

        if (! $product) {
            abort(404, 'Product not found');
        }

        // Ensure vendor can only delete their own products
        if ($product->vendor_id !== $vendor->id) {
            abort(403, 'You can only delete your own products');
        }

        $this->productService->deleteProduct($productId);

        return response()->json([
            'message' => 'Product deleted successfully',
        ]);
    }
}
