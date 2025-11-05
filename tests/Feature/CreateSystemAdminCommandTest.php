<?php

declare(strict_types=1);

use App\Models\User;
use Spatie\Permission\Models\Role;

describe('CreateSystemAdminCommand', function () {
    beforeEach(function () {
        // Ensure the system-administrator role exists
        Role::firstOrCreate(
            ['name' => 'system-administrator'],
            ['guard_name' => 'web']
        );
    });

    test('creates system administrator with default credentials', function () {
        $this->artisan('admin:create-system-admin')
            ->expectsQuestion('Name', 'Test Admin')
            ->expectsQuestion('Email', 'testadmin@digimall.com')
            ->expectsQuestion('Password (leave empty for default)', 'TestPassword123!')
            ->expectsConfirmation('Do you want to create this system administrator?', 'yes')
            ->assertSuccessful();

        $user = User::query()->where('email', 'testadmin@digimall.com')->first();

        expect($user)->not->toBeNull()
            ->and($user->name)->toBe('Test Admin')
            ->and($user->email)->toBe('testadmin@digimall.com')
            ->and($user->hasRole('system-administrator'))->toBeTrue();
    });

    test('creates system administrator with command options', function () {
        $this->artisan('admin:create-system-admin', [
            '--name' => 'Option Admin',
            '--email' => 'optionadmin@digimall.com',
            '--password' => 'OptionPassword123!',
            '--force' => true,
        ])->assertSuccessful();

        $user = User::query()->where('email', 'optionadmin@digimall.com')->first();

        expect($user)->not->toBeNull()
            ->and($user->name)->toBe('Option Admin')
            ->and($user->hasRole('system-administrator'))->toBeTrue();
    });

    test('fails when email already exists', function () {
        // Create existing user
        User::factory()->create(['email' => 'existing@digimall.com']);

        $this->artisan('admin:create-system-admin', [
            '--name' => 'Duplicate Admin',
            '--email' => 'existing@digimall.com',
            '--password' => 'Password123!',
            '--force' => true,
        ])->assertFailed();
    });

    test('validates email format', function () {
        $this->artisan('admin:create-system-admin', [
            '--name' => 'Invalid Email Admin',
            '--email' => 'invalid-email',
            '--password' => 'Password123!',
            '--force' => true,
        ])->assertFailed();
    });

    test('validates password minimum length', function () {
        $this->artisan('admin:create-system-admin', [
            '--name' => 'Weak Password Admin',
            '--email' => 'weakpass@digimall.com',
            '--password' => 'short',
            '--force' => true,
        ])->assertFailed();
    });

    test('creates system-administrator role if not exists', function () {
        // Delete the role if it exists
        Role::query()->where('name', 'system-administrator')->delete();

        $this->artisan('admin:create-system-admin', [
            '--name' => 'Auto Role Admin',
            '--email' => 'autorole@digimall.com',
            '--password' => 'Password123!',
            '--force' => true,
        ])->assertSuccessful();

        $role = Role::query()->where('name', 'system-administrator')->first();
        expect($role)->not->toBeNull();

        $user = User::query()->where('email', 'autorole@digimall.com')->first();
        expect($user->hasRole('system-administrator'))->toBeTrue();
    });

    test('sets email_verified_at on creation', function () {
        $this->artisan('admin:create-system-admin', [
            '--name' => 'Verified Admin',
            '--email' => 'verified@digimall.com',
            '--password' => 'Password123!',
            '--force' => true,
        ])->assertSuccessful();

        $user = User::query()->where('email', 'verified@digimall.com')->first();
        expect($user->email_verified_at)->not->toBeNull();
    });

    test('displays credentials after creation', function () {
        $this->artisan('admin:create-system-admin', [
            '--name' => 'Display Admin',
            '--email' => 'display@digimall.com',
            '--password' => 'DisplayPassword123!',
            '--force' => true,
        ])
            ->expectsOutput('✓ System Administrator created successfully!')
            ->assertSuccessful();
    });

    test('cancels operation when user declines confirmation', function () {
        $this->artisan('admin:create-system-admin')
            ->expectsQuestion('Name', 'Cancelled Admin')
            ->expectsQuestion('Email', 'cancelled@digimall.com')
            ->expectsQuestion('Password (leave empty for default)', 'Password123!')
            ->expectsConfirmation('Do you want to create this system administrator?', 'no')
            ->expectsOutput('Operation cancelled.')
            ->assertSuccessful();

        $user = User::query()->where('email', 'cancelled@digimall.com')->first();
        expect($user)->toBeNull();
    });
});
