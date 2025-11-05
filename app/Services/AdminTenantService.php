<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\ActivateTenantDTO;
use App\DTOs\AdminUpdateTenantDTO;
use App\DTOs\DeactivateTenantDTO;
use App\DTOs\TenantFilterDTO;
use App\Models\Tenant;
use App\Repositories\Contracts\TenantRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Service for admin-level tenant management operations.
 */
final class AdminTenantService
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository
    ) {}

    /**
     * Get filtered and paginated tenants based on admin criteria.
     */
    public function getFilteredTenants(TenantFilterDTO $filterDTO): LengthAwarePaginator
    {
        return $this->tenantRepository->getFiltered(
            active: $filterDTO->active,
            search: $filterDTO->search,
            sortBy: $filterDTO->sortBy ?? 'created_at',
            sortDirection: $filterDTO->sortDirection ?? 'desc',
            perPage: $filterDTO->perPage ?? 15
        );
    }

    /**
     * Activate a tenant with audit trail.
     */
    public function activateTenant(Tenant $tenant, ActivateTenantDTO $dto): Tenant
    {
        if ($tenant->active) {
            throw new \InvalidArgumentException('Tenant is already active');
        }

        return $this->tenantRepository->updateStatus($tenant, true, $dto->reason);
    }

    /**
     * Deactivate a tenant with audit trail.
     */
    public function deactivateTenant(Tenant $tenant, DeactivateTenantDTO $dto): Tenant
    {
        if (! $tenant->active) {
            throw new \InvalidArgumentException('Tenant is already inactive');
        }

        return $this->tenantRepository->updateStatus($tenant, false, $dto->reason);
    }

    /**
     * Bulk activate multiple tenants.
     */
    public function bulkActivateTenants(array $tenantIds, ActivateTenantDTO $dto): int
    {
        // Filter to only include inactive tenants
        $inactiveTenants = $this->tenantRepository->getIdsByStatus($tenantIds, false);

        if (empty($inactiveTenants)) {
            return 0;
        }

        return $this->tenantRepository->bulkUpdateStatus($inactiveTenants, true, $dto->reason);
    }

    /**
     * Bulk deactivate multiple tenants.
     */
    public function bulkDeactivateTenants(array $tenantIds, DeactivateTenantDTO $dto): int
    {
        // Filter to only include active tenants
        $activeTenants = $this->tenantRepository->getIdsByStatus($tenantIds, true);

        if (empty($activeTenants)) {
            return 0;
        }

        return $this->tenantRepository->bulkUpdateStatus($activeTenants, false, $dto->reason);
    }

    /**
     * Update tenant details with admin privileges.
     */
    public function updateTenant(Tenant $tenant, AdminUpdateTenantDTO $dto): Tenant
    {
        $updateData = $dto->toArray();

        // Remove updatedBy from update data as it's not a database column
        $updatedBy = $updateData['updatedBy'] ?? null;
        unset($updateData['updatedBy']);

        // Handle settings merge instead of replace
        if (isset($updateData['settings'])) {
            $updateData['settings'] = array_merge(
                $tenant->settings ?? [],
                $updateData['settings']
            );
        } else {
            // Initialize settings if not set
            $updateData['settings'] = $tenant->settings ?? [];
        }

        // Add audit trail for any admin update
        if ($updatedBy) {
            $updateData['settings']['last_admin_update'] = [
                'by' => $updatedBy,
                'at' => now()->toISOString(),
            ];
        }

        return $this->tenantRepository->update($tenant, $updateData);
    }

    /**
     * Get all inactive tenants for review.
     */
    public function getInactiveTenants(): Collection
    {
        return $this->tenantRepository->allInactive();
    }

    /**
     * Get a specific tenant by ID.
     */
    public function getTenant(string $id): ?Tenant
    {
        return $this->tenantRepository->find($id);
    }
}
