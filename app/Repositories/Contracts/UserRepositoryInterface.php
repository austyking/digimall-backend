<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    /**
     * Find a user by ID.
     */
    public function find(string $id): ?User;

    /**
     * Find a user by email.
     */
    public function findByEmail(string $email): ?User;

    /**
     * Get all users.
     */
    public function all(): Collection;

    /**
     * Get users with pagination.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate(int $perPage = 15);

    /**
     * Get users by tenant ID.
     */
    public function getByTenant(string $tenantId, ?int $limit = null): Collection;

    /**
     * Search users by query.
     */
    public function search(string $query): Collection;

    /**
     * Create a new user.
     */
    public function create(array $data): User;

    /**
     * Update a user.
     */
    public function update(string $id, array $data): User;

    /**
     * Delete a user.
     */
    public function delete(string $id): bool;

    /**
     * Get user with relationships loaded.
     */
    public function findWithRelations(string $id, array $relations = []): ?User;

    /**
     * Check if user exists by ID.
     */
    public function exists(string $id): bool;

    /**
     * Check if email is already registered.
     */
    public function emailExists(string $email): bool;

    /**
     * Assign role to user.
     */
    public function assignRole(User $user, string $role): void;

    /**
     * Remove role from user.
     */
    public function removeRole(User $user, string $role): void;

    /**
     * Check if user has role.
     */
    public function hasRole(User $user, string $role): bool;

    /**
     * Get users by role.
     */
    public function getByRole(string $role): Collection;
}
