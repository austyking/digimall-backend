<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Lunar\Models\Attribute;
use Lunar\Models\Product;
use Lunar\Models\ProductType;

class LunarProductTypeSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed essential product types for Lunar.
     * Based on Lunar's InstallLunar command logic.
     */
    public function run(): void
    {
        if (! ProductType::count()) {
            $this->command->info('Adding a product type.');

            $type = ProductType::create([
                'name' => 'Stock',
            ]);

            $type->mappedAttributes()->attach(
                Attribute::whereAttributeType(
                    Product::morphName()
                )->get()->pluck('id')
            );
        } else {
            $this->command->info('Product types already exist, skipping...');
        }
    }
}
