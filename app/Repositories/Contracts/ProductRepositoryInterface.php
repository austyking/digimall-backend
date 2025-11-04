<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Lunar\Models\Product;

interface ProductRepositoryInterface
{
    /**
     * Find a product by ID.
     */
    public function find(string $id): ?Product;

    /**
     * Find a product by SKU.
     */
    public function findBySku(string $sku): ?Product;

    /**
     * Get all products.
     */
    public function all(): Collection;

    /**
     * Get products with pagination.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate(int $perPage = 15);

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
     * Get products by collection ID.
     */
    public function getByCollection(string $collectionId): Collection;

    /**
     * Get products by brand ID.
     */
    public function getByBrand(string $brandId): Collection;

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
    public function update(string $id, array $data): Product;

    /**
     * Delete a product.
     */
    public function delete(string $id): bool;

    /**
     * Get product with relationships loaded.
     */
    public function findWithRelations(string $id, array $relations = []): ?Product;

    /**
     * Check if product exists by ID.
     */
    public function exists(string $id): bool;

    /**
     * Get products by IDs.
     */
    public function findMany(array $ids): Collection;

    /**
     * Get low stock products.
     */
    public function getLowStock(int $threshold = 10): Collection;

    /**
     * Update product stock.
     */
    public function updateStock(string $id, int $quantity): bool;
}
