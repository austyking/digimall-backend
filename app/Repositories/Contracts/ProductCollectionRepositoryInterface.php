<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;
use Lunar\Models\Collection as LunarCollection;

interface ProductCollectionRepositoryInterface
{
    /**
     * Find a collection by ID.
     */
    public function find(int $id): ?LunarCollection;

    /**
     * Get all collections.
     */
    public function all(): Collection;

    /**
     * Attach products to a collection.
     */
    public function attachProducts(int $collectionId, array $productIds, ?int $startPosition = null): void;

    /**
     * Detach products from a collection.
     */
    public function detachProducts(int $collectionId, array $productIds): void;

    /**
     * Get products in a collection.
     */
    public function getProducts(int $collectionId): Collection;
}
