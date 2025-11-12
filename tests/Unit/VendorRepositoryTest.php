<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\User;
use App\Models\Vendor;
use App\Repositories\VendorRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('VendorRepository', function () {
    beforeEach(function () {
        $this->repository = new VendorRepository;
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create();
    });

    test('finds vendor by ID', function () {
        $vendor = Vendor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
        ]);

        $found = $this->repository->find($vendor->id);

        expect($found)->not()->toBeNull()
            ->and($found->id)->toBe($vendor->id);
    });

    test('finds vendor by user ID', function () {
        $vendor = Vendor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
        ]);

        $found = $this->repository->findByUserId($this->user->id);

        expect($found)->not()->toBeNull()
            ->and($found->user_id)->toBe($this->user->id);
    });

    test('finds vendor by email', function () {
        $vendor = Vendor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'email' => 'vendor@example.com',
        ]);

        $found = $this->repository->findByEmail('vendor@example.com');

        expect($found)->not()->toBeNull()
            ->and($found->email)->toBe('vendor@example.com');
    });

    test('returns all vendors', function () {
        Vendor::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $vendors = $this->repository->all();

        expect($vendors)->toHaveCount(3);
    });

    test('paginates vendors', function () {
        Vendor::factory()->count(10)->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $paginated = $this->repository->paginate(5);

        expect($paginated)->toHaveCount(5)
            ->and($paginated->total())->toBe(10);
    });

    test('gets active vendors only', function () {
        Vendor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'active',
        ]);
        Vendor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'pending',
        ]);

        $active = $this->repository->getActive();

        expect($active)->toHaveCount(1)
            ->and($active->first()->status)->toBe('active');
    });

    test('gets pending vendors', function () {
        Vendor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'pending',
        ]);
        Vendor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'active',
        ]);

        $pending = $this->repository->getPending();

        expect($pending)->toHaveCount(1)
            ->and($pending->first()->status)->toBe('pending');
    });

    test('gets approved vendors', function () {
        Vendor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'approved',
            'approved_at' => now(),
        ]);
        Vendor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'pending',
        ]);

        $approved = $this->repository->getApproved();

        expect($approved)->toHaveCount(1)
            ->and($approved->first()->status)->toBe('approved');
    });

    test('gets vendors by tenant', function () {
        $otherTenant = Tenant::factory()->create();

        Vendor::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
        ]);
        Vendor::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        $tenantVendors = $this->repository->getByTenant($this->tenant->id);

        expect($tenantVendors)->toHaveCount(2);
    });

    test('gets vendors by status', function () {
        Vendor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'active',
        ]);
        Vendor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'suspended',
        ]);

        $activeVendors = $this->repository->getByStatus('active');

        expect($activeVendors)->toHaveCount(1)
            ->and($activeVendors->first()->status)->toBe('active');
    });

    test('searches vendors by query', function () {
        Vendor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'business_name' => 'Test Company',
        ]);
        Vendor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'business_name' => 'Other Company',
        ]);

        $results = $this->repository->search('Test');

        expect($results)->toHaveCount(1)
            ->and($results->first()->business_name)->toBe('Test Company');
    });

    test('creates new vendor', function () {
        $data = [
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'business_name' => 'New Vendor',
            'contact_name' => 'John Doe',
            'email' => 'new@vendor.com',
            'status' => 'pending',
        ];

        $vendor = $this->repository->create($data);

        expect($vendor)->toBeInstanceOf(Vendor::class)
            ->and($vendor->business_name)->toBe('New Vendor')
            ->and($vendor->contact_name)->toBe('John Doe')
            ->and($vendor->email)->toBe('new@vendor.com');
    });

    test('updates vendor', function () {
        $vendor = Vendor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'business_name' => 'Old Name',
        ]);

        $updated = $this->repository->update($vendor->id, [
            'business_name' => 'New Name',
        ]);

        expect($updated->business_name)->toBe('New Name');
    });

    test('approves vendor', function () {
        $vendor = Vendor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'pending',
        ]);

        $approved = $this->repository->approve($vendor->id);

        expect($approved->status)->toBe('approved')
            ->and($approved->approved_at)->not()->toBeNull();
    });

    test('rejects vendor', function () {
        $vendor = Vendor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'pending',
        ]);

        $rejected = $this->repository->reject($vendor->id, 'Not qualified');

        expect($rejected->status)->toBe('rejected')
            ->and($rejected->rejected_at)->not()->toBeNull()
            ->and($rejected->rejection_reason)->toBe('Not qualified');
    });

    test('suspends vendor', function () {
        $vendor = Vendor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'active',
        ]);

        $suspended = $this->repository->suspend($vendor->id, 'Violation');

        expect($suspended)->toBeTrue();

        $vendor->refresh();
        expect($vendor->status)->toBe('suspended')
            ->and($vendor->suspended_at)->not()->toBeNull();
    });

    test('checks if vendor exists', function () {
        $vendor = Vendor::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        expect($this->repository->exists($vendor->id))->toBeTrue()
            ->and($this->repository->exists('non-existent'))->toBeFalse();
    });

    test('checks if email exists', function () {
        Vendor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'existing@email.com',
        ]);

        expect($this->repository->emailExists('existing@email.com'))->toBeTrue()
            ->and($this->repository->emailExists('new@email.com'))->toBeFalse();
    });
});
