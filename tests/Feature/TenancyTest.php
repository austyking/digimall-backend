<?php

declare(strict_types=1);

use App\DTOs\CreateTenantDTO;
use App\DTOs\UpdateTenantDTO;
use App\DTOs\UpdateTenantSettingsDTO;
use App\Http\Resources\TenantConfigResource;
use App\Http\Resources\TenantResource;
use App\Models\Tenant;
use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Services\TenantService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

uses()->beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\TenantSeeder']);
    $this->tenantRepository = app(TenantRepositoryInterface::class);
    $this->tenantService = app(TenantService::class);
});

// DTO TESTS
describe('CreateTenantDTO', function () {
    test('creates DTO with all required fields', function () {
        $dto = new CreateTenantDTO(
            name: 'TEST',
            displayName: 'Test Association',
            description: 'Test description',
            active: true,
            settings: ['theme' => ['primary_color' => '#000000']]
        );

        expect($dto->name)->toBe('TEST')
            ->and($dto->displayName)->toBe('Test Association')
            ->and($dto->description)->toBe('Test description')
            ->and($dto->active)->toBeTrue()
            ->and($dto->settings)->toBeArray();
    });

    test('creates DTO with only required fields', function () {
        $dto = new CreateTenantDTO(
            name: 'MINIMAL',
            displayName: 'Minimal Test'
        );

        expect($dto->name)->toBe('MINIMAL')
            ->and($dto->displayName)->toBe('Minimal Test')
            ->and($dto->description)->toBeNull()
            ->and($dto->active)->toBeTrue()
            ->and($dto->settings)->toBe([]);
    });

    test('converts DTO to array for model creation', function () {
        $dto = new CreateTenantDTO(
            name: 'ARRAY_TEST',
            displayName: 'Array Test',
            description: 'Testing array conversion'
        );

        $array = $dto->toArray();

        expect($array)->toBeArray()
            ->toHaveKeys(['name', 'display_name', 'description', 'active', 'settings'])
            ->and($array['name'])->toBe('ARRAY_TEST')
            ->and($array['display_name'])->toBe('Array Test');
    });

    test('creates DTO from HTTP request', function () {
        $request = Request::create('/test', 'POST', [
            'name' => 'REQUEST_TEST',
            'display_name' => 'Request Test',
            'description' => 'From request',
            'active' => true,
            'settings' => ['key' => 'value'],
        ]);

        $dto = CreateTenantDTO::fromRequest($request);

        expect($dto->name)->toBe('REQUEST_TEST')
            ->and($dto->displayName)->toBe('Request Test')
            ->and($dto->active)->toBeTrue();
    });
});

describe('UpdateTenantDTO', function () {
    test('creates DTO with nullable fields', function () {
        $dto = new UpdateTenantDTO(
            displayName: 'Updated Display',
            description: 'Updated description',
            active: true
        );

        expect($dto->displayName)->toBe('Updated Display')
            ->and($dto->description)->toBe('Updated description')
            ->and($dto->active)->toBeTrue();
    });

    test('converts to array excluding null values', function () {
        $dto = new UpdateTenantDTO(
            displayName: 'Update Test',
            description: null,
            active: null
        );

        $array = $dto->toArray();

        expect($array)->toHaveKey('display_name')
            ->and($array)->not->toHaveKey('description')
            ->and($array)->not->toHaveKey('active');
    });
});

describe('UpdateTenantSettingsDTO', function () {
    test('creates settings DTO', function () {
        $dto = new UpdateTenantSettingsDTO(
            settings: ['theme' => ['primary_color' => '#ff0000']]
        );

        expect($dto->settings)->toBeArray()
            ->and($dto->settings['theme']['primary_color'])->toBe('#ff0000');
    });
});

