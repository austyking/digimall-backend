<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Product;

use App\DTOs\AttachProductsToCollectionDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\AttachProductsToCollectionRequest;
use App\Http\Resources\CollectionResource;
use App\Http\Resources\ProductResource;
use App\Repositories\ProductCollectionRepository;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Lunar\Models\Collection;

/**
 * Controller for product collection relationships.
 * Provides API v1 endpoints as alternatives to Lunar admin routes.
 */
final class ProductCollectionController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly ProductCollectionRepository $collectionRepository
    ) {}

    /**
     * Get collections for a specific product.
     *
     * API v1 alternative to: lunar/products/{record}/collections
     */
    public function collections(int $productId): AnonymousResourceCollection
    {
        $product = $this->productService->findById($productId);

        if (! $product) {
            abort(404, 'Product not found');
        }

        $collections = $this->collectionRepository->getCollectionsByProduct($productId);

        return CollectionResource::collection($collections);
    }

    /**
     * Get products in a specific collection.
     *
     * API v1 alternative to: lunar/collections/{record}/products
     */
    public function index(Request $request, int $collectionId): AnonymousResourceCollection
    {
        $limit = $request->input('limit', null);
        $products = $this->productService->getByCollection($collectionId, $limit);

        return ProductResource::collection($products);
    }

    /**
     * Attach products to a collection.
     */
    public function attach(AttachProductsToCollectionRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $dto = AttachProductsToCollectionDTO::fromRequest($validated['collection_id'], $validated);

        $this->productService->attachToCollection($dto);

        return response()->json([
            'message' => 'Products attached to collection successfully',
        ]);
    }

    /**
     * Detach products from a collection.
     */
    public function detach(AttachProductsToCollectionRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $this->productService->detachFromCollection($validated['collection_id'], $validated['product_ids']);

        return response()->json([
            'message' => 'Products detached from collection successfully',
        ]);
    }
}
