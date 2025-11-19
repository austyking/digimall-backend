<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for creating Brand models with tenant support.
 */
class BrandFactory extends Factory
{
    protected $model = Brand::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
        ];
    }

    /**
     * Set the tenant for the brand.
     */
    public function forTenant(Tenant $tenant): self
    {
        return $this->state([
            'tenant_id' => $tenant->id,
        ]);
    }
}
