<?php

declare(strict_types=1);

use App\DTOs\AdminCreateTenantDTO;
use App\DTOs\AdminUpdateTenantDTO;
use Illuminate\Http\Request;

describe('AdminCreateTenantDTO', function () {
    describe('fromRequest with JSON settings', function () {
        test('decodes valid JSON settings string', function () {
            $request = Request::create('/test', 'POST', [
                'name' => 'TEST',
                'display_name' => 'Test Association',
                'settings' => json_encode([
                    'theme' => ['primary_color' => '#1976d2'],
                    'features' => ['hire_purchase_enabled' => true],
                ]),
            ]);

            $dto = AdminCreateTenantDTO::fromRequest($request);

            expect($dto->settings)
                ->toBeArray()
                ->toHaveKey('theme')
                ->toHaveKey('features')
                ->and($dto->settings['theme']['primary_color'])->toBe('#1976d2');
        });

        test('throws exception for invalid JSON settings string', function () {
            $request = Request::create('/test', 'POST', [
                'name' => 'TEST',
                'display_name' => 'Test Association',
                'settings' => '{invalid json syntax}',
            ]);

            expect(fn () => AdminCreateTenantDTO::fromRequest($request))
                ->toThrow(InvalidArgumentException::class, 'Invalid JSON format for settings');
        });

        test('handles array settings directly', function () {
            $request = Request::create('/test', 'POST', [
                'name' => 'TEST',
                'display_name' => 'Test Association',
                'settings' => [
                    'theme' => ['primary_color' => '#1976d2'],
                ],
            ]);

            $dto = AdminCreateTenantDTO::fromRequest($request);

            expect($dto->settings)
                ->toBeArray()
                ->toHaveKey('theme');
        });

        test('returns empty settings when settings not provided', function () {
            $request = Request::create('/test', 'POST', [
                'name' => 'TEST',
                'display_name' => 'Test Association',
            ]);

            $dto = AdminCreateTenantDTO::fromRequest($request);

            expect($dto->settings)->toBeArray()->toBeEmpty();
        });
    });
});

describe('AdminUpdateTenantDTO', function () {
    describe('fromRequest with JSON settings', function () {
        test('decodes valid JSON settings string', function () {
            $request = Request::create('/test', 'PUT', [
                'display_name' => 'Updated Name',
                'settings' => json_encode([
                    'theme' => ['primary_color' => '#FF5722'],
                ]),
            ]);

            $dto = AdminUpdateTenantDTO::fromRequest($request);

            expect($dto->settings)
                ->toBeArray()
                ->toHaveKey('theme')
                ->and($dto->settings['theme']['primary_color'])->toBe('#FF5722');
        });

        test('throws exception for invalid JSON settings string', function () {
            $request = Request::create('/test', 'PUT', [
                'display_name' => 'Updated Name',
                'settings' => 'not valid json at all',
            ]);

            expect(fn () => AdminUpdateTenantDTO::fromRequest($request))
                ->toThrow(InvalidArgumentException::class, 'Invalid JSON format for settings');
        });

        test('handles array settings directly', function () {
            $request = Request::create('/test', 'PUT', [
                'display_name' => 'Updated Name',
                'settings' => [
                    'theme' => ['secondary_color' => '#4CAF50'],
                ],
            ]);

            $dto = AdminUpdateTenantDTO::fromRequest($request);

            expect($dto->settings)
                ->toBeArray()
                ->toHaveKey('theme');
        });

        test('handles null settings when not provided', function () {
            $request = Request::create('/test', 'PUT', [
                'display_name' => 'Updated Name',
            ]);

            $dto = AdminUpdateTenantDTO::fromRequest($request);

            expect($dto->settings)->toBeNull();
        });

        test('throws exception for malformed JSON with syntax error', function () {
            $request = Request::create('/test', 'PUT', [
                'settings' => '{"theme": "missing closing brace"',
            ]);

            expect(fn () => AdminUpdateTenantDTO::fromRequest($request))
                ->toThrow(InvalidArgumentException::class);
        });
    });
});
