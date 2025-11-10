<?php

declare(strict_types=1);

use App\DTOs\AdminCreateTenantDTO;
use App\DTOs\DeleteTenantDTO;
use App\Models\Tenant;
use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Services\AdminTenantService;
use Illuminate\Validation\ValidationException;

describe('AdminTenantService Unit Tests', function () {
    beforeEach(function () {
        $this->mockRepository = Mockery::mock(TenantRepositoryInterface::class);
        $this->service = new AdminTenantService($this->mockRepository);
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('createTenant()', function () {
        test('creates tenant when name is unique', function () {
            $dto = new AdminCreateTenantDTO(
                name: 'GPA',
                displayName: 'Ghana Pharmacy Association',
                description: 'Test description',
                active: true,
                settings: ['branding' => ['primary_color' => '#4caf50']],
                createdBy: '123'
            );

            $expectedTenant = Tenant::factory()->make([
                'name' => 'GPA',
                'display_name' => 'Ghana Pharmacy Association',
            ]);

            $this->mockRepository
                ->shouldReceive('findByName')
                ->once()
                ->with('GPA')
                ->andReturn(null);

            $this->mockRepository
                ->shouldReceive('create')
                ->once()
                ->with(Mockery::on(function ($data) {
                    return $data['name'] === 'GPA'
                        && $data['display_name'] === 'Ghana Pharmacy Association'
                        && $data['description'] === 'Test description'
                        && $data['status'] === 'active'
                        && isset($data['settings']['created_by']);
                }))
                ->andReturn($expectedTenant);

            $result = $this->service->createTenant($dto);

            expect($result)
                ->toBeInstanceOf(Tenant::class)
                ->and($result->name)->toBe('GPA')
                ->and($result->display_name)->toBe('Ghana Pharmacy Association');
        });

        test('throws ValidationException when tenant name already exists', function () {
            $dto = new AdminCreateTenantDTO(
                name: 'DUPLICATE',
                displayName: 'Duplicate Association',
                createdBy: '123'
            );

            $existingTenant = Tenant::factory()->make(['name' => 'DUPLICATE']);

            $this->mockRepository
                ->shouldReceive('findByName')
                ->once()
                ->with('DUPLICATE')
                ->andReturn($existingTenant);

            $this->mockRepository
                ->shouldNotReceive('create');

            try {
                $this->service->createTenant($dto);
                expect(true)->toBeFalse('Expected ValidationException to be thrown');
            } catch (ValidationException $e) {
                expect($e->errors())->toHaveKey('name')
                    ->and($e->errors()['name'][0])->toBe('A tenant with this name already exists.');
            }
        });

        test('validates uniqueness case-sensitively', function () {
            $dto = new AdminCreateTenantDTO(
                name: 'GRNMA',
                displayName: 'Test',
                createdBy: '123'
            );

            $this->mockRepository
                ->shouldReceive('findByName')
                ->once()
                ->with('GRNMA')
                ->andReturn(Tenant::factory()->make());

            expect(fn () => $this->service->createTenant($dto))
                ->toThrow(ValidationException::class, 'A tenant with this name already exists.');
        });

        test('passes settings from DTO to repository', function () {
            $settings = [
                'branding' => ['primary_color' => '#1976d2'],
                'features' => ['hire_purchase_enabled' => true],
            ];

            $dto = new AdminCreateTenantDTO(
                name: 'TEST',
                displayName: 'Test',
                settings: $settings,
                createdBy: '123'
            );

            $this->mockRepository
                ->shouldReceive('findByName')
                ->once()
                ->andReturn(null);

            $this->mockRepository
                ->shouldReceive('create')
                ->once()
                ->with(Mockery::on(function ($data) {
                    return isset($data['settings']['branding']['primary_color'])
                        && $data['settings']['branding']['primary_color'] === '#1976d2'
                        && isset($data['settings']['features']['hire_purchase_enabled'])
                        && $data['settings']['features']['hire_purchase_enabled'] === true;
                }))
                ->andReturn(Tenant::factory()->make());

            $this->service->createTenant($dto);
        });

        test('includes audit trail in created tenant', function () {
            $dto = new AdminCreateTenantDTO(
                name: 'AUDIT',
                displayName: 'Audit Test',
                createdBy: 'user-123'
            );

            $this->mockRepository
                ->shouldReceive('findByName')
                ->once()
                ->andReturn(null);

            $this->mockRepository
                ->shouldReceive('create')
                ->once()
                ->with(Mockery::on(function ($data) {
                    return isset($data['settings']['created_by']['user_id'])
                        && $data['settings']['created_by']['user_id'] === 'user-123'
                        && isset($data['settings']['created_by']['at']);
                }))
                ->andReturn(Tenant::factory()->make());

            $this->service->createTenant($dto);
        });
    });

    describe('deleteTenant()', function () {
        test('deletes tenant when it exists', function () {
            $tenantId = 'test-uuid-123';
            $dto = new DeleteTenantDTO(
                tenantId: $tenantId,
                reason: 'Test deletion reason',
                force: false,
                deletedBy: 'admin-456'
            );

            // Mock the tenant object
            $tenant = Mockery::mock(Tenant::class)->makePartial();
            $tenant->id = $tenantId;
            $tenant->settings = [];
            $tenant->shouldReceive('save')->once()->andReturn(true);

            $this->mockRepository
                ->shouldReceive('find')
                ->once()
                ->with($tenantId)
                ->andReturn($tenant);

            $this->mockRepository
                ->shouldReceive('delete')
                ->once()
                ->with($tenant)
                ->andReturn(true);

            $result = $this->service->deleteTenant($dto);

            expect($result)->toBeTrue();
        });

        test('throws exception when tenant not found', function () {
            $dto = new DeleteTenantDTO(
                tenantId: 'nonexistent-uuid',
                reason: 'Test reason',
                deletedBy: 'admin-123'
            );

            $this->mockRepository
                ->shouldReceive('find')
                ->once()
                ->with('nonexistent-uuid')
                ->andReturn(null);

            $this->mockRepository
                ->shouldNotReceive('delete');

            expect(fn () => $this->service->deleteTenant($dto))
                ->toThrow(InvalidArgumentException::class, 'Tenant not found');
        });

        test('supports force delete', function () {
            $dto = new DeleteTenantDTO(
                tenantId: 'force-delete-id',
                reason: 'Force deletion',
                force: true,
                deletedBy: 'admin-789'
            );

            // Mock tenant with makePartial() to allow property assignment
            $tenant = Mockery::mock(Tenant::class)->makePartial();
            $tenant->id = 'force-delete-id';
            $tenant->settings = [];
            $tenant->shouldReceive('save')->once()->andReturn(true);

            $this->mockRepository
                ->shouldReceive('find')
                ->once()
                ->andReturn($tenant);

            $this->mockRepository
                ->shouldReceive('delete')
                ->once()
                ->with($tenant)
                ->andReturn(true);

            $result = $this->service->deleteTenant($dto);

            expect($result)->toBeTrue();
        });

        test('adds audit trail with reason to tenant settings', function () {
            $dto = new DeleteTenantDTO(
                tenantId: 'audit-tenant',
                reason: 'Association requested closure',
                force: false,
                deletedBy: 'admin-999'
            );

            // Mock tenant with existing settings
            $tenant = Mockery::mock(Tenant::class)->makePartial();
            $tenant->id = 'audit-tenant';
            $tenant->settings = ['existing' => 'data'];
            $tenant->shouldReceive('save')->once()->andReturn(true);

            $this->mockRepository
                ->shouldReceive('find')
                ->once()
                ->andReturn($tenant);

            $this->mockRepository
                ->shouldReceive('delete')
                ->once()
                ->with($tenant)
                ->andReturn(true);

            $this->service->deleteTenant($dto);

            // Verify settings were merged correctly
            expect($tenant->settings)
                ->toHaveKey('deletion')
                ->and($tenant->settings['deletion']['reason'])->toBe('Association requested closure')
                ->and($tenant->settings['deletion']['deleted_by'])->toBe('admin-999')
                ->and($tenant->settings['deletion']['force'])->toBe(false)
                ->and($tenant->settings)->toHaveKey('existing')
                ->and($tenant->settings['existing'])->toBe('data');
        });

        test('preserves existing settings when adding audit trail', function () {
            $dto = new DeleteTenantDTO(
                tenantId: 'preserve-test',
                reason: 'Test',
                deletedBy: 'admin'
            );

            $existingSettings = [
                'branding' => ['primary_color' => '#000'],
                'features' => ['enabled' => true],
            ];

            // Mock tenant with existing settings
            $tenant = Mockery::mock(Tenant::class)->makePartial();
            $tenant->id = 'preserve-test';
            $tenant->settings = $existingSettings;
            $tenant->shouldReceive('save')->once()->andReturn(true);

            $this->mockRepository
                ->shouldReceive('find')
                ->once()
                ->andReturn($tenant);

            $this->mockRepository
                ->shouldReceive('delete')
                ->once()
                ->with($tenant)
                ->andReturn(true);

            $this->service->deleteTenant($dto);

            // Verify original settings preserved
            expect($tenant->settings['branding']['primary_color'])->toBe('#000')
                ->and($tenant->settings['features']['enabled'])->toBe(true)
                ->and($tenant->settings)->toHaveKey('deletion');
        });
    });

    describe('getTenant()', function () {
        test('returns tenant when found', function () {
            $tenantId = 'find-me';
            $expectedTenant = Tenant::factory()->make(['id' => $tenantId]);

            $this->mockRepository
                ->shouldReceive('find')
                ->once()
                ->with($tenantId)
                ->andReturn($expectedTenant);

            $result = $this->service->getTenant($tenantId);

            expect($result)
                ->toBeInstanceOf(Tenant::class)
                ->and($result->id)->toBe($tenantId);
        });

        test('returns null when tenant not found', function () {
            $this->mockRepository
                ->shouldReceive('find')
                ->once()
                ->with('nonexistent')
                ->andReturn(null);

            $result = $this->service->getTenant('nonexistent');

            expect($result)->toBeNull();
        });
    });
});
