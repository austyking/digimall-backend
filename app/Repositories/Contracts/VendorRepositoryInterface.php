<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Vendor;
use Illuminate\Database\Eloquent\Collection;

interface VendorRepositoryInterface
{
    /**
     * Find a vendor by ID.
     */
    public function find(string $id): ?Vendor;

    /**
     * Find a vendor by user ID.
     */
    public function findByUserId(string $userId): ?Vendor;

    /**
     * Find a vendor by email.
     */
    public function findByEmail(string $email): ?Vendor;

    /**
     * Get all vendors.
     */
    public function all(): Collection;

    /**
     * Get vendors with pagination.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate(int $perPage = 15);

    /**
     * Get active vendors only.
     */
    public function getActive(): Collection;

    /**
     * Get pending vendor applications.
     */
    public function getPending(): Collection;

    /**
     * Get approved vendors.
     */
    public function getApproved(): Collection;

    /**
     * Get rejected vendor applications.
     */
    public function getRejected(): Collection;

    /**
     * Get vendors by tenant ID.
     */
    public function getByTenant(string $tenantId, ?int $limit = null): Collection;

    /**
     * Search vendors by query.
     */
    public function search(string $query): Collection;

    /**
     * Create a new vendor.
     */
    public function create(array $data): Vendor;

    /**
     * Update a vendor.
     */
    public function update(string $id, array $data): Vendor;

    /**
     * Update vendor status.
     */
    public function updateStatus(string $id, string $status): Vendor;

    /**
     * Delete a vendor.
     */
    public function delete(string $id): bool;

    /**
     * Get vendor with relationships loaded.
     */
    public function findWithRelations(string $id, array $relations = []): ?Vendor;

    /**
     * Check if vendor exists by ID.
     */
    public function exists(string $id): bool;

    /**
     * Check if email is already registered.
     */
    public function emailExists(string $email): bool;

    /**
     * Get vendor statistics.
     */
    public function getStatistics(string $id): array;

    /**
     * Get top vendors by sales.
     */
    public function getTopBySales(int $limit = 10): Collection;

    /**
     * Approve vendor application.
     */
    public function approve(string $id): Vendor;

    /**
     * Reject vendor application.
     */
    public function reject(string $id, ?string $reason = null): Vendor;

    /**
     * Get vendors by status.
     */
    public function getByStatus(string $status, ?int $limit = null): Collection;

    /**
     * Suspend a vendor.
     */
    public function suspend(string $vendorId, ?string $reason = null): bool;
}
