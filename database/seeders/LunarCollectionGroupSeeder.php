<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Lunar\Models\CollectionGroup;

class LunarCollectionGroupSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed essential collection groups for Lunar.
     * Based on Lunar's InstallLunar command logic.
     */
    public function run(): void
    {
        if (! CollectionGroup::count()) {
            $this->command->info('Adding an initial collection group');

            CollectionGroup::create([
                'name' => 'Main',
                'handle' => 'main',
            ]);
        } else {
            $this->command->info('Collection groups already exist, skipping...');
        }
    }
}
