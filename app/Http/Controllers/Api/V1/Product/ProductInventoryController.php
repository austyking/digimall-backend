<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProductInventoryRequest;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller for product inventory management.
 * Provides API v1 endpoints as alternatives to Lunar admin routes.
 */
final class ProductInventoryController extends Controller
{
    public function __construct(
        private readonly ProductService $productService
    ) {}

    /**
     * Get product inventory information.
     *
     * API v1 alternative to: lunar/products/{record}/inventory
     */
    public function show(string $productId): JsonResponse
    {
        $product = $this->productService->findById($productId);

        if (! $product) {
            abort(404, 'Product not found');
        }

        $availableQuantity = $this->productService->getAvailableQuantity($productId);
        $lowStockThreshold = config('lunar.products.low_stock_threshold', 10);

        return response()->json([
            'data' => [
                'product_id' => $productId,
                'total_stock' => $availableQuantity,
                'low_stock_threshold' => $lowStockThreshold,
                'is_low_stock' => $availableQuantity <= $lowStockThreshold,
                'variants' => $product->variants->map(function ($variant) {
                    return [
                        'id' => $variant->id,
                        'sku' => $variant->sku,
                        'stock' => $variant->stock,
                        'purchasable' => $variant->purchasable,
                        'backorder' => $variant->backorder ?? false,
                    ];
                }),
            ],
        ]);
    }

    /**
     * Update product inventory.
     */
    public function update(UpdateProductInventoryRequest $request, string $productId): JsonResponse
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

        $operation = $validated['operation'] ?? 'set';
        $currentStock = $this->productService->getAvailableQuantity($productId);

        $newStock = match ($operation) {
            'increment' => $currentStock + $validated['stock'],
            'decrement' => max(0, $currentStock - $validated['stock']),
            default => $validated['stock'],
        };

        $this->productService->updateStock($productId, $newStock);

        return response()->json([
            'message' => 'Product inventory updated successfully',
            'data' => [
                'previous_stock' => $currentStock,
                'new_stock' => $newStock,
            ],
        ]);
    }

    /**
     * Get low stock products.
     */
    public function lowStock(Request $request): JsonResponse
    {
        $user = $request->user();
        $vendor = $user->vendor;

        if (! $vendor) {
            abort(403, 'User must be associated with a vendor');
        }

        $threshold = $request->input('threshold', config('lunar.products.low_stock_threshold', 10));
        $limit = $request->input('limit', null);

        $products = $this->productService->getLowStock($threshold, $limit);

        // Filter by vendor
        $vendorProducts = $products->filter(function ($product) use ($vendor) {
            return $product->vendor_id === $vendor->id;
        });

        return response()->json([
            'data' => $vendorProducts->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->attribute_data['name'] ?? null,
                    'sku' => $product->attribute_data['sku'] ?? null,
                    'stock' => $this->productService->getAvailableQuantity($product->id),
                    'status' => $product->status,
                ];
            })->values(),
        ]);
    }
}
