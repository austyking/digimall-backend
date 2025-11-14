<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Models\Brand;
use Lunar\Models\Collection;
use Lunar\Models\Currency;
use Lunar\Models\Language;
use Lunar\Models\Product;
use Lunar\Models\ProductType;
use Lunar\Models\TaxClass;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withMiddleware();
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\RolesAndPermissionsSeeder']);
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\TenantSeeder']);
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\ProductSeeder']);

    $this->tenant = Tenant::where('name', 'GRNMA')->first();

    $this->user = User::factory()->create();
    $this->vendor = Vendor::factory()->create([
        'user_id' => $this->user->id,
        'tenant_id' => $this->tenant->id,
    ]);

    $this->language = Language::where('code', 'en')->first();
    $this->currency = Currency::where('code', 'GHS')->first();
    $this->taxClass = TaxClass::where('name', 'Default')->first();
    $this->productType = ProductType::where('name', 'Physical')->first();
    $this->brand = Brand::first();
    $this->collection = Collection::first();

    // Use GRNMA tenant domain
    $this->tenantUrl = 'http://shop.grnmainfonet.test';
});

describe('ProductController', function () {

    test('lists products for authenticated vendor', function () {
        Product::factory()->count(3)->create(['vendor_id' => $this->vendor->id]);
        Product::factory()->count(2)->create(); // Other vendor's products

        $response = $this->actingAs($this->user, 'api')
            ->getJson("{$this->tenantUrl}/api/v1/products");

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    });

    test('filters products by query', function () {
        $product1 = Product::factory()->create([
            'vendor_id' => $this->vendor->id,
            'attribute_data' => ['name' => new \Lunar\FieldTypes\Text('Laptop Computer')],
        ]);

        $product2 = Product::factory()->create([
            'vendor_id' => $this->vendor->id,
            'attribute_data' => ['name' => new \Lunar\FieldTypes\Text('Mobile Phone')],
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson("{$this->tenantUrl}/api/v1/products?query=laptop");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.attribute_data.name', 'Laptop Computer');
    });

    test('filters products by status', function () {
        Product::factory()->create([
            'vendor_id' => $this->vendor->id,
            'status' => 'published',
        ]);
        Product::factory()->create([
            'vendor_id' => $this->vendor->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson("{$this->tenantUrl}/api/v1/products?status=published");

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    });

    test('filters products by brand', function () {
        $brand = Brand::factory()->create();
        Product::factory()->create([
            'vendor_id' => $this->vendor->id,
            'brand_id' => $brand->id,
        ]);
        Product::factory()->create([
            'vendor_id' => $this->vendor->id,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson("{$this->tenantUrl}/api/v1/products?brand_id={$brand->id}");

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    });

    test('filters products by collection', function () {
        $collection = Collection::factory()->create();
        $product = Product::factory()->create(['vendor_id' => $this->vendor->id]);
        $collection->products()->attach($product->id, ['position' => 1]);

        Product::factory()->create(['vendor_id' => $this->vendor->id]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson("{$this->tenantUrl}/api/v1/products?collection_id={$collection->id}");

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    });

    test('shows single product', function () {
        $product = Product::factory()->create(['vendor_id' => $this->vendor->id]);

        $response = $this->getJson("{$this->tenantUrl}/api/v1/products/{$product->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $product->id);
    });

    test('creates product', function () {
        $response = $this->actingAs($this->user, 'api')->postJson("{$this->tenantUrl}/api/v1/products", [
            'name' => 'New Product',
            'attribute_data' => [
                'name' => 'New Product',
                'sku' => 'NEW-PRODUCT-001',
                'description' => 'A new test product',
            ],
            'status' => 'draft',
            'product_type_id' => (string) $this->productType->id,
            'brand_id' => (string) $this->brand->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.attribute_data.name', 'New Product')
            ->assertJsonPath('data.vendor_id', $this->vendor->id);

        $this->assertDatabaseHas('products', [
            'vendor_id' => $this->vendor->id,
        ]);
    });

    test('validates required fields when creating product', function () {
        $response = $this->actingAs($this->user, 'api')->postJson("{$this->tenantUrl}/api/v1/products", []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'status', 'product_type_id']);
    });

    test('validates status is valid enum', function () {
        $response = $this->actingAs($this->user, 'api')->postJson("{$this->tenantUrl}/api/v1/products", [
            'name' => 'Test Product',
            'status' => 'invalid_status',
            'product_type_id' => $this->productType->id,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    });

    test('updates product', function () {
        $product = Product::factory()->create(['vendor_id' => $this->vendor->id]);

        $response = $this->actingAs($this->user, 'api')
            ->putJson("{$this->tenantUrl}/api/v1/products/{$product->id}", [
                'attribute_data' => [
                    'name' => 'Updated Product',
                    'sku' => 'UPDATED-PRODUCT-001',
                    'description' => 'An updated test product',
                ],
                'status' => 'published',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.attribute_data.name', 'Updated Product');

        $product->refresh();
        expect($product->status)->toBe('published');
    });

    test('allows partial updates', function () {
        $product = Product::factory()->create([
            'vendor_id' => $this->vendor->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->putJson("{$this->tenantUrl}/api/v1/products/{$product->id}", [
                'status' => 'published',
            ]);

        $response->assertOk();

        $product->refresh();
        expect($product->status)->toBe('published');
    });

    test('prevents updating products from other vendors', function () {
        $otherVendor = Vendor::factory()->create();
        $product = Product::factory()->create(['vendor_id' => $otherVendor->id]);

        $response = $this->actingAs($this->user, 'api')->putJson("{$this->tenantUrl}/api/v1/products/{$product->id}", [
            'attribute_data' => [
                'name' => 'Hacked Product',
            ],
        ]);

        $response->assertForbidden();
    });

    test('deletes product', function () {
        $product = Product::factory()->create(['vendor_id' => $this->vendor->id]);

        $response = $this->actingAs($this->user, 'api')
            ->deleteJson("{$this->tenantUrl}/api/v1/products/{$product->id}");

        $response->assertOk();

        $this->assertSoftDeleted('products', [
            'id' => $product->id,
        ]);
    });

    test('prevents deleting products from other vendors', function () {
        $otherVendor = Vendor::factory()->create();
        $product = Product::factory()->create(['vendor_id' => $otherVendor->id]);

        $response = $this->actingAs($this->user, 'api')->deleteJson("{$this->tenantUrl}/api/v1/products/{$product->id}");

        $response->assertForbidden();
    });

    test('returns 404 for non-existent product', function () {
        $response = $this->actingAs($this->user, 'api')->getJson("{$this->tenantUrl}/api/v1/products/999999");

        $response->assertNotFound();
    });

    test('paginates results', function () {
        Product::factory()->count(30)->create(['vendor_id' => $this->vendor->id]);

        $response = $this->actingAs($this->user, 'api')->getJson("{$this->tenantUrl}/api/v1/products?per_page=10");

        $response->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure([
                'data',
                'links',
                'meta',
            ]);
    });
});
