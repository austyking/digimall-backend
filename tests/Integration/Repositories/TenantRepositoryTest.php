<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Repositories\TenantRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

describe('TenantRepository Integration Tests', function () {
    beforeEach(function () {
        $this->repository = new TenantRepository;
    });

    describe('findByName()', function () {
        test('returns tenant when name exists', function () {
            $tenant = Tenant::factory()->create(['name' => 'GRNMA']);

            $result = $this->repository->findByName('GRNMA');

            expect($result)
                ->toBeInstanceOf(Tenant::class)
                ->and($result->name)->toBe('GRNMA')
                ->and($result->id)->toBe($tenant->id);
        });

        test('returns null when name does not exist', function () {
            $result = $this->repository->findByName('NONEXISTENT');

            expect($result)->toBeNull();
        });

        test('is case sensitive', function () {
            Tenant::factory()->create(['name' => 'GRNMA']);

            $result = $this->repository->findByName('grnma');

            expect($result)->toBeNull();
        });

        test('finds tenant with soft deleted records present', function () {
            Tenant::factory()->create(['name' => 'DELETED', 'deleted_at' => now()]);
            $activeTenant = Tenant::factory()->create(['name' => 'ACTIVE']);

            $result = $this->repository->findByName('ACTIVE');

            expect($result)
                ->toBeInstanceOf(Tenant::class)
                ->and($result->id)->toBe($activeTenant->id);
        });
    });

    describe('create()', function () {
        test('stores tenant in database with all fields', function () {
            $data = [
                'name' => 'GPA',
                'display_name' => 'Ghana Pharmacy Association',
                'description' => 'Pharmaceutical professionals association',
                'status' => 'active',
                'settings' => [
                    'branding' => ['primary_color' => '#4caf50'],
                    'features' => ['hire_purchase_enabled' => true],
                    'created_by' => ['user_id' => '123', 'at' => now()->toISOString()],
                ],
            ];

            $tenant = $this->repository->create($data);

            expect($tenant)
                ->toBeInstanceOf(Tenant::class)
                ->and($tenant->name)->toBe('GPA')
                ->and($tenant->display_name)->toBe('Ghana Pharmacy Association')
                ->and($tenant->description)->toBe('Pharmaceutical professionals association')
                ->and($tenant->status)->toBe('active')
                ->and($tenant->settings)->toBeArray()
                ->and($tenant->settings['branding']['primary_color'])->toBe('#4caf50')
                ->and($tenant->settings['features']['hire_purchase_enabled'])->toBe(true)
                ->and($tenant->settings['created_by']['user_id'])->toBe('123');

            $this->assertDatabaseHas('tenants', [
                'name' => 'GPA',
                'display_name' => 'Ghana Pharmacy Association',
            ]);
        });

        test('generates UUID for id automatically', function () {
            $data = [
                'name' => 'TEST',
                'display_name' => 'Test Association',
            ];

            $tenant = $this->repository->create($data);

            expect($tenant->id)
                ->toBeString()
                ->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
        });

        test('sets status to active by default', function () {
            $data = [
                'name' => 'TEST',
                'display_name' => 'Test Association',
                'status' => 'active', // Explicitly set for clarity in test
            ];

            $tenant = $this->repository->create($data);

            expect($tenant->status)->toBe('active');
        });

        test('allows setting inactive status', function () {
            $data = [
                'name' => 'INACTIVE',
                'display_name' => 'Inactive Association',
                'status' => 'inactive',
            ];

            $tenant = $this->repository->create($data);

            expect($tenant->status)->toBe('inactive');
        });

        test('stores settings as JSON in database', function () {
            $settings = [
                'branding' => ['primary_color' => '#1976d2'],
                'features' => ['hire_purchase_enabled' => false],
            ];

            $tenant = $this->repository->create([
                'name' => 'TEST',
                'display_name' => 'Test',
                'settings' => $settings,
            ]);

            $dbTenant = DB::table('tenants')->where('id', $tenant->id)->first();
            expect(json_decode($dbTenant->settings, true))->toBe($settings);
        });
    });

    describe('delete()', function () {
        test('soft deletes tenant', function () {
            $tenant = Tenant::factory()->create(['name' => 'TODELETE']);

            $result = $this->repository->delete($tenant);

            expect($result)->toBeTrue();
            $this->assertSoftDeleted('tenants', ['id' => $tenant->id]);

            // Verify tenant no longer returned by normal queries
            expect($this->repository->findByName('TODELETE'))->toBeNull();
        });

        test('returns true when tenant deleted', function () {
            $tenant = Tenant::factory()->create();

            $result = $this->repository->delete($tenant);

            expect($result)->toBeTrue();
        });

        test('preserves deleted_at timestamp on soft delete', function () {
            $tenant = Tenant::factory()->create();

            $this->repository->delete($tenant);

            $deletedTenant = Tenant::withTrashed()->find($tenant->id);
            expect($deletedTenant->deleted_at)->not->toBeNull();
        });

        test('can delete already soft deleted tenant', function () {
            $tenant = Tenant::factory()->create();

            // First soft delete
            $this->repository->delete($tenant);
            $this->assertSoftDeleted('tenants', ['id' => $tenant->id]);

            // Verify soft deleted
            $deletedTenant = Tenant::withTrashed()->find($tenant->id);
            expect($deletedTenant)->not->toBeNull()
                ->and($deletedTenant->deleted_at)->not->toBeNull();
        });
    });

    describe('find()', function () {
        test('returns tenant by id', function () {
            $tenant = Tenant::factory()->create(['name' => 'FINDME']);

            $result = $this->repository->find($tenant->id);

            expect($result)
                ->toBeInstanceOf(Tenant::class)
                ->and($result->id)->toBe($tenant->id)
                ->and($result->name)->toBe('FINDME');
        });

        test('returns null for non-existent id', function () {
            $result = $this->repository->find('00000000-0000-0000-0000-000000000000');

            expect($result)->toBeNull();
        });

        test('does not return soft deleted tenants', function () {
            $tenant = Tenant::factory()->create();
            $tenant->delete(); // Soft delete using method

            $result = $this->repository->find($tenant->id);

            expect($result)->toBeNull();
        });
    });

    describe('update()', function () {
        test('updates tenant fields', function () {
            $tenant = Tenant::factory()->create(['name' => 'ORIG']);

            $this->repository->update($tenant, ['display_name' => 'Updated Name']);

            expect($tenant->fresh()->display_name)->toBe('Updated Name')
                ->and($tenant->fresh()->name)->toBe('ORIG');
        });

        test('merges settings on update', function () {
            $tenant = Tenant::factory()->create([
                'settings' => ['feature_a' => true],
            ]);

            $this->repository->update($tenant, [
                'settings' => ['feature_b' => false],
            ]);

            $updated = $tenant->fresh();
            expect($updated->settings)->toHaveKey('feature_b')
                ->and($updated->settings['feature_b'])->toBe(false);
        });
    });

    describe('allActive()', function () {
        test('returns only active tenants', function () {
            Tenant::factory()->count(3)->create(['status' => 'active']);
            Tenant::factory()->count(2)->create(['status' => 'inactive']);

            $active = $this->repository->allActive();

            expect($active)->toHaveCount(3);
            $active->each(fn ($tenant) => expect($tenant->status)->toBe('active'));
        });

        test('does not return soft deleted active tenants', function () {
            Tenant::factory()->create(['status' => 'active']);
            $deleted = Tenant::factory()->create(['status' => 'active']);
            $deleted->delete(); // Soft delete

            $active = $this->repository->allActive();

            expect($active)->toHaveCount(1);
        });
    });

    describe('allInactive()', function () {
        test('returns only inactive tenants', function () {
            Tenant::factory()->count(2)->create(['status' => 'inactive']);
            Tenant::factory()->count(3)->create(['status' => 'active']);

            $inactive = $this->repository->allInactive();

            expect($inactive)->toHaveCount(2);
            $inactive->each(fn ($tenant) => expect($tenant->status)->toBe('inactive'));
        });
    });
});
