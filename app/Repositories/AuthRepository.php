<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Contracts\AuthRepositoryInterface;

final class AuthRepository implements AuthRepositoryInterface
{
    /**
     * Find a user by email address.
     */
    public function findByEmail(string $email): ?User
    {
        return User::query()
            ->where('email', $email)
            ->first();
    }

    /**
     * Find a user by ID.
     */
    public function findById(string $id): ?User
    {
        return User::query()->find($id);
    }

    /**
     * Revoke a specific token for the user.
     */
    public function revokeToken(User $user): void
    {
        $user->token()?->revoke();
    }

    /**
     * Revoke all tokens for the user.
     */
    public function revokeAllTokens(User $user): void
    {
        $user->tokens()->each(function ($token) {
            $token->revoke();
        });
    }
}
