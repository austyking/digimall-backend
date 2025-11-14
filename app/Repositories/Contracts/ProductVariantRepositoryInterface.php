<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;
use Lunar\Models\ProductVariant;

interface ProductVariantRepositoryInterface
{
    /**
     * Find a variant by ID.
     */
    public function find(int $id): ?ProductVariant;

    /**
     * Get all variants for a product.
     */
    public function getByProduct(int $productId): Collection;

    /**
     * Create a new variant.
     */
    public function create(array $data): ProductVariant;

    /**
     * Update a variant.
     */
    public function update(int $id, array $data): ProductVariant;

    /**
     * Delete a variant.
     */
    public function delete(int $id): bool;

    /**
     * Find variant by SKU.
     */
    public function findBySku(string $sku): ?ProductVariant;

    /**
     * Get variants with low stock.
     */
    public function getLowStock(int $threshold): Collection;
}
