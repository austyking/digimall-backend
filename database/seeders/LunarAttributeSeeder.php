<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Lunar\FieldTypes\TranslatedText;
use Lunar\Models\Attribute;
use Lunar\Models\AttributeGroup;
use Lunar\Models\Collection;
use Lunar\Models\Product;

class LunarAttributeSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed essential attributes for Lunar.
     * Based on Lunar's InstallLunar command logic.
     */
    public function run(): void
    {
        if (! Attribute::count()) {
            $this->command->info('Setting up initial attributes');

            $group = AttributeGroup::create([
                'attributable_type' => Product::morphName(),
                'name' => collect([
                    'en' => 'Details',
                ]),
                'handle' => 'details',
                'position' => 1,
            ]);

            $collectionGroup = AttributeGroup::create([
                'attributable_type' => Collection::morphName(),
                'name' => collect([
                    'en' => 'Details',
                ]),
                'handle' => 'collection_details',
                'position' => 1,
            ]);

            Attribute::create([
                'attribute_type' => 'product',
                'attribute_group_id' => $group->id,
                'position' => 1,
                'name' => [
                    'en' => 'Name',
                ],
                'handle' => 'name',
                'section' => 'main',
                'type' => TranslatedText::class,
                'required' => true,
                'default_value' => null,
                'configuration' => [
                    'richtext' => false,
                ],
                'system' => true,
                'description' => [
                    'en' => '',
                ],
            ]);

            Attribute::create([
                'attribute_type' => 'collection',
                'attribute_group_id' => $collectionGroup->id,
                'position' => 1,
                'name' => [
                    'en' => 'Name',
                ],
                'handle' => 'name',
                'section' => 'main',
                'type' => TranslatedText::class,
                'required' => true,
                'default_value' => null,
                'configuration' => [
                    'richtext' => false,
                ],
                'system' => true,
                'description' => [
                    'en' => '',
                ],
            ]);

            Attribute::create([
                'attribute_type' => 'product',
                'attribute_group_id' => $group->id,
                'position' => 2,
                'name' => [
                    'en' => 'Description',
                ],
                'handle' => 'description',
                'section' => 'main',
                'type' => TranslatedText::class,
                'required' => false,
                'default_value' => null,
                'configuration' => [
                    'richtext' => true,
                ],
                'system' => false,
                'description' => [
                    'en' => '',
                ],
            ]);

            Attribute::create([
                'attribute_type' => 'collection',
                'attribute_group_id' => $collectionGroup->id,
                'position' => 2,
                'name' => [
                    'en' => 'Description',
                ],
                'handle' => 'description',
                'section' => 'main',
                'type' => TranslatedText::class,
                'required' => false,
                'default_value' => null,
                'configuration' => [
                    'richtext' => true,
                ],
                'system' => false,
                'description' => [
                    'en' => '',
                ],
            ]);
        } else {
            $this->command->info('Attributes already exist, skipping...');
        }
    }
}