// REPOSITORY TESTS
describe('TenantRepository', function () {
    test('finds tenant by ID', function () {
        $grnma = Tenant::where('name', 'GRNMA')->first();
        $found = $this->tenantRepository->find($grnma->id);

        expect($found)->toBeInstanceOf(Tenant::class)
            ->and($found->id)->toBe($grnma->id);
    });

    test('finds tenant by name', function () {
        $tenant = $this->tenantRepository->findByName('GRNMA');

        expect($tenant)->toBeInstanceOf(Tenant::class)
            ->and($tenant->name)->toBe('GRNMA');
    });

    test('returns null for non-existent tenant', function () {
        expect($this->tenantRepository->find('non-existent-id'))->toBeNull()
            ->and($this->tenantRepository->findByName('NON_EXISTENT'))->toBeNull();
    });

    test('gets all tenants', function () {
        $tenants = $this->tenantRepository->all();

        expect($tenants)->toHaveCount(2) // GRNMA and GMA from seeder
            ->and($tenants->first())->toBeInstanceOf(Tenant::class);
    });

    test('gets only active tenants', function () {
        // Create inactive tenant
        Tenant::create([
            'id' => Str::uuid()->toString(),
            'name' => 'INACTIVE',
            'display_name' => 'Inactive Tenant',
            'active' => false,
        ]);

        $activeTenants = $this->tenantRepository->allActive();

        expect($activeTenants)->toHaveCount(2) // Only GRNMA and GMA
            ->and($activeTenants->every(fn ($t) => $t->active))->toBeTrue();
    });

    test('creates tenant through repository', function () {
        $data = [
            'id' => Str::uuid()->toString(),
            'name' => 'REPO_TEST',
            'display_name' => 'Repository Test',
            'active' => true,
        ];

        $tenant = $this->tenantRepository->create($data);

        expect($tenant)->toBeInstanceOf(Tenant::class)
            ->and($tenant->name)->toBe('REPO_TEST')
            ->and($tenant->exists)->toBeTrue();
    });

    test('updates tenant through repository', function () {
        $tenant = Tenant::where('name', 'GRNMA')->first();
        $updated = $this->tenantRepository->update($tenant, [
            'description' => 'Updated description',
        ]);

        expect($updated->description)->toBe('Updated description')
            ->and($updated->name)->toBe('GRNMA'); // Other fields unchanged
    });

    test('deletes tenant through repository', function () {
        $tenant = Tenant::create([
            'id' => Str::uuid()->toString(),
            'name' => 'DELETE_ME',
            'display_name' => 'To Be Deleted',
            'active' => true,
        ]);

        $result = $this->tenantRepository->delete($tenant);

        expect($result)->toBeTrue()
            ->and(Tenant::find($tenant->id))->toBeNull();
    });

    test('finds tenant with domains loaded', function () {
        $grnma = Tenant::where('name', 'GRNMA')->first();
        $tenantWithDomains = $this->tenantRepository->findWithDomains($grnma->id);

        expect($tenantWithDomains->relationLoaded('domains'))->toBeTrue()
            ->and($tenantWithDomains->domains)->not->toBeEmpty();
    });

    test('searches tenants by query', function () {
        $results = $this->tenantRepository->search('GRNMA');

        expect($results)->not->toBeEmpty()
            ->and($results->first()->name)->toBe('GRNMA');
    });
});

// SERVICE LAYER TESTS
describe('TenantService', function () {
    test('creates tenant using service with DTO', function () {
        $dto = new CreateTenantDTO(
            name: 'SERVICE_TEST',
            displayName: 'Service Test Association',
            description: 'Created via service',
            active: true,
            settings: ['theme' => ['primary_color' => '#123456']]
        );

        $tenant = $this->tenantService->createTenant($dto);

        expect($tenant)->toBeInstanceOf(Tenant::class)
            ->and($tenant->name)->toBe('SERVICE_TEST')
            ->and($tenant->getSetting('theme.primary_color'))->toBe('#123456')
            ->and($tenant->exists)->toBeTrue();
    });

    test('updates tenant using service with DTO', function () {
        $tenant = Tenant::where('name', 'GRNMA')->first();
        $dto = new UpdateTenantDTO(
            displayName: 'Updated Display Name',
            description: 'Updated via service',
            active: null
        );

        $updated = $this->tenantService->updateTenant($tenant, $dto);

        expect($updated->display_name)->toBe('Updated Display Name')
            ->and($updated->description)->toBe('Updated via service')
            ->and($updated->name)->toBe('GRNMA'); // Unchanged
    });

    test('updates tenant settings using service', function () {
        $tenant = Tenant::where('name', 'GRNMA')->first();
        $dto = new UpdateTenantSettingsDTO(
            settings: ['new_feature' => ['enabled' => true]]
        );

        $updated = $this->tenantService->updateSettings($tenant, $dto);

        expect($updated->getSetting('new_feature.enabled'))->toBeTrue()
            ->and($updated->getSetting('theme.primary_color'))->toBe('#1976d2'); // Old settings preserved
    });

    test('deletes tenant using service', function () {
        $tenant = Tenant::create([
            'id' => Str::uuid()->toString(),
            'name' => 'SERVICE_DELETE',
            'display_name' => 'Service Delete Test',
            'active' => true,
        ]);

        $result = $this->tenantService->deleteTenant($tenant);

        expect($result)->toBeTrue()
            ->and(Tenant::find($tenant->id))->toBeNull();
    });

    test('gets tenant config through service', function () {
        $tenant = Tenant::where('name', 'GRNMA')->first();
        $config = $this->tenantService->getTenantConfig($tenant);

        expect($config)->toBeArray()
            ->toHaveKeys(['tenant', 'branding', 'features', 'payment_gateways', 'settings'])
            ->and($config['tenant']['name'])->toBe('GRNMA')
            ->and($config['branding']['primary_color'])->toBe('#1976d2');
    });
});

