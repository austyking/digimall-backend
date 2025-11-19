<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Lunar\Models\Currency;

class LunarCurrencySeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed essential currencies for Lunar.
     * Based on Lunar's InstallLunar command logic.
     */
    public function run(): void
    {
        if (! Currency::whereDefault(true)->exists()) {
            $this->command->info('Adding a default currency (USD)');

            Currency::create([
                'code' => 'USD',
                'name' => 'US Dollar',
                'exchange_rate' => 1,
                'decimal_places' => 2,
                'default' => true,
                'enabled' => true,
            ]);
        } else {
            $this->command->info('Default currency already exists, skipping...');
        }
    }
}
