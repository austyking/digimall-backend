<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\RegisterVendorDTO;
use App\DTOs\UpdateVendorDTO;
use App\Models\Vendor;
use App\Repositories\Contracts\VendorRepositoryInterface;
use Illuminate\Support\Collection;

final readonly class VendorService
{
    public function __construct(
        private VendorRepositoryInterface $vendorRepository
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

        // Create vendor with pending status
        return $this->vendorRepository->create($dto->toArray());
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
    public function suspendVendor(string $vendorId, ?string $reason = null): bool
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
