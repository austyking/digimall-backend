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
            Tenant::factory()->count(3)->create(['active' => true]);
            Tenant::factory()->count(2)->create(['active' => false]);

            $result = $this->repository->allInactive();

            expect($result)->toHaveCount(2)
                ->and($result->every(fn ($tenant) => $tenant->active === false))->toBeTrue();
        });

        test('returns empty collection when no inactive tenants', function () {
            Tenant::factory()->count(3)->create(['active' => true]);

            $result = $this->repository->allInactive();

            expect($result)->toBeEmpty();
        });
    });

    describe('getFiltered()', function () {
        test('filters tenants by active status', function () {
            Tenant::factory()->count(5)->create(['active' => true]);
            Tenant::factory()->count(3)->create(['active' => false]);

            $result = $this->repository->getFiltered(active: true);

            expect($result->total())->toBe(5);

            foreach ($result->items() as $item) {
                expect($item->active)->toBeTrue();
            }
        });

        test('filters tenants by inactive status', function () {
            Tenant::factory()->count(5)->create(['active' => true]);
            Tenant::factory()->count(3)->create(['active' => false]);

            $result = $this->repository->getFiltered(active: false);

            expect($result->total())->toBe(3);

            foreach ($result->items() as $item) {
                expect($item->active)->toBeFalse();
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

        test('combines active filter and search', function () {
            Tenant::factory()->create(['name' => 'GRNMA', 'active' => true]);
            Tenant::factory()->create(['name' => 'GMA', 'active' => true]);
            Tenant::factory()->create(['name' => 'GMDA', 'active' => false]);

            $result = $this->repository->getFiltered(active: true, search: 'GM');

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
            Tenant::factory()->count(5)->create(['active' => true]);
            Tenant::factory()->count(3)->create(['active' => false]);

            $result = $this->repository->getFiltered();

            expect($result->total())->toBe(8);
        });
    });

    describe('count methods', function () {
        test('count returns total tenant count', function () {
            Tenant::factory()->count(5)->create(['active' => true]);
            Tenant::factory()->count(3)->create(['active' => false]);

            $result = $this->repository->count();

            expect($result)->toBe(8);
        });

        test('countActive returns active tenant count', function () {
            Tenant::factory()->count(5)->create(['active' => true]);
            Tenant::factory()->count(3)->create(['active' => false]);

            $result = $this->repository->countActive();

            expect($result)->toBe(5);
        });

        test('countInactive returns inactive tenant count', function () {
            Tenant::factory()->count(5)->create(['active' => true]);
            Tenant::factory()->count(3)->create(['active' => false]);

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
            $tenant = Tenant::factory()->create(['active' => false]);

            $result = $this->repository->updateStatus($tenant, true);

            expect($result->active)->toBeTrue()
                ->and($result->id)->toBe($tenant->id);
        });

        test('updates tenant status to inactive', function () {
            $tenant = Tenant::factory()->create(['active' => true]);

            $result = $this->repository->updateStatus($tenant, false);

            expect($result->active)->toBeFalse();
        });

        test('stores status change reason in settings', function () {
            $tenant = Tenant::factory()->create(['active' => false]);

            $result = $this->repository->updateStatus($tenant, true, 'Approved by admin');

            expect($result->settings)->toHaveKey('status_history')
                ->and($result->settings['status_history'])->toHaveCount(1)
                ->and($result->settings['status_history'][0]['status'])->toBe('activated')
                ->and($result->settings['status_history'][0]['reason'])->toBe('Approved by admin');
        });

        test('appends to existing status history', function () {
            $tenant = Tenant::factory()->create([
                'active' => true,
                'settings' => [
                    'status_history' => [
                        ['status' => 'activated', 'reason' => 'Initial setup', 'timestamp' => now()->subDay()->toISOString()],
                    ],
                ],
            ]);

            $result = $this->repository->updateStatus($tenant, false, 'Policy violation');

            expect($result->settings['status_history'])->toHaveCount(2)
                ->and($result->settings['status_history'][1]['status'])->toBe('deactivated')
                ->and($result->settings['status_history'][1]['reason'])->toBe('Policy violation');
        });

        test('does not modify settings when no reason provided', function () {
            $tenant = Tenant::factory()->create(['active' => false, 'settings' => ['foo' => 'bar']]);

            $result = $this->repository->updateStatus($tenant, true);

            expect($result->settings)->toBe(['foo' => 'bar']);
        });
    });

    describe('bulkUpdateStatus()', function () {
        test('updates status for multiple tenants', function () {
            $tenants = Tenant::factory()->count(5)->create(['active' => false]);
            $tenantIds = $tenants->pluck('id')->toArray();

            $affectedCount = $this->repository->bulkUpdateStatus($tenantIds, true);

            expect($affectedCount)->toBe(5)
                ->and(Tenant::whereIn('id', $tenantIds)->where('active', true)->count())->toBe(5);
        });

        test('only updates specified tenants', function () {
            $targetTenants = Tenant::factory()->count(3)->create(['active' => false]);
            $otherTenants = Tenant::factory()->count(2)->create(['active' => false]);

            $affectedCount = $this->repository->bulkUpdateStatus($targetTenants->pluck('id')->toArray(), true);

            expect($affectedCount)->toBe(3)
                ->and($targetTenants->fresh()->every(fn ($t) => $t->active === true))->toBeTrue()
                ->and($otherTenants->fresh()->every(fn ($t) => $t->active === false))->toBeTrue();
        });

        test('returns zero when no tenant IDs provided', function () {
            Tenant::factory()->count(3)->create(['active' => false]);

            $affectedCount = $this->repository->bulkUpdateStatus([], true);

            expect($affectedCount)->toBe(0);
        });

        test('handles non-existent tenant IDs gracefully', function () {
            $existingTenant = Tenant::factory()->create(['active' => false]);
            $nonExistentId = 'non-existent-uuid';

            $affectedCount = $this->repository->bulkUpdateStatus([$existingTenant->id, $nonExistentId], true);

            expect($affectedCount)->toBe(1);
        });
    });
});
