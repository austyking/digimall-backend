<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

final class UserRepository implements UserRepositoryInterface
{
    /**
     * Find a user by ID.
     */
    public function find(string $id): ?User
    {
        return User::query()->find($id);
    }

    /**
     * Find a user by email.
     */
    public function findByEmail(string $email): ?User
    {
        return User::query()->where('email', $email)->first();
    }

    /**
     * Get all users.
     */
    public function all(): Collection
    {
        return User::query()->get();
    }

    /**
     * Get users with pagination.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return User::query()->paginate($perPage);
    }

    /**
     * Get users by tenant ID.
     */
    public function getByTenant(string $tenantId, ?int $limit = null): Collection
    {
        $query = User::query()->where('tenant_id', $tenantId);

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Search users by query.
     */
    public function search(string $query): Collection
    {
        return User::query()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            })
            ->get();
    }

    /**
     * Create a new user.
     */
    public function create(array $data): User
    {
        return User::query()->create($data);
    }

    /**
     * Update a user.
     */
    public function update(string $id, array $data): User
    {
        $user = $this->find($id);

        if (! $user) {
            throw new \Exception("User with ID {$id} not found");
        }

        $user->update($data);

        return $user->fresh();
    }

    /**
     * Delete a user.
     */
    public function delete(string $id): bool
    {
        $user = $this->find($id);

        if (! $user) {
            return false;
        }

        return $user->delete();
    }

    /**
     * Get user with relationships loaded.
     */
    public function findWithRelations(string $id, array $relations = []): ?User
    {
        return User::query()->with($relations)->find($id);
    }

    /**
     * Check if user exists by ID.
     */
    public function exists(string $id): bool
    {
        return User::query()->where('id', $id)->exists();
    }

    /**
     * Check if email is already registered.
     */
    public function emailExists(string $email): bool
    {
        return User::query()->where('email', $email)->exists();
    }

    /**
     * Assign role to user.
     */
    public function assignRole(User $user, string $role): void
    {
        $user->assignRole($role);
    }

    /**
     * Remove role from user.
     */
    public function removeRole(User $user, string $role): void
    {
        $user->removeRole($role);
    }

    /**
     * Check if user has role.
     */
    public function hasRole(User $user, string $role): bool
    {
        return $user->hasRole($role);
    }

    /**
     * Get users by role.
     */
    public function getByRole(string $role): Collection
    {
        return User::query()
            ->role($role)
            ->get();
    }
}
