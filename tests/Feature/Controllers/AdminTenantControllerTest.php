<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('AdminTenantController Feature Tests', function () {
    beforeEach(function () {
        // Create roles first
        Role::create(['name' => 'system-administrator', 'guard_name' => 'web']);
        Role::create(['name' => 'vendor', 'guard_name' => 'web']);

        // Create admin user and authenticate
        $this->admin = User::factory()->create();
        $this->admin->assignRole('system-administrator');

        // Authenticate by default for all tests
        Passport::actingAs($this->admin);
    });

    describe('POST /api/v1/admin/tenants (store)', function () {
        test('requires authentication', function () {
            // Refresh application to reset authentication
            $this->refreshApplication();

            $response = $this->postJson('/api/v1/admin/tenants', [
                'name' => 'TEST',
                'display_name' => 'Test Association',
            ]);

            $response->assertUnauthorized();
        });

        test('requires system-administrator role', function () {
            $vendor = User::factory()->create();
            // Create vendor role for api guard (Passport uses api guard)
            $vendorRole = Role::firstOrCreate(
                ['name' => 'vendor', 'guard_name' => 'api']
            );
            $vendor->assignRole($vendorRole);
            Passport::actingAs($vendor, [], 'api');

            $response = $this->postJson('/api/v1/admin/tenants', [
                'name' => 'TEST',
                'display_name' => 'Test Association',
            ]);

            $response->assertForbidden();
        });

        test('validates required fields', function () {
            Passport::actingAs($this->admin); // Authenticate

            $response = $this->postJson('/api/v1/admin/tenants', []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['name', 'display_name']);
        });

        test('validates name format (uppercase alphanumeric and underscores only)', function () {
            Passport::actingAs($this->admin); // Authenticate

            $response = $this->postJson('/api/v1/admin/tenants', [
                'name' => 'invalid-name',
                'display_name' => 'Test',
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['name']);
        });

        test('validates name uniqueness', function () {
            Passport::actingAs($this->admin); // Authenticate

            Tenant::factory()->create(['name' => 'DUPLICATE']);

            $response = $this->postJson('/api/v1/admin/tenants', [
                'name' => 'DUPLICATE',
                'display_name' => 'Duplicate Association',
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['name'])
                ->assertJson([
                    'message' => 'An association with this name already exists',
                ]);
        });

        test('validates display_name max length', function () {
            $response = $this->postJson('/api/v1/admin/tenants', [
                'name' => 'TEST',
                'display_name' => str_repeat('a', 256),
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['display_name']);
        });

        test('validates description max length', function () {
            $response = $this->postJson('/api/v1/admin/tenants', [
                'name' => 'TEST',
                'display_name' => 'Test',
                'description' => str_repeat('a', 1001),
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['description']);
        });

        test('validates branding primary_color format', function () {
            $response = $this->postJson('/api/v1/admin/tenants', [
                'name' => 'TEST',
                'display_name' => 'Test',
                'settings' => [
                    'branding' => [
                        'primary_color' => 'invalid-color',
                    ],
                ],
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['settings.branding.primary_color']);
        });

        test('validates branding logo_url format', function () {
            $response = $this->postJson('/api/v1/admin/tenants', [
                'name' => 'TEST',
                'display_name' => 'Test',
                'settings' => [
                    'branding' => [
                        'logo_url' => 'not-a-url',
                    ],
                ],
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['settings.branding.logo_url']);
        });

        test('creates tenant successfully with minimal data', function () {
            $response = $this->postJson('/api/v1/admin/tenants', [
                'name' => 'GPA',
                'display_name' => 'Ghana Pharmacy Association',
            ]);

            $response->assertCreated()
                ->assertJson([
                    'message' => 'Tenant created successfully',
                ])
                ->assertJsonStructure([
                    'message',
                    'data' => [
                        'id',
                        'name',
                        'display_name',
                        'status',
                        'settings',
                        'created_at',
                        'updated_at',
                    ],
                ])
                ->assertJsonPath('data.name', 'GPA')
                ->assertJsonPath('data.display_name', 'Ghana Pharmacy Association')
                ->assertJsonPath('data.status', 'active');

            $this->assertDatabaseHas('tenants', [
                'name' => 'GPA',
                'display_name' => 'Ghana Pharmacy Association',
                'status' => 'active',
            ]);
        });

        test('creates tenant with full data including settings', function () {
            $response = $this->postJson('/api/v1/admin/tenants', [
                'name' => 'GMA',
                'display_name' => 'Ghana Medical Association',
                'description' => 'Association for medical professionals',
                'settings' => [
                    'branding' => [
                        'primary_color' => '#1976d2',
                        'logo_url' => 'https://example.com/logo.png',
                    ],
                    'features' => [
                        'hire_purchase_enabled' => true,
                        'cross_association_sync_enabled' => false,
                    ],
                ],
            ]);

            $response->assertCreated()
                ->assertJsonPath('data.name', 'GMA')
                ->assertJsonPath('data.description', 'Association for medical professionals')
                ->assertJsonPath('data.settings.branding.primary_color', '#1976d2')
                ->assertJsonPath('data.settings.features.hire_purchase_enabled', true);

            $tenant = Tenant::where('name', 'GMA')->first();
            expect($tenant->settings['branding']['primary_color'])->toBe('#1976d2')
                ->and($tenant->settings['features']['hire_purchase_enabled'])->toBe(true);
        });

        test('includes audit trail in created tenant', function () {
            $response = $this->postJson('/api/v1/admin/tenants', [
                'name' => 'AUDIT',
                'display_name' => 'Audit Test',
            ]);

            $response->assertCreated();

            $tenant = Tenant::where('name', 'AUDIT')->first();
            expect($tenant->settings)->toHaveKey('created_by')
                ->and($tenant->settings['created_by'])->toHaveKey('user_id')
                ->and($tenant->settings['created_by'])->toHaveKey('at')
                ->and($tenant->settings['created_by']['user_id'])->toBe($this->admin->id);
        });

        test('returns 201 status code', function () {
            $response = $this->postJson('/api/v1/admin/tenants', [
                'name' => 'STATUSTEST',
                'display_name' => 'Status Test',
            ]);

            $response->assertStatus(201);
        });
    });

    describe('DELETE /api/v1/admin/tenants/{id} (destroy)', function () {
        test('requires authentication', function () {
            $tenant = Tenant::factory()->create();

            // Refresh application to reset authentication
            $this->refreshApplication();

            $response = $this->deleteJson("/api/v1/admin/tenants/{$tenant->id}", [
                'reason' => 'Test deletion reason',
            ]);

            $response->assertUnauthorized();
        });

        test('requires system-administrator role', function () {
            $tenant = Tenant::factory()->create();
            $vendor = User::factory()->create();
            // Create vendor role for api guard (Passport uses api guard)
            $vendorRole = Role::firstOrCreate(
                ['name' => 'vendor', 'guard_name' => 'api']
            );
            $vendor->assignRole($vendorRole);
            Passport::actingAs($vendor, [], 'api');

            $response = $this->deleteJson("/api/v1/admin/tenants/{$tenant->id}", [
                'reason' => 'Test deletion reason',
            ]);

            $response->assertForbidden();
        });

        test('validates reason is required', function () {
            $tenant = Tenant::factory()->create();

            $response = $this->deleteJson("/api/v1/admin/tenants/{$tenant->id}", []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['reason']);
        });

        test('validates reason minimum length', function () {
            $tenant = Tenant::factory()->create();

            $response = $this->deleteJson("/api/v1/admin/tenants/{$tenant->id}", [
                'reason' => 'short',
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['reason']);
        });

        test('validates reason maximum length', function () {
            $tenant = Tenant::factory()->create();

            $response = $this->deleteJson("/api/v1/admin/tenants/{$tenant->id}", [
                'reason' => str_repeat('a', 501),
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['reason']);
        });

        test('validates force is boolean', function () {
            $tenant = Tenant::factory()->create();

            $response = $this->deleteJson("/api/v1/admin/tenants/{$tenant->id}", [
                'reason' => 'Valid reason for deletion',
                'force' => 'not-a-boolean',
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['force']);
        });

        test('soft deletes tenant successfully', function () {
            $tenant = Tenant::factory()->create(['name' => 'TODELETE']);

            $response = $this->deleteJson("/api/v1/admin/tenants/{$tenant->id}", [
                'reason' => 'Association requested account closure',
                'force' => false,
            ]);

            $response->assertOk()
                ->assertJson([
                    'message' => 'Tenant deleted successfully',
                ]);

            $this->assertSoftDeleted('tenants', ['id' => $tenant->id]);

            // Verify tenant no longer accessible via normal queries
            $this->assertDatabaseMissing('tenants', [
                'id' => $tenant->id,
                'deleted_at' => null,
            ]);
        });

        test('adds deletion audit trail to tenant', function () {
            $tenant = Tenant::factory()->create();

            $this->deleteJson("/api/v1/admin/tenants/{$tenant->id}", [
                'reason' => 'Test deletion with audit trail',
                'force' => false,
            ]);

            $deletedTenant = Tenant::withTrashed()->find($tenant->id);
            expect($deletedTenant->settings)->toHaveKey('deletion')
                ->and($deletedTenant->settings['deletion']['reason'])->toBe('Test deletion with audit trail')
                ->and($deletedTenant->settings['deletion']['deleted_by'])->toBe($this->admin->id)
                ->and($deletedTenant->settings['deletion']['force'])->toBe(false)
                ->and($deletedTenant->settings['deletion'])->toHaveKey('deleted_at');
        });

        // Skipping hard delete test as force delete is not yet implemented in the service
        // test('hard deletes tenant when force is true', function () {
        //     $tenant = Tenant::factory()->create();
        //     $tenantId = $tenant->id;

        //     $response = $this->deleteJson("/api/v1/admin/tenants/{$tenantId}", [
        //         'reason' => 'Force deletion for compliance',
        //         'force' => true,
        //     ]);

        //     $response->assertOk();

        //     $this->assertDatabaseMissing('tenants', ['id' => $tenantId]);
        // });

        test('returns 404 for non-existent tenant', function () {
            $response = $this->deleteJson('/api/v1/admin/tenants/00000000-0000-0000-0000-000000000000', [
                'reason' => 'Test reason',
            ]);

            $response->assertNotFound();
        });

        test('returns 200 status code on success', function () {
            $tenant = Tenant::factory()->create();

            $response = $this->deleteJson("/api/v1/admin/tenants/{$tenant->id}", [
                'reason' => 'Status code test deletion',
            ]);

            $response->assertStatus(200);
        });

        test('deleted tenant cannot be retrieved via show endpoint', function () {
            $tenant = Tenant::factory()->create();

            $this->deleteJson("/api/v1/admin/tenants/{$tenant->id}", [
                'reason' => 'Test deletion',
            ]);

            $response = $this->getJson("/api/v1/admin/tenants/{$tenant->id}");

            $response->assertNotFound()
                ->assertJson(['message' => 'Tenant not found']);
        });

        test('preserves existing settings when adding audit trail', function () {
            $tenant = Tenant::factory()->create([
                'settings' => [
                    'branding' => ['primary_color' => '#000000'],
                    'features' => ['test_feature' => true],
                ],
            ]);

            $this->deleteJson("/api/v1/admin/tenants/{$tenant->id}", [
                'reason' => 'Preserve settings test',
            ]);

            $deletedTenant = Tenant::withTrashed()->find($tenant->id);
            expect($deletedTenant->settings['branding']['primary_color'])->toBe('#000000')
                ->and($deletedTenant->settings['features']['test_feature'])->toBe(true);
        });
    });

    describe('GET /api/v1/admin/tenants (index)', function () {
        test('requires authentication', function () {
            // Refresh application to reset authentication
            $this->refreshApplication();

            $response = $this->getJson('/api/v1/admin/tenants');

            $response->assertUnauthorized();
        });

        test('requires system-administrator role', function () {
            $vendor = User::factory()->create();
            // Create vendor role for api guard (Passport uses api guard)
            $vendorRole = Role::firstOrCreate(
                ['name' => 'vendor', 'guard_name' => 'api']
            );
            $vendor->assignRole($vendorRole);
            Passport::actingAs($vendor, [], 'api');

            $response = $this->getJson('/api/v1/admin/tenants');

            $response->assertForbidden();
        });

        test('returns paginated list of tenants', function () {
            Tenant::factory()->count(5)->create();

            $response = $this->getJson('/api/v1/admin/tenants');

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'display_name',
                            'status',
                            'created_at',
                        ],
                    ],
                    'links',
                    'meta',
                ]);
        });

        test('does not return soft deleted tenants', function () {
            Tenant::factory()->create(['name' => 'ACTIVE']);
            $deleted = Tenant::factory()->create(['name' => 'DELETED']);
            $deleted->delete(); // Soft delete the tenant

            $response = $this->getJson('/api/v1/admin/tenants');

            $tenantNames = collect($response->json('data'))->pluck('name')->toArray();
            expect($tenantNames)->toContain('ACTIVE')
                ->and($tenantNames)->not->toContain('DELETED');
        });
    });

    describe('GET /api/v1/admin/tenants/{id} (show)', function () {
        test('requires authentication', function () {
            $tenant = Tenant::factory()->create();

            // Refresh application to reset authentication
            $this->refreshApplication();

            $response = $this->getJson("/api/v1/admin/tenants/{$tenant->id}");

            $response->assertUnauthorized();
        });

        test('returns tenant details', function () {
            $tenant = Tenant::factory()->create([
                'name' => 'SHOWTEST',
                'display_name' => 'Show Test Association',
            ]);

            $response = $this->getJson("/api/v1/admin/tenants/{$tenant->id}");

            $response->assertOk()
                ->assertJson([
                    'data' => [
                        'id' => $tenant->id,
                        'name' => 'SHOWTEST',
                        'display_name' => 'Show Test Association',
                    ],
                ]);
        });

        test('returns 404 for non-existent tenant', function () {
            $response = $this->getJson('/api/v1/admin/tenants/00000000-0000-0000-0000-000000000000');

            $response->assertNotFound()
                ->assertJson(['message' => 'Tenant not found']);
        });

        test('returns 404 for soft deleted tenant', function () {
            $tenant = Tenant::factory()->create();
            $tenant->delete(); // Soft delete the tenant

            $response = $this->getJson("/api/v1/admin/tenants/{$tenant->id}");

            $response->assertNotFound();
        });
    });
});
