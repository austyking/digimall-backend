<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\DTOs\ProductFilterDTO;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class AdminProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService
    ) {}

    /**
     * Get all products with admin oversight (no vendor filtering).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filterDTO = ProductFilterDTO::fromRequest($request->all());

        if (! $filterDTO->validate()) {
            abort(400, 'Invalid filter parameters');
        }

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
            'sort_by' => $filterDTO->sortBy,
            'sort_direction' => $filterDTO->sortDirection,
        ]);

        $perPage = $filterDTO->limit ?? 15;

        $products = $this->productService->getFilteredProductsPaginated($filters, $perPage);

        return ProductResource::collection($products);
    }

    /**
     * Get a specific product for admin review.
     */
    public function show(int $id): ProductResource
    {
        $product = $this->productService->findById($id);

        if (! $product) {
            abort(404, 'Product not found');
        }

        return new ProductResource($product);
    }

    /**
     * Get products pending admin review/approval.
     */
    public function pendingReview(Request $request): AnonymousResourceCollection
    {
        $perPage = $request->get('per_page', 15);

        $products = $this->productService->getPendingReviewProducts($perPage);

        return ProductResource::collection($products);
    }

    /**
     * Approve a product for public visibility.
     */
    public function approve(int $id): JsonResponse
    {
        $product = $this->productService->findById($id);

        if (! $product) {
            abort(404, 'Product not found');
        }

        $this->productService->approveProduct($id);

        return response()->json([
            'message' => 'Product approved successfully',
            'data' => new ProductResource($product->fresh()),
        ]);
    }

    /**
     * Reject a product with reason.
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $product = $this->productService->findById($id);

        if (! $product) {
            abort(404, 'Product not found');
        }

        $this->productService->rejectProduct($id, $request->input('reason'));

        return response()->json([
            'message' => 'Product rejected',
            'data' => new ProductResource($product->fresh()),
        ]);
    }

    /**
     * Get product statistics for admin dashboard KPI cards.
     */
    public function statistics(): JsonResponse
    {
        $statistics = $this->productService->getProductStatistics();

        return response()->json($statistics);
    }
}
