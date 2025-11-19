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

describe('ProductInventoryController', function () {
    beforeEach(function () {
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

        Language::firstOrCreate(
            ['code' => 'en'],
            ['name' => 'English', 'default' => true]
        );
        $this->currency = Currency::firstOrCreate(
            ['code' => 'GHS'],
            ['name' => 'Ghana Cedi', 'exchange_rate' => 1, 'decimal_places' => 2, 'enabled' => true, 'default' => true]
        );
        $this->taxClass = TaxClass::firstOrCreate(
            ['name' => 'Default'],
            ['default' => true]
        );

        $this->product = Product::factory()->create(['vendor_id' => $this->vendor->id]);
    });

    test('shows product inventory information', function () {
        $variant1 = ProductVariant::factory()->create([
            'product_id' => $this->product->id,
            'stock' => 10,
            'purchasable' => 'in_stock',
            'backorder' => 0,
        ]);

        $variant2 = ProductVariant::factory()->create([
            'product_id' => $this->product->id,
            'stock' => 5,
            'purchasable' => 'always',
            'backorder' => 10,
        ]);

        $response = $this->getJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/inventory");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'product_id',
                    'total_stock',
                    'low_stock_threshold',
                    'is_low_stock',
                    'variants' => [
                        '*' => [
                            'id',
                            'sku',
                            'stock',
                            'purchasable',
                            'backorder',
                        ],
                    ],
                ],
            ])
            ->assertJsonPath('data.total_stock', 15)
            ->assertJsonCount(2, 'data.variants');
    });

    test('identifies low stock products', function () {
        ProductVariant::factory()->create([
            'product_id' => $this->product->id,
            'stock' => 5,
        ]);

        $response = $this->getJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/inventory");

        $response->assertOk()
            ->assertJsonPath('data.is_low_stock', true);
    });

    test('updates product inventory by setting stock', function () {
        $variant = ProductVariant::factory()->create([
            'product_id' => $this->product->id,
            'stock' => 10,
        ]);

        $response = $this->putJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/inventory", [
            'variant_id' => $variant->id,
            'action' => 'set',
            'quantity' => 50,
        ]);

        $response->assertOk();

        $variant->refresh();
        expect($variant->stock)->toBe(50);
    });

    test('updates product inventory by incrementing stock', function () {
        $variant = ProductVariant::factory()->create([
            'product_id' => $this->product->id,
            'stock' => 10,
        ]);

        $response = $this->putJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/inventory", [
            'variant_id' => $variant->id,
            'action' => 'increment',
            'quantity' => 5,
        ]);

        $response->assertOk();

        $variant->refresh();
        expect($variant->stock)->toBe(15);
    });

    test('updates product inventory by decrementing stock', function () {
        $variant = ProductVariant::factory()->create([
            'product_id' => $this->product->id,
            'stock' => 10,
        ]);

        $response = $this->putJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/inventory", [
            'variant_id' => $variant->id,
            'action' => 'decrement',
            'quantity' => 3,
        ]);

        $response->assertOk();

        $variant->refresh();
        expect($variant->stock)->toBe(7);
    });

    test('prevents decrementing below zero', function () {
        $variant = ProductVariant::factory()->create([
            'product_id' => $this->product->id,
            'stock' => 5,
        ]);

        $response = $this->putJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/inventory", [
            'variant_id' => $variant->id,
            'action' => 'decrement',
            'quantity' => 10,
        ]);

        $response->assertUnprocessable();

        $variant->refresh();
        expect($variant->stock)->toBe(5); // Should not change
    });

    test('validates variant_id is required', function () {
        $response = $this->putJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/inventory", [
            'action' => 'set',
            'quantity' => 10,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['variant_id']);
    });

    test('validates action is required', function () {
        $variant = ProductVariant::factory()->create([
            'product_id' => $this->product->id,
        ]);

        $response = $this->putJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/inventory", [
            'variant_id' => $variant->id,
            'quantity' => 10,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['action']);
    });

    test('validates action is valid enum', function () {
        $variant = ProductVariant::factory()->create([
            'product_id' => $this->product->id,
        ]);

        $response = $this->putJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/inventory", [
            'variant_id' => $variant->id,
            'action' => 'invalid_action',
            'quantity' => 10,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['action']);
    });

    test('validates quantity is required', function () {
        $variant = ProductVariant::factory()->create([
            'product_id' => $this->product->id,
        ]);

        $response = $this->putJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/inventory", [
            'variant_id' => $variant->id,
            'action' => 'set',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['quantity']);
    });

    test('validates quantity is integer', function () {
        $variant = ProductVariant::factory()->create([
            'product_id' => $this->product->id,
        ]);

        $response = $this->putJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/inventory", [
            'variant_id' => $variant->id,
            'action' => 'set',
            'quantity' => 'not-a-number',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['quantity']);
    });

    test('validates quantity is not negative', function () {
        $variant = ProductVariant::factory()->create([
            'product_id' => $this->product->id,
        ]);

        $response = $this->putJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/inventory", [
            'variant_id' => $variant->id,
            'action' => 'set',
            'quantity' => -5,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['quantity']);
    });

    test('returns 404 for non-existent product', function () {
        $response = $this->actingAs($this->user, 'api')->getJson("{$this->tenantUrl}/api/v1/products/999999/inventory");

        $response->assertNotFound();
    });

    test('returns 404 for non-existent variant', function () {
        $response = $this->putJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/inventory", [
            'variant_id' => '999999',
            'action' => 'set',
            'quantity' => 10,
        ]);

        $response->assertNotFound();
    });
});
