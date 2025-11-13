<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Repositories\Contracts\PriceRepositoryInterface;
use Illuminate\Support\Collection;
use Lunar\Models\Currency;
use Lunar\Models\Price;

final class PriceRepository implements PriceRepositoryInterface
{
    /**
     * Find a price by ID.
     */
    public function find(int $id): ?Price
    {
        return Price::query()->find($id);
    }

    /**
     * Get default currency.
     */
    public function getDefaultCurrency(): ?Currency
    {
        return Currency::query()->where('default', true)->first();
    }

    /**
     * Create a price for a priceable (ProductVariant).
     */
    public function createForPriceable(string $priceableId, string $priceableType, float $amount, ?int $currencyId = null, int $minQuantity = 1): Price
    {
        if ($currencyId === null) {
            $currency = $this->getDefaultCurrency();
            $currencyId = $currency?->id;
        }

        return Price::query()->create([
            'priceable_id' => $priceableId,
            'priceable_type' => $priceableType,
            'currency_id' => $currencyId,
            'price' => (int) ($amount * 100), // Convert to cents
            'min_quantity' => $minQuantity,
        ]);
    }

    /**
     * Update a price.
     */
    public function update(int $id, array $data): Price
    {
        $price = $this->find($id);

        if (! $price) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Price not found');
        }

        // Convert price to cents if provided
        if (isset($data['price']) && ! isset($data['skip_conversion'])) {
            $data['price'] = (int) ($data['price'] * 100);
        }

        $price->update($data);

        return $price->fresh();
    }

    /**
     * Get prices for a priceable.
     */
    public function getForPriceable(string $priceableId, string $priceableType): Collection
    {
        return Price::query()
            ->where('priceable_id', $priceableId)
            ->where('priceable_type', $priceableType)
            ->with('currency')
            ->get();
    }

    /**
     * Find price by priceable, currency, and minimum quantity.
     */
    public function findByPriceableAndCurrency(string $priceableId, string $priceableType, int $currencyId, int $minQuantity = 1): ?Price
    {
        return Price::query()
            ->where('priceable_id', $priceableId)
            ->where('priceable_type', $priceableType)
            ->where('currency_id', $currencyId)
            ->where('min_quantity', $minQuantity)
            ->first();
    }
}
