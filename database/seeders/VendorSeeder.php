<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

/**
 * Seed vendors for testing and development.
 */
class VendorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding vendors...');

        // Get all tenants
        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->command->warn('No tenants found. Please run TenantSeeder first.');

            return;
        }

        foreach ($tenants as $tenant) {
            $this->command->info("  Creating vendors for {$tenant->display_name}...");

            // Create approved vendors (active)
            $this->createApprovedVendors($tenant, 3);

            // Create pending vendors (awaiting approval)
            $this->createPendingVendors($tenant, 2);

            // Create one rejected vendor
            $this->createRejectedVendor($tenant);
        }

        $this->command->info('✓ Vendors seeded successfully!');
    }

    /**
     * Create approved vendors for a tenant.
     */
    private function createApprovedVendors(Tenant $tenant, int $count): void
    {
        $businesses = [
            ['name' => 'MediCare Pharmacy', 'type' => 'pharmacy', 'commission' => 12.5],
            ['name' => 'HealthPlus Supplies', 'type' => 'medical_supplies', 'commission' => 15.0],
            ['name' => 'WellBeing Store', 'type' => 'wellness', 'commission' => 10.0],
            ['name' => 'ProHealth Equipment', 'type' => 'equipment', 'commission' => 18.0],
            ['name' => 'CareFirst Pharmacy', 'type' => 'pharmacy', 'commission' => 12.0],
        ];

        for ($i = 0; $i < min($count, count($businesses)); $i++) {
            $business = $businesses[$i];
            $slug = str($business['name'])->slug();

            // Create user with vendor role
            $user = User::factory()->create([
                'tenant_id' => $tenant->id,
                'name' => $business['name'].' Admin',
                'email' => $slug.'@'.strtolower($tenant->name).'.vendor',
            ]);
            $user->assignRole('vendor');

            Vendor::factory()
                ->forUser($user)
                ->approved()
                ->create([
                    'business_name' => $business['name'],
                    'contact_name' => $business['name'].' Manager',
                    'email' => $slug.'@'.strtolower($tenant->name).'.vendor',
                    'phone' => '+233'.rand(200000000, 599999999),
                    'commission_rate' => $business['commission'],
                    'description' => "Leading {$business['type']} provider serving {$tenant->display_name} members.",
                    'status' => 'active',
                ]);

            $this->command->info("    ✓ {$business['name']} (Approved)", 'v');
        }
    }

    /**
     * Create pending vendors for a tenant.
     */
    private function createPendingVendors(Tenant $tenant, int $count): void
    {
        $businesses = [
            ['name' => 'New Era Pharmacy', 'type' => 'pharmacy'],
            ['name' => 'Quick Meds Store', 'type' => 'pharmacy'],
            ['name' => 'Global Health Supplies', 'type' => 'medical_supplies'],
        ];

        for ($i = 0; $i < min($count, count($businesses)); $i++) {
            $business = $businesses[$i];
            $slug = str($business['name'])->slug();

            // Create user with vendor role
            $user = User::factory()->create([
                'tenant_id' => $tenant->id,
                'name' => $business['name'].' Admin',
                'email' => $slug.'@'.strtolower($tenant->name).'.vendor',
            ]);
            $user->assignRole('vendor');

            Vendor::factory()
                ->forUser($user)
                ->pending()
                ->create([
                    'business_name' => $business['name'],
                    'contact_name' => $business['name'].' Manager',
                    'email' => $slug.'@'.strtolower($tenant->name).'.vendor',
                    'phone' => '+233'.rand(200000000, 599999999),
                    'description' => "New {$business['type']} looking to serve {$tenant->display_name} members.",
                ]);

            $this->command->info("    ✓ {$business['name']} (Pending)", 'v');
        }
    }

    /**
     * Create a rejected vendor for a tenant.
     */
    private function createRejectedVendor(Tenant $tenant): void
    {
        $slug = 'rejected-vendor';

        // Create user with vendor role
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Rejected Vendor Admin',
            'email' => $slug.'@'.strtolower($tenant->name).'.vendor',
        ]);
        $user->assignRole('vendor');

        Vendor::factory()
            ->forUser($user)
            ->rejected()
            ->create([
                'business_name' => 'Unverified Pharmacy',
                'contact_name' => 'John Doe',
                'email' => $slug.'@'.strtolower($tenant->name).'.vendor',
                'phone' => '+233'.rand(200000000, 599999999),
                'rejection_reason' => 'Unable to verify business registration documents.',
            ]);

        $this->command->info('    ✓ Unverified Pharmacy (Rejected)', 'v');
    }
}
