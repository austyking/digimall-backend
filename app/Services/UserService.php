<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;

final readonly class UserService
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    /**
     * Create a new user with role assignment.
     *
     * Creates a user account and assigns the specified role.
     * Handles password hashing and tenant scoping.
     *
     * @param  array  $data  User data including name, email, password
     * @param  string  $role  Role to assign to the user
     * @return User The created user instance
     *
     * @throws \RuntimeException If email already exists or tenant context missing
     */
    public function createUserWithRole(array $data, string $role): User
    {
        // Check email uniqueness
        if ($this->userRepository->emailExists($data['email'])) {
            throw new \RuntimeException("A user with email {$data['email']} already exists.");
        }

        // Get current tenant for scoping
        $tenant = tenancy()->tenant;
        if (! $tenant) {
            throw new \RuntimeException('No tenant context found for user creation.');
        }

        // Hash password if provided
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        // Add tenant ID to data
        $data['tenant_id'] = $tenant->id;

        // Create user
        $user = $this->userRepository->create($data);

        // Assign role
        $this->userRepository->assignRole($user, $role);

        return $user;
    }

    /**
     * Create a user with a random password (for vendor registration).
     *
     * Generates a random password and creates the user with the specified role.
     * Returns both the user and the plain text password.
     *
     * @param  array  $data  User data including name, email
     * @param  string  $role  Role to assign to the user
     * @return array{user: User, password: string} User instance and plain text password
     */
    public function createUserWithRandomPassword(array $data, string $role): array
    {
        $plainPassword = \Illuminate\Support\Str::random(8);
        $data['password'] = $plainPassword;

        $user = $this->createUserWithRole($data, $role);

        return [
            'user' => $user,
            'password' => $plainPassword,
        ];
    }

    /**
     * Find a user by ID.
     */
    public function findById(string $id): ?User
    {
        return $this->userRepository->find($id);
    }

    /**
     * Find a user by email.
     */
    public function findByEmail(string $email): ?User
    {
        return $this->userRepository->findByEmail($email);
    }

    /**
     * Update user information.
     */
    public function updateUser(string $id, array $data): User
    {
        return $this->userRepository->update($id, $data);
    }

    /**
     * Assign role to user.
     */
    public function assignRole(User $user, string $role): void
    {
        $this->userRepository->assignRole($user, $role);
    }

    /**
     * Check if user has role.
     */
    public function hasRole(User $user, string $role): bool
    {
        return $this->userRepository->hasRole($user, $role);
    }

    /**
     * Get users by role.
     */
    public function getUsersByRole(string $role): \Illuminate\Database\Eloquent\Collection
    {
        return $this->userRepository->getByRole($role);
    }
}
