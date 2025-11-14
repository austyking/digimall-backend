<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Repositories\Contracts\ProductCollectionRepositoryInterface;
use Illuminate\Support\Collection;
use Lunar\Models\Collection as LunarCollection;

final class ProductCollectionRepository implements ProductCollectionRepositoryInterface
{
    /**
     * Find a collection by ID.
     */
    public function find(int $id): ?LunarCollection
    {
        return LunarCollection::query()->find($id);
    }

    /**
     * Get all collections.
     */
    public function all(): Collection
    {
        return LunarCollection::query()->get();
    }

    /**
     * Attach products to a collection.
     */
    public function attachProducts(int $collectionId, array $productIds, ?int $startPosition = null): void
    {
        $collection = $this->find($collectionId);

        if (! $collection) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Collection not found');
        }

        // Calculate position if not provided
        if ($startPosition === null) {
            $startPosition = $collection->products()->count() + 1;
        }

        $syncData = [];
        foreach ($productIds as $productId) {
            $syncData[$productId] = ['position' => $startPosition++];
        }

        $collection->products()->syncWithoutDetaching($syncData);
    }

    /**
     * Detach products from a collection.
     */
    public function detachProducts(int $collectionId, array $productIds): void
    {
        $collection = $this->find($collectionId);

        if (! $collection) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Collection not found');
        }

        // If empty array, detach all products
        if (empty($productIds)) {
            $collection->products()->detach();
        } else {
            $collection->products()->detach($productIds);
        }
    }

    /**
     * Get products in a collection.
     */
    public function getProducts(int $collectionId): Collection
    {
        $collection = $this->find($collectionId);

        if (! $collection) {
            return collect();
        }

        return $collection->products;
    }

    /**
     * Get collections that contain a specific product.
     */
    public function getCollectionsByProduct(int $productId): Collection
    {
        return \Lunar\Models\Collection::whereHas('products', function ($query) use ($productId) {
            $query->where('products.id', $productId);
        })->get();
    }
}
