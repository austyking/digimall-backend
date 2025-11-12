<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BelongsToTenant
{
    /**
     * Boot the tenant-scoped trait for a model.
     */
    public static function bootBelongsToTenant(): void
    {
        // Automatically set tenant_id when creating
        static::creating(function (Model $model) {
            if (tenancy()->initialized && ! $model->getAttribute('tenant_id')) {
                $model->setAttribute('tenant_id', tenant('id'));
            }
        });

        // Automatically scope all queries to current tenant
        if (tenancy()->initialized) {
            static::addGlobalScope('tenant', function (Builder $builder) {
                $builder->where('tenant_id', tenant('id'));
            });
        }
    }

    /**
     * Get the tenant that owns the model.
     */
    public function tenant()
    {
        return $this->belongsTo(config('tenancy.tenant_model'), 'tenant_id');
    }
}
