<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\CreateTenantDTO;
use App\DTOs\UpdateTenantDTO;
use App\DTOs\UpdateTenantSettingsDTO;
use App\Models\Tenant;
use App\Repositories\Contracts\TenantRepositoryInterface;
use Illuminate\Support\Collection;

final readonly class TenantService
{
    public function __construct(
        private TenantRepositoryInterface $tenantRepository
    ) {}

    /**
     * Get tenant configuration including branding and settings.
     */
    public function getTenantConfig(Tenant $tenant): array
    {
        return [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'display_name' => $tenant->display_name,
                'subdomain' => $tenant->subdomain,
                'description' => $tenant->description,
                'active' => $tenant->active,
            ],
            'branding' => $tenant->getBrandingConfig(),
            'features' => $tenant->getSetting('features', []),
            'payment_gateways' => $tenant->getSetting('payment_gateways', []),
            'settings' => $tenant->settings ?? [],
        ];
    }

    /**
     * Get tenant branding configuration.
     */
    public function getBrandingConfig(Tenant $tenant): array
    {
        return $tenant->getBrandingConfig();
    }

    /**
     * Get a specific tenant setting.
     */
    public function getSetting(Tenant $tenant, string $key, mixed $default = null): mixed
    {
        return $tenant->getSetting($key, $default);
    }

    /**
     * Update tenant settings.
     */
    public function updateSettings(Tenant $tenant, UpdateTenantSettingsDTO $dto): Tenant
    {
        $settings = array_merge($tenant->settings ?? [], $dto->toArray());

        return $this->tenantRepository->update($tenant, ['settings' => $settings]);
    }

    /**
     * Create a new tenant.
     */
    public function createTenant(CreateTenantDTO $dto): Tenant
    {
        return $this->tenantRepository->create($dto->toArray());
    }

    /**
     * Update tenant information.
     */
    public function updateTenant(Tenant $tenant, UpdateTenantDTO $dto): Tenant
    {
        return $this->tenantRepository->update($tenant, $dto->toArray());
    }

    /**
     * Delete a tenant.
     */
    public function deleteTenant(Tenant $tenant): bool
    {
        return $this->tenantRepository->delete($tenant);
    }

    /**
     * Get all tenants.
     */
    public function getAllTenants(): Collection
    {
        return $this->tenantRepository->all();
    }

    /**
     * Get all active tenants.
     */
    public function getActiveTenants(): Collection
    {
        return $this->tenantRepository->allActive();
    }

    /**
     * Search tenants.
     */
    public function searchTenants(string $query): Collection
    {
        return $this->tenantRepository->search($query);
    }

    /**
     * Find tenant by ID.
     */
    public function findTenant(string $id): ?Tenant
    {
        return $this->tenantRepository->find($id);
    }

    /**
     * Find tenant by name.
     */
    public function findByName(string $name): ?Tenant
    {
        return $this->tenantRepository->findByName($name);
    }

    /**
     * Find tenant by subdomain.
     */
    public function findBySubdomain(string $subdomain): ?Tenant
    {
        return $this->tenantRepository->findBySubdomain($subdomain);
    }

    /**
     * Get tenant with domains.
     */
    public function getTenantWithDomains(string $id): ?Tenant
    {
        return $this->tenantRepository->findWithDomains($id);
    }
}
