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
        // Generate 3-4 letter uppercase code
        $name = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 3));

        // Simple names without fancy Faker formatters
        $associations = ['Medical', 'Nursing', 'Engineering', 'Legal', 'Education', 'Business', 'Agricultural', 'Dental'];
        $displayName = $associations[array_rand($associations)].' Association';

        // Simple colors without Faker
        $colors = ['#1976d2', '#dc004e', '#388e3c', '#f57c00', '#7b1fa2', '#0288d1', '#c62828', '#689f38'];

        return [
            'id' => Str::uuid()->toString(),
            'name' => $name.$this->faker->unique()->numberBetween(1, 999),
            'display_name' => $displayName,
            'description' => 'Test association description',
            'logo_url' => 'https://example.com/logo.png',
            'status' => 'active',
            'settings' => [
                'theme' => [
                    'primary_color' => $colors[array_rand($colors)],
                    'secondary_color' => $colors[array_rand($colors)],
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
