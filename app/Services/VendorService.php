<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\RegisterVendorDTO;
use App\DTOs\UpdateVendorDTO;
use App\Models\User;
use App\Models\Vendor;
use App\Repositories\Contracts\VendorRepositoryInterface;
use App\RolesEnum;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final readonly class VendorService
{
    public function __construct(
        private VendorRepositoryInterface $vendorRepository,
        private UserService $userService
    ) {}

    /**
     * Register a new vendor.
     *
     * Creates a new vendor account with 'pending' status awaiting admin approval.
     * Validates email uniqueness and applies business rules.
     *
     * @throws \InvalidArgumentException If DTO validation fails
     * @throws \RuntimeException If email already exists
     */
    public function registerVendor(RegisterVendorDTO $dto): Vendor
    {
        // Validate DTO
        if (! $dto->validate()) {
            throw new \InvalidArgumentException('Invalid vendor registration data provided.');
        }

        // Check email uniqueness
        if ($this->vendorRepository->emailExists($dto->email)) {
            throw new \RuntimeException("A vendor with email {$dto->email} already exists.");
        }

        // Get current tenant
        $tenant = tenancy()->tenant;
        if (! $tenant) {
            throw new \RuntimeException('No tenant context found for vendor registration.');
        }

        return DB::transaction(function () use ($dto, $tenant) {
            // Create user account for the vendor
            $result = $this->userService->createUserWithRandomPassword([
                'name' => $dto->contactName,
                'email' => $dto->email,
                'email_verified_at' => now(),
            ], RolesEnum::VENDOR->value);

            $user = $result['user'];
            $password = $result['password'];
            Log::info('Provisioned new user account for vendor', [
                'user_id' => $user->id,
                'email' => $dto->email,
                'temporary_password' => $password,
            ]);

            // Create vendor with tenant and user
            $vendorData = $dto->toArray();
            $vendorData['tenant_id'] = $tenant->id;
            $vendorData['user_id'] = $user->id;

            return $this->vendorRepository->create($vendorData);
        });
    }

    /**
     * Find a vendor by ID.
     */
    public function findById(string $id): ?Vendor
    {
        return $this->vendorRepository->find($id);
    }

    /**
     * Update vendor profile.
     *
     * Updates vendor information with provided data.
     * Only non-null fields in DTO will be updated.
     *
     * @throws \RuntimeException If vendor not found
     */
    public function updateVendor(string $vendorId, UpdateVendorDTO $dto): Vendor
    {
        $updateData = $dto->toArray();

        if (empty($updateData)) {
            throw new \InvalidArgumentException('No update data provided.');
        }

        return $this->vendorRepository->update($vendorId, $updateData);
    }

    /**
     * Find vendor by user ID.
     */
    public function findByUserId(string $userId): ?Vendor
    {
        return $this->vendorRepository->findByUserId($userId);
    }

    /**
     * Find vendor by email.
     */
    public function findByEmail(string $email): ?Vendor
    {
        return $this->vendorRepository->findByEmail($email);
    }

    /**
     * Get all vendors for a tenant.
     */
    public function getAllVendors(string $tenantId, ?int $limit = null): Collection
    {
        return $this->vendorRepository->getByTenant($tenantId, $limit);
    }

    /**
     * Get all vendors for a tenant (alias for consistency with controller).
     */
    public function getAllForTenant(string $tenantId, ?int $limit = null): Collection
    {
        return $this->getAllVendors($tenantId, $limit);
    }

    /**
     * Get filtered and paginated vendors for a tenant.
     */
    public function getFilteredVendors(
        string $tenantId,
        ?string $status = null,
        ?string $search = null,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->vendorRepository->getFiltered(
            $tenantId,
            $status,
            $search,
            $sortBy,
            $sortDirection,
            $perPage
        );
    }

    /**
     * Get vendor statistics for KPI cards.
     *
     * Returns total, active, and pending vendor counts for the given tenant.
     * This method is independent of any search or filter parameters.
     *
     * @param  string  $tenantId  The tenant ID
     * @return array{total: int, active: int, pending: int} Vendor statistics
     */
    public function getTenantVendorStatistics(string $tenantId): array
    {
        return $this->vendorRepository->getTenantStatistics($tenantId);
    }

    /**
     * Get vendors by status.
     */
    public function getByStatus(string $status, ?int $limit = null): Collection
    {
        return $this->vendorRepository->getByStatus($status, $limit);
    }

    /**
     * Get approved vendors.
     */
    public function getApprovedVendors(?int $limit = null): Collection
    {
        return $this->vendorRepository->getApproved($limit);
    }

    /**
     * Get pending vendors (awaiting approval).
     */
    public function getPendingVendors(?int $limit = null): Collection
    {
        return $this->vendorRepository->getPending($limit);
    }

    /**
     * Search vendors by query.
     */
    public function searchVendors(string $query, ?int $limit = null): Collection
    {
        return $this->vendorRepository->search($query, $limit);
    }

    /**
     * Approve a vendor.
     */
    public function approveVendor(string $vendorId): Vendor
    {
        return $this->vendorRepository->approve($vendorId);
    }

    /**
     * Reject a vendor.
     */
    public function rejectVendor(string $vendorId, ?string $reason = null): Vendor
    {
        return $this->vendorRepository->reject($vendorId, $reason);
    }

    /**
     * Suspend a vendor.
     */
    public function suspendVendor(string $vendorId, ?string $reason = null): Vendor
    {
        return $this->vendorRepository->suspend($vendorId, $reason);
    }

    /**
     * Get vendor statistics.
     */
    public function getVendorStatistics(string $vendorId): array
    {
        return $this->vendorRepository->getStatistics($vendorId);
    }

    /**
     * Get top vendors by sales.
     */
    public function getTopVendorsBySales(?int $limit = 10): Collection
    {
        return $this->vendorRepository->getTopBySales($limit);
    }

    /**
     * Check if vendor is approved.
     */
    public function isApproved(string $vendorId): bool
    {
        $vendor = $this->findById($vendorId);

        return $vendor && $vendor->status === 'approved';
    }

    /**
     * Check if vendor can sell products.
     */
    public function canSellProducts(string $vendorId): bool
    {
        return $this->isApproved($vendorId);
    }
}
