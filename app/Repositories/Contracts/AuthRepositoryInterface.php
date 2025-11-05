<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\User;

interface AuthRepositoryInterface
{
    /**
     * Find a user by email address.
     */
    public function findByEmail(string $email): ?User;

    /**
     * Find a user by ID.
     */
    public function findById(string $id): ?User;

    /**
     * Revoke a specific token for the user.
     */
    public function revokeToken(User $user): void;

    /**
     * Revoke all tokens for the user.
     */
    public function revokeAllTokens(User $user): void;
}
