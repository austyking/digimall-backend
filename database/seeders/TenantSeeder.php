<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // GRNMA Tenant
        $grnma = Tenant::create([
            'id' => Str::uuid()->toString(),
            'name' => 'GRNMA',
            'subdomain' => 'grnmainfonet',
            'display_name' => 'Ghana Registered Nurses and Midwives Association',
            'logo_url' => '/assets/images/grnma-logo.png',
            'active' => true,
            'settings' => [
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
                    'api_key' => env('GRNMA_API_KEY'),
                ],
                'sms' => [
                    'provider' => 'arkesel',
                    'sender_id' => 'GRNMA',
                ],
            ],
        ]);

        // Create domains for GRNMA
        $grnma->domains()->create(['domain' => 'shop.grnmainfonet.com']); // Production
        $grnma->domains()->create(['domain' => 'shop.grnmainfonet.org']); // Alternative production
        $grnma->domains()->create(['domain' => 'shop.grnmainfonet.test']); // Local development
        $grnma->domains()->create(['domain' => 'grnma.digimall.test']); // Alternative local

        // GMA Tenant
        $gma = Tenant::create([
            'id' => Str::uuid()->toString(),
            'name' => 'GMA',
            'subdomain' => 'ghanamedassoc',
            'display_name' => 'Ghana Medical Association',
            'logo_url' => '/assets/images/gma-logo.png',
            'active' => true,
            'settings' => [
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
                    'api_key' => env('GMA_API_KEY'),
                ],
                'sms' => [
                    'provider' => 'arkesel',
                    'sender_id' => 'GMA',
                ],
            ],
        ]);

        // Create domains for GMA
        $gma->domains()->create(['domain' => 'mall.ghanamedassoc.com']); // Production
        $gma->domains()->create(['domain' => 'shop.ghanamedassoc.org']); // Alternative production
        $gma->domains()->create(['domain' => 'mall.ghanamedassoc.test']); // Local development
        $gma->domains()->create(['domain' => 'gma.digimall.test']); // Alternative local

        $this->command->info('Tenants seeded successfully!');
        $this->command->info('GRNMA ID: '.$grnma->id);
        $this->command->info('GMA ID: '.$gma->id);
    }
}
