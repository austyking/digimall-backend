<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Lunar\Models\AttributeGroup;

class LunarAttributeGroupSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed essential attribute groups for Lunar.
     */
    public function run(): void
    {
        $this->command->info('Seeding attribute groups...');

        $groups = [
            [
                'name' => ['en' => 'Product Details'],
                'handle' => 'product-details',
                'position' => 1,
            ],
            [
                'name' => ['en' => 'SEO'],
                'handle' => 'seo',
                'position' => 2,
            ],
            [
                'name' => ['en' => 'Shipping'],
                'handle' => 'shipping',
                'position' => 3,
            ],
            [
                'name' => ['en' => 'Inventory'],
                'handle' => 'inventory',
                'position' => 4,
            ],
        ];

        foreach ($groups as $groupData) {
            AttributeGroup::firstOrCreate(
                ['handle' => $groupData['handle']],
                $groupData
            );
        }

        $this->command->info('Attribute groups seeded successfully!');
    }
}