// RESOURCE TESTS
describe('TenantResource', function () {
    test('transforms tenant to JSON structure', function () {
        $tenant = Tenant::where('name', 'GRNMA')->first()->load('domains');
        $resource = new TenantResource($tenant);
        $request = Request::create('/test');
        $array = $resource->toArray($request);

        expect($array)->toBeArray()
            ->toHaveKeys(['id', 'name', 'display_name', 'description', 'active', 'domains', 'created_at', 'updated_at'])
            ->and($array['name'])->toBe('GRNMA')
            ->and($array['domains'])->toBeInstanceOf(\Illuminate\Support\Collection::class)
            ->and($array['domains'])->toHaveCount(3) // grnma.test (factory auto) + shop.grnmainfonet.test + grnma.digimall.test
            ->and($array['created_at'])->toBeString(); // ISO 8601 format
    });

    test('handles null description in resource', function () {
        $tenant = Tenant::factory()->create([
            'name' => 'NULL_DESC',
            'display_name' => 'Null Description',
            'description' => null,
        ]);

        $resource = new TenantResource($tenant);
        $array = $resource->toArray(Request::create('/test'));

        expect($array['description'])->toBeNull();
    });
});

describe('TenantConfigResource', function () {
    test('transforms tenant config to JSON structure', function () {
        $tenant = Tenant::where('name', 'GRNMA')->first();
        $resource = new TenantConfigResource($tenant);
        $request = Request::create('/test');
        $array = $resource->toArray($request);

        expect($array)->toBeArray()
            ->toHaveKeys(['tenant', 'branding', 'features', 'payment_gateways', 'settings'])
            ->and($array['tenant'])->toHaveKeys(['id', 'name', 'display_name', 'description', 'active'])
            ->and($array['branding'])->toHaveKeys(['name', 'display_name', 'logo_url', 'primary_color', 'secondary_color'])
            ->and($array['features'])->toBeArray()
            ->and($array['features']['hire_purchase'])->toBeTrue();
    });
});

// TENANT MODEL TESTS
test('tenant uses UUID as primary key', function () {
    $tenant = Tenant::first();
    expect($tenant->id)->toBeString()
        ->and(Str::isUuid($tenant->id))->toBeTrue()
        ->and($tenant->incrementing)->toBeFalse();
});

test('tenant has domains relationship', function () {
    $tenant = Tenant::where('name', 'GRNMA')->first();
    expect($tenant->domains)->not->toBeEmpty()
        ->and($tenant->domains->pluck('domain')->toArray())->toContain('grnma.test'); // Factory auto-created domain
});

// SETTINGS MANAGEMENT TESTS
test('can get nested settings using dot notation', function () {
    $tenant = Tenant::where('name', 'GRNMA')->first();
    expect($tenant->getSetting('theme.primary_color'))->toBe('#1976d2')
        ->and($tenant->getSetting('features.hire_purchase'))->toBeTrue();
});

test('returns default value for missing settings', function () {
    $tenant = Tenant::where('name', 'GRNMA')->first();
    expect($tenant->getSetting('non.existent.key', 'default_value'))->toBe('default_value');
});

