<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Tenant;
use Illuminate\Support\Collection;

interface TenantRepositoryInterface
{
    /**
     * Find a tenant by ID.
     */
    public function find(string $id): ?Tenant;

    /**
     * Find a tenant by name.
     */
    public function findByName(string $name): ?Tenant;

    /**
     * Get all tenants.
     */
    public function all(): Collection;

    /**
     * Get all active tenants.
     */
    public function allActive(): Collection;

    /**
     * Create a new tenant.
     */
    public function create(array $data): Tenant;

    /**
     * Update a tenant.
     */
    public function update(Tenant $tenant, array $data): Tenant;

    /**
     * Delete a tenant.
     */
    public function delete(Tenant $tenant): bool;

    /**
     * Get tenant with domains loaded.
     */
    public function findWithDomains(string $id): ?Tenant;

    /**
     * Search tenants by name.
     */
    public function search(string $query): Collection;
}
