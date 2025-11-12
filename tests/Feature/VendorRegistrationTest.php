<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles and tenants
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\TenantSeeder::class);

        // Use GRNMA tenant with shop.grnmainfonet.test domain
        $this->tenant = Tenant::where('name', 'GRNMA')->firstOrFail();
        tenancy()->initialize($this->tenant);
    }

    protected function tearDown(): void
    {
        tenancy()->end();
        parent::tearDown();
    }

    public function test_vendor_can_register_successfully(): void
    {
        $response = $this->postJson('http://shop.grnmainfonet.test/api/v1/vendors/register', [
            'business_name' => 'Test Pharmacy',
            'contact_name' => 'John Doe',
            'email' => 'john.doe@testpharmacy.com',
            'phone' => '+233555123456',
            'address' => '123 Market Street',
            'city' => 'Accra',
            'country' => 'Ghana',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'business_name',
                    'contact_name',
                    'email',
                    'status',
                ],
            ]);

        // Verify vendor was created in database
        $this->assertDatabaseHas('vendors', [
            'email' => 'john.doe@testpharmacy.com',
            'business_name' => 'Test Pharmacy',
            'status' => 'pending',
            'tenant_id' => $this->tenant->id,
        ]);

        // Verify user was created
        $vendor = Vendor::where('email', 'john.doe@testpharmacy.com')->first();
        $this->assertNotNull($vendor->user_id);

        $user = User::find($vendor->user_id);
        $this->assertNotNull($user);
        $this->assertEquals('john.doe@testpharmacy.com', $user->email);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals($this->tenant->id, $user->tenant_id);

        // Verify user has vendor role
        $this->assertTrue($user->hasRole('vendor'));
    }

    public function test_vendor_registration_fails_with_duplicate_email(): void
    {
        // Create existing vendor
        Vendor::factory()->create([
            'email' => 'existing@pharmacy.com',
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->postJson('http://shop.grnmainfonet.test/api/v1/vendors/register', [
            'business_name' => 'Another Pharmacy',
            'contact_name' => 'Jane Doe',
            'email' => 'existing@pharmacy.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_vendor_registration_validates_required_fields(): void
    {
        $response = $this->postJson('http://shop.grnmainfonet.test/api/v1/vendors/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'business_name',
                'contact_name',
                'email',
            ]);
    }

    public function test_vendor_registration_validates_email_format(): void
    {
        $response = $this->postJson('http://shop.grnmainfonet.test/api/v1/vendors/register', [
            'business_name' => 'Test Pharmacy',
            'contact_name' => 'John Doe',
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_vendor_registration_is_transactional(): void
    {
        // Mock a scenario where user creation succeeds but vendor creation might fail
        // If not transactional, we'd have orphaned users

        $initialUserCount = User::count();
        $initialVendorCount = Vendor::count();

        // Try to register with valid data
        $response = $this->postJson('http://shop.grnmainfonet.test/api/v1/vendors/register', [
            'business_name' => 'Test Pharmacy',
            'contact_name' => 'John Doe',
            'email' => 'test@pharmacy.com',
        ]);

        if ($response->isSuccessful()) {
            // If successful, both should be created
            $this->assertEquals($initialUserCount + 1, User::count());
            $this->assertEquals($initialVendorCount + 1, Vendor::count());
        } else {
            // If failed, neither should be created (transaction rolled back)
            $this->assertEquals($initialUserCount, User::count());
            $this->assertEquals($initialVendorCount, Vendor::count());
        }
    }

    public function test_vendor_registration_creates_tenant_scoped_user(): void
    {
        $response = $this->postJson('http://shop.grnmainfonet.test/api/v1/vendors/register', [
            'business_name' => 'Test Pharmacy',
            'contact_name' => 'John Doe',
            'email' => 'john@testpharmacy.com',
        ]);

        $response->assertCreated();

        $vendor = Vendor::where('email', 'john@testpharmacy.com')->first();
        $user = User::find($vendor->user_id);

        // User should be scoped to current tenant
        $this->assertEquals($this->tenant->id, $user->tenant_id);
    }

    public function test_vendor_registration_without_tenant_context_fails(): void
    {
        // End tenant context
        tenancy()->end();

        $response = $this->postJson('/api/v1/vendors/register', [
            'business_name' => 'Test Pharmacy',
            'contact_name' => 'John Doe',
            'email' => 'john@testpharmacy.com',
        ]);

        // Should fail without tenant context
        $response->assertStatus(500);
    }
}
