<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Support\Collection;
use Lunar\Models\Product;

final readonly class ProductService
{
    public function __construct(
        private ProductRepositoryInterface $productRepository
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
}
