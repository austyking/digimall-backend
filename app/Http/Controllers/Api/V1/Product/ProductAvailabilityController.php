<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProductAvailabilityRequest;
use App\Http\Resources\ProductAvailabilityResource;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;

/**
 * Controller for product availability management.
 * Provides API v1 endpoints as alternatives to Lunar admin routes.
 */
final class ProductAvailabilityController extends Controller
{
    public function __construct(
        private ProductService $productService
    ) {}

    /**
     * Get product availability information.
     *
     * API v1 alternative to: lunar/products/{record}/availability
     */
    public function show(string $productId): JsonResponse
    {
        $product = $this->productService->findById($productId);

        if (! $product) {
            abort(404, 'Product not found');
        }

        // Load variants for the resource
        $product->load('variants');

        // Add computed availability fields
        $product->is_available = $this->productService->isAvailable($productId);
        $product->available_quantity = $this->productService->getAvailableQuantity($productId);

        return (new ProductAvailabilityResource($product))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Update product availability.
     */
    public function update(UpdateProductAvailabilityRequest $request, string $productId): JsonResponse
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
            abort(403, 'You can only update your own products');
        }

        $validated = $request->validated();

        if (isset($validated['stock'])) {
            $this->productService->updateStock($productId, $validated['stock']);
        }

        if (isset($validated['status'])) {
            $this->productService->updateStatus($productId, $validated['status']);
        }

        return response()->json([
            'message' => 'Product availability updated successfully',
        ]);
    }
}
