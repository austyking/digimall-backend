<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant>
 */
final class TenantFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\App\Models\Tenant>
     */
    protected $model = Tenant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'name' => strtoupper($this->faker->unique()->bothify('???')),
            'display_name' => $this->faker->company(),
            'description' => $this->faker->optional()->paragraph(),
            'logo_url' => $this->faker->optional()->imageUrl(200, 200, 'business'),
            'status' => 'active',
            'settings' => [
                'theme' => [
                    'primary_color' => $this->faker->hexColor(),
                    'secondary_color' => $this->faker->hexColor(),
                ],
                'features' => [
                    'hire_purchase' => $this->faker->boolean(80),
                    'vendor_registration' => $this->faker->boolean(90),
                    'member_verification' => $this->faker->boolean(85),
                ],
                'payment_gateways' => [
                    'moolre' => ['enabled' => $this->faker->boolean(70)],
                    'stripe' => ['enabled' => $this->faker->boolean(50)],
                    'flutterwave' => ['enabled' => $this->faker->boolean(50)],
                ],
            ],
        ];
    }

    /**
     * Indicate that the tenant is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Indicate that the tenant has minimal settings.
     */
    public function withMinimalSettings(): static
    {
        return $this->state(fn (array $attributes) => [
            'settings' => [],
        ]);
    }

    /**
     * Create a tenant with specific association configuration.
     */
    public function forAssociation(string $name, string $displayName, array $settings = []): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'display_name' => $displayName,
            'settings' => array_merge($attributes['settings'], $settings),
        ]);
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Tenant $tenant) {
            // Only auto-create test domains in testing environment
            if (app()->environment('testing')) {
                $tenant->domains()->create([
                    'domain' => strtolower($tenant->name).'.test',
                ]);
            }
        });
    }
}
