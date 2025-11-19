<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\ActivateTenantDTO;
use App\DTOs\AdminCreateTenantDTO;
use App\DTOs\AdminUpdateTenantDTO;
use App\DTOs\DeactivateTenantDTO;
use App\DTOs\DeleteTenantDTO;
use App\DTOs\TenantFilterDTO;
use App\Models\Tenant;
use App\Repositories\Contracts\TenantRepositoryInterface;
use App\RolesEnum;
use App\Services\Contracts\FileUploadServiceInterface;
use App\Services\Contracts\UserServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Service for admin-level tenant management operations.
 */
final class AdminTenantService
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository,
        private readonly FileUploadServiceInterface $fileUploadService,
        private readonly UserServiceInterface $userService
    ) {}

    /**
     * Get filtered and paginated tenants based on admin criteria.
     */
    public function getFilteredTenants(TenantFilterDTO $filterDTO): LengthAwarePaginator
    {
        return $this->tenantRepository->getFiltered(
            status: $filterDTO->status,
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
        if ($tenant->isActive()) {
            throw new \InvalidArgumentException('Tenant is already active');
        }

        return $this->tenantRepository->updateStatus($tenant, 'active', $dto->reason);
    }

    /**
     * Deactivate a tenant with audit trail.
     */
    public function deactivateTenant(Tenant $tenant, DeactivateTenantDTO $dto): Tenant
    {
        if ($tenant->isInactive()) {
            throw new \InvalidArgumentException('Tenant is already inactive');
        }

        return $this->tenantRepository->updateStatus($tenant, 'inactive', $dto->reason);
    }

    /**
     * Bulk activate multiple tenants.
     */
    public function bulkActivateTenants(array $tenantIds, ActivateTenantDTO $dto): int
    {
        // Filter to only include inactive tenants
        $inactiveTenants = $this->tenantRepository->getIdsByStatus($tenantIds, 'inactive');

        if (empty($inactiveTenants)) {
            return 0;
        }

        return $this->tenantRepository->bulkUpdateStatus($inactiveTenants, 'active', $dto->reason);
    }

    /**
     * Bulk deactivate multiple tenants.
     */
    public function bulkDeactivateTenants(array $tenantIds, DeactivateTenantDTO $dto): int
    {
        // Filter to only include active tenants
        $activeTenants = $this->tenantRepository->getIdsByStatus($tenantIds, 'active');

        if (empty($activeTenants)) {
            return 0;
        }

        return $this->tenantRepository->bulkUpdateStatus($activeTenants, 'inactive', $dto->reason);
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

        // Convert active boolean to status string
        if (isset($updateData['active'])) {
            $updateData['status'] = $updateData['active'] ? 'active' : 'inactive';
            unset($updateData['active']);
        }

        // Handle logo upload if present
        if ($dto->logo !== null) {
            $logoUrl = $this->fileUploadService->uploadTenantLogo($dto->logo, $tenant->id);
            $updateData['logo_url'] = $logoUrl;
        }

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

    /**
     * Create a new tenant with initial configuration.
     */
    public function createTenant(AdminCreateTenantDTO $dto): Tenant
    {
        // Verify uniqueness
        $existing = $this->tenantRepository->findByName($dto->name);
        if ($existing !== null) {
            throw ValidationException::withMessages([
                'name' => ['A tenant with this name already exists.'],
            ]);
        }

        // Prepare tenant data
        $tenantData = $dto->toArray();

        // Create tenant and provision a tenant-scoped user in a transaction
        return DB::transaction(function () use ($tenantData, $dto) {
            $tenant = $this->tenantRepository->create($tenantData);

            // Initialize tenancy context so user creation is properly scoped
            tenancy()->initialize($tenant);

            try {
                // Create a user with a random password and assign 'vendor' role (tenant admin)
                // Only create user if contact email is provided in settings
                if (isset($dto->settings['contact']['email'])) {
                    $result = $this->userService->createUserWithRandomPassword([
                        'name' => $dto->name,
                        'email' => $dto->settings['contact']['email'],
                        'email_verified_at' => now(),
                    ], RolesEnum::ASSOCIATION_ADMIN->value);

                    Log::info('Provisioned tenant admin user', [
                        'tenant_id' => $tenant->id,
                        'user_id' => $result['user']->id ?? null,
                        'temporary_password' => $result['password'] ?? null,
                    ]);
                } else {
                    Log::info('Tenant created without admin user - no contact email provided', [
                        'tenant_id' => $tenant->id,
                        'tenant_name' => $dto->name,
                    ]);
                }
            } finally {
                tenancy()->end();
            }

            return $tenant->fresh(['domains']);
        });
    }

    /**
     * Delete a tenant with optional force delete.
     */
    public function deleteTenant(DeleteTenantDTO $dto): bool
    {
        // Find tenant
        $tenant = $this->tenantRepository->find($dto->tenantId);
        if ($tenant === null) {
            throw new \InvalidArgumentException('Tenant not found');
        }

        // Add deletion audit trail to settings
        $tenant->settings = array_merge($tenant->settings ?? [], [
            'deletion' => [
                'reason' => $dto->reason,
                'deleted_by' => $dto->deletedBy,
                'deleted_at' => now()->toISOString(),
                'force' => $dto->force,
            ],
        ]);
        $tenant->save();

        // Perform deletion (soft delete via repository)
        // Note: Force delete would need to be implemented separately
        return $this->tenantRepository->delete($tenant);
    }
}
