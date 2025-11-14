<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Models\Language;
use Lunar\Models\Product;
use Lunar\Models\ProductAssociation;

uses(RefreshDatabase::class);

describe('ProductAssociationController', function () {
    beforeEach(function () {
        $this->withMiddleware();
        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\RolesAndPermissionsSeeder']);
        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\TenantSeeder']);

        $this->tenant = Tenant::where('name', 'GRNMA')->first();

        $this->user = User::factory()->create();
        $this->user->assignRole('association-administrator');
        $this->actingAs($this->user, 'api');

        $this->tenantUrl = 'http://shop.grnmainfonet.test';

        Language::factory()->create(['code' => 'en', 'default' => true]);

        $this->product = Product::factory()->create();
        $this->associatedProduct1 = Product::factory()->create();
        $this->associatedProduct2 = Product::factory()->create();
    });

    test('lists all associations grouped by type', function () {
        $this->product->associate($this->associatedProduct1, ProductAssociation::CROSS_SELL);
        $this->product->associate($this->associatedProduct2, ProductAssociation::UP_SELL);

        $response = $this->getJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/associations");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'cross_sell',
                    'up_sell',
                    'alternate',
                ],
            ]);
    });

    test('attaches cross-sell products', function () {
        $response = $this->postJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/associations/cross-sell", [
            'product_ids' => [$this->associatedProduct1->id, $this->associatedProduct2->id],
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'type',
                        'target_product',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('product_associations', [
            'product_parent_id' => $this->product->id,
            'product_target_id' => $this->associatedProduct1->id,
            'type' => ProductAssociation::CROSS_SELL,
        ]);

        $this->assertDatabaseHas('product_associations', [
            'product_parent_id' => $this->product->id,
            'product_target_id' => $this->associatedProduct2->id,
            'type' => ProductAssociation::CROSS_SELL,
        ]);
    });

    test('attaches up-sell products', function () {
        $response = $this->postJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/associations/up-sell", [
            'product_ids' => [$this->associatedProduct1->id],
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('product_associations', [
            'product_parent_id' => $this->product->id,
            'product_target_id' => $this->associatedProduct1->id,
            'type' => ProductAssociation::UP_SELL,
        ]);
    });

    test('attaches alternate products', function () {
        $response = $this->postJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/associations/alternate", [
            'product_ids' => [$this->associatedProduct2->id],
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('product_associations', [
            'product_parent_id' => $this->product->id,
            'product_target_id' => $this->associatedProduct2->id,
            'type' => ProductAssociation::ALTERNATE,
        ]);
    });

    test('detaches specific association type', function () {
        $this->product->associate($this->associatedProduct1, ProductAssociation::CROSS_SELL);
        $this->product->associate($this->associatedProduct2, ProductAssociation::UP_SELL);

        $response = $this->postJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/associations/detach", [
            'product_ids' => [$this->associatedProduct1->id],
            'type' => ProductAssociation::CROSS_SELL,
        ]);

        $response->assertOk();

        $this->assertDatabaseMissing('product_associations', [
            'product_parent_id' => $this->product->id,
            'product_target_id' => $this->associatedProduct1->id,
            'type' => ProductAssociation::CROSS_SELL,
        ]);

        // Up-sell should still exist
        $this->assertDatabaseHas('product_associations', [
            'product_parent_id' => $this->product->id,
            'product_target_id' => $this->associatedProduct2->id,
            'type' => ProductAssociation::UP_SELL,
        ]);
    });

    test('detaches all association types when type not specified', function () {
        $this->product->associate($this->associatedProduct1, ProductAssociation::CROSS_SELL);
        $this->product->associate($this->associatedProduct1, ProductAssociation::UP_SELL);

        $response = $this->postJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/associations/detach", [
            'product_ids' => [$this->associatedProduct1->id],
        ]);

        $response->assertOk();

        expect(
            $this->product->associations()
                ->where('product_target_id', $this->associatedProduct1->id)
                ->count()
        )->toBe(0);
    });

    test('gets cross-sell products', function () {
        $this->product->associate($this->associatedProduct1, ProductAssociation::CROSS_SELL);
        $this->product->associate($this->associatedProduct2, ProductAssociation::UP_SELL);

        $response = $this->getJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/associations/cross-sell");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', ProductAssociation::CROSS_SELL);
    });

    test('gets up-sell products', function () {
        $this->product->associate($this->associatedProduct1, ProductAssociation::UP_SELL);

        $response = $this->getJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/associations/up-sell");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', ProductAssociation::UP_SELL);
    });

    test('gets alternate products', function () {
        $this->product->associate($this->associatedProduct1, ProductAssociation::ALTERNATE);

        $response = $this->getJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/associations/alternate");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', ProductAssociation::ALTERNATE);
    });

    test('validates product_ids is required', function () {
        $response = $this->postJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/associations/cross-sell", []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['product_ids']);
    });

    test('validates product_ids is array', function () {
        $response = $this->postJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/associations/cross-sell", [
            'product_ids' => 'not-an-array',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['product_ids']);
    });

    test('returns 404 for non-existent product', function () {
        $response = $this->actingAs($this->user, 'api')->getJson("{$this->tenantUrl}/api/v1/products/999999/associations");

        $response->assertNotFound();
    });
});
