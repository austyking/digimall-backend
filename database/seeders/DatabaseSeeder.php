<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed Lunar essential data first
        $this->call([
            LunarDatabaseSeeder::class,
        ]);

        // Seed tenants and roles
        $this->call(RolesAndPermissionsSeeder::class);

        if ($this->command->confirm('Do you want to seed sample tenants?', true)) {
            $this->call(TenantSeeder::class);

            // Optionally seed vendors for development
            if ($this->command->confirm('Do you want to seed sample vendors?', true)) {
                $this->call(VendorSeeder::class);

                if ($this->command->confirm('Do you want to seed sample products?', true)) {
                    $this->call(ProductSeeder::class);
                }
            }
        }


        // call php artisan passport:client --personal to create personal access client
        $this->command->call('passport:client', [
            '--personal' => true,
            '--name' => 'DigiMall Personal Access Client',
            '--provider' => 'users',
        ]);

        // call php artisan admin:create-system-admin to create system admin user
        $this->command->call('admin:create-system-admin', [
            '--force' => true,
            '--name' => 'System Administrator',
            '--email' => 'admin@digimall.com',
            '--password' => 'admin123.',
        ]);



        // User::factory(10)->create();

//        User::factory()->create([
//            'name' => 'Test User',
//            'email' => 'test@example.com',
//        ]);
    }
}
