<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttachProductAssociationsRequest;
use App\Http\Requests\DetachProductAssociationsRequest;
use App\Http\Resources\ProductAssociationResource;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Lunar\Models\ProductAssociation;

/**
 * Product Association Controller
 *
 * Handles product associations (cross-sell, up-sell, alternate products).
 * Implements API v1 endpoints for Lunar's product association features.
 */
class ProductAssociationController extends Controller
{
    public function __construct(
        private readonly ProductService $productService
    ) {}

    /**
     * Get all associations for a product.
     */
    public function index(string $productId): JsonResponse
    {
        $product = $this->productService->findById($productId);

        if (! $product) {
            abort(404, 'Product not found');
        }

        // Load associations with their target products
        $product->load(['associations.target.brand', 'associations.target.productType']);

        $associations = $product->associations->groupBy('type');

        return response()->json([
            'data' => [
                'cross_sell' => ProductAssociationResource::collection($associations->get(ProductAssociation::CROSS_SELL, collect())),
                'up_sell' => ProductAssociationResource::collection($associations->get(ProductAssociation::UP_SELL, collect())),
                'alternate' => ProductAssociationResource::collection($associations->get(ProductAssociation::ALTERNATE, collect())),
            ],
        ]);
    }

    /**
     * Add cross-sell associations to a product.
     */
    public function attachCrossSell(AttachProductAssociationsRequest $request, string $productId): AnonymousResourceCollection
    {
        $validated = $request->validated();

        $product = $this->productService->findById($productId);

        if (! $product) {
            abort(404, 'Product not found');
        }

        $this->authorize('update', $product);

        $associatedProducts = $this->productService->filterProducts([
            'id' => $validated['product_ids'],
        ]);

        $product->associate($associatedProducts, ProductAssociation::CROSS_SELL);

        $product->load(['associations' => function ($query) {
            $query->where('type', ProductAssociation::CROSS_SELL)->with('target');
        }]);

        return ProductAssociationResource::collection($product->associations);
    }

    /**
     * Add up-sell associations to a product.
     */
    public function attachUpSell(AttachProductAssociationsRequest $request, string $productId): AnonymousResourceCollection
    {
        $validated = $request->validated();

        $product = $this->productService->findById($productId);

        if (! $product) {
            abort(404, 'Product not found');
        }

        $this->authorize('update', $product);

        $associatedProducts = $this->productService->filterProducts([
            'id' => $validated['product_ids'],
        ]);

        $product->associate($associatedProducts, ProductAssociation::UP_SELL);

        $product->load(['associations' => function ($query) {
            $query->where('type', ProductAssociation::UP_SELL)->with('target');
        }]);

        return ProductAssociationResource::collection($product->associations);
    }

    /**
     * Add alternate associations to a product.
     */
    public function attachAlternate(AttachProductAssociationsRequest $request, string $productId): AnonymousResourceCollection
    {
        $validated = $request->validated();

        $product = $this->productService->findById($productId);

        if (! $product) {
            abort(404, 'Product not found');
        }

        $this->authorize('update', $product);

        $associatedProducts = $this->productService->filterProducts([
            'id' => $validated['product_ids'],
        ]);

        $product->associate($associatedProducts, ProductAssociation::ALTERNATE);

        $product->load(['associations' => function ($query) {
            $query->where('type', ProductAssociation::ALTERNATE)->with('target');
        }]);

        return ProductAssociationResource::collection($product->associations);
    }

    /**
     * Remove product associations.
     */
    public function detach(DetachProductAssociationsRequest $request, string $productId): JsonResponse
    {
        $validated = $request->validated();

        $product = $this->productService->findById($productId);

        if (! $product) {
            abort(404, 'Product not found');
        }

        $this->authorize('update', $product);

        $associatedProducts = $this->productService->filterProducts([
            'id' => $validated['product_ids'],
        ]);

        // If type is specified, only remove that association type
        if (isset($validated['type'])) {
            $product->dissociate($associatedProducts, $validated['type']);
        } else {
            // Remove all association types
            $product->dissociate($associatedProducts);
        }

        return response()->json([
            'message' => 'Product associations removed successfully',
        ]);
    }

    /**
     * Get cross-sell products for a product.
     */
    public function getCrossSell(string $productId): AnonymousResourceCollection
    {
        $product = $this->productService->findById($productId);

        if (! $product) {
            abort(404, 'Product not found');
        }

        $product->load(['associations' => function ($query) {
            $query->where('type', ProductAssociation::CROSS_SELL)
                ->with(['target.brand', 'target.productType', 'target.variants']);
        }]);

        return ProductAssociationResource::collection($product->associations);
    }

    /**
     * Get up-sell products for a product.
     */
    public function getUpSell(string $productId): JsonResponse
    {
        $product = Product::findOrFail($productId);

        $upSellProducts = $product->associations()
            ->upSell()
            ->with(['target' => function ($query) {
                $query->with(['brand', 'productType', 'variants']);
            }])
            ->get()
            ->pluck('target');

        return response()->json([
            'data' => $upSellProducts,
        ]);
    }

    /**
     * Get alternate products for a product.
     */
    public function getAlternate(string $productId): JsonResponse
    {
        $product = Product::findOrFail($productId);

        $alternateProducts = $product->associations()
            ->alternate()
            ->with(['target' => function ($query) {
                $query->with(['brand', 'productType', 'variants']);
            }])
            ->get()
            ->pluck('target');

        return response()->json([
            'data' => $alternateProducts,
        ]);
    }
}
