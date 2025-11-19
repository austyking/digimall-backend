<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Lunar\FieldTypes\TranslatedText;
use Lunar\Models\CollectionGroup;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // GRNMA Tenant
        $grnma = Tenant::factory()
            ->forAssociation(
                name: 'GRNMA',
                displayName: 'Ghana Registered Nurses and Midwives Association',
                settings: [
                    'theme' => [
                        'primary_color' => '#1976d2',
                        'secondary_color' => '#dc004e',
                    ],
                    'features' => [
                        'hire_purchase' => true,
                        'vendor_registration' => true,
                        'member_verification' => true,
                    ],
                    'payment_gateways' => [
                        'moolre' => ['enabled' => true],
                        'stripe' => ['enabled' => false],
                        'flutterwave' => ['enabled' => false],
                    ],
                    'association_api' => [
                        'base_url' => 'https://grnmainfonet.com/api',
                        'api_key' => '123',
                    ],
                    'sms' => [
                        'provider' => 'arkesel',
                        'sender_id' => 'GRNMA',
                    ],
                ]
            )
            ->create([
                'logo_url' => '/assets/images/grnma-logo.png',
            ]);

        // Create domains for GRNMA
        if (! \Stancl\Tenancy\Database\Models\Domain::where('domain', 'shop.grnmainfonet.test')->exists()) {
            $grnma->domains()->create(['domain' => 'shop.grnmainfonet.test']); // Local development
        }
        if (! \Stancl\Tenancy\Database\Models\Domain::where('domain', 'grnma.digimall.test')->exists()) {
            $grnma->domains()->create(['domain' => 'grnma.digimall.test']); // Alternative local
        }

        // GMA Tenant
        $gma = Tenant::factory()
            ->forAssociation(
                name: 'GMA',
                displayName: 'Ghana Medical Association',
                settings: [
                    'theme' => [
                        'primary_color' => '#2e7d32',
                        'secondary_color' => '#f57c00',
                    ],
                    'features' => [
                        'hire_purchase' => true,
                        'vendor_registration' => true,
                        'member_verification' => true,
                    ],
                    'payment_gateways' => [
                        'moolre' => ['enabled' => true],
                        'stripe' => ['enabled' => false],
                        'flutterwave' => ['enabled' => false],
                    ],
                    'association_api' => [
                        'base_url' => 'https://ghanamedassoc.org/api',
                        'api_key' => '456',
                    ],
                    'sms' => [
                        'provider' => 'arkesel',
                        'sender_id' => 'GMA',
                    ],
                ]
            )
            ->create([
                'logo_url' => '/assets/images/gma-logo.png',
            ]);

        // Create domains for GMA
        if (! \Stancl\Tenancy\Database\Models\Domain::where('domain', 'mall.ghanamedassoc.test')->exists()) {
            $gma->domains()->create(['domain' => 'mall.ghanamedassoc.test']); // Local development
        }
        if (! \Stancl\Tenancy\Database\Models\Domain::where('domain', 'gma.digimall.test')->exists()) {
            $gma->domains()->create(['domain' => 'gma.digimall.test']);
        } // Alternative local

        // Additional Active Tenants for Testing
        $psgh = Tenant::factory()
            ->forAssociation(
                name: 'PSGH',
                displayName: 'Pharmaceutical Society of Ghana',
                settings: [
                    'theme' => [
                        'primary_color' => '#0288d1',
                        'secondary_color' => '#f57c00',
                    ],
                ]
            )
            ->create([
                'description' => 'Digital marketplace for pharmaceutical professionals',
                'status' => 'active',
                'created_at' => now()->subDays(60),
            ]);
        if (! \Stancl\Tenancy\Database\Models\Domain::where('domain', 'psgh.digimall.test')->exists()) {
            $psgh->domains()->create(['domain' => 'psgh.digimall.test']);
        }

        $ghalaw = Tenant::factory()
            ->forAssociation(
                name: 'GHALAW',
                displayName: 'Ghana Law Association'
            )
            ->create([
                'description' => 'Legal professionals marketplace',
                'status' => 'active',
                'created_at' => now()->subDays(45),
            ]);
        if (! \Stancl\Tenancy\Database\Models\Domain::where('domain', 'ghalaw.digimall.test')->exists()) {
            $ghalaw->domains()->create(['domain' => 'ghalaw.digimall.test']);
        }

        $ghaeng = Tenant::factory()
            ->forAssociation(
                name: 'GHAENG',
                displayName: 'Ghana Engineering Society'
            )
            ->create([
                'description' => 'Engineering professionals marketplace',
                'status' => 'active',
                'created_at' => now()->subDays(30),
            ]);
        if (! \Stancl\Tenancy\Database\Models\Domain::where('domain', 'ghaeng.digimall.test')->exists()) {
            $ghaeng->domains()->create(['domain' => 'ghaeng.digimall.test']);
        }

        $ghateach = Tenant::factory()
            ->forAssociation(
                name: 'GHATEACH',
                displayName: 'Ghana Teachers Association'
            )
            ->create([
                'description' => 'Educational professionals marketplace',
                'status' => 'active',
                'created_at' => now()->subDays(15),
            ]);
        if (! \Stancl\Tenancy\Database\Models\Domain::where('domain', 'ghateach.digimall.test')->exists()) {
            $ghateach->domains()->create(['domain' => 'ghateach.digimall.test']);
        }

        $ghaacc = Tenant::factory()
            ->forAssociation(
                name: 'GHAACC',
                displayName: 'Ghana Accountants Association'
            )
            ->create([
                'description' => 'Accounting professionals marketplace',
                'status' => 'active',
                'created_at' => now()->subDays(7),
            ]);
        if (! \Stancl\Tenancy\Database\Models\Domain::where('domain', 'ghaacc.digimall.test')->exists()) {
            $ghaacc->domains()->create(['domain' => 'ghaacc.digimall.test']);
        }

        // Inactive Tenants for Testing
        $gmda = Tenant::factory()
            ->forAssociation(
                name: 'GMDA',
                displayName: 'Ghana Medical and Dental Association'
            )
            ->create([
                'description' => 'Platform for medical and dental practitioners',
                'status' => 'inactive',
                'settings' => [
                    'status_history' => [
                        [
                            'status' => 'inactive',
                            'reason' => 'Pending compliance verification',
                            'timestamp' => now()->subDays(5)->toISOString(),
                        ],
                    ],
                ],
            ]);
        if (! \Stancl\Tenancy\Database\Models\Domain::where('domain', 'gmda.digimall.test')->exists()) {
            $gmda->domains()->create(['domain' => 'gmda.digimall.test']);
        }

        $grna = Tenant::factory()
            ->forAssociation(
                name: 'GRNA',
                displayName: 'Ghana Registered Nurses Association'
            )
            ->create([
                'description' => 'E-commerce for registered nurses',
                'status' => 'inactive',
                'settings' => [
                    'status_history' => [
                        [
                            'status' => 'inactive',
                            'reason' => 'Administrative review required',
                            'timestamp' => now()->subDays(10)->toISOString(),
                        ],
                    ],
                ],
            ]);
        if (! \Stancl\Tenancy\Database\Models\Domain::where('domain', 'grna.digimall.test')->exists()) {
            $grna->domains()->create(['domain' => 'grna.digimall.test']);
        }

        // Seed taxonomy for each active tenant
        $this->seedTaxonomyForTenant($grnma);
        $this->seedTaxonomyForTenant($gma);
        $this->seedTaxonomyForTenant($psgh);
        $this->seedTaxonomyForTenant($ghalaw);
        $this->seedTaxonomyForTenant($ghaeng);
        $this->seedTaxonomyForTenant($ghateach);
        $this->seedTaxonomyForTenant($ghaacc);

        $this->command->info('Tenants seeded successfully!');
        $this->command->info('Active Tenants: 8 | Inactive Tenants: 2');
        $this->command->info('GRNMA ID: '.$grnma->id);
        $this->command->info('GMA ID: '.$gma->id);
    }

    /**
     * Seed tenant-specific taxonomy data.
     */
    private function seedTaxonomyForTenant(Tenant $tenant): void
    {
        // Ensure collection group exists (global)
        $collectionGroup = CollectionGroup::firstOrCreate([
            'handle' => 'main',
        ], [
            'name' => 'Main',
        ]);

        // Seed categories for this tenant
        $tenant->run(function () use ($collectionGroup, $tenant) {
            // Ensure default language exists for this tenant
            \Lunar\Models\Language::firstOrCreate([
                'code' => 'en',
            ], [
                'name' => 'English',
                'default' => true,
            ]);

            // Create default categories
            \App\Models\Collection::create([
                'collection_group_id' => $collectionGroup->id,
                'attribute_data' => [
                    'name' => new TranslatedText(['en' => 'Medical Supplies']),
                    'description' => new TranslatedText(['en' => 'Essential medical supplies and equipment']),
                ],
                'tenant_id' => $tenant->id,
                'type' => 'static',
                'sort' => 'custom',
            ]);

            \App\Models\Collection::create([
                'collection_group_id' => $collectionGroup->id,
                'attribute_data' => [
                    'name' => new TranslatedText(['en' => 'Pharmaceuticals']),
                    'description' => new TranslatedText(['en' => 'Medicines and pharmaceutical products']),
                ],
                'tenant_id' => $tenant->id,
                'type' => 'static',
                'sort' => 'custom',
            ]);

            // Create default brands
            \App\Models\Brand::create([
                'name' => $tenant->display_name.' Official',
                'attribute_data' => [
                    'name' => new TranslatedText(['en' => $tenant->display_name.' Official']),
                    'description' => new TranslatedText(['en' => 'Official brand for '.$tenant->display_name]),
                ],
                'tenant_id' => $tenant->id,
            ]);

            // Create default tags
            \App\Models\Tag::create(['value' => 'featured', 'tenant_id' => $tenant->id]);
            \App\Models\Tag::create(['value' => 'new', 'tenant_id' => $tenant->id]);
            \App\Models\Tag::create(['value' => 'bestseller', 'tenant_id' => $tenant->id]);
        });
    }
}
