<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Collection;

interface CustomerRepositoryInterface
{
    /**
     * Find a customer by ID.
     */
    public function find(string $id): ?Customer;

    /**
     * Find a customer by membership number.
     */
    public function findByMembershipNumber(string $membershipNumber): ?Customer;

    /**
     * Find a customer by user ID.
     */
    public function findByUserId(string $userId): ?Customer;

    /**
     * Get all customers.
     */
    public function all(): Collection;

    /**
     * Get customers with pagination.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate(int $perPage = 15);

    /**
     * Get customers by tenant ID (association).
     */
    public function getByTenant(string $tenantId): Collection;

    /**
     * Search customers by query.
     */
    public function search(string $query): Collection;

    /**
     * Fetch customer data from association API using membership number.
     */
    public function fetchFromAssociation(string $membershipNumber, string $tenantId): ?array;

    /**
     * Verify customer credentials with association API.
     */
    public function verifyWithAssociation(string $membershipNumber, string $tenantId): bool;

    /**
     * Get customer hire-purchase eligibility from association API.
     */
    public function getHirePurchaseEligibility(string $membershipNumber, string $tenantId): array;

    /**
     * Check if customer is eligible for hire-purchase.
     */
    public function isEligibleForHirePurchase(string $membershipNumber, string $tenantId): bool;

    /**
     * Create a new customer record (stores only membership_number).
     */
    public function create(array $data): Customer;

    /**
     * Update a customer.
     */
    public function update(string $id, array $data): Customer;

    /**
     * Delete a customer.
     */
    public function delete(string $id): bool;

    /**
     * Get customer with relationships loaded.
     */
    public function findWithRelations(string $id, array $relations = []): ?Customer;

    /**
     * Check if customer exists by ID.
     */
    public function exists(string $id): bool;

    /**
     * Check if membership number exists.
     */
    public function membershipNumberExists(string $membershipNumber): bool;

    /**
     * Get customer orders.
     */
    public function getOrders(string $id): Collection;
}
