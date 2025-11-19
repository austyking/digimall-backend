<?php

declare(strict_types=1);

use App\DTOs\ActivateTenantDTO;
use App\DTOs\AdminUpdateTenantDTO;
use App\DTOs\DeactivateTenantDTO;
use App\DTOs\TenantFilterDTO;
use App\Models\Tenant;
use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Services\AdminTenantService;
use App\Services\Contracts\FileUploadServiceInterface;
use App\Services\Contracts\UserServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

beforeEach(function () {
    $this->mockRepository = mock(TenantRepositoryInterface::class);
    $this->mockFileUploadService = mock(FileUploadServiceInterface::class);
    $this->mockUserService = mock(UserServiceInterface::class);
    $this->service = new AdminTenantService($this->mockRepository, $this->mockFileUploadService, $this->mockUserService);
});

describe('AdminTenantService Unit Tests', function () {
    describe('getFilteredTenants()', function () {
        test('delegates to repository with correct parameters', function () {
            $filterDTO = new TenantFilterDTO(status: 'active', search: 'test', sortBy: 'name', sortDirection: 'asc', perPage: 20);
            $mockPaginator = mock(LengthAwarePaginator::class);

            $this->mockRepository
                ->expects('getFiltered')
                ->with('active', 'test', 'name', 'asc', 20)
                ->andReturn($mockPaginator);

            $result = $this->service->getFilteredTenants($filterDTO);

            expect($result)->toBe($mockPaginator);
        });
    });

    describe('activateTenant()', function () {
        test('activates inactive tenant and calls repository', function () {
            $tenant = new Tenant(['id' => 'test-id', 'status' => 'inactive']);
            $dto = new ActivateTenantDTO(tenantId: 'test-id', reason: 'Approved by admin');
            $activatedTenant = new Tenant(['id' => 'test-id', 'status' => 'active']);

            $this->mockRepository
                ->expects('updateStatus')
                ->with($tenant, 'active', 'Approved by admin')
                ->andReturn($activatedTenant);

            $result = $this->service->activateTenant($tenant, $dto);

            expect($result->status)->toBe('active');
        });

        test('throws exception when tenant already active', function () {
            $tenant = new Tenant(['id' => 'test-id', 'status' => 'active']);
            $dto = new ActivateTenantDTO(tenantId: 'test-id', reason: 'Test');

            expect(fn () => $this->service->activateTenant($tenant, $dto))
                ->toThrow(\InvalidArgumentException::class, 'Tenant is already active');
        });
    });

    describe('deactivateTenant()', function () {
        test('deactivates active tenant and calls repository', function () {
            $tenant = new Tenant(['id' => 'test-id', 'status' => 'active']);
            $dto = new DeactivateTenantDTO(tenantId: 'test-id', reason: 'Policy violation');
            $deactivatedTenant = new Tenant(['id' => 'test-id', 'status' => 'inactive']);

            $this->mockRepository
                ->expects('updateStatus')
                ->with($tenant, 'inactive', 'Policy violation')
                ->andReturn($deactivatedTenant);

            $result = $this->service->deactivateTenant($tenant, $dto);

            expect($result->status)->toBe('inactive');
        });

        test('throws exception when tenant already inactive', function () {
            $tenant = new Tenant(['id' => 'test-id', 'status' => 'inactive']);
            $dto = new DeactivateTenantDTO(tenantId: 'test-id', reason: 'Test');

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
            $inactiveTenants = collect([new Tenant(['status' => 'inactive'])]);

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
