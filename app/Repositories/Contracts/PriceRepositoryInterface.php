<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;
use Lunar\Models\Currency;
use Lunar\Models\Price;

interface PriceRepositoryInterface
{
    /**
     * Find a price by ID.
     */
    public function find(int $id): ?Price;

    /**
     * Get default currency.
     */
    public function getDefaultCurrency(): ?Currency;

    /**
     * Create a price for a priceable (ProductVariant).
     */
    public function createForPriceable(int $priceableId, string $priceableType, float $amount, ?int $currencyId = null, int $minQuantity = 1): Price;

    /**
     * Update a price.
     */
    public function update(int $id, array $data): Price;

    /**
     * Get prices for a priceable.
     */
    public function getForPriceable(int $priceableId, string $priceableType): Collection;

    /**
     * Find price by priceable, currency, and minimum quantity.
     */
    public function findByPriceableAndCurrency(int $priceableId, string $priceableType, int $currencyId, int $minQuantity = 1): ?Price;
}
