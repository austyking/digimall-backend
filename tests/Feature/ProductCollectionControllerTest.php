<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Models\Collection;
use Lunar\Models\Language;
use Lunar\Models\Product;

uses(RefreshDatabase::class);

describe('ProductCollectionController', function () {
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

        Language::firstOrCreate(['code' => 'en'], ['name' => 'English', 'default' => true]);

        // Create product manually to avoid factory issues
        $productType = \Lunar\Models\ProductType::factory()->create();
        $this->product = Product::create([
            'product_type_id' => $productType->id,
            'status' => 'published',
            'attribute_data' => collect([
                'name' => new \Lunar\FieldTypes\Text('Test Product'),
                'description' => new \Lunar\FieldTypes\Text('Test description'),
            ]),
        ]);
        $this->collection1 = Collection::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->collection2 = Collection::factory()->create(['tenant_id' => $this->tenant->id]);
    });

    test('attaches product to collections', function () {
        $response = $this->postJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/collections/attach", [
            'collection_id' => $this->collection1->id,
            'product_ids' => [$this->product->id],
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'message',
            ]);

        $this->assertDatabaseHas('collection_product', [
            'collection_id' => $this->collection1->id,
            'product_id' => $this->product->id,
        ]);
    });

    test('attaches multiple products to collection', function () {
        $product2 = Product::factory()->create();

        $response = $this->postJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/collections/attach", [
            'collection_id' => $this->collection1->id,
            'product_ids' => [$this->product->id, $product2->id],
        ]);

        $response->assertOk();

        expect($this->collection1->products()->count())->toBe(2);
    });

    test('sets position when attaching products', function () {
        $product2 = Product::factory()->create();

        $this->postJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/collections/attach", [
            'collection_id' => $this->collection1->id,
            'product_ids' => [$this->product->id, $product2->id],
        ]);

        $this->assertDatabaseHas('collection_product', [
            'collection_id' => $this->collection1->id,
            'product_id' => $this->product->id,
            'position' => 1,
        ]);

        $this->assertDatabaseHas('collection_product', [
            'collection_id' => $this->collection1->id,
            'product_id' => $product2->id,
            'position' => 2,
        ]);
    });

    test('detaches product from collection', function () {
        $this->collection1->products()->attach($this->product->id, ['position' => 1]);

        $response = $this->postJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/collections/detach", [
            'collection_id' => $this->collection1->id,
            'product_ids' => [$this->product->id],
        ]);

        $response->assertOk();

        $this->assertDatabaseMissing('collection_product', [
            'collection_id' => $this->collection1->id,
            'product_id' => $this->product->id,
        ]);
    });

    test('lists product collections', function () {
        $this->collection1->products()->attach($this->product->id, ['position' => 1]);
        $this->collection2->products()->attach($this->product->id, ['position' => 2]);

        $response = $this->getJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/collections");

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                    ],
                ],
            ]);
    });

    test('validates collection_id is required', function () {
        $response = $this->postJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/collections/attach", [
            'product_ids' => [$this->product->id],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['collection_id']);
    });

    test('validates product_ids is required', function () {
        $response = $this->postJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/collections/attach", [
            'collection_id' => $this->collection1->id,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['product_ids']);
    });

    test('validates product_ids is array', function () {
        $response = $this->postJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/collections/attach", [
            'collection_id' => $this->collection1->id,
            'product_ids' => 'not-an-array',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['product_ids']);
    });

    test('validates collection exists', function () {
        $response = $this->postJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/collections/attach", [
            'collection_id' => 'non-existent-uuid',
            'product_ids' => [$this->product->id],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['collection_id']);
    });

    test('returns 404 for non-existent product', function () {
        $response = $this->actingAs($this->user, 'api')->getJson("{$this->tenantUrl}/api/v1/products/999999/collections");

        $response->assertNotFound();
    });
});
