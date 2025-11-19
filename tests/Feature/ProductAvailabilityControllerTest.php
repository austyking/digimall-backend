<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Models\Language;
use Lunar\Models\Price;
use Lunar\Models\Product;
use Lunar\Models\ProductVariant;

uses(RefreshDatabase::class);

describe('ProductAvailabilityController', function () {
    beforeEach(function () {
        $this->withMiddleware();
        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\RolesAndPermissionsSeeder']);
        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\TenantSeeder']);

        $this->tenant = Tenant::where('name', 'GRNMA')->first();

        $this->user = User::factory()->create();
        $this->user->assignRole('association-administrator');
        $this->actingAs($this->user, 'api');

        $this->tenantUrl = 'http://shop.grnmainfonet.test';

        // Initialize tenant context for database operations
        tenancy()->initialize($this->tenant);

        // Ensure we have an English language for the test
        Language::firstOrCreate(
            ['code' => 'en'],
            ['name' => 'English', 'default' => true]
        );

        $this->product = Product::factory()->create();
        $this->variant = ProductVariant::factory()->create([
            'product_id' => $this->product->id,
            'stock' => 10,
            'purchasable' => 'in_stock',
            'backorder' => 0,
        ]);

        Price::factory()->create([
            'priceable_id' => $this->variant->id,
            'priceable_type' => ProductVariant::class,
            'price' => 9999, // GHS 99.99
        ]);
    });

    test('shows availability with stock information', function () {
        $response = $this->getJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/availability");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'is_available',
                    'stock',
                    'backorder',
                    'purchasable',
                ],
            ]);
    });

    test('returns available true when purchasable is always', function () {
        $this->variant->update(['purchasable' => 'always', 'stock' => 0]);

        $response = $this->getJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/availability");

        $response->assertOk()
            ->assertJsonPath('data.is_available', true)
            ->assertJsonPath('data.purchasable', 'always');
    });

    test('returns available true when purchasable is in_stock and stock exists', function () {
        $this->variant->update(['purchasable' => 'in_stock', 'stock' => 5]);

        $response = $this->getJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/availability");

        $response->assertOk()
            ->assertJsonPath('data.is_available', true)
            ->assertJsonPath('data.stock', 5);
    });

    test('returns available false when purchasable is in_stock and no stock', function () {
        $this->variant->update(['purchasable' => 'in_stock', 'stock' => 0]);

        $response = $this->getJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/availability");

        $response->assertOk()
            ->assertJsonPath('data.is_available', false)
            ->assertJsonPath('data.stock', 0);
    });

    test('returns available true when purchasable is backorder and has backorder quantity', function () {
        $this->variant->update([
            'purchasable' => 'backorder',
            'stock' => 0,
            'backorder' => 20,
        ]);

        $response = $this->getJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/availability");

        $response->assertOk()
            ->assertJsonPath('data.is_available', true)
            ->assertJsonPath('data.backorder', 20);
    });

    test('returns available true when purchasable is backorder and has stock', function () {
        $this->variant->update([
            'purchasable' => 'backorder',
            'stock' => 5,
            'backorder' => 0,
        ]);

        $response = $this->getJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/availability");

        $response->assertOk()
            ->assertJsonPath('data.is_available', true)
            ->assertJsonPath('data.stock', 5);
    });

    test('returns available false when purchasable is backorder with no stock or backorder', function () {
        $this->variant->update([
            'purchasable' => 'backorder',
            'stock' => 0,
            'backorder' => 0,
        ]);

        $response = $this->getJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/availability");

        $response->assertOk()
            ->assertJsonPath('data.is_available', false);
    });

    test('updates availability settings', function () {
        $response = $this->putJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/availability", [
            'purchasable' => 'always',
            'stock' => 50,
            'backorder' => 10,
        ]);

        $response->assertOk();

        $this->variant->refresh();
        expect($this->variant->purchasable)->toBe('always')
            ->and($this->variant->stock)->toBe(50)
            ->and($this->variant->backorder)->toBe(10);
    });

    test('validates purchasable is enum', function () {
        $response = $this->putJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/availability", [
            'purchasable' => 'invalid_value',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['purchasable']);
    });

    test('validates stock is integer', function () {
        $response = $this->putJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/availability", [
            'stock' => 'not-a-number',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['stock']);
    });

    test('validates backorder is integer', function () {
        $response = $this->putJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/availability", [
            'backorder' => 'not-a-number',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['backorder']);
    });

    test('validates stock is not negative', function () {
        $response = $this->putJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/availability", [
            'stock' => -5,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['stock']);
    });

    test('validates backorder is not negative', function () {
        $response = $this->putJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/availability", [
            'backorder' => -10,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['backorder']);
    });

    test('returns 404 for non-existent product', function () {
        $response = $this->actingAs($this->user, 'api')->getJson("{$this->tenantUrl}{$this->tenantUrl}/api/v1/products/999999/availability");

        $response->assertNotFound();
    });
});
