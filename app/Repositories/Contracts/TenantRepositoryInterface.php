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

    /**
     * Get all inactive tenants.
     */
    public function allInactive(): Collection;

    /**
     * Get filtered and paginated tenants.
     */
    public function getFiltered(
        ?string $status = null,
        ?string $search = null,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc',
        int $perPage = 15
    );

    /**
     * Count total tenants.
     */
    public function count(): int;

    /**
     * Count active tenants.
     */
    public function countActive(): int;

    /**
     * Count inactive tenants.
     */
    public function countInactive(): int;

    /**
     * Update tenant status.
     */
    public function updateStatus(Tenant $tenant, string $status, ?string $reason = null): Tenant;

    /**
     * Bulk update tenant statuses.
     */
    public function bulkUpdateStatus(array $tenantIds, string $status, ?string $reason = null): int;

    /**
     * Get count of tenants created since a specific date.
     */
    public function countCreatedSince(\DateTimeInterface $date): int;

    /**
     * Get tenant distribution by creation date (for charts).
     *
     * @param  int  $days  Number of days to look back
     * @return array<string, int> Date => count mapping
     */
    public function getDistributionByDate(int $days = 30): array;

    /**
     * Get tenant IDs by status from a list of IDs.
     *
     * @param  array  $tenantIds  List of tenant IDs to filter
     * @param  string  $status  Status to filter by ('active' or 'inactive')
     * @return array List of tenant IDs matching the status
     */
    public function getIdsByStatus(array $tenantIds, string $status): array;
}
