<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Repositories\Contracts\ProductVariantRepositoryInterface;
use Illuminate\Support\Collection;
use Lunar\Models\ProductVariant;

final class ProductVariantRepository implements ProductVariantRepositoryInterface
{
    /**
     * Find a variant by ID.
     */
    public function find(int $id): ?ProductVariant
    {
        return ProductVariant::query()->find($id);
    }

    /**
     * Get all variants for a product.
     */
    public function getByProduct(int $productId): Collection
    {
        return ProductVariant::query()
            ->where('product_id', $productId)
            ->with(['prices.currency', 'values.option', 'taxClass'])
            ->get();
    }

    /**
     * Create a new variant.
     */
    public function create(array $data): ProductVariant
    {
        return ProductVariant::query()->create($data);
    }

    /**
     * Update a variant.
     */
    public function update(int $id, array $data): ProductVariant
    {
        $variant = $this->find($id);

        if (! $variant) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Variant not found');
        }

        $variant->update($data);

        return $variant->fresh();
    }

    /**
     * Delete a variant.
     */
    public function delete(int $id): bool
    {
        $variant = $this->find($id);

        if (! $variant) {
            return false;
        }

        return (bool) $variant->delete();
    }

    /**
     * Find variant by SKU.
     */
    public function findBySku(string $sku): ?ProductVariant
    {
        return ProductVariant::query()->where('sku', $sku)->first();
    }

    /**
     * Get variants with low stock.
     */
    public function getLowStock(int $threshold): Collection
    {
        return ProductVariant::query()
            ->where('stock', '<=', $threshold)
            ->where('stock', '>', 0)
            ->with(['product', 'prices.currency'])
            ->get();
    }
}
