<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Lunar\Models\Product;

final class ProductRepository implements ProductRepositoryInterface
{
    /**
     * Find a product by ID.
     */
    public function find(string $id): ?Product
    {
        return Product::query()->find($id);
    }

    /**
     * Find a product by SKU.
     */
    public function findBySku(string $sku): ?Product
    {
        return Product::query()
            ->whereHas('variants', function ($query) use ($sku): void {
                $query->where('sku', $sku);
            })
            ->first();
    }

    /**
     * Get all products.
     */
    public function all(): Collection
    {
        return Product::query()->get();
    }

    /**
     * Get products with pagination.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Product::query()->paginate($perPage);
    }

    /**
     * Get active products only.
     */
    public function getActive(): Collection
    {
        return Product::query()
            ->where('status', 'published')
            ->get();
    }

    /**
     * Get products by vendor ID.
     */
    public function getByVendor(string $vendorId): Collection
    {
        return Product::query()
            ->where('vendor_id', $vendorId)
            ->get();
    }

    /**
     * Search products by query.
     */
    public function search(string $query): Collection
    {
        return Product::query()
            ->where('name', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->get();
    }

    /**
     * Get products by collection ID.
     */
    public function getByCollection(string $collectionId): Collection
    {
        return Product::query()
            ->whereHas('collections', function ($query) use ($collectionId): void {
                $query->where('collection_id', $collectionId);
            })
            ->get();
    }

    /**
     * Get products by brand ID.
     */
    public function getByBrand(string $brandId): Collection
    {
        return Product::query()
            ->where('brand_id', $brandId)
            ->get();
    }

    /**
     * Get featured products.
     */
    public function getFeatured(int $limit = 10): Collection
    {
        return Product::query()
            ->where('status', 'published')
            ->where('featured', true)
            ->limit($limit)
            ->get();
    }

    /**
     * Create a new product.
     */
    public function create(array $data): Product
    {
        return Product::query()->create($data);
    }

    /**
     * Update a product.
     */
    public function update(string $id, array $data): Product
    {
        $product = $this->find($id);

        if ($product === null) {
            throw new \RuntimeException("Product with ID {$id} not found");
        }

        $product->update($data);

        return $product->refresh();
    }

    /**
     * Delete a product.
     */
    public function delete(string $id): bool
    {
        $product = $this->find($id);

        if ($product === null) {
            return false;
        }

        return (bool) $product->delete();
    }

    /**
     * Get product with relationships loaded.
     */
    public function findWithRelations(string $id, array $relations = []): ?Product
    {
        return Product::query()
            ->with($relations)
            ->find($id);
    }

    /**
     * Check if product exists by ID.
     */
    public function exists(string $id): bool
    {
        return Product::query()->where('id', $id)->exists();
    }

    /**
     * Get products by IDs.
     */
    public function findMany(array $ids): Collection
    {
        return Product::query()->whereIn('id', $ids)->get();
    }

    /**
     * Get low stock products.
     */
    public function getLowStock(int $threshold = 10): Collection
    {
        return Product::query()
            ->whereHas('variants', function ($query) use ($threshold): void {
                $query->where('stock', '<=', $threshold);
            })
            ->get();
    }

    /**
     * Update product stock.
     */
    public function updateStock(string $id, int $quantity): bool
    {
        $product = $this->find($id);

        if ($product === null) {
            return false;
        }

        // Update the default variant's stock
        $variant = $product->variants()->first();

        if ($variant === null) {
            return false;
        }

        $variant->stock = $quantity;

        return $variant->save();
    }
}
