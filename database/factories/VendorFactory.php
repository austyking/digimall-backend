<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vendor>
 */
class VendorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tenant = Tenant::factory()->create();

        return [
            'tenant_id' => $tenant->id,
            'user_id' => User::factory()->create(['tenant_id' => $tenant->id])->id,
            'business_name' => $this->faker->company(),
            'contact_name' => $this->faker->name(),
            'email' => $this->faker->unique()->companyEmail(),
            'phone' => $this->faker->phoneNumber(),
            'description' => $this->faker->paragraph(),
            'address_line_1' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->city(),
            'postal_code' => $this->faker->postcode(),
            'country' => 'Ghana',
            'business_registration_number' => $this->faker->unique()->numerify('BRN#####'),
            'tax_id' => $this->faker->unique()->numerify('TAX#####'),
            'status' => 'pending',
            'commission_rate' => 15.00,
            'commission_type' => 'percentage',
            'settings' => [
                'notifications' => [
                    'email' => true,
                    'sms' => false,
                ],
                'features' => [
                    'cross_association_sync' => false,
                ],
            ],
        ];
    }

    /**
     * Create an approved vendor.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    /**
     * Create an active vendor.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Create a pending vendor.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Create a rejected vendor.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejection_reason' => $this->faker->sentence(),
        ]);
    }

    /**
     * Create a suspended vendor.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
            'suspended_at' => now(),
            'suspension_reason' => $this->faker->sentence(),
        ]);
    }

    /**
     * Create vendor for a specific tenant.
     */
    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
            'user_id' => User::factory()->create(['tenant_id' => $tenant->id])->id,
        ]);
    }

    /**
     * Create vendor for a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
        ]);
    }
}
