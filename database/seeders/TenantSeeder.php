<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

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
        $grnma->domains()->create(['domain' => 'shop.grnmainfonet.test']); // Local development
        $grnma->domains()->create(['domain' => 'grnma.digimall.test']); // Alternative local

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
        $gma->domains()->create(['domain' => 'mall.ghanamedassoc.test']); // Local development
        $gma->domains()->create(['domain' => 'gma.digimall.test']); // Alternative local

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
        $psgh->domains()->create(['domain' => 'psgh.digimall.test']);

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
        $ghalaw->domains()->create(['domain' => 'ghalaw.digimall.test']);

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
        $ghaeng->domains()->create(['domain' => 'ghaeng.digimall.test']);

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
        $ghateach->domains()->create(['domain' => 'ghateach.digimall.test']);

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
        $ghaacc->domains()->create(['domain' => 'ghaacc.digimall.test']);

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
        $gmda->domains()->create(['domain' => 'gmda.digimall.test']);

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
        $grna->domains()->create(['domain' => 'grna.digimall.test']);

        $this->command->info('Tenants seeded successfully!');
        $this->command->info('Active Tenants: 8 | Inactive Tenants: 2');
        $this->command->info('GRNMA ID: '.$grnma->id);
        $this->command->info('GMA ID: '.$gma->id);
    }
}
