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

        $this->command->info('Tenants seeded successfully!');
        $this->command->info('GRNMA ID: '.$grnma->id);
        $this->command->info('GMA ID: '.$gma->id);
    }
}
