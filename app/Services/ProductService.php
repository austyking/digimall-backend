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
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
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
    public function findById(string $id): ?Product
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
    public function getByCollection(string $collectionId, ?int $limit = null): Collection
    {
        return $this->productRepository->getByCollection($collectionId, $limit);
    }

    /**
     * Get products by brand.
     */
    public function getByBrand(string $brandId, ?int $limit = null): Collection
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
    public function updateStock(string $productId, int $quantity): bool
    {
        return $this->productRepository->updateStock($productId, $quantity);
    }

    /**
     * Check if product is available.
     */
    public function isAvailable(string $productId): bool
    {
        return $this->productRepository->isAvailable($productId);
    }

    /**
     * Get available quantity for a product.
     */
    public function getAvailableQuantity(string $productId): int
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
    public function updateProduct(string $productId, UpdateProductDTO $dto): Product
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
    public function deleteProduct(string $productId): bool
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
    public function detachFromCollection(string $collectionId, array $productIds): void
    {
        $this->collectionRepository->detachProducts($collectionId, $productIds);
    }

    /**
     * Update product status.
     */
    public function updateStatus(string $productId, string $status): bool
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
    public function createVariant(string $productId, CreateProductVariantDTO $dto): ProductVariant
    {
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
            $this->priceRepository->createForPriceable(
                $variant->id,
                ProductVariant::class,
                $dto->price
            );
        }

        // Attach option values if provided
        if ($dto->values !== null && ! empty($dto->values)) {
            $variant->values()->sync($dto->values);
        }

        return $variant->fresh(['prices.currency', 'values.option']);
    }

    /**
     * Update a product variant.
     */
    public function updateVariant(string $productId, string $variantId, UpdateProductVariantDTO $dto): ProductVariant
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
    public function deleteVariant(string $productId, string $variantId): bool
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
}
