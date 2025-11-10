<?php

declare(strict_types=1);

use App\Models\User;
use App\Repositories\Contracts\AuthRepositoryInterface;
use App\Services\Auth\AuthService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;
use Mockery;

beforeEach(function (): void {
    $this->mockRepository = Mockery::mock(AuthRepositoryInterface::class);
    $this->authService = new AuthService($this->mockRepository);
});

afterEach(function (): void {
    Mockery::close();
});

describe('AuthService - Exception Handling', function (): void {
    test('throws AuthenticationException when user not found', function (): void {
        // Mock repository returns null (user not found)
        $this->mockRepository
            ->shouldReceive('findByEmail')
            ->once()
            ->with('nonexistent@example.com')
            ->andReturn(null);

        // Expect exception to be thrown
        expect(fn () => $this->authService->login('nonexistent@example.com', 'password123'))
            ->toThrow(AuthenticationException::class, 'Invalid credentials');
    });

    test('throws AuthenticationException when password is incorrect', function (): void {
        // Create a partial mock to allow password property access
        $user = Mockery::mock(User::class)->makePartial();
        $user->email = 'test@example.com';
        $user->password = Hash::make('correct-password');

        $this->mockRepository
            ->shouldReceive('findByEmail')
            ->once()
            ->with('test@example.com')
            ->andReturn($user);

        // Attempt login with wrong password
        expect(fn () => $this->authService->login('test@example.com', 'wrong-password'))
            ->toThrow(AuthenticationException::class, 'Invalid credentials');
    });
});

describe('AuthService - Logout Operations', function (): void {
    test('logout calls repository revokeToken method', function (): void {
        $user = Mockery::mock(User::class);

        // Mock repository expects revokeToken to be called once
        $this->mockRepository
            ->shouldReceive('revokeToken')
            ->once()
            ->with($user)
            ->andReturnNull();

        $this->authService->logout($user);

        // Mockery will automatically verify expectations
        expect(true)->toBeTrue();
    });

    test('logoutFromAllDevices calls repository revokeAllTokens method', function (): void {
        $user = Mockery::mock(User::class);

        // Mock repository expects revokeAllTokens to be called once
        $this->mockRepository
            ->shouldReceive('revokeAllTokens')
            ->once()
            ->with($user)
            ->andReturnNull();

        $this->authService->logoutFromAllDevices($user);

        // Mockery will automatically verify expectations
        expect(true)->toBeTrue();
    });
});

describe('AuthService - Dependency Injection', function (): void {
    test('resolves from service container with dependency injection', function (): void {
        $service = app(AuthService::class);

        expect($service)->toBeInstanceOf(AuthService::class);
    });
});
