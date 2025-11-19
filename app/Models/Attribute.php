<?php

declare(strict_types=1);

namespace App\Models;

use Lunar\Models\Attribute as LunarAttribute;

/**
 * Extended Attribute model for DigiMall.
 *
 * Extends Lunar's Attribute model to add multi-tenant functionality.
 */
class Attribute extends LunarAttribute
{
    use \Stancl\Tenancy\Database\Concerns\BelongsToTenant;

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
