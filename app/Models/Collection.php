<?php

declare(strict_types=1);

namespace App\Models;

use Lunar\Models\Collection as LunarCollection;

/**
 * Extended Collection model for DigiMall.
 *
 * Extends Lunar's Collection model to add multi-tenant functionality.
 */
class Collection extends LunarCollection
{
    use \Stancl\Tenancy\Database\Concerns\BelongsToTenant;

    /**
     * The factory associated with the model.
     */
    protected static string $factory = \Database\Factories\CollectionFactory::class;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'collection_group_id',
        'parent_id',
        'type',
        'attribute_data',
        'sort',
        'tenant_id',
    ];

    /**
     * Resolve the model for route model binding.
     *
     * Ensures tenant scoping is applied during route model binding.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $field = $field ?? $this->getRouteKeyName();

        $query = static::query();

        // Ensure tenant scope is applied if tenancy is initialized
        if (tenancy()->initialized) {
            $query->where('tenant_id', tenant()->getTenantKey());
        }

        return $query->where($field, $value)->first();
    }
}
