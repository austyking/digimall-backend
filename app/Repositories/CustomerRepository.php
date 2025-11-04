<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Customer;
use App\Models\Tenant;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;

final class CustomerRepository implements CustomerRepositoryInterface
{
    /**
     * Find a customer by ID.
     */
    public function find(string $id): ?Customer
    {
        return Customer::query()->find($id);
    }

    /**
     * Find a customer by membership number.
     */
    public function findByMembershipNumber(string $membershipNumber): ?Customer
    {
        return Customer::query()->where('membership_number', $membershipNumber)->first();
    }

    /**
     * Find a customer by user ID.
     */
    public function findByUserId(string $userId): ?Customer
    {
        return Customer::query()->where('user_id', $userId)->first();
    }

    /**
     * Get all customers.
     */
    public function all(): Collection
    {
        return Customer::query()->get();
    }

    /**
     * Get customers with pagination.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Customer::query()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get customers by tenant ID (association).
     */
    public function getByTenant(string $tenantId): Collection
    {
        return Customer::query()
            ->where('tenant_id', $tenantId)
            ->get();
    }

    /**
     * Search customers by query (membership number only, since we don't store other data).
     */
    public function search(string $query): Collection
    {
        return Customer::query()
            ->where('membership_number', 'like', "%{$query}%")
            ->get();
    }

    /**
     * Fetch customer data from association API using membership number.
     */
    public function fetchFromAssociation(string $membershipNumber, string $tenantId): ?array
    {
        $tenant = Tenant::query()->find($tenantId);

        if ($tenant === null) {
            return null;
        }

        $apiBaseUrl = $tenant->getSetting('association_api.base_url');
        $apiKey = $tenant->getSetting('association_api.api_key');

        if ($apiBaseUrl === null || $apiKey === null) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Accept' => 'application/json',
            ])->get("{$apiBaseUrl}/members/{$membershipNumber}");

            if (! $response->successful()) {
                return null;
            }

            return $response->json('data');
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Verify customer credentials with association API.
     */
    public function verifyWithAssociation(string $membershipNumber, string $tenantId): bool
    {
        $tenant = Tenant::query()->find($tenantId);

        if ($tenant === null) {
            return false;
        }

        $apiBaseUrl = $tenant->getSetting('association_api.base_url');
        $apiKey = $tenant->getSetting('association_api.api_key');

        if ($apiBaseUrl === null || $apiKey === null) {
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Accept' => 'application/json',
            ])->get("{$apiBaseUrl}/members/verify", [
                'membership_number' => $membershipNumber,
            ]);

            return $response->successful() && $response->json('verified') === true;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Get customer hire-purchase eligibility from association API.
     */
    public function getHirePurchaseEligibility(string $membershipNumber, string $tenantId): array
    {
        $tenant = Tenant::query()->find($tenantId);

        if ($tenant === null) {
            return [
                'eligible' => false,
                'reason' => 'Tenant not found',
            ];
        }

        $apiBaseUrl = $tenant->getSetting('association_api.base_url');
        $apiKey = $tenant->getSetting('association_api.api_key');

        if ($apiBaseUrl === null || $apiKey === null) {
            return [
                'eligible' => false,
                'reason' => 'Association API not configured',
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Accept' => 'application/json',
            ])->get("{$apiBaseUrl}/members/{$membershipNumber}/hire-purchase-eligibility");

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'eligible' => false,
                'reason' => 'Unable to verify eligibility',
            ];
        } catch (\Exception) {
            return [
                'eligible' => false,
                'reason' => 'API error',
            ];
        }
    }

    /**
     * Check if customer is eligible for hire-purchase.
     */
    public function isEligibleForHirePurchase(string $membershipNumber, string $tenantId): bool
    {
        $eligibility = $this->getHirePurchaseEligibility($membershipNumber, $tenantId);

        return $eligibility['eligible'] ?? false;
    }

    /**
     * Create a new customer record (stores only membership_number).
     */
    public function create(array $data): Customer
    {
        return Customer::query()->create($data);
    }

    /**
     * Update a customer.
     */
    public function update(string $id, array $data): Customer
    {
        $customer = $this->find($id);

        if ($customer === null) {
            throw new \RuntimeException("Customer with ID {$id} not found");
        }

        $customer->update($data);

        return $customer->refresh();
    }

    /**
     * Delete a customer.
     */
    public function delete(string $id): bool
    {
        $customer = $this->find($id);

        if ($customer === null) {
            return false;
        }

        return (bool) $customer->delete();
    }

    /**
     * Get customer with relationships loaded.
     */
    public function findWithRelations(string $id, array $relations = []): ?Customer
    {
        return Customer::query()
            ->with($relations)
            ->find($id);
    }

    /**
     * Check if customer exists by ID.
     */
    public function exists(string $id): bool
    {
        return Customer::query()->where('id', $id)->exists();
    }

    /**
     * Check if membership number exists.
     */
    public function membershipNumberExists(string $membershipNumber): bool
    {
        return Customer::query()->where('membership_number', $membershipNumber)->exists();
    }

    /**
     * Get customer orders.
     */
    public function getOrders(string $id): Collection
    {
        $customer = $this->find($id);

        if ($customer === null) {
            return new Collection;
        }

        return $customer->orders()->orderBy('created_at', 'desc')->get();
    }
}
