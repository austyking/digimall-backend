<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Lunar\Models\Currency;

class CurrencyFactory extends Factory
{
    protected $model = Currency::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->currencyCode,
            'name' => $this->faker->word.' Currency',
            'symbol' => $this->faker->randomElement(['GHS', 'USD', 'EUR']),
            'decimal_places' => 2,
            'default' => false,
            'enabled' => true,
            'exchange_rate' => $this->faker->randomFloat(2, 1, 10),
        ];
    }

    public function default(): self
    {
        return $this->state([
            'default' => true,
            'code' => 'GHS',
            'symbol' => 'GHS',
            'name' => 'Ghanaian Cedi',
            'enabled' => true,
        ]);
    }
}
