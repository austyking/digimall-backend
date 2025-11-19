<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\User;

/**
 * Interface for user management operations.
 */
interface UserServiceInterface
{
    /**
     * Create a new user with role assignment and random password.
     *
     * @param  array  $data  User data including name, email
     * @param  string  $role  Role to assign to the user
     * @return array{user: User, password: string} User instance and generated password
     */
    public function createUserWithRandomPassword(array $data, string $role): array;
}
