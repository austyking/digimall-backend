<?php

declare(strict_types=1);

namespace App\Models;

use Lunar\Models\Brand as LunarBrand;

/**
 * Extended Brand model for DigiMall.
 *
 * Extends Lunar's Brand model to add multi-tenant functionality.
 */
class Brand extends LunarBrand
{
    use \Stancl\Tenancy\Database\Concerns\BelongsToTenant;

    /**
     * The factory associated with the model.
     */
    protected static string $factory = \Database\Factories\BrandFactory::class;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'attribute_data',
        'name',
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
