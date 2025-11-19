<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\AttachProductsToCollectionDTO;
use App\DTOs\CreateProductDTO;
use App\DTOs\CreateProductVariantDTO;
use App\DTOs\UpdateProductDTO;
use App\DTOs\UpdateProductVariantDTO;
use App\Models\Product;
use App\Repositories\Contracts\PriceRepositoryInterface;
use App\Repositories\Contracts\ProductCollectionRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\ProductVariantRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Lunar\Models\ProductVariant;

final readonly class ProductService
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private ProductVariantRepositoryInterface $variantRepository,
        private ProductCollectionRepositoryInterface $collectionRepository,
        private PriceRepositoryInterface $priceRepository
    ) {}

    /**
     * Find a product by ID.
     */
    public function findById(int $id): ?Product
    {
        return $this->productRepository->find($id);
    }

    /**
     * Get all products for a tenant.
     */
    public function getAllProducts(?int $limit = null): Collection
    {
        return $this->productRepository->all($limit);
    }

    /**
     * Get paginated products for a tenant.
     */
    public function getPaginatedProducts(int $perPage = 15): LengthAwarePaginator
    {
        return $this->productRepository->paginate($perPage);
    }

    /**
     * Search products by query.
     */
    public function searchProducts(string $query, ?int $limit = null): Collection
    {
        return $this->productRepository->search($query, $limit);
    }

    /**
     * Find product by SKU.
     */
    public function findBySku(string $sku): ?Product
    {
        return $this->productRepository->findBySku($sku);
    }

    /**
     * Get products by vendor.
     */
    public function getByVendor(string $vendorId, ?int $limit = null): Collection
    {
        return $this->productRepository->getByVendor($vendorId, $limit);
    }

    /**
     * Get products by collection.
     */
    public function getByCollection(int $collectionId, ?int $limit = null): Collection
    {
        return $this->productRepository->getByCollection($collectionId, $limit);
    }

    /**
     * Get products by brand.
     */
    public function getByBrand(int $brandId, ?int $limit = null): Collection
    {
        return $this->productRepository->getByBrand($brandId, $limit);
    }

    /**
     * Get featured products.
     */
    public function getFeaturedProducts(?int $limit = null): Collection
    {
        return $this->productRepository->getFeatured($limit);
    }

    /**
     * Get products that are in stock.
     */
    public function getInStock(?int $limit = null): Collection
    {
        return $this->productRepository->getInStock($limit);
    }

    /**
     * Get products that are low in stock.
     */
    public function getLowStock(int $threshold = 10, ?int $limit = null): Collection
    {
        return $this->productRepository->getLowStock($threshold, $limit);
    }

    /**
     * Update product stock.
     */
    public function updateStock(int $productId, int $quantity): bool
    {
        return $this->productRepository->updateStock($productId, $quantity);
    }

    /**
     * Check if product is available.
     */
    public function isAvailable(int $productId): bool
    {
        return $this->productRepository->isAvailable($productId);
    }

    /**
     * Get available quantity for a product.
     */
    public function getAvailableQuantity(int $productId): int
    {
        return $this->productRepository->getAvailableQuantity($productId);
    }

    /**
     * Filter products by criteria.
     */
    public function filterProducts(array $filters): Collection
    {
        return $this->productRepository->filter($filters);
    }

    /**
     * Create a new product.
     */
    public function createProduct(CreateProductDTO $dto): Product
    {
        return $this->productRepository->create($dto->toArray());
    }

    /**
     * Update an existing product.
     */
    public function updateProduct(int $productId, UpdateProductDTO $dto): Product
    {
        $product = $this->findById($productId);

        if (! $product) {
            throw new ModelNotFoundException('Product not found');
        }

        return $this->productRepository->update($productId, $dto->toArray());
    }

    /**
     * Delete a product.
     */
    public function deleteProduct(int $productId): bool
    {
        $product = $this->findById($productId);

        if (! $product) {
            throw new ModelNotFoundException('Product not found');
        }

        return $this->productRepository->delete($productId);
    }

    /**
     * Attach products to a collection.
     */
    public function attachToCollection(AttachProductsToCollectionDTO $dto): void
    {
        $this->collectionRepository->attachProducts(
            $dto->collectionId,
            $dto->productIds
        );
    }

    /**
     * Detach products from a collection.
     */
    public function detachFromCollection(int $collectionId, array $productIds): void
    {
        $this->collectionRepository->detachProducts($collectionId, $productIds);
    }

    /**
     * Update product status.
     */
    public function updateStatus(int $productId, string $status): bool
    {
        $product = $this->findById($productId);

        if (! $product) {
            throw new ModelNotFoundException('Product not found');
        }

        $product->status = $status;

        return $product->save();
    }

    /**
     * Create a new product variant.
     */
    public function createVariant(int $productId, CreateProductVariantDTO $dto): ProductVariant
    {
        return \DB::transaction(function () use ($productId, $dto) {
            $product = $this->findById($productId);
            if (! $product) {
                throw new ModelNotFoundException('Product not found');
            }

            // Create the variant
            $variant = $this->variantRepository->create([
                'product_id' => $productId,
                'sku' => $dto->sku,
                'stock' => $dto->stock,
                'purchasable' => $dto->purchasable,
                'unit_quantity' => $dto->unitQuantity,
                'tax_class_id' => $dto->taxClassId,
                'backorder' => $dto->backorder,
            ]);

            // Create price for variant
            if ($dto->price !== null) {
                $currencyId = $dto->currencyId;
                if (empty($currencyId)) {
                    $defaultCurrency = $this->priceRepository->getDefaultCurrency();
                    $currencyId = $defaultCurrency?->id;
                }

                if (empty($currencyId)) {
                    abort(400, 'A currency is required to create a price for variant');
                }
                $variant->prices()->create([
                    'price' => $dto->price,
                    'currency_id' => $currencyId,
                ]);
                $this->priceRepository->createForPriceable(
                    $variant->id,
                    ProductVariant::class,
                    $dto->price,
                    $currencyId
                );
            }

            // Attach option values if provided
            if (! empty($dto->values)) {
                $variant->values()->sync($dto->values);
            }

            return $variant->fresh(['prices.currency', 'values.option']);
        });
    }

    /**
     * Update a product variant.
     */
    public function updateVariant(int $productId, int $variantId, UpdateProductVariantDTO $dto): ProductVariant
    {
        $product = $this->findById($productId);

        if (! $product) {
            throw new ModelNotFoundException('Product not found');
        }

        // Get variant through repository
        $variant = $this->variantRepository->find($variantId);

        if (! $variant || $variant->product_id !== $productId) {
            throw new ModelNotFoundException('Variant not found for this product');
        }

        // Update variant attributes using DTO
        $updateData = array_filter([
            'sku' => $dto->sku,
            'stock' => $dto->stock,
            'purchasable' => $dto->purchasable,
            'unit_quantity' => $dto->unitQuantity,
            'tax_class_id' => $dto->taxClassId,
            'backorder' => $dto->backorder,
        ], fn ($value) => $value !== null);

        if (! empty($updateData)) {
            $variant = $this->variantRepository->update($variantId, $updateData);
        }

        // Update price if provided
        if ($dto->price !== null) {
            $currency = $this->priceRepository->getDefaultCurrency();

            if ($currency) {
                $existingPrice = $this->priceRepository->findByPriceableAndCurrency(
                    $variant->id,
                    ProductVariant::class,
                    $currency->id,
                    1
                );

                if ($existingPrice) {
                    $this->priceRepository->update($existingPrice->id, ['price' => $dto->price]);
                } else {
                    $this->priceRepository->createForPriceable(
                        $variant->id,
                        ProductVariant::class,
                        $dto->price,
                        $currency->id,
                        1
                    );
                }
            }
        }

        // Update option values if provided
        if ($dto->values !== null) {
            $variant->values()->sync($dto->values);
        }

        return $variant->fresh(['prices.currency', 'values.option']);
    }

    /**
     * Delete a product variant.
     */
    public function deleteVariant(int $productId, int $variantId): bool
    {
        $product = $this->findById($productId);

        if (! $product) {
            throw new ModelNotFoundException('Product not found');
        }

        $variant = $this->variantRepository->find($variantId);

        if (! $variant || $variant->product_id !== $productId) {
            throw new ModelNotFoundException('Variant not found for this product');
        }

        return $this->variantRepository->delete($variantId);
    }

    /**
     * Get aggregated availability data for a product.
     */
    public function getAvailabilityData(int $productId): array
    {
        $product = $this->findById($productId);

        if (! $product) {
            return [
                'is_available' => false,
                'stock' => 0,
                'backorder' => 0,
                'purchasable' => 'in_stock',
            ];
        }

        $totalStock = 0;
        $totalBackorder = 0;
        $purchasable = 'in_stock';
        $isAvailable = false;

        foreach ($product->variants as $variant) {
            $totalStock += $variant->stock;
            $totalBackorder += $variant->backorder;

            // Determine overall purchasable mode (prioritize 'always' if any variant has it)
            if ($variant->purchasable === 'always') {
                $purchasable = 'always';
            } elseif ($variant->purchasable === 'backorder' && $purchasable !== 'always') {
                $purchasable = 'backorder';
            }

            // Check if this variant is available
            $variantAvailable = match ($variant->purchasable) {
                'always' => true,
                'in_stock' => $variant->stock > 0,
                'backorder' => $variant->stock > 0 || $variant->backorder > 0,
                default => false,
            };

            if ($variantAvailable) {
                $isAvailable = true;
            }
        }

        return [
            'is_available' => $isAvailable,
            'stock' => $totalStock,
            'backorder' => $totalBackorder,
            'purchasable' => $purchasable,
        ];
    }

    /**
     * Get filtered products with pagination for admin oversight.
     */
    public function getFilteredProductsPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->productRepository->filterPaginated($filters, $perPage);
    }

    /**
     * Get products pending admin review.
     */
    public function getPendingReviewProducts(int $perPage = 15): LengthAwarePaginator
    {
        return $this->productRepository->getPendingReview($perPage);
    }

    /**
     * Approve a product for public visibility.
     */
    public function approveProduct(int $productId): bool
    {
        return $this->productRepository->updateStatus($productId, 'active');
    }

    /**
     * Reject a product with reason.
     */
    public function rejectProduct(int $productId, string $reason): bool
    {
        // Update status and store rejection reason
        $this->productRepository->update($productId, [
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);

        return true;
    }

    /**
     * Get product statistics for admin dashboard.
     */
    public function getProductStatistics(): array
    {
        $totalProducts = $this->productRepository->count();
        $activeProducts = $this->productRepository->countByStatus('active');
        $pendingProducts = $this->productRepository->countByStatus('pending');
        $rejectedProducts = $this->productRepository->countByStatus('rejected');
        $lowStockProducts = $this->productRepository->countLowStock();

        return [
            'total_products' => $totalProducts,
            'active_products' => $activeProducts,
            'pending_products' => $pendingProducts,
            'rejected_products' => $rejectedProducts,
            'low_stock_products' => $lowStockProducts,
        ];
    }
}
