<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Lunar\Models\TaxClass;
use Lunar\Models\TaxZone;

class LunarTaxSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed essential tax data for Lunar.
     * Based on Lunar's InstallLunar command logic.
     */
    public function run(): void
    {
        if (! TaxClass::count()) {
            $this->command->info('Adding a default tax class.');

            TaxClass::create([
                'name' => 'Default Tax Class',
                'default' => true,
            ]);
        }

        if (! TaxZone::count()) {
            $this->command->info('Adding a default tax zone.');

            $taxZone = TaxZone::create([
                'name' => 'Default Tax Zone',
                'zone_type' => 'country',
                'price_display' => 'tax_exclusive',
                'default' => true,
                'active' => true,
            ]);

            // Import countries first if not already done
            if (! \Lunar\Models\Country::count()) {
                $this->command->info('Importing countries first...');
                $this->command->call('lunar:import:address-data');
            }

            $taxZone->countries()->createMany(
                \Lunar\Models\Country::get()->map(fn ($country) => [
                    'country_id' => $country->id,
                ])
            );
        } else {
            $this->command->info('Tax data already exists, skipping...');
        }
    }
}
