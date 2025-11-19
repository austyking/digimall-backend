<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Models\Language;
use Lunar\Models\Product;
use Lunar\Models\Url;

uses(RefreshDatabase::class);

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

    $this->language = Language::firstOrCreate(
        ['code' => 'en'],
        ['name' => 'English', 'default' => true]
    );

    $this->product = Product::factory()->create(['vendor_id' => $this->vendor->id]);
});

describe('ProductUrlController', function () {
    test('lists all product URLs', function () {
        Url::factory()->create([
            'element_type' => Product::class,
            'element_id' => $this->product->id,
            'language_id' => $this->language->id,
            'slug' => 'test-product',
            'default' => true,
        ]);

        $response = $this->getJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/urls");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'slug',
                        'language' => [
                            'id',
                            'name',
                            'code',
                        ],
                        'default',
                    ],
                ],
            ]);
    });

    test('creates new URL', function () {
        $response = $this->postJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/urls", [
            'slug' => 'new-product-url',
            'language_id' => $this->language->id,
            'default' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.slug', 'new-product-url')
            ->assertJsonPath('data.default', true);

        $this->assertDatabaseHas('urls', [
            'element_type' => Product::class,
            'element_id' => $this->product->id,
            'slug' => 'new-product-url',
        ]);
    });

    test('gets default URL for language', function () {
        Url::factory()->create([
            'element_type' => Product::class,
            'element_id' => $this->product->id,
            'language_id' => $this->language->id,
            'slug' => 'default-url',
            'default' => true,
        ]);

        $response = $this->getJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/urls/default?language_code=en");

        $response->assertOk()
            ->assertJsonPath('data.slug', 'default-url')
            ->assertJsonPath('data.default', true);
    });

    test('returns null when no default URL exists', function () {
        $response = $this->getJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/urls/default?language_code=en");

        $response->assertNotFound()
            ->assertJson(['data' => null]);
    });

    test('generates unique slug from name', function () {
        $response = $this->postJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/urls/generate-slug", [
            'name' => 'Test Product Name',
            'language_id' => $this->language->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'slug',
                ],
            ]);

        expect($response->json('data.slug'))->toContain('test-product-name');
    });

    test('updates URL slug', function () {
        $url = Url::factory()->create([
            'element_type' => Product::class,
            'element_id' => $this->product->id,
            'language_id' => $this->language->id,
            'slug' => 'old-slug',
            'default' => false,
        ]);

        $response = $this->putJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/urls/{$url->id}", [
            'slug' => 'new-slug',
            'default' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.slug', 'new-slug')
            ->assertJsonPath('data.default', true);

        $url->refresh();
        expect($url->slug)->toBe('new-slug')
            ->and($url->default)->toBeTrue();
    });

    test('deletes URL and promotes another to default', function () {
        $url1 = Url::factory()->create([
            'element_type' => Product::class,
            'element_id' => $this->product->id,
            'language_id' => $this->language->id,
            'slug' => 'default-url',
            'default' => true,
        ]);

        $url2 = Url::factory()->create([
            'element_type' => Product::class,
            'element_id' => $this->product->id,
            'language_id' => $this->language->id,
            'slug' => 'alternate-url',
            'default' => false,
        ]);

        $response = $this->deleteJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/urls/{$url1->id}");

        $response->assertOk();

        $this->assertDatabaseMissing('urls', [
            'id' => $url1->id,
        ]);

        $url2->refresh();
        expect($url2->default)->toBeTrue();
    });

    test('sets URL as default for language', function () {
        $url1 = Url::factory()->create([
            'element_type' => Product::class,
            'element_id' => $this->product->id,
            'language_id' => $this->language->id,
            'slug' => 'first-url',
            'default' => true,
        ]);

        $url2 = Url::factory()->create([
            'element_type' => Product::class,
            'element_id' => $this->product->id,
            'language_id' => $this->language->id,
            'slug' => 'second-url',
            'default' => false,
        ]);

        $response = $this->postJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/urls/{$url2->id}/set-default");

        $response->assertOk();

        $url1->refresh();
        $url2->refresh();

        expect($url1->default)->toBeFalse()
            ->and($url2->default)->toBeTrue();
    });

    test('validates slug is required', function () {
        $response = $this->postJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/urls", [
            'language_id' => $this->language->id,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['slug']);
    });

    test('validates slug is unique within language', function () {
        Url::factory()->create([
            'element_type' => Product::class,
            'element_id' => $this->product->id,
            'language_id' => $this->language->id,
            'slug' => 'duplicate-slug',
            'default' => true,
        ]);

        $response = $this->postJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/urls", [
            'slug' => 'duplicate-slug',
            'language_id' => $this->language->id,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['slug']);
    });

    test('validates language_id is required', function () {
        $response = $this->postJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/urls", [
            'slug' => 'test-slug',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['language_id']);
    });

    test('validates language exists', function () {
        $response = $this->postJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/urls", [
            'slug' => 'test-slug',
            'language_id' => 999999,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['language_id']);
    });

    test('returns 404 for non-existent product', function () {
        $response = $this->actingAs($this->user, 'api')->getJson("{$this->tenantUrl}/api/v1/products/999999/urls");

        $response->assertNotFound();
    });

    test('returns 404 for non-existent URL', function () {
        $response = $this->getJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/urls/999999");

        $response->assertNotFound();
    });
});
