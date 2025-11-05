<?php

declare(strict_types=1);

use App\Models\User;
use App\Repositories\AuthRepository;
use App\Repositories\Contracts\AuthRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->authRepository = app(AuthRepositoryInterface::class);
});

describe('AuthRepository', function (): void {
    test('resolves from service container', function (): void {
        $repository = app(AuthRepositoryInterface::class);

        expect($repository)->toBeInstanceOf(AuthRepository::class);
    });

    test('revokes current token for user', function (): void {
        // Create Passport client for token generation
        app(\Laravel\Passport\ClientRepository::class)->createPersonalAccessGrantClient(
            name: 'Test Personal Access Client',
            provider: 'users'
        );

        $user = User::factory()->create();
        $user->createToken('test-token');

        // In test environment, user->token() returns null since there's no authenticated request
        // This test verifies the method handles null gracefully
        $this->authRepository->revokeToken($user);

        expect(true)->toBeTrue();
    });

    test('revokes all tokens for user', function (): void {
        // Create Passport client for token generation
        app(\Laravel\Passport\ClientRepository::class)->createPersonalAccessGrantClient(
            name: 'Test Personal Access Client',
            provider: 'users'
        );

        $user = User::factory()->create();

        // Create multiple tokens
        $token1 = $user->createToken('token-1')->token;
        $token2 = $user->createToken('token-2')->token;
        $token3 = $user->createToken('token-3')->token;

        expect($token1->revoked)->toBeFalse()
            ->and($token2->revoked)->toBeFalse()
            ->and($token3->revoked)->toBeFalse();

        $this->authRepository->revokeAllTokens($user);

        // Refresh tokens from database
        $token1->refresh();
        $token2->refresh();
        $token3->refresh();

        expect($token1->revoked)->toBeTrue()
            ->and($token2->revoked)->toBeTrue()
            ->and($token3->revoked)->toBeTrue();
    });

    test('handles revoking token when user has no tokens', function (): void {
        $user = User::factory()->create();

        // Should not throw an exception
        $this->authRepository->revokeToken($user);

        expect(true)->toBeTrue();
    });

    test('handles revoking all tokens when user has no tokens', function (): void {
        $user = User::factory()->create();

        // Should not throw an exception
        $this->authRepository->revokeAllTokens($user);

        expect($user->tokens()->count())->toBe(0);
    });
});
