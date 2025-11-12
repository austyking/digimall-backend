<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Seed roles and permissions for the DigiMall platform.
 */
final class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->info('Seeding roles and permissions...');

        // Create permissions
        $permissions = $this->createPermissions();

        // Create roles and assign permissions
        $this->createRoles($permissions);

        $this->info('✓ Roles and permissions seeded successfully!');
    }

    /**
     * Create all system permissions.
     *
     * @return array<string, Permission>
     */
    private function createPermissions(): array
    {
        $this->info('  Creating permissions...', 'v');

        $permissionGroups = [
            // System Administration
            'system' => [
                'manage-tenants',
                'manage-system-users',
                'manage-system-settings',
                'view-system-logs',
                'manage-integrations',
            ],

            // Tenant/Association Administration
            'tenant' => [
                'manage-vendors',
                'manage-categories',
                'manage-brands',
                'manage-attributes',
                'manage-commission-rules',
                'manage-tenant-settings',
                'view-tenant-reports',
            ],

            // Vendor Management
            'vendor' => [
                'manage-own-products',
                'manage-own-orders',
                'view-own-analytics',
                'manage-own-profile',
                'sync-cross-association',
            ],

            // Member/Customer Management
            'member' => [
                'place-orders',
                'view-own-orders',
                'manage-own-profile',
                'apply-hire-purchase',
            ],

            // Product Management
            'product' => [
                'create-products',
                'edit-products',
                'delete-products',
                'publish-products',
            ],

            // Order Management
            'order' => [
                'view-all-orders',
                'manage-all-orders',
                'process-refunds',
            ],

            // Financial Management
            'financial' => [
                'view-payouts',
                'process-payouts',
                'manage-commission-rules',
                'view-financial-reports',
            ],
        ];

        $permissions = [];

        foreach ($permissionGroups as $group => $groupPermissions) {
            foreach ($groupPermissions as $permission) {
                // Create permission for both web and api guards
                $permissions[$permission] = Permission::firstOrCreate(
                    ['name' => $permission, 'guard_name' => 'web']
                );
                Permission::firstOrCreate(
                    ['name' => $permission, 'guard_name' => 'api']
                );
                $this->info("    • {$permission}", 'vv');
            }
        }

        return $permissions;
    }

    /**
     * Create roles and assign permissions.
     *
     * @param  array<string, Permission>  $permissions
     */
    private function createRoles(array $permissions): void
    {
        $this->info('  Creating roles...', 'v');

        // System Administrator - Full access to everything
        $systemAdmin = Role::firstOrCreate(
            ['name' => 'system-administrator', 'guard_name' => 'web']
        );
        $systemAdmin->syncPermissions($permissions);
        $this->info('    • system-administrator (all permissions)', 'vv');

        // API guard version
        $systemAdminApi = Role::firstOrCreate(
            ['name' => 'system-administrator', 'guard_name' => 'api']
        );
        $systemAdminApi->syncPermissions(
            Permission::where('guard_name', 'api')->pluck('name')
        );

        // Association Administrator - Full access within their tenant
        $tenantAdmin = Role::firstOrCreate(
            ['name' => 'association-administrator', 'guard_name' => 'web']
        );
        $tenantAdmin->syncPermissions([
            $permissions['manage-vendors'],
            $permissions['manage-categories'],
            $permissions['manage-brands'],
            $permissions['manage-attributes'],
            $permissions['manage-commission-rules'],
            $permissions['manage-tenant-settings'],
            $permissions['view-tenant-reports'],
            $permissions['view-all-orders'],
            $permissions['manage-all-orders'],
            $permissions['process-refunds'],
            $permissions['view-payouts'],
            $permissions['process-payouts'],
            $permissions['view-financial-reports'],
        ]);
        $this->info('    • association-administrator', 'vv');

        // API guard version
        $tenantAdminApi = Role::firstOrCreate(
            ['name' => 'association-administrator', 'guard_name' => 'api']
        );
        $tenantAdminApi->syncPermissions([
            'manage-vendors',
            'manage-categories',
            'manage-brands',
            'manage-attributes',
            'manage-commission-rules',
            'manage-tenant-settings',
            'view-tenant-reports',
            'view-all-orders',
            'manage-all-orders',
            'process-refunds',
            'view-payouts',
            'process-payouts',
            'view-financial-reports',
        ]);

        // Vendor - Manage their own products and orders
        $vendor = Role::firstOrCreate(
            ['name' => 'vendor', 'guard_name' => 'web']
        );
        $vendor->syncPermissions([
            $permissions['manage-own-products'],
            $permissions['manage-own-orders'],
            $permissions['view-own-analytics'],
            $permissions['manage-own-profile'],
            $permissions['sync-cross-association'],
            $permissions['create-products'],
            $permissions['edit-products'],
            $permissions['delete-products'],
            $permissions['publish-products'],
        ]);
        $this->info('    • vendor', 'vv');

        // API guard version
        $vendorApi = Role::firstOrCreate(
            ['name' => 'vendor', 'guard_name' => 'api']
        );
        $vendorApi->syncPermissions([
            'manage-own-products',
            'manage-own-orders',
            'view-own-analytics',
            'manage-own-profile',
            'sync-cross-association',
            'create-products',
            'edit-products',
            'delete-products',
            'publish-products',
        ]);

        // Member/Customer - Basic access to shop and order
        $member = Role::firstOrCreate(
            ['name' => 'member', 'guard_name' => 'web']
        );
        $member->syncPermissions([
            $permissions['place-orders'],
            $permissions['view-own-orders'],
            $permissions['manage-own-profile'],
            $permissions['apply-hire-purchase'],
        ]);
        $this->info('    • member', 'vv');

        // API guard version
        $memberApi = Role::firstOrCreate(
            ['name' => 'member', 'guard_name' => 'api']
        );
        $memberApi->syncPermissions([
            'place-orders',
            'view-own-orders',
            'manage-own-profile',
            'apply-hire-purchase',
        ]);
    }

    /**
     * Output info message with optional verbosity.
     */
    private function info(string $message, string $verbosity = ''): void
    {
        if ($verbosity === '') {
            $this->command?->info($message);
        } else {
            $this->command?->info($message, $verbosity);
        }
    }
}
