<?php

declare(strict_types=1);

namespace App\Models;

use Lunar\Models\Tag as LunarTag;

/**
 * Extended Tag model for DigiMall.
 *
 * Extends Lunar's Tag model to add multi-tenant functionality.
 */
class Tag extends LunarTag
{
    use \Stancl\Tenancy\Database\Concerns\BelongsToTenant;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'value',
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
