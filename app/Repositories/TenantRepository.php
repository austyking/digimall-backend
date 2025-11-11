<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Tenant;
use App\Repositories\Contracts\TenantRepositoryInterface;
use DB;
use Illuminate\Support\Collection;

final class TenantRepository implements TenantRepositoryInterface
{
    /**
     * Find a tenant by ID.
     */
    public function find(string $id): ?Tenant
    {
        return Tenant::find($id);
    }

    /**
     * Find a tenant by name.
     */
    public function findByName(string $name): ?Tenant
    {
        return Tenant::query()->where('name', $name)->first();
    }

    /**
     * Get all tenants.
     */
    public function all(): Collection
    {
        return Tenant::query()->get();
    }

    /**
     * Get all active tenants.
     */
    public function allActive(): Collection
    {
        return Tenant::query()->with('domains')->where('status', 'active')->get();
    }

    /**
     * Create a new tenant.
     */
    public function create(array $data): Tenant
    {
        return DB::transaction(function () use ($data) {
            // Extract domain (handled separately)
            $domain = $data['domain'] ?? null;
            unset($data['domain']);

            // Extract and handle logo file upload
            $logo = $data['logo'] ?? null;
            unset($data['logo']);

            // Create the tenant
            $tenant = Tenant::create($data);

            // Handle logo upload if present
            if ($logo && $logo instanceof \Illuminate\Http\UploadedFile) {
                $path = $logo->store("tenants/{$tenant->id}", 'public');

                // Update tenant with logo URL
                $tenant->update(['logo_url' => \Storage::disk('public')->url($path)]);
            }

            // Create domain if provided
            if ($domain) {
                $tenant->domains()->create(['domain' => $domain]);
            }

            return $tenant->fresh(['domains']);
        });
    }

    /**
     * Update a tenant.
     */
    public function update(Tenant $tenant, array $data): Tenant
    {
        $tenant->update($data);

        return $tenant->fresh();
    }

    /**
     * Delete a tenant.
     */
    public function delete(Tenant $tenant): bool
    {
        return $tenant->delete();
    }

    /**
     * Get tenant with domains loaded.
     */
    public function findWithDomains(string $id): ?Tenant
    {
        return Tenant::with('domains')->find($id);
    }

    /**
     * Search tenants by name.
     */
    public function search(string $query): Collection
    {
        return Tenant::query()
            ->where('name', 'LIKE', "%{$query}%")
            ->orWhere('display_name', 'LIKE', "%{$query}%")
            ->get();
    }

    /**
     * Get all inactive tenants.
     */
    public function allInactive(): Collection
    {
        return Tenant::query()->with('domains')->where('status', 'inactive')->get();
    }

    /**
     * Get filtered and paginated tenants.
     */
    public function getFiltered(
        ?string $status = null,
        ?string $search = null,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc',
        int $perPage = 15
    ) {
        $query = Tenant::query()->with('domains');

        // Apply status filter
        if ($status !== null) {
            $query->where('status', $status);
        }

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('display_name', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        // Apply sorting
        $query->orderBy($sortBy, $sortDirection);

        return $query->paginate($perPage);
    }

    /**
     * Count total tenants.
     */
    public function count(): int
    {
        return Tenant::count();
    }

    /**
     * Count active tenants.
     */
    public function countActive(): int
    {
        return Tenant::query()->where('status', 'active')->count();
    }

    /**
     * Count inactive tenants.
     */
    public function countInactive(): int
    {
        return Tenant::query()->where('status', 'inactive')->count();
    }

    /**
     * Update tenant status.
     */
    public function updateStatus(Tenant $tenant, string $status, ?string $reason = null): Tenant
    {
        $updateData = ['status' => $status];

        if ($reason) {
            $settings = $tenant->settings ?? [];
            $settings['status_history'] = $settings['status_history'] ?? [];
            $settings['status_history'][] = [
                'status' => $status,
                'reason' => $reason,
                'timestamp' => now()->toISOString(),
            ];
            $updateData['settings'] = $settings;
        }

        $tenant->update($updateData);

        return $tenant->fresh();
    }

    /**
     * Bulk update tenant statuses.
     */
    public function bulkUpdateStatus(array $tenantIds, string $status, ?string $reason = null): int
    {
        return Tenant::query()
            ->whereIn('id', $tenantIds)
            ->update(['status' => $status]);
    }

    /**
     * Get count of tenants created since a specific date.
     */
    public function countCreatedSince(\DateTimeInterface $date): int
    {
        return Tenant::query()
            ->where('created_at', '>=', $date)
            ->count();
    }

    /**
     * Get tenant distribution by creation date (for charts).
     *
     * @param  int  $days  Number of days to look back
     * @return array<string, int> Date => count mapping
     */
    public function getDistributionByDate(int $days = 30): array
    {
        $results = Tenant::query()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $distribution = [];
        foreach ($results as $result) {
            $distribution[$result->date] = $result->count;
        }

        return $distribution;
    }

    /**
     * Get tenant IDs by status from a list of IDs.
     *
     * @param  array  $tenantIds  List of tenant IDs to filter
     * @param  string  $status  Status to filter by ('active' or 'inactive')
     * @return array List of tenant IDs matching the status
     */
    public function getIdsByStatus(array $tenantIds, string $status): array
    {
        return Tenant::query()
            ->whereIn('id', $tenantIds)
            ->where('status', $status)
            ->pluck('id')
            ->toArray();
    }
}
