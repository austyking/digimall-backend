<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Lunar\Models\CustomerGroup;

class LunarCustomerGroupSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed essential customer groups for Lunar.
     * Based on Lunar's InstallLunar command logic.
     */
    public function run(): void
    {
        if (! CustomerGroup::whereDefault(true)->exists()) {
            $this->command->info('Adding a default customer group.');

            CustomerGroup::create([
                'name' => 'Retail',
                'handle' => 'retail',
                'default' => true,
            ]);
        } else {
            $this->command->info('Default customer group already exists, skipping...');
        }
    }
}
