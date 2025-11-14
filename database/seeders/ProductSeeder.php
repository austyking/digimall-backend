<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Lunar\FieldTypes\Text;
use Lunar\Models\Brand;
use Lunar\Models\Collection;
use Lunar\Models\CollectionGroup;
use Lunar\Models\Currency;
use Lunar\Models\Language;
use Lunar\Models\Price;
use Lunar\Models\Product;
use Lunar\Models\ProductType;
use Lunar\Models\ProductVariant;
use Lunar\Models\TaxClass;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default language if not exists
        $language = Language::firstOrCreate(
            ['code' => 'en'],
            [
                'name' => 'English',
                'default' => true,
            ]
        );

        // Create default currency if not exists
        $currency = Currency::firstOrCreate(
            ['code' => 'GHS'],
            [
                'name' => 'Ghana Cedi',
                'exchange_rate' => 1,
                'decimal_places' => 2,
                'enabled' => true,
                'default' => true,
            ]
        );

        // Create default tax class if not exists
        $taxClass = TaxClass::firstOrCreate(
            ['name' => 'Default'],
            ['default' => true]
        );

        // Create product type
        $productType = ProductType::firstOrCreate(
            ['name' => 'Physical'],
            []
        );

        // Create brands
        $brands = [
            [
                'name' => 'Generic',
            ],
            [
                'name' => 'Premium',
            ],
            [
                'name' => 'Budget',
            ],
        ];

        foreach ($brands as $brandData) {
            Brand::firstOrCreate(['name' => $brandData['name']], $brandData);
        }

        // Create default collection group if not exists
        $collectionGroup = CollectionGroup::firstOrCreate(
            ['name' => 'Default'],
            ['handle' => 'default']
        );

        // Create collections
        $collections = [
            ['name' => 'Featured', 'type' => 'static'],
            ['name' => 'New Arrivals', 'type' => 'static'],
            ['name' => 'Best Sellers', 'type' => 'static'],
        ];

        foreach ($collections as $collectionData) {
            Collection::firstOrCreate(
                [
                    'collection_group_id' => $collectionGroup->id,
                    'attribute_data->name->value' => $collectionData['name'],
                ],
                [
                    'collection_group_id' => $collectionGroup->id,
                    'type' => $collectionData['type'],
                    'attribute_data' => [
                        'name' => new Text($collectionData['name']),
                    ],
                ]
            );
        }

        // Create sample products with variants
        $products = [
            [
                'name' => 'Test Product 1',
                'status' => 'published',
                'brand' => 'Generic',
                'variants' => [
                    [
                        'sku' => 'TEST-001-S',
                        'stock' => 50,
                        'purchasable' => 'in_stock',
                        'backorder' => 0,
                        'price' => 9999, // GHS 99.99
                    ],
                    [
                        'sku' => 'TEST-001-M',
                        'stock' => 30,
                        'purchasable' => 'in_stock',
                        'backorder' => 0,
                        'price' => 12999, // GHS 129.99
                    ],
                ],
            ],
            [
                'name' => 'Test Product 2',
                'status' => 'published',
                'brand' => 'Premium',
                'variants' => [
                    [
                        'sku' => 'TEST-002',
                        'stock' => 0,
                        'purchasable' => 'backorder',
                        'backorder' => 20,
                        'price' => 19999, // GHS 199.99
                    ],
                ],
            ],
            [
                'name' => 'Test Product 3',
                'status' => 'published',
                'brand' => 'Generic',
                'variants' => [
                    [
                        'sku' => 'TEST-003',
                        'stock' => 100,
                        'purchasable' => 'always',
                        'backorder' => 0,
                        'price' => 4999, // GHS 49.99
                    ],
                ],
            ],
        ];

        foreach ($products as $productData) {
            $brand = Brand::where('name', $productData['brand'])->first();

            $product = Product::firstOrCreate(
                ['attribute_data->name->value' => $productData['name']],
                [
                    'product_type_id' => $productType->id,
                    'status' => $productData['status'],
                    'brand_id' => $brand->id,
                    'attribute_data' => [
                        'name' => new Text($productData['name']),
                    ],
                ]
            );

            // Create variants with prices
            foreach ($productData['variants'] as $variantData) {
                $variant = ProductVariant::firstOrCreate(
                    [
                        'product_id' => $product->id,
                        'sku' => $variantData['sku'],
                    ],
                    [
                        'tax_class_id' => $taxClass->id,
                        'stock' => $variantData['stock'],
                        'purchasable' => $variantData['purchasable'],
                        'backorder' => $variantData['backorder'],
                        'unit_quantity' => 1,
                    ]
                );

                // Create price
                Price::firstOrCreate(
                    [
                        'priceable_type' => 'product_variant',
                        'priceable_id' => $variant->id,
                        'currency_id' => $currency->id,
                    ],
                    [
                        'price' => $variantData['price'],
                        'compare_price' => null,
                        'min_quantity' => 1,
                    ]
                );
            }

            // Create URL
            $product->urls()->firstOrCreate(
                [
                    'language_id' => $language->id,
                    'default' => true,
                ],
                [
                    'slug' => \Illuminate\Support\Str::slug($productData['name']),
                ]
            );
        }
    }
}