test('can set and update nested settings', function () {
    $tenant = Tenant::where('name', 'GRNMA')->first();
    $tenant->setSetting('theme.accent_color', '#ff5722');
    $tenant->save();
    $tenant->refresh();
    expect($tenant->getSetting('theme.accent_color'))->toBe('#ff5722');
});

// BRANDING TESTS
test('returns complete branding config for GRNMA', function () {
    $tenant = Tenant::where('name', 'GRNMA')->first();
    $branding = $tenant->getBrandingConfig();

    expect($branding)->toBeArray()
        ->toHaveKeys(['name', 'display_name', 'primary_color', 'secondary_color'])
        ->and($branding['name'])->toBe('GRNMA')
        ->and($branding['primary_color'])->toBe('#1976d2')
        ->and($branding['secondary_color'])->toBe('#dc004e');
});

test('uses default colors when theme settings are missing', function () {
    $dto = new CreateTenantDTO(
        name: 'NO_THEME',
        displayName: 'No Theme Tenant',
        settings: []
    );

    $tenant = $this->tenantService->createTenant($dto);
    $branding = $tenant->getBrandingConfig();

    expect($branding['primary_color'])->toBe('#1976d2')
        ->and($branding['secondary_color'])->toBe('#dc004e');
});

// API ENDPOINT TESTS
test('resolves GRNMA tenant by test domain', function () {
    $this->get('http://shop.grnmainfonet.test/api/v1/health')
        ->assertStatus(200)
        ->assertJson(['status' => 'ok', 'tenant_name' => 'GRNMA']);
});

test('returns complete config for GRNMA tenant', function () {
    $response = $this->get('http://shop.grnmainfonet.test/api/v1/config');
    $data = $response->json();

    expect($data['data']['tenant']['name'])->toBe('GRNMA')
        ->and($data['data']['branding']['primary_color'])->toBe('#1976d2')
        ->and($data['data']['features']['hire_purchase'])->toBeTrue();
});

// MULTI-TENANT ISOLATION TESTS
test('different tenants have different settings', function () {
    $grnma = Tenant::where('name', 'GRNMA')->first();
    $gma = Tenant::where('name', 'GMA')->first();

    expect($grnma->getSetting('theme.primary_color'))->toBe('#1976d2')
        ->and($gma->getSetting('theme.primary_color'))->toBe('#2e7d32')
        ->and($grnma->getSetting('theme.primary_color'))
        ->not->toBe($gma->getSetting('theme.primary_color'));
});

test('each tenant has unique domains', function () {
    $grnma = Tenant::where('name', 'GRNMA')->first();
    $gma = Tenant::where('name', 'GMA')->first();

    $grnmaDomains = $grnma->domains->pluck('domain')->toArray();
    $gmaDomains = $gma->domains->pluck('domain')->toArray();

    expect($grnmaDomains)->toContain('shop.grnmainfonet.test')
        ->and($gmaDomains)->toContain('mall.ghanamedassoc.test')
        ->and(array_intersect($grnmaDomains, $gmaDomains))->toBeEmpty();
});

// EDGE CASE TESTS
test('handles null logo_url gracefully', function () {
    $dto = new CreateTenantDTO(
        name: 'NULL_LOGO',
        displayName: 'Null Logo Tenant'
    );

    $tenant = $this->tenantService->createTenant($dto);

    expect($tenant->getBrandingConfig()['logo_url'])->toBeNull();
});

test('handles complex nested settings', function () {
    $dto = new CreateTenantDTO(
        name: 'COMPLEX',
        displayName: 'Complex Settings',
        settings: [
            'level1' => ['level2' => ['level3' => ['deep_value' => 'found']]],
        ]
    );

    $tenant = $this->tenantService->createTenant($dto);

    expect($tenant->getSetting('level1.level2.level3.deep_value'))->toBe('found');
});

// PERFORMANCE TESTS
test('loads tenant efficiently with single query', function () {
    DB::enableQueryLog();
    $this->tenantRepository->findByName('GRNMA');
    $queries = DB::getQueryLog();
    expect(count($queries))->toBe(1);
    DB::disableQueryLog();
});

// NEGATIVE TESTS
test('fails with invalid domain', function () {
    $response = $this->get('http://invalid.domain.test/api/v1/health');
    expect($response->getStatusCode())->toBeIn([404, 500]);
});
