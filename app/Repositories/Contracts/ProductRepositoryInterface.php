<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ProductRepositoryInterface
{
    /**
     * Find a product by ID.
     */
    public function find(int $id): ?Product;

    /**
     * Find a product by SKU.
     */
    public function findBySku(string $sku): ?Product;

    /**
     * Get all products.
     */
    public function all(int $perPage = 15): Collection;

    /**
     * Get products with pagination.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * Get active products only.
     */
    public function getActive(): Collection;

    /**
     * Get products by vendor ID.
     */
    public function getByVendor(string $vendorId): Collection;

    /**
     * Search products by query.
     */
    public function search(string $query): Collection;

    /**
     * Filter products by criteria.
     */
    public function filter(array $filters): Collection;

    /**
     * Get products by collection ID.
     */
    public function getByCollection(int $collectionId): Collection;

    /**
     * Find products by collection (alias).
     */
    public function findByCollection(int $collectionId): Collection;

    /**
     * Get products by brand ID.
     */
    public function getByBrand(int $brandId): Collection;

    /**
     * Find products by brand (alias).
     */
    public function findByBrand(int $brandId): Collection;

    /**
     * Find products by status.
     */
    public function findByStatus(string $status): Collection;

    /**
     * Find products by tags.
     */
    public function findByTags(array $tags): Collection;

    /**
     * Find products by price range.
     */
    public function findByPriceRange(int $minPrice, int $maxPrice): Collection;

    /**
     * Get featured products.
     */
    public function getFeatured(int $limit = 10): Collection;

    /**
     * Create a new product.
     */
    public function create(array $data): Product;

    /**
     * Update a product.
     */
    public function update(int $id, array $data): Product;

    /**
     * Delete a product.
     */
    public function delete(int $id): bool;

    /**
     * Get product with relationships loaded.
     */
    public function findWithRelations(int $id, array $relations = []): ?Product;

    /**
     * Check if product exists by ID.
     */
    public function exists(int $id): bool;

    /**
     * Get products by IDs.
     */
    public function findMany(array $ids): Collection;

    /**
     * Get low stock products.
     */
    public function getLowStock(int $threshold = 10): Collection;

    /**
     * Get low stock products (alias).
     */
    public function getLowStockProducts(int $threshold = 10): Collection;

    /**
     * Update product stock.
     */
    public function updateStock(int $id, int $quantity): bool;

    /**
     * Check if product is available.
     */
    public function isAvailable(int $id): bool;

    /**
     * Get available quantity for a product.
     */
    public function getAvailableQuantity(int $id): int;

    /**
     * Count products by vendor.
     */
    public function countByVendor(string $vendorId): int;
}
