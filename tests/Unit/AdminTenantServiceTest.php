<?php

declare(strict_types=1);

use App\DTOs\ActivateTenantDTO;
use App\DTOs\AdminUpdateTenantDTO;
use App\DTOs\DeactivateTenantDTO;
use App\DTOs\TenantFilterDTO;
use App\Models\Tenant;
use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Services\AdminTenantService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

beforeEach(function () {
    $this->mockRepository = mock(TenantRepositoryInterface::class);
    $this->service = new AdminTenantService($this->mockRepository);
});

describe('AdminTenantService Unit Tests', function () {
    describe('getFilteredTenants()', function () {
        test('delegates to repository with correct parameters', function () {
            $filterDTO = new TenantFilterDTO(active: true, search: 'test', sortBy: 'name', sortDirection: 'asc', perPage: 20);
            $mockPaginator = mock(LengthAwarePaginator::class);

            $this->mockRepository
                ->expects('getFiltered')
                ->with(true, 'test', 'name', 'asc', 20)
                ->andReturn($mockPaginator);

            $result = $this->service->getFilteredTenants($filterDTO);

            expect($result)->toBe($mockPaginator);
        });
    });

    describe('activateTenant()', function () {
        test('activates inactive tenant and calls repository', function () {
            $tenant = new Tenant(['id' => 'test-id', 'active' => false]);
            $dto = new ActivateTenantDTO(reason: 'Approved by admin');
            $activatedTenant = new Tenant(['id' => 'test-id', 'active' => true]);

            $this->mockRepository
                ->expects('updateStatus')
                ->with($tenant, true, 'Approved by admin')
                ->andReturn($activatedTenant);

            $result = $this->service->activateTenant($tenant, $dto);

            expect($result->active)->toBeTrue();
        });

        test('throws exception when tenant already active', function () {
            $tenant = new Tenant(['id' => 'test-id', 'active' => true]);
            $dto = new ActivateTenantDTO(reason: 'Test');

            expect(fn () => $this->service->activateTenant($tenant, $dto))
                ->toThrow(\InvalidArgumentException::class, 'Tenant is already active');
        });
    });

    describe('deactivateTenant()', function () {
        test('deactivates active tenant and calls repository', function () {
            $tenant = new Tenant(['id' => 'test-id', 'active' => true]);
            $dto = new DeactivateTenantDTO(reason: 'Policy violation');
            $deactivatedTenant = new Tenant(['id' => 'test-id', 'active' => false]);

            $this->mockRepository
                ->expects('updateStatus')
                ->with($tenant, false, 'Policy violation')
                ->andReturn($deactivatedTenant);

            $result = $this->service->deactivateTenant($tenant, $dto);

            expect($result->active)->toBeFalse();
        });

        test('throws exception when tenant already inactive', function () {
            $tenant = new Tenant(['id' => 'test-id', 'active' => false]);
            $dto = new DeactivateTenantDTO(reason: 'Test');

            expect(fn () => $this->service->deactivateTenant($tenant, $dto))
                ->toThrow(\InvalidArgumentException::class, 'Tenant is already inactive');
        });
    });

    describe('updateTenant()', function () {
        test('updates tenant and calls repository', function () {
            $tenant = new Tenant(['id' => 'test-id', 'display_name' => 'Old Name', 'settings' => ['foo' => 'bar']]);
            $dto = new AdminUpdateTenantDTO(displayName: 'New Name', updatedBy: 'admin-123');
            $updatedTenant = new Tenant(['id' => 'test-id', 'display_name' => 'New Name']);

            $this->mockRepository
                ->expects('update')
                ->withArgs(function ($passedTenant, $data) use ($tenant) {
                    return $passedTenant === $tenant
                        && $data['display_name'] === 'New Name'
                        && ! isset($data['updatedBy']);
                })
                ->andReturn($updatedTenant);

            $result = $this->service->updateTenant($tenant, $dto);

            expect($result)->toBe($updatedTenant);
        });
    });

    describe('getInactiveTenants()', function () {
        test('delegates to repository', function () {
            $inactiveTenants = collect([new Tenant(['active' => false])]);

            $this->mockRepository
                ->expects('allInactive')
                ->andReturn($inactiveTenants);

            $result = $this->service->getInactiveTenants();

            expect($result)->toBe($inactiveTenants);
        });
    });

    describe('getTenant()', function () {
        test('delegates to repository', function () {
            $tenant = new Tenant(['id' => 'test-id']);

            $this->mockRepository
                ->expects('find')
                ->with('test-id')
                ->andReturn($tenant);

            $result = $this->service->getTenant('test-id');

            expect($result)->toBe($tenant);
        });

        test('returns null when not found', function () {
            $this->mockRepository
                ->expects('find')
                ->with('nonexistent')
                ->andReturn(null);

            $result = $this->service->getTenant('nonexistent');

            expect($result)->toBeNull();
        });
    });
});
