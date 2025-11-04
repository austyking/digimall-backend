<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Tenant;
use App\Repositories\Contracts\TenantRepositoryInterface;
use Illuminate\Support\Collection;

final class TenantRepository implements TenantRepositoryInterface
{
    /**
     * Find a tenant by ID.
     */
    public function find(string $id): ?Tenant
    {
        return Tenant::find($id);
    }

    /**
     * Find a tenant by name.
     */
    public function findByName(string $name): ?Tenant
    {
        return Tenant::query()->where('name', $name)->first();
    }

    /**
     * Get all tenants.
     */
    public function all(): Collection
    {
        return Tenant::query()->get();
    }

    /**
     * Get all active tenants.
     */
    public function allActive(): Collection
    {
        return Tenant::query()->where('active', true)->get();
    }

    /**
     * Create a new tenant.
     */
    public function create(array $data): Tenant
    {
        return Tenant::create($data);
    }

    /**
     * Update a tenant.
     */
    public function update(Tenant $tenant, array $data): Tenant
    {
        $tenant->update($data);

        return $tenant->fresh();
    }

    /**
     * Delete a tenant.
     */
    public function delete(Tenant $tenant): bool
    {
        return $tenant->delete();
    }

    /**
     * Get tenant with domains loaded.
     */
    public function findWithDomains(string $id): ?Tenant
    {
        return Tenant::with('domains')->find($id);
    }

    /**
     * Search tenants by name.
     */
    public function search(string $query): Collection
    {
        return Tenant::query()
            ->where('name', 'LIKE', "%{$query}%")
            ->orWhere('display_name', 'LIKE', "%{$query}%")
            ->get();
    }
}
