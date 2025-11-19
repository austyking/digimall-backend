<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Lunar\Models\Language;
use Lunar\Models\Product;

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

    Storage::fake('public');

    Language::firstOrCreate(
        ['code' => 'en'],
        ['name' => 'English', 'default' => true]
    );

    $this->product = Product::factory()->create(['vendor_id' => $this->vendor->id]);
});

describe('ProductMediaController', function () {
    test('uploads single image', function () {
        $file = UploadedFile::fake()->image('product.jpg');

        $response = $this->postJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/media", [
            'image' => $file,
            'collection' => 'images',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'file_name',
                    'mime_type',
                    'size',
                    'url',
                ],
            ]);

        expect($this->product->getMedia('images')->count())->toBe(1);
    });

    test('uploads multiple images', function () {
        $files = [
            UploadedFile::fake()->image('product1.jpg'),
            UploadedFile::fake()->image('product2.jpg'),
        ];

        $response = $this->postJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/media/multiple", [
            'images' => $files,
            'collection' => 'images',
        ]);

        $response->assertCreated()
            ->assertJsonCount(2, 'data');

        expect($this->product->getMedia('images')->count())->toBe(2);
    });

    test('gets all media for product', function () {
        $this->product
            ->addMedia(UploadedFile::fake()->image('test.jpg'))
            ->toMediaCollection('images');

        $response = $this->getJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/media");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'file_name',
                        'url',
                    ],
                ],
            ]);
    });

    test('gets primary image', function () {
        $this->product
            ->addMedia(UploadedFile::fake()->image('primary.jpg'))
            ->toMediaCollection('images');

        $response = $this->getJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/media/primary");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'url',
                ],
            ]);
    });

    test('returns null for primary when no images', function () {
        $response = $this->getJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/media/primary");

        $response->assertNotFound()
            ->assertJson([
                'message' => 'No primary image found',
                'data' => [
                    'url' => null,
                    'path' => null,
                ],
            ]);
    });

    test('updates media properties', function () {
        $media = $this->product
            ->addMedia(UploadedFile::fake()->image('test.jpg'))
            ->toMediaCollection('images');

        $response = $this->putJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/media/{$media->id}", [
            'name' => 'Updated Name',
            'custom_properties' => ['alt' => 'Product image'],
        ]);

        $response->assertOk();

        $media->refresh();
        expect($media->name)->toBe('Updated Name')
            ->and($media->getCustomProperty('alt'))->toBe('Product image');
    });

    test('deletes media', function () {
        $media = $this->product
            ->addMedia(UploadedFile::fake()->image('test.jpg'))
            ->toMediaCollection('images');

        $response = $this->deleteJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/media/{$media->id}");

        $response->assertOk();

        expect($this->product->getMedia('images')->count())->toBe(0);
    });

    test('reorders media items', function () {
        $media1 = $this->product
            ->addMedia(UploadedFile::fake()->image('first.jpg'))
            ->toMediaCollection('images');

        $media2 = $this->product
            ->addMedia(UploadedFile::fake()->image('second.jpg'))
            ->toMediaCollection('images');

        $response = $this->postJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/media/reorder", [
            'order' => [$media2->id, $media1->id],
        ]);

        $response->assertOk();

        $orderedMedia = $this->product->getMedia('images');
        expect($orderedMedia[0]->id)->toBe($media2->id)
            ->and($orderedMedia[1]->id)->toBe($media1->id);
    });

    test('validates image is required for single upload', function () {
        $response = $this->postJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/media", [
            'collection' => 'images',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['image']);
    });

    test('validates image is file for single upload', function () {
        $response = $this->postJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/media", [
            'image' => 'not-a-file',
            'collection' => 'images',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['image']);
    });

    test('validates images is required for multiple upload', function () {
        $response = $this->postJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/media/multiple", [
            'collection' => 'images',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['images']);
    });

    test('validates images is array for multiple upload', function () {
        $response = $this->postJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/media/multiple", [
            'images' => 'not-an-array',
            'collection' => 'images',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['images']);
    });

    test('validates order is required for reorder', function () {
        $response = $this->postJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/media/reorder", []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['order']);
    });

    test('returns 404 for non-existent product', function () {
        $response = $this->actingAs($this->user, 'api')->getJson("{$this->tenantUrl}{$this->tenantUrl}/api/v1/products/999999/media");

        $response->assertNotFound();
    });

    test('returns 404 for non-existent media', function () {
        $response = $this->deleteJson("{$this->tenantUrl}/api/v1/products/{$this->product->id}/media/999999");

        $response->assertNotFound();
    });
});
