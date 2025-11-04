<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Vendor;
use App\Repositories\Contracts\VendorRepositoryInterface;
use Illuminate\Support\Collection;

final readonly class VendorService
{
    public function __construct(
        private VendorRepositoryInterface $vendorRepository
    ) {}

    /**
     * Find a vendor by ID.
     */
    public function findById(string $id): ?Vendor
    {
        return $this->vendorRepository->find($id);
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
    public function getAllVendors(?int $limit = null): Collection
    {
        return $this->vendorRepository->getByTenant($limit);
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
    public function approveVendor(string $vendorId): bool
    {
        return $this->vendorRepository->approve($vendorId);
    }

    /**
     * Reject a vendor.
     */
    public function rejectVendor(string $vendorId, ?string $reason = null): bool
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
