<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Lunar\FieldTypes\Text;
use Lunar\Models\Product;
use Lunar\Models\ProductType;

/**
 * Factory for creating Product models with tenant support.
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'product_type_id' => ProductType::factory(),
            'status' => 'published',
            'attribute_data' => collect([
                'name' => new Text($this->faker->name),
                'description' => new Text($this->faker->sentence),
            ]),
        ];
    }

    /**
     * Set the brand for the product with tenant.
     */
    public function withBrand(Tenant $tenant): self
    {
        return $this->state([
            'brand_id' => \App\Models\Brand::factory()->forTenant($tenant)->create()->id,
        ]);
    }

    /**
     * Set the vendor for the product.
     */
    public function forVendor(\App\Models\Vendor $vendor): self
    {
        return $this->state([
            'vendor_id' => $vendor->id,
        ]);
    }
}
