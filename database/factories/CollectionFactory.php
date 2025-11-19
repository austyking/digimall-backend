<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Collection;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Lunar\FieldTypes\Text;
use Lunar\Models\CollectionGroup;

/**
 * Factory for creating Collection models with tenant support.
 */
class CollectionFactory extends Factory
{
    protected $model = Collection::class;

    public function definition(): array
    {
        return [
            'collection_group_id' => CollectionGroup::factory(),
            'attribute_data' => collect([
                'name' => new Text($this->faker->name),
            ]),
        ];
    }

    /**
     * Set the tenant for the collection.
     */
    public function forTenant(Tenant $tenant): self
    {
        return $this->state([
            'tenant_id' => $tenant->id,
        ]);
    }
}
