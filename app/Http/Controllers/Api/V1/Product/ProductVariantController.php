<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Product;

use App\DTOs\CreateProductVariantDTO;
use App\DTOs\UpdateProductVariantDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateProductVariantRequest;
use App\Http\Requests\UpdateProductVariantRequest;
use App\Http\Resources\ProductVariantResource;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Controller for product variant management.
 * Provides API v1 endpoints as alternatives to Lunar admin routes.
 */
final class ProductVariantController extends Controller
{
    public function __construct(
        private readonly ProductService $productService
    ) {}

    /**
     * Get all variants for a product.
     *
     * API v1 alternative to: lunar/products/{record}/variants
     */
    public function index(string $productId): AnonymousResourceCollection
    {
        $product = $this->productService->findById($productId);

        if (! $product) {
            abort(404, 'Product not found');
        }

        $product->load(['variants.prices.currency', 'variants.values.option', 'variants.taxClass']);

        return ProductVariantResource::collection($product->variants);
    }

    /**
     * Create a new variant for a product.
     */
    public function store(CreateProductVariantRequest $request, string $productId): JsonResponse
    {
        $user = $request->user();
        $vendor = $user->vendor;

        if (! $vendor) {
            abort(403, 'User must be associated with a vendor');
        }

        $product = $this->productService->findById($productId);

        if (! $product) {
            abort(404, 'Product not found');
        }

        if ($product->vendor_id !== $vendor->id) {
            abort(403, 'You can only manage your own products');
        }

        $dto = CreateProductVariantDTO::fromRequest($request->validated());
        $variant = $this->productService->createVariant($productId, $dto);

        return (new ProductVariantResource($variant))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update a product variant.
     */
    public function update(UpdateProductVariantRequest $request, string $productId, string $variantId): JsonResponse
    {
        $user = $request->user();
        $vendor = $user->vendor;

        if (! $vendor) {
            abort(403, 'User must be associated with a vendor');
        }

        $product = $this->productService->findById($productId);

        if (! $product) {
            abort(404, 'Product not found');
        }

        if ($product->vendor_id !== $vendor->id) {
            abort(403, 'You can only manage your own products');
        }

        $dto = UpdateProductVariantDTO::fromRequest($request->validated());
        $variant = $this->productService->updateVariant($productId, $variantId, $dto);

        return (new ProductVariantResource($variant))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Delete a product variant.
     */
    public function destroy(Request $request, string $productId, string $variantId): JsonResponse
    {
        $user = $request->user();
        $vendor = $user->vendor;

        if (! $vendor) {
            abort(403, 'User must be associated with a vendor');
        }

        $product = $this->productService->findById($productId);

        if (! $product) {
            abort(404, 'Product not found');
        }

        if ($product->vendor_id !== $vendor->id) {
            abort(403, 'You can only manage your own products');
        }

        $this->productService->deleteVariant($productId, $variantId);

        return response()->json([
            'message' => 'Product variant deleted successfully',
        ]);
    }
}
