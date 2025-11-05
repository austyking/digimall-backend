<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Repositories\Contracts\TenantRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->repository = app(TenantRepositoryInterface::class);
});

describe('TenantRepository Admin Methods', function () {
    describe('allInactive()', function () {
        test('returns only inactive tenants', function () {
            Tenant::factory()->count(3)->create(['status' => 'active']);
            Tenant::factory()->count(2)->create(['status' => 'inactive']);

            $result = $this->repository->allInactive();

            expect($result)->toHaveCount(2)
                ->and($result->every(fn ($tenant) => $tenant->status === 'inactive'))->toBeTrue();
        });

        test('returns empty collection when no inactive tenants', function () {
            Tenant::factory()->count(3)->create(['status' => 'active']);

            $result = $this->repository->allInactive();

            expect($result)->toBeEmpty();
        });
    });

    describe('getFiltered()', function () {
        test('filters tenants by active status', function () {
            Tenant::factory()->count(5)->create(['status' => 'active']);
            Tenant::factory()->count(3)->create(['status' => 'inactive']);

            $result = $this->repository->getFiltered(status: 'active');

            expect($result->total())->toBe(5);

            foreach ($result->items() as $item) {
                expect($item->status)->toBe('active');
            }
        });

        test('filters tenants by inactive status', function () {
            Tenant::factory()->count(5)->create(['status' => 'active']);
            Tenant::factory()->count(3)->create(['status' => 'inactive']);

            $result = $this->repository->getFiltered(status: 'inactive');

            expect($result->total())->toBe(3);

            foreach ($result->items() as $item) {
                expect($item->status)->toBe('inactive');
            }
        });

        test('searches tenants by name', function () {
            Tenant::factory()->create(['name' => 'GRNMA', 'display_name' => 'Ghana Nurses']);
            Tenant::factory()->create(['name' => 'GMA', 'display_name' => 'Ghana Medical']);
            Tenant::factory()->create(['name' => 'TEST', 'display_name' => 'Test Org']);

            $result = $this->repository->getFiltered(search: 'Ghana');

            expect($result->total())->toBe(2);
        });

        test('searches tenants by display name', function () {
            Tenant::factory()->create(['name' => 'ORG1', 'display_name' => 'Medical Association']);
            Tenant::factory()->create(['name' => 'ORG2', 'display_name' => 'Nurses Guild']);

            $result = $this->repository->getFiltered(search: 'Medical');

            expect($result->total())->toBe(1)
                ->and($result->first()->display_name)->toContain('Medical');
        });

        test('combines status filter and search', function () {
            Tenant::factory()->create(['name' => 'GRNMA', 'status' => 'active']);
            Tenant::factory()->create(['name' => 'GMA', 'status' => 'active']);
            Tenant::factory()->create(['name' => 'GMDA', 'status' => 'inactive']);

            $result = $this->repository->getFiltered(status: 'active', search: 'GM');

            expect($result->total())->toBe(1)
                ->and($result->first()->name)->toBe('GMA');
        });

        test('sorts tenants by specified field', function () {
            Tenant::factory()->create(['name' => 'C_ORG', 'created_at' => now()->subDays(3)]);
            Tenant::factory()->create(['name' => 'A_ORG', 'created_at' => now()->subDays(1)]);
            Tenant::factory()->create(['name' => 'B_ORG', 'created_at' => now()->subDays(2)]);

            $result = $this->repository->getFiltered(sortBy: 'name', sortDirection: 'asc');

            expect($result->first()->name)->toBe('A_ORG')
                ->and($result->last()->name)->toBe('C_ORG');
        });

        test('paginates results with specified per page', function () {
            Tenant::factory()->count(25)->create();

            $result = $this->repository->getFiltered(perPage: 10);

            expect($result->perPage())->toBe(10)
                ->and($result->lastPage())->toBe(3);
        });

        test('returns all tenants when no filters applied', function () {
            Tenant::factory()->count(5)->create(['status' => 'active']);
            Tenant::factory()->count(3)->create(['status' => 'inactive']);

            $result = $this->repository->getFiltered();

            expect($result->total())->toBe(8);
        });
    });

    describe('count methods', function () {
        test('count returns total tenant count', function () {
            Tenant::factory()->count(5)->create(['status' => 'active']);
            Tenant::factory()->count(3)->create(['status' => 'inactive']);

            $result = $this->repository->count();

            expect($result)->toBe(8);
        });

        test('countActive returns active tenant count', function () {
            Tenant::factory()->count(5)->create(['status' => 'active']);
            Tenant::factory()->count(3)->create(['status' => 'inactive']);

            $result = $this->repository->countActive();

            expect($result)->toBe(5);
        });

        test('countInactive returns inactive tenant count', function () {
            Tenant::factory()->count(5)->create(['status' => 'active']);
            Tenant::factory()->count(3)->create(['status' => 'inactive']);

            $result = $this->repository->countInactive();

            expect($result)->toBe(3);
        });

        test('count methods return zero when no tenants', function () {
            expect($this->repository->count())->toBe(0)
                ->and($this->repository->countActive())->toBe(0)
                ->and($this->repository->countInactive())->toBe(0);
        });
    });

    describe('updateStatus()', function () {
        test('updates tenant status to active', function () {
            $tenant = Tenant::factory()->create(['status' => 'inactive']);

            $result = $this->repository->updateStatus($tenant, 'active');

            expect($result->status)->toBe('active')
                ->and($result->id)->toBe($tenant->id);
        });

        test('updates tenant status to inactive', function () {
            $tenant = Tenant::factory()->create(['status' => 'active']);

            $result = $this->repository->updateStatus($tenant, 'inactive');

            expect($result->status)->toBe('inactive');
        });

        test('stores status change reason in settings', function () {
            $tenant = Tenant::factory()->create(['status' => 'inactive']);

            $result = $this->repository->updateStatus($tenant, 'active', 'Approved by admin');

            expect($result->settings)->toHaveKey('status_history')
                ->and($result->settings['status_history'])->toHaveCount(1)
                ->and($result->settings['status_history'][0]['status'])->toBe('active')
                ->and($result->settings['status_history'][0]['reason'])->toBe('Approved by admin');
        });

        test('appends to existing status history', function () {
            $tenant = Tenant::factory()->create([
                'status' => 'active',
                'settings' => [
                    'status_history' => [
                        ['status' => 'active', 'reason' => 'Initial setup', 'timestamp' => now()->subDay()->toISOString()],
                    ],
                ],
            ]);

            $result = $this->repository->updateStatus($tenant, 'inactive', 'Policy violation');

            expect($result->settings['status_history'])->toHaveCount(2)
                ->and($result->settings['status_history'][1]['status'])->toBe('inactive')
                ->and($result->settings['status_history'][1]['reason'])->toBe('Policy violation');
        });

        test('does not modify settings when no reason provided', function () {
            $tenant = Tenant::factory()->create(['status' => 'inactive', 'settings' => ['foo' => 'bar']]);

            $result = $this->repository->updateStatus($tenant, 'active');

            expect($result->settings)->toBe(['foo' => 'bar']);
        });
    });

    describe('bulkUpdateStatus()', function () {
        test('updates status for multiple tenants', function () {
            $tenants = Tenant::factory()->count(5)->create(['status' => 'inactive']);
            $tenantIds = $tenants->pluck('id')->toArray();

            $affectedCount = $this->repository->bulkUpdateStatus($tenantIds, 'active');

            expect($affectedCount)->toBe(5)
                ->and(Tenant::whereIn('id', $tenantIds)->where('status', 'active')->count())->toBe(5);
        });

        test('only updates specified tenants', function () {
            $targetTenants = Tenant::factory()->count(3)->create(['status' => 'inactive']);
            $otherTenants = Tenant::factory()->count(2)->create(['status' => 'inactive']);

            $affectedCount = $this->repository->bulkUpdateStatus($targetTenants->pluck('id')->toArray(), 'active');

            expect($affectedCount)->toBe(3)
                ->and($targetTenants->fresh()->every(fn ($t) => $t->status === 'active'))->toBeTrue()
                ->and($otherTenants->fresh()->every(fn ($t) => $t->status === 'inactive'))->toBeTrue();
        });

        test('returns zero when no tenant IDs provided', function () {
            Tenant::factory()->count(3)->create(['status' => 'inactive']);

            $affectedCount = $this->repository->bulkUpdateStatus([], 'active');

            expect($affectedCount)->toBe(0);
        });

        test('handles non-existent tenant IDs gracefully', function () {
            $existingTenant = Tenant::factory()->create(['status' => 'inactive']);
            $nonExistentId = 'non-existent-uuid';

            $affectedCount = $this->repository->bulkUpdateStatus([$existingTenant->id, $nonExistentId], 'active');

            expect($affectedCount)->toBe(1);
        });
    });
});
