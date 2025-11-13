<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Product;

use App\DTOs\AttachProductsToCollectionDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\AttachProductsToCollectionRequest;
use App\Http\Resources\ProductResource;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Controller for product collection relationships.
 * Provides API v1 endpoints as alternatives to Lunar admin routes.
 */
final class ProductCollectionController extends Controller
{
    public function __construct(
        private readonly ProductService $productService
    ) {}

    /**
     * Get products in a specific collection.
     *
     * API v1 alternative to: lunar/collections/{record}/products
     */
    public function index(Request $request, string $collectionId): AnonymousResourceCollection
    {
        $limit = $request->input('limit', null);
        $products = $this->productService->getByCollection($collectionId, $limit);

        return ProductResource::collection($products);
    }

    /**
     * Attach products to a collection.
     */
    public function attach(AttachProductsToCollectionRequest $request, string $collectionId): JsonResponse
    {
        $dto = AttachProductsToCollectionDTO::fromRequest($collectionId, $request->validated());

        $this->productService->attachToCollection($dto);

        return response()->json([
            'message' => 'Products attached to collection successfully',
        ]);
    }

    /**
     * Detach products from a collection.
     */
    public function detach(AttachProductsToCollectionRequest $request, string $collectionId): JsonResponse
    {
        $validated = $request->validated();

        $this->productService->detachFromCollection($collectionId, $validated['product_ids']);

        return response()->json([
            'message' => 'Products detached from collection successfully',
        ]);
    }
}
