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
        // Seed tenants first
        $this->call([
            RolesAndPermissionsSeeder::class,
            TenantSeeder::class,
        ]);

        // Optionally seed vendors for development
        if ($this->command->confirm('Do you want to seed sample vendors?', true)) {
            $this->call(VendorSeeder::class);
        }

        if ($this->command->confirm('Do you want to seed sample products?', true)) {
            $this->call(ProductSeeder::class);
        }

        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
