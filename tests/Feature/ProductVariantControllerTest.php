<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Models\Currency;
use Lunar\Models\Language;
use Lunar\Models\Product;
use Lunar\Models\ProductVariant;
use Lunar\Models\TaxClass;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withMiddleware();
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\RolesAndPermissionsSeeder']);
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\TenantSeeder']);

    $this->tenant = Tenant::where('name', 'GRNMA')->first();

    // Initialize tenancy for this tenant
    tenancy()->initialize($this->tenant);

    $this->user = User::factory()->create();
    $this->user->assignRole('association-administrator');
    $this->actingAs($this->user, 'api');

    $this->tenantUrl = 'http://shop.grnmainfonet.test';

    $this->vendor = Vendor::factory()->create(['user_id' => $this->user->id]);
    $this->user->vendor_id = $this->vendor->id;

    // Setup necessary models
    $this->currency = Currency::firstOrCreate(
        ['code' => 'GHS'],
        ['name' => 'Ghana Cedi', 'exchange_rate' => 1, 'decimal_places' => 2, 'enabled' => true, 'default' => true]
    );
    $this->taxClass = TaxClass::firstOrCreate(
        ['name' => 'Standard'],
        ['default' => true]
    );
    $this->language = Language::firstOrCreate(
        ['code' => 'en'],
        ['name' => 'English', 'default' => true]
    );
    $this->product = Product::factory()->create(['vendor_id' => $this->vendor->id]);
});

describe('ProductVariantController', function () {
    test('lists all variants for a product', function () {
        ProductVariant::factory()->count(3)->create([
            'product_id' => $this->product->id,
        ]);

        $response = $this->getJson("$this->tenantUrl/api/v1/products/{$this->product->id}/variants");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'product_id',
                        'sku',
                        'stock',
                        'purchasable',
                        'backorder',
                    ],
                ],
            ])
            ->assertJsonCount(3, 'data');
    });

    test('creates a new variant with price', function () {
        $response = $this->postJson("$this->tenantUrl/api/v1/products/{$this->product->id}/variants", [
            'sku' => 'NEW-VARIANT-001',
            'stock' => 50,
            'purchasable' => 'always',
            'price' => 99.99,
            'unit_quantity' => 1,
            'tax_class_id' => $this->taxClass->id,
            'backorder' => 0,
            'currency_id' => $this->currency->id,
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'sku',
                    'stock',
                    'purchasable',
                    'prices',
                ],
            ])
            ->assertJsonPath('data.sku', 'NEW-VARIANT-001')
            ->assertJsonPath('data.stock', 50)
            ->assertJsonPath('data.purchasable', 'always');

        $this->assertDatabaseHas('product_variants', [
            'sku' => 'NEW-VARIANT-001',
            'stock' => 50,
        ]);
    });

    test('uses default no currency is provided', function () {
        $response = $this->postJson("$this->tenantUrl/api/v1/products/{$this->product->id}/variants", [
            'sku' => 'DEFAULT-CURR-SKU',
            'stock' => 10,
            'purchasable' => 'always',
            'price' => 50.00,
            'unit_quantity' => 1,
            'tax_class_id' => $this->taxClass->id,
            'backorder' => 0,
        ]);

        Log::info($response->json());

        $response->assertCreated()
            ->assertJsonPath('data.sku', 'DEFAULT-CURR-SKU')
            ->assertJsonPath('data.prices.0.currency.id', $this->currency->id);
    });

    test('validates required fields when creating variant', function () {
        $response = $this->postJson("$this->tenantUrl/api/v1/products/{$this->product->id}/variants", [
            'sku' => '',
            'stock' => -5,
            'purchasable' => 'invalid_enum',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['sku', 'stock', 'purchasable', 'price']);
    });

    test('validates purchasable enum values', function () {
        $response = $this->postJson("$this->tenantUrl/api/v1/products/{$this->product->id}/variants", [
            'sku' => 'TEST-SKU',
            'stock' => 10,
            'purchasable' => 'not_a_valid_value',
            'price' => 50.00,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['purchasable']);
    });

    test('validates stock is not negative', function () {
        $response = $this->postJson("$this->tenantUrl/api/v1/products/{$this->product->id}/variants", [
            'sku' => 'TEST-SKU',
            'stock' => -10,
            'purchasable' => 'always',
            'price' => 50.00,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['stock']);
    });

    test('validates backorder is integer', function () {
        $response = $this->postJson("$this->tenantUrl/api/v1/products/{$this->product->id}/variants", [
            'sku' => 'TEST-SKU',
            'stock' => 10,
            'purchasable' => 'backorder',
            'price' => 50.00,
            'backorder' => -5,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['backorder']);
    });

    test('updates existing variant', function () {
        $variant = ProductVariant::factory()->create([
            'product_id' => $this->product->id,
            'sku' => 'OLD-SKU',
            'stock' => 100,
        ]);

        $response = $this->putJson("$this->tenantUrl/api/v1/products/{$this->product->id}/variants/{$variant->id}", [
            'sku' => 'UPDATED-SKU',
            'stock' => 75,
            'purchasable' => 'in_stock',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.sku', 'UPDATED-SKU')
            ->assertJsonPath('data.stock', 75)
            ->assertJsonPath('data.purchasable', 'in_stock');

        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'sku' => 'UPDATED-SKU',
            'stock' => 75,
        ]);
    });

    test('allows partial updates', function () {
        $variant = ProductVariant::factory()->create([
            'product_id' => $this->product->id,
            'sku' => 'PARTIAL-SKU',
            'stock' => 50,
            'purchasable' => 'always',
        ]);

        $response = $this->putJson("$this->tenantUrl/api/v1/products/{$this->product->id}/variants/{$variant->id}", [
            'stock' => 30,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.sku', 'PARTIAL-SKU') // Unchanged
            ->assertJsonPath('data.stock', 30); // Updated
    });

    test('deletes variant', function () {
        $variant = ProductVariant::factory()->create([
            'product_id' => $this->product->id,
        ]);

        $response = $this->deleteJson("$this->tenantUrl/api/v1/products/{$this->product->id}/variants/{$variant->id}");

        $response->assertOk()
            ->assertJson(['message' => 'Variant deleted successfully']);

        $this->assertSoftDeleted('product_variants', [
            'id' => $variant->id,
        ]);
    });

    test('returns 404 for non-existent product', function () {
        $response = $this->actingAs($this->user, 'api')->getJson("{$this->tenantUrl}/api/v1/products/999999/variants");

        $response->assertNotFound();
    });

    test('returns 404 for non-existent variant', function () {
        $response = $this->putJson("$this->tenantUrl/api/v1/products/{$this->product->id}/variants/999999", [
            'stock' => 100,
        ]);

        $response->assertNotFound();
    });
});
