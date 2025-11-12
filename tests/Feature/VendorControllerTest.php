<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Re-enable tenant middleware for feature tests (disabled globally in TestCase)
    $this->withMiddleware();

    // Seed tenants (creates GRNMA and other tenants with proper domains)
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\TenantSeeder']);

    // Get GRNMA tenant from seeder
    $this->tenant = Tenant::where('name', 'GRNMA')->first();

    // Create authenticated user
    $this->user = User::factory()->create();

    // Use GRNMA tenant domain from seeder
    $this->tenantUrl = 'http://shop.grnmainfonet.test';
});

describe('VendorController Registration', function (): void {
    test('registers a new vendor successfully with valid data', function (): void {
        $response = $this
            ->actingAs($this->user, 'api')
            ->postJson("{$this->tenantUrl}/api/v1/vendors/register", [
                'tenant_id' => $this->tenant->id,
                'user_id' => $this->user->id,
                'business_name' => 'Test Pharmacy',
                'contact_name' => 'John Doe',
                'email' => 'john@testpharmacy.com',
                'phone' => '0244123456',
                'address' => '123 Main Street',
                'city' => 'Accra',
                'country' => 'Ghana',
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'tenant_id',
                    'user_id',
                    'business_name',
                    'contact_name',
                    'email',
                    'phone',
                    'status',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('data.business_name', 'Test Pharmacy')
            ->assertJsonPath('data.email', 'john@testpharmacy.com')
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('vendors', [
            'business_name' => 'Test Pharmacy',
            'email' => 'john@testpharmacy.com',
            'status' => 'pending',
        ]);
    });

    test('requires authentication for vendor registration', function (): void {
        $response = $this->postJson("{$this->tenantUrl}/api/v1/vendors/register", [
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'business_name' => 'Test Pharmacy',
            'contact_name' => 'John Doe',
            'email' => 'john@testpharmacy.com',
        ]);

        $response->assertUnauthorized();
    });

    test('validates required fields for vendor registration', function (): void {
        $response = $this->actingAs($this->user, 'api')
            ->postJson("{$this->tenantUrl}/api/v1/vendors/register", []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'tenant_id',
                'user_id',
                'business_name',
                'contact_name',
                'email',
            ]);
    });

    test('validates email format during registration', function (): void {
        $response = $this->actingAs($this->user, 'api')
            ->postJson("{$this->tenantUrl}/api/v1/vendors/register", [
                'tenant_id' => $this->tenant->id,
                'user_id' => $this->user->id,
                'business_name' => 'Test Pharmacy',
                'contact_name' => 'John Doe',
                'email' => 'invalid-email',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    test('prevents duplicate vendor email registration', function (): void {
        // Create existing vendor
        Vendor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'existing@testpharmacy.com',
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->postJson("{$this->tenantUrl}/api/v1/vendors/register", [
                'tenant_id' => $this->tenant->id,
                'user_id' => $this->user->id,
                'business_name' => 'New Pharmacy',
                'contact_name' => 'Jane Doe',
                'email' => 'existing@testpharmacy.com',
            ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['email']);
    });

    test('includes optional fields in vendor registration', function (): void {
        $response = $this->actingAs($this->user, 'api')
            ->postJson("{$this->tenantUrl}/api/v1/vendors/register", [
                'tenant_id' => $this->tenant->id,
                'user_id' => $this->user->id,
                'business_name' => 'Test Pharmacy',
                'contact_name' => 'John Doe',
                'email' => 'john@testpharmacy.com',
                'phone' => '0244123456',
                'address' => '123 Main Street',
                'city' => 'Accra',
                'state' => 'Greater Accra',
                'postal_code' => '00233',
                'commission_rate' => 12.5,
                'commission_type' => 'percentage',
                'description' => 'Leading pharmacy in Accra',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.phone', '0244123456')
            ->assertJsonPath('data.city', 'Accra')
            ->assertJsonPath('data.commission_rate', '12.50')
            ->assertJsonPath('data.description', 'Leading pharmacy in Accra');
    });
});

describe('VendorController Show', function (): void {
    test('retrieves vendor details by ID', function (): void {
        $vendor = Vendor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'business_name' => 'Test Pharmacy',
            'email' => 'test@pharmacy.com',
        ]);

        $response = $this
            ->getJson("{$this->tenantUrl}/api/v1/vendors/{$vendor->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'business_name',
                    'email',
                    'status',
                ],
            ])
            ->assertJsonPath('data.id', $vendor->id)
            ->assertJsonPath('data.business_name', 'Test Pharmacy');
    });

    test('returns 404 for non-existent vendor', function (): void {
        $response = $this
            ->getJson("{$this->tenantUrl}/api/v1/vendors/non-existent-id");

        $response->assertNotFound();
    });
});

describe('VendorController Update', function (): void {
    test('updates vendor profile with valid data', function (): void {
        $vendor = Vendor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'business_name' => 'Old Pharmacy',
        ]);

        $response = $this->actingAs($this->user, 'api')

            ->putJson("{$this->tenantUrl}/api/v1/vendors/{$vendor->id}", [
                'business_name' => 'Updated Pharmacy',
                'phone' => '0244999999',
                'description' => 'Updated description',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.business_name', 'Updated Pharmacy')
            ->assertJsonPath('data.phone', '0244999999');

        $this->assertDatabaseHas('vendors', [
            'id' => $vendor->id,
            'business_name' => 'Updated Pharmacy',
            'phone' => '0244999999',
        ]);
    });

    test('requires authentication for vendor update', function (): void {
        $vendor = Vendor::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this
            ->putJson("{$this->tenantUrl}/api/v1/vendors/{$vendor->id}", [
                'business_name' => 'Updated Pharmacy',
            ]);

        $response->assertUnauthorized();
    });

    test('allows partial vendor profile updates', function (): void {
        $vendor = Vendor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'business_name' => 'Original Pharmacy',
            'phone' => '0244111111',
        ]);

        $response = $this->actingAs($this->user, 'api')

            ->putJson("{$this->tenantUrl}/api/v1/vendors/{$vendor->id}", [
                'phone' => '0244222222',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.business_name', 'Original Pharmacy')
            ->assertJsonPath('data.phone', '0244222222');
    });
});

describe('VendorController Admin Actions', function (): void {
    test('approves a pending vendor', function (): void {
        $vendor = Vendor::factory()->pending()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->postJson("{$this->tenantUrl}/api/v1/vendors/{$vendor->id}/approve", []);

        $response->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('vendors', [
            'id' => $vendor->id,
            'status' => 'approved',
        ]);

        expect($vendor->fresh()->approved_at)->not->toBeNull();
    });

    test('rejects a pending vendor with reason', function (): void {
        $vendor = Vendor::factory()->pending()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->postJson("{$this->tenantUrl}/api/v1/vendors/{$vendor->id}/reject", [
                'reason' => 'Incomplete documentation',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'rejected');

        $this->assertDatabaseHas('vendors', [
            'id' => $vendor->id,
            'status' => 'rejected',
        ]);

        expect($vendor->fresh()->rejected_at)->not->toBeNull();
    });

    test('suspends an active vendor', function (): void {
        $vendor = Vendor::factory()->approved()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->postJson("{$this->tenantUrl}/api/v1/vendors/{$vendor->id}/suspend", [
                'reason' => 'Policy violation',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'suspended');

        $this->assertDatabaseHas('vendors', [
            'id' => $vendor->id,
            'status' => 'suspended',
        ]);
    });
});

describe('VendorController Index', function (): void {
    test('lists all vendors for tenant', function (): void {
        // Manually initialize tenancy for this test
        tenancy()->initialize($this->tenant);

        Vendor::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson("{$this->tenantUrl}/api/v1/vendors");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'business_name', 'email', 'status'],
                ],
            ])
            ->assertJsonCount(3, 'data');
    });

    test('filters vendors by status', function (): void {
        // Manually initialize tenancy for this test
        tenancy()->initialize($this->tenant);

        Vendor::factory()->count(2)->pending()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        Vendor::factory()->count(3)->approved()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson("{$this->tenantUrl}/api/v1/vendors?status=approved");

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    });

    test('supports pagination for vendor listing', function (): void {
        // Manually initialize tenancy for this test
        tenancy()->initialize($this->tenant);

        Vendor::factory()->count(20)->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson("{$this->tenantUrl}/api/v1/vendors?per_page=10");

        $response->assertOk()
            ->assertJsonCount(10, 'data');
    });
});
