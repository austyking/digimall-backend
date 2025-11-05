<?php

declare(strict_types=1);

use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Services\TenantStatisticsService;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->mockRepository = mock(TenantRepositoryInterface::class);
    $this->service = new TenantStatisticsService($this->mockRepository);
});

describe('TenantStatisticsService Unit Tests', function () {
    describe('getTotalCount()', function () {
        test('returns count from repository', function () {
            $this->mockRepository
                ->expects('count')
                ->andReturn(100);

            $result = $this->service->getTotalCount();

            expect($result)->toBe(100);
        });
    });

    describe('getActiveCount()', function () {
        test('returns active count from repository', function () {
            $this->mockRepository
                ->expects('countActive')
                ->andReturn(75);

            $result = $this->service->getActiveCount();

            expect($result)->toBe(75);
        });
    });

    describe('getInactiveCount()', function () {
        test('returns inactive count from repository', function () {
            $this->mockRepository
                ->expects('countInactive')
                ->andReturn(25);

            $result = $this->service->getInactiveCount();

            expect($result)->toBe(25);
        });
    });

    describe('getActivationRate()', function () {
        test('calculates percentage correctly', function () {
            $this->mockRepository
                ->expects('count')
                ->andReturn(100);
            $this->mockRepository
                ->expects('countActive')
                ->andReturn(75);

            $result = $this->service->getActivationRate();

            expect($result)->toBe(75.0);
        });

        test('returns zero when no tenants', function () {
            $this->mockRepository
                ->expects('count')
                ->andReturn(0);

            $result = $this->service->getActivationRate();

            expect($result)->toBe(0.0);
        });

        test('rounds to two decimal places', function () {
            $this->mockRepository
                ->expects('count')
                ->andReturn(3);
            $this->mockRepository
                ->expects('countActive')
                ->andReturn(2);

            $result = $this->service->getActivationRate();

            expect($result)->toBe(66.67);
        });
    });

    // NOTE: getSummaryStatistics(), getGrowthMetrics(), and getTenantDistributionByDate()
    // are tested in tests/Integration/TenantStatisticsServiceIntegrationTest.php
    // because they use DB::table() directly which requires database access
});
