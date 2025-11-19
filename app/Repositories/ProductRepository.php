<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

final class ProductRepository implements ProductRepositoryInterface
{
    /**
     * Find a product by ID.
     */
    public function find(int $id): ?Product
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
    public function all(int $perPage = 15): Collection
    {
        return Product::query()->limit($perPage)->get();
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
            ->where('attribute_data->name->value', 'like', "%{$query}%")
            ->orWhere('attribute_data->description->value', 'like', "%{$query}%")
            ->get();
    }

    /**
     * Filter products by criteria.
     */
    public function filter(array $filters): Collection
    {
        $query = Product::query();

        // Text search
        if (! empty($filters['query'])) {
            $query->where(function ($q) use ($filters): void {
                $q->where('attribute_data->name->value', 'like', '%'.$filters['query'].'%')
                    ->orWhere('attribute_data->description->value', 'like', '%'.$filters['query'].'%')
                    ->orWhere('attribute_data->short_description->value', 'like', '%'.$filters['query'].'%');
            });
        }

        // Status filter
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Brand filter
        if (! empty($filters['brand_id'])) {
            $query->where('brand_id', $filters['brand_id']);
        }

        // Product type filter
        if (! empty($filters['product_type_id'])) {
            $query->where('product_type_id', $filters['product_type_id']);
        }

        // Vendor filter
        if (! empty($filters['vendor_id'])) {
            $query->where('vendor_id', $filters['vendor_id']);
        }

        // ID filter (for whereIn scenarios)
        if (! empty($filters['id']) && is_array($filters['id'])) {
            $query->whereIn('id', $filters['id']);
        }

        // Collection filter
        if (! empty($filters['collection_id'])) {
            $query->whereHas('collections', function ($q) use ($filters): void {
                $q->where('collection_id', $filters['collection_id']);
            });
        }

        // Tags filter
        if (! empty($filters['tags']) && is_array($filters['tags'])) {
            $query->whereHas('tags', function ($q) use ($filters): void {
                $q->whereIn('tag_id', $filters['tags']);
            });
        }

        // Price range filter
        if (isset($filters['min_price']) || isset($filters['max_price'])) {
            $query->whereHas('variants', function ($q) use ($filters): void {
                if (isset($filters['min_price'])) {
                    $q->where('price', '>=', $filters['min_price']);
                }
                if (isset($filters['max_price'])) {
                    $q->where('price', '<=', $filters['max_price']);
                }
            });
        }

        // In stock filter
        if (isset($filters['in_stock'])) {
            if ($filters['in_stock']) {
                $query->whereHas('variants', function ($q): void {
                    $q->where('stock', '>', 0)->where('purchasable', true);
                });
            } else {
                $query->whereDoesntHave('variants', function ($q): void {
                    $q->where('stock', '>', 0)->where('purchasable', true);
                });
            }
        }

        // Sorting
        if (! empty($filters['sort_by'])) {
            $direction = $filters['sort_direction'] ?? 'asc';
            switch ($filters['sort_by']) {
                case 'name':
                    $query->orderBy('attribute_data->name', $direction);
                    break;
                case 'price':
                    $query->leftJoin('product_variants', 'products.id', '=', 'product_variants.product_id')
                        ->orderBy('product_variants.price', $direction)
                        ->select('products.*');
                    break;
                case 'created_at':
                    $query->orderBy('created_at', $direction);
                    break;
                case 'updated_at':
                    $query->orderBy('updated_at', $direction);
                    break;
                default:
                    $query->orderBy('created_at', 'desc');
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Limit
        if (! empty($filters['limit'])) {
            $query->limit($filters['limit']);
        }

        // Offset
        if (! empty($filters['offset'])) {
            $query->offset($filters['offset']);
        }

        return $query->get();
    }

    /**
     * Get products by collection ID.
     */
    public function getByCollection(int $collectionId): Collection
    {
        return Product::query()
            ->whereHas('collections', function ($query) use ($collectionId): void {
                $query->where('collection_id', $collectionId);
            })
            ->get();
    }

    /**
     * Find products by collection (alias).
     */
    public function findByCollection(int $collectionId): Collection
    {
        return $this->getByCollection($collectionId);
    }

    /**
     * Get products by brand ID.
     */
    public function getByBrand(int $brandId): Collection
    {
        return Product::query()
            ->where('brand_id', $brandId)
            ->get();
    }

    /**
     * Find products by brand (alias).
     */
    public function findByBrand(int $brandId): Collection
    {
        return $this->getByBrand($brandId);
    }

    /**
     * Find products by status.
     */
    public function findByStatus(string $status): Collection
    {
        return Product::query()
            ->where('status', $status)
            ->get();
    }

    /**
     * Find products by tags.
     */
    public function findByTags(array $tags): Collection
    {
        // Check if tags relationship exists on Product model
        $product = new Product;
        if (! method_exists($product, 'tags')) {
            // Return empty collection if tags relationship doesn't exist
            return collect();
        }

        return Product::query()
            ->whereHas('tags', function ($query) use ($tags): void {
                $query->whereIn('tag_id', $tags);
            })
            ->get();
    }

    /**
     * Find products by price range.
     */
    public function findByPriceRange(int $minPrice, int $maxPrice): Collection
    {
        return Product::query()
            ->whereHas('prices', function ($priceQuery) use ($minPrice, $maxPrice): void {
                $priceQuery->whereBetween('price', [$minPrice, $maxPrice]);
            })
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
    public function update(int $id, array $data): Product
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
    public function delete(int $id): bool
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
    public function findWithRelations(int $id, array $relations = []): ?Product
    {
        return Product::query()
            ->with($relations)
            ->find($id);
    }

    /**
     * Check if product exists by ID.
     */
    public function exists(int $id): bool
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
     * Get low stock products (alias).
     */
    public function getLowStockProducts(int $threshold = 10): Collection
    {
        return $this->getLowStock($threshold);
    }

    /**
     * Update product stock.
     */
    public function updateStock(int $id, int $quantity): bool
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

    public function isAvailable(int $id): bool
    {
        $product = $this->find($id);

        if ($product === null) {
            return false;
        }

        // Check the default variant's stock
        $variant = $product->variants()->first();

        if ($variant === null) {
            return false;
        }

        return $variant->stock > 0;
    }

    public function getAvailableQuantity(int $id): int
    {
        $product = $this->find($id);

        if ($product === null) {
            return 0;
        }

        // Sum stock from all variants
        return $product->variants->sum('stock');
    }

    /**
     * Count products by vendor.
     */
    public function countByVendor(string $vendorId): int
    {
        return Product::query()
            ->where('vendor_id', $vendorId)
            ->count();
    }

    /**
     * Get filtered products with pagination.
     */
    public function filterPaginated(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Product::query();

        // Apply the same filters as the filter method
        $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    /**
     * Get products pending admin review.
     */
    public function getPendingReview(int $perPage = 15): LengthAwarePaginator
    {
        return Product::query()
            ->where('status', 'pending')
            ->paginate($perPage);
    }

    /**
     * Update product status.
     */
    public function updateStatus(int $id, string $status): bool
    {
        $product = $this->find($id);

        if (! $product) {
            return false;
        }

        $product->status = $status;
        $product->save();

        return true;
    }

    /**
     * Count all products.
     */
    public function count(): int
    {
        return Product::query()->count();
    }

    /**
     * Count products by status.
     */
    public function countByStatus(string $status): int
    {
        return Product::query()
            ->where('status', $status)
            ->count();
    }

    /**
     * Count low stock products.
     */
    public function countLowStock(int $threshold = 10): int
    {
        return Product::query()
            ->whereHas('variants', function ($query) use ($threshold): void {
                $query->where('stock', '<=', $threshold)
                    ->where('stock', '>', 0);
            })
            ->count();
    }

    /**
     * Apply filters to query (extracted from filter method).
     */
    private function applyFilters($query, array $filters): void
    {
        // Text search
        if (! empty($filters['query'])) {
            $query->where(function ($q) use ($filters): void {
                $q->where('attribute_data->name->value', 'like', '%'.$filters['query'].'%')
                    ->orWhere('attribute_data->description->value', 'like', '%'.$filters['query'].'%')
                    ->orWhere('attribute_data->short_description->value', 'like', '%'.$filters['query'].'%');
            });
        }

        // Status filter
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Brand filter
        if (! empty($filters['brand_id'])) {
            $query->where('brand_id', $filters['brand_id']);
        }

        // Product type filter
        if (! empty($filters['product_type_id'])) {
            $query->where('product_type_id', $filters['product_type_id']);
        }

        // Vendor filter
        if (! empty($filters['vendor_id'])) {
            $query->where('vendor_id', $filters['vendor_id']);
        }

        // ID filter (for whereIn scenarios)
        if (! empty($filters['id']) && is_array($filters['id'])) {
            $query->whereIn('id', $filters['id']);
        }

        // Collection filter
        if (! empty($filters['collection_id'])) {
            $query->whereHas('collections', function ($q) use ($filters): void {
                $q->where('collection_id', $filters['collection_id']);
            });
        }

        // Tags filter
        if (! empty($filters['tags']) && is_array($filters['tags'])) {
            $query->whereHas('tags', function ($q) use ($filters): void {
                $q->whereIn('tag_id', $filters['tags']);
            });
        }

        // Price range filter
        if (isset($filters['min_price']) || isset($filters['max_price'])) {
            $query->whereHas('variants', function ($q) use ($filters): void {
                if (isset($filters['min_price'])) {
                    $q->where('price', '>=', $filters['min_price']);
                }
                if (isset($filters['max_price'])) {
                    $q->where('price', '<=', $filters['max_price']);
                }
            });
        }

        // In stock filter
        if (isset($filters['in_stock'])) {
            if ($filters['in_stock']) {
                $query->whereHas('variants', function ($q): void {
                    $q->where('stock', '>', 0)->where('purchasable', true);
                });
            } else {
                $query->whereDoesntHave('variants', function ($q): void {
                    $q->where('stock', '>', 0)->where('purchasable', true);
                });
            }
        }

        // Sorting
        if (! empty($filters['sort_by'])) {
            $direction = $filters['sort_direction'] ?? 'asc';
            switch ($filters['sort_by']) {
                case 'name':
                    $query->orderBy('attribute_data->name', $direction);
                    break;
                case 'price':
                    $query->leftJoin('product_variants', 'products.id', '=', 'product_variants.product_id')
                        ->orderBy('product_variants.price', $direction)
                        ->select('products.*');
                    break;
                case 'created_at':
                    $query->orderBy('created_at', $direction);
                    break;
                case 'updated_at':
                    $query->orderBy('updated_at', $direction);
                    break;
                default:
                    $query->orderBy('created_at', 'desc');
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }
    }
}
