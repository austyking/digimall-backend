<?php

declare(strict_types=1);

use App\DTOs\UpdateVendorDTO;
use App\Models\Vendor;
use App\Repositories\Contracts\VendorRepositoryInterface;
use App\Services\VendorService;
use Illuminate\Database\Eloquent\Collection;
use Mockery;

// Note: registerVendor() tests moved to Feature tests due to complexity (DB transactions, UserService, tenant context)
// These unit tests focus on simple delegation methods that don't require complex mocking

describe('VendorService', function () {
    beforeEach(function () {
        $this->mockRepository = Mockery::mock(VendorRepositoryInterface::class);
        // UserService is final readonly, so we test methods that don't need it
        $this->service = new VendorService($this->mockRepository, app(\App\Services\UserService::class));
    });

    afterEach(function () {
        Mockery::close();
    });

    test('updates vendor successfully', function () {
        $dto = new UpdateVendorDTO(
            businessName: 'Updated Pharmacy',
            phone: '0244999999'
        );

        $updatedVendor = new Vendor([
            'id' => 'vendor-123',
            'business_name' => 'Updated Pharmacy',
            'phone' => '0244999999',
        ]);

        $this->mockRepository
            ->shouldReceive('update')
            ->with('vendor-123', ['business_name' => 'Updated Pharmacy', 'phone' => '0244999999'])
            ->once()
            ->andReturn($updatedVendor);

        $result = $this->service->updateVendor('vendor-123', $dto);

        expect($result)->toBeInstanceOf(Vendor::class)
            ->and($result->business_name)->toBe('Updated Pharmacy');
    });

    test('throws exception when updating vendor with no data', function () {
        $dto = new UpdateVendorDTO;

        expect(fn () => $this->service->updateVendor('vendor-123', $dto))
            ->toThrow(\InvalidArgumentException::class, 'No update data provided.');
    });

    test('finds vendor by ID', function () {
        $vendor = new Vendor([
            'id' => 'vendor-123',
            'business_name' => 'Test Vendor',
        ]);

        $this->mockRepository
            ->shouldReceive('find')
            ->with('vendor-123')
            ->once()
            ->andReturn($vendor);

        $result = $this->service->findById('vendor-123');

        expect($result)->toBe($vendor);
    });

    test('finds vendor by email', function () {
        $vendor = new Vendor([
            'id' => 'vendor-123',
            'email' => 'test@example.com',
            'business_name' => 'Test Vendor',
        ]);

        $this->mockRepository
            ->shouldReceive('findByEmail')
            ->with('test@example.com')
            ->once()
            ->andReturn($vendor);

        $result = $this->service->findByEmail('test@example.com');

        expect($result)->toBe($vendor);
    });

    test('gets all vendors for tenant', function () {
        $vendors = new Collection([
            new Vendor(['id' => 'vendor-1', 'business_name' => 'Vendor 1']),
            new Vendor(['id' => 'vendor-2', 'business_name' => 'Vendor 2']),
        ]);

        $this->mockRepository
            ->shouldReceive('getByTenant')
            ->with('tenant-123', 10)
            ->once()
            ->andReturn($vendors);

        $result = $this->service->getAllVendors('tenant-123', 10);

        expect($result)->toBe($vendors);
    });

    test('gets vendors by status', function () {
        $vendors = new Collection([
            new Vendor(['id' => 'vendor-1', 'status' => 'active']),
        ]);

        $this->mockRepository
            ->shouldReceive('getByStatus')
            ->with('active', 5)
            ->once()
            ->andReturn($vendors);

        $result = $this->service->getByStatus('active', 5);

        expect($result)->toBe($vendors);
    });

    test('gets approved vendors', function () {
        $vendors = new Collection([
            new Vendor(['id' => 'vendor-1', 'status' => 'approved']),
        ]);

        $this->mockRepository
            ->shouldReceive('getApproved')
            ->with(10)
            ->once()
            ->andReturn($vendors);

        $result = $this->service->getApprovedVendors(10);

        expect($result)->toBe($vendors);
    });

    test('gets pending vendors', function () {
        $vendors = new Collection([
            new Vendor(['id' => 'vendor-1', 'status' => 'pending']),
        ]);

        $this->mockRepository
            ->shouldReceive('getPending')
            ->with(5)
            ->once()
            ->andReturn($vendors);

        $result = $this->service->getPendingVendors(5);

        expect($result)->toBe($vendors);
    });

    test('searches vendors', function () {
        $vendors = new Collection([
            new Vendor(['id' => 'vendor-1', 'business_name' => 'Test Vendor']),
        ]);

        $this->mockRepository
            ->shouldReceive('search')
            ->with('Test', 20)
            ->once()
            ->andReturn($vendors);

        $result = $this->service->searchVendors('Test', 20);

        expect($result)->toBe($vendors);
    });

    test('approves vendor', function () {
        $vendor = new Vendor([
            'id' => 'vendor-123',
            'status' => 'approved',
        ]);

        $this->mockRepository
            ->shouldReceive('approve')
            ->with('vendor-123')
            ->once()
            ->andReturn($vendor);

        $result = $this->service->approveVendor('vendor-123');

        expect($result)->toBe($vendor);
    });

    test('rejects vendor', function () {
        $vendor = new Vendor([
            'id' => 'vendor-123',
            'status' => 'rejected',
        ]);

        $this->mockRepository
            ->shouldReceive('reject')
            ->with('vendor-123', 'Not qualified')
            ->once()
            ->andReturn($vendor);

        $result = $this->service->rejectVendor('vendor-123', 'Not qualified');

        expect($result)->toBe($vendor);
    });

    test('suspends vendor', function () {
        // Repository should return suspended vendor after suspension
        $vendor = new Vendor([
            'id' => 'vendor-123',
            'status' => 'suspended',
        ]);

        $this->mockRepository
            ->shouldReceive('suspend')
            ->with('vendor-123', 'Violation')
            ->once()
            ->andReturn($vendor);

        $result = $this->service->suspendVendor('vendor-123', 'Violation');

        expect($result)->toBeInstanceOf(Vendor::class)
            ->and($result->id)->toBe('vendor-123')
            ->and($result->status)->toBe('suspended');
    });

    test('gets vendor statistics', function () {
        $stats = [
            'products_count' => 5,
            'orders_count' => 10,
            'total_sales' => 1500.00,
        ];

        $this->mockRepository
            ->shouldReceive('getStatistics')
            ->with('vendor-123')
            ->once()
            ->andReturn($stats);

        $result = $this->service->getVendorStatistics('vendor-123');

        expect($result)->toBe($stats);
    });

    test('gets top vendors by sales', function () {
        $vendors = new Collection([
            new Vendor(['id' => 'vendor-1', 'business_name' => 'Top Vendor']),
        ]);

        $this->mockRepository
            ->shouldReceive('getTopBySales')
            ->with(5)
            ->once()
            ->andReturn($vendors);

        $result = $this->service->getTopVendorsBySales(5);

        expect($result)->toBe($vendors);
    });

    test('checks if vendor is approved', function () {
        $approvedVendor = new Vendor([
            'id' => 'approved-vendor',
            'status' => 'approved',
        ]);
        $pendingVendor = new Vendor([
            'id' => 'pending-vendor',
            'status' => 'pending',
        ]);

        $this->mockRepository
            ->shouldReceive('find')
            ->with('approved-vendor')
            ->once()
            ->andReturn($approvedVendor);

        $this->mockRepository
            ->shouldReceive('find')
            ->with('pending-vendor')
            ->once()
            ->andReturn($pendingVendor);

        expect($this->service->isApproved('approved-vendor'))->toBeTrue()
            ->and($this->service->isApproved('pending-vendor'))->toBeFalse();
    });

    test('checks if vendor can sell products', function () {
        $approvedVendor = new Vendor([
            'id' => 'approved-vendor',
            'status' => 'approved',
        ]);

        $this->mockRepository
            ->shouldReceive('find')
            ->with('approved-vendor')
            ->once()
            ->andReturn($approvedVendor);

        expect($this->service->canSellProducts('approved-vendor'))->toBeTrue();
    });
});
