<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use App\Repositories\Contracts\AuthRepositoryInterface;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;

final class AuthService
{
    public function __construct(
        private readonly AuthRepositoryInterface $authRepository
    ) {}

    /**
     * Attempt to authenticate a user and generate an access token.
     *
     * @return array{user: User, token: string}
     *
     * @throws AuthenticationException
     */
    public function login(string $email, string $password, bool $remember = false): array
    {
        $user = $this->authRepository->findByEmail($email);

        if (! $user || ! Hash::check($password, $user->password)) {
            throw new AuthenticationException('Invalid credentials');
        }

        // Generate OAuth2 personal access token using Passport
        $tokenResult = $user->createToken('auth-token');

        // Set token expiration based on remember me option
        $token = $tokenResult->token;
        $token->expires_at = $remember ? now()->addDays(30) : now()->addDay();
        $token->save();

        return [
            'user' => $user,
            'token' => $tokenResult->accessToken,
        ];
    }

    /**
     * Log out the authenticated user by revoking their current token.
     */
    public function logout(User $user): void
    {
        $this->authRepository->revokeToken($user);
    }

    /**
     * Log out the user from all devices by revoking all tokens.
     */
    public function logoutFromAllDevices(User $user): void
    {
        $this->authRepository->revokeAllTokens($user);
    }
}
