<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LunarDatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed Lunar's essential database data.
     * This replicates the seeding logic from Lunar's InstallLunar command.
     */
    public function run(): void
    {
        $this->command->info('Seeding Lunar essential data...');

        // Run individual seeders
        $this->call([
            LunarLanguageSeeder::class,
            LunarCurrencySeeder::class,
            LunarCollectionGroupSeeder::class,
            LunarCustomerGroupSeeder::class,
            LunarTaxSeeder::class,
            LunarAttributeSeeder::class,
            LunarProductTypeSeeder::class,
        ]);

        $this->command->info('Lunar essential data seeded successfully!');
    }
}
