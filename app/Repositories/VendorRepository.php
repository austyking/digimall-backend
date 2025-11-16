<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Vendor;
use App\Repositories\Contracts\VendorRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

final class VendorRepository implements VendorRepositoryInterface
{
    /**
     * Find a vendor by ID.
     */
    public function find(string $id): ?Vendor
    {
        return Vendor::query()->find($id);
    }

    /**
     * Find a vendor by user ID.
     */
    public function findByUserId(string $userId): ?Vendor
    {
        return Vendor::query()->where('user_id', $userId)->first();
    }

    /**
     * Find a vendor by email.
     */
    public function findByEmail(string $email): ?Vendor
    {
        return Vendor::query()->where('email', $email)->first();
    }

    /**
     * Get all vendors.
     */
    public function all(): Collection
    {
        return Vendor::query()->get();
    }

    /**
     * Get vendors with pagination.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Vendor::query()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get active vendors only.
     */
    public function getActive(): Collection
    {
        return Vendor::query()
            ->where('status', 'active')
            ->get();
    }

    /**
     * Get pending vendor applications.
     */
    public function getPending(): Collection
    {
        return Vendor::query()
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get approved vendors.
     */
    public function getApproved(): Collection
    {
        return Vendor::query()
            ->where('status', 'approved')
            ->whereNotNull('approved_at')
            ->get();
    }

    /**
     * Get rejected vendor applications.
     */
    public function getRejected(): Collection
    {
        return Vendor::query()
            ->where('status', 'rejected')
            ->whereNotNull('rejected_at')
            ->get();
    }

    /**
     * Get vendors by tenant ID.
     */
    public function getByTenant(string $tenantId, ?int $limit = null): Collection
    {
        $query = Vendor::query()->where('tenant_id', $tenantId);

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Get filtered and paginated vendors for a tenant.
     */
    public function getFiltered(
        string $tenantId,
        ?string $status = null,
        ?string $search = null,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = Vendor::query()->withCount('products')->where('tenant_id', $tenantId);

        // Apply status filter
        if ($status !== null) {
            $query->where('status', $status);
        }

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('business_name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('contact_name', 'LIKE', "%{$search}%");
            });
        }

        // Apply sorting
        $query->orderBy($sortBy, $sortDirection);

        return $query->paginate($perPage);
    }

    /**
     * Get vendors by status.
     */
    public function getByStatus(string $status, ?int $limit = null): Collection
    {
        $query = Vendor::query()->where('status', $status);

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Search vendors by query.
     */
    public function search(string $query, ?int $limit = null): Collection
    {
        $searchQuery = Vendor::query()
            ->where('business_name', 'like', "%{$query}%")
            ->orWhere('contact_name', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%");

        if ($limit !== null) {
            $searchQuery->limit($limit);
        }

        return $searchQuery->get();
    }

    /**
     * Create a new vendor.
     */
    public function create(array $data): Vendor
    {
        return Vendor::query()->create($data);
    }

    /**
     * Update a vendor.
     */
    public function update(string $id, array $data): Vendor
    {
        $vendor = $this->find($id);

        if ($vendor === null) {
            throw new \RuntimeException("Vendor with ID {$id} not found");
        }

        $vendor->update($data);

        return $vendor->refresh();
    }

    /**
     * Update vendor status.
     */
    public function updateStatus(string $id, string $status): Vendor
    {
        return $this->update($id, ['status' => $status]);
    }

    /**
     * Delete a vendor.
     */
    public function delete(string $id): bool
    {
        $vendor = $this->find($id);

        if ($vendor === null) {
            return false;
        }

        return (bool) $vendor->delete();
    }

    /**
     * Get vendor with relationships loaded.
     */
    public function findWithRelations(string $id, array $relations = []): ?Vendor
    {
        return Vendor::query()
            ->with($relations)
            ->find($id);
    }

    /**
     * Check if vendor exists by ID.
     */
    public function exists(string $id): bool
    {
        return Vendor::query()->where('id', $id)->exists();
    }

    /**
     * Check if email is already registered.
     */
    public function emailExists(string $email): bool
    {
        return Vendor::query()->where('email', $email)->exists();
    }

    /**
     * Get vendor statistics.
     */
    public function getStatistics(string $id): array
    {
        $vendor = $this->findWithRelations($id, ['products']);

        if ($vendor === null) {
            return [];
        }

        $productsCount = $vendor->products->count();
        $ordersCount = \Lunar\Models\Order::query()
            ->whereHas('lines.purchasable.product', function ($query) use ($id): void {
                $query->where('vendor_id', $id);
            })
            ->count();

        $totalSales = \Lunar\Models\Order::query()
            ->whereHas('lines.purchasable.product', function ($query) use ($id): void {
                $query->where('vendor_id', $id);
            })
            ->sum('total');

        return [
            'products_count' => $productsCount,
            'orders_count' => $ordersCount,
            'total_sales' => $totalSales,
        ];
    }

    /**
     * Get tenant-wide vendor statistics for KPI cards.
     *
     * Returns total, active, and pending vendor counts for the given tenant.
     * This method is independent of any search or filter parameters.
     *
     * @param  string  $tenantId  The tenant ID
     * @return array{total: int, active: int, pending: int} Vendor statistics
     */
    public function getTenantStatistics(string $tenantId): array
    {
        $total = Vendor::where('tenant_id', $tenantId)->count();
        $active = Vendor::where('tenant_id', $tenantId)->where('status', 'active')->count();
        $pending = Vendor::where('tenant_id', $tenantId)->where('status', 'pending')->count();

        return [
            'total' => $total,
            'active' => $active,
            'pending' => $pending,
        ];
    }

    /**
     * Get top vendors by sales.
     */
    public function getTopBySales(int $limit = 10): Collection
    {
        return Vendor::query()
            ->with(['products'])
            ->get()
            ->map(function (Vendor $vendor): array {
                $totalSales = \Lunar\Models\Order::query()
                    ->whereHas('lines.purchasable.product', function ($query) use ($vendor): void {
                        $query->where('vendor_id', $vendor->id);
                    })
                    ->sum('total');

                return [
                    'vendor' => $vendor,
                    'total_sales' => $totalSales,
                ];
            })
            ->sortByDesc('total_sales')
            ->take($limit)
            ->pluck('vendor');
    }

    /**
     * Approve vendor application.
     */
    public function approve(string $id): Vendor
    {
        return $this->update($id, [
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    /**
     * Reject vendor application.
     */
    public function reject(string $id, ?string $reason = null): Vendor
    {
        $data = [
            'status' => 'rejected',
            'rejected_at' => now(),
        ];

        if ($reason !== null) {
            $data['rejection_reason'] = $reason;
        }

        return $this->update($id, $data);
    }

    /**
     * Suspend a vendor.
     */
    public function suspend(string $vendorId, ?string $reason = null): Vendor
    {
        $data = [
            'status' => 'suspended',
            'suspended_at' => now(),
        ];

        if ($reason !== null) {
            $data['suspension_reason'] = $reason;
        }

        return $this->update($vendorId, $data);
    }
}
