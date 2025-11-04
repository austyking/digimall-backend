<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Member;
use Illuminate\Database\Eloquent\Collection;

interface MemberRepositoryInterface
{
    /**
     * Find a member by ID.
     */
    public function find(string $id): ?Member;

    /**
     * Find a member by membership number.
     */
    public function findByMembershipNumber(string $membershipNumber): ?Member;

    /**
     * Find a member by email.
     */
    public function findByEmail(string $email): ?Member;

    /**
     * Find a member by user ID.
     */
    public function findByUserId(string $userId): ?Member;

    /**
     * Get all members.
     */
    public function all(): Collection;

    /**
     * Get members with pagination.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate(int $perPage = 15);

    /**
     * Get active members only.
     */
    public function getActive(): Collection;

    /**
     * Get members by tenant ID (association).
     */
    public function getByTenant(string $tenantId): Collection;

    /**
     * Search members by query.
     */
    public function search(string $query): Collection;

    /**
     * Verify member credentials with association API.
     */
    public function verifyWithAssociation(string $membershipNumber, string $tenantId): bool;

    /**
     * Get member hire-purchase eligibility.
     */
    public function getHirePurchaseEligibility(string $id): array;

    /**
     * Check if member is eligible for hire-purchase.
     */
    public function isEligibleForHirePurchase(string $id): bool;

    /**
     * Get member's hire-purchase applications.
     */
    public function getHirePurchaseApplications(string $id): Collection;

    /**
     * Create a new member.
     */
    public function create(array $data): Member;

    /**
     * Update a member.
     */
    public function update(string $id, array $data): Member;

    /**
     * Update member status.
     */
    public function updateStatus(string $id, string $status): Member;

    /**
     * Delete a member.
     */
    public function delete(string $id): bool;

    /**
     * Get member with relationships loaded.
     */
    public function findWithRelations(string $id, array $relations = []): ?Member;

    /**
     * Check if member exists by ID.
     */
    public function exists(string $id): bool;

    /**
     * Check if membership number exists.
     */
    public function membershipNumberExists(string $membershipNumber): bool;

    /**
     * Sync member data from association API.
     */
    public function syncFromAssociation(string $membershipNumber, string $tenantId): ?Member;

    /**
     * Get member orders.
     */
    public function getOrders(string $id): Collection;

    /**
     * Get member statistics.
     */
    public function getStatistics(string $id): array;
}
