<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProductAvailabilityRequest;
use App\Services\ProductService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

/**
 * Controller for product availability management.
 * Provides API v1 endpoints as alternatives to Lunar admin routes.
 */
final class ProductAvailabilityController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private ProductService $productService
    ) {}

    /**
     * Get product availability information.
     *
     * API v1 alternative to: lunar/products/{record}/availability
     */
    public function show(int $productId): JsonResponse
    {
        $product = $this->productService->findById($productId);

        if (! $product) {
            abort(404, 'Product not found');
        }

        $availabilityData = $this->productService->getAvailabilityData($productId);

        return response()->json([
            'data' => $availabilityData,
        ]);
    }

    /**
     * Update product availability.
     */
    public function update(UpdateProductAvailabilityRequest $request, int $productId): JsonResponse
    {
        $product = $this->productService->findById($productId);

        if (! $product) {
            abort(404, 'Product not found');
        }

        // $this->authorize('update', $product);

        $validated = $request->validated();

        // Update all variants with the provided availability settings
        foreach ($product->variants as $variant) {
            $updateData = [];

            if (isset($validated['purchasable'])) {
                $updateData['purchasable'] = $validated['purchasable'];
            }

            if (isset($validated['stock'])) {
                $updateData['stock'] = $validated['stock'];
            }

            if (isset($validated['backorder'])) {
                $updateData['backorder'] = $validated['backorder'];
            }

            if (! empty($updateData)) {
                $variant->update($updateData);
            }
        }

        return response()->json([
            'message' => 'Product availability updated successfully',
        ]);
    }
}
