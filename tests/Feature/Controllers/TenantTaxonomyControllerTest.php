<?php

declare(strict_types=1);

use App\Models\Brand;
use App\Models\Collection;
use App\Models\Tag;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\FieldTypes\Text;
use Lunar\Models\Attribute;
use Lunar\Models\CollectionGroup;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('TenantTaxonomyController', function () {
    beforeEach(function () {
        // Re-enable tenant middleware for feature tests (disabled globally in TestCase)
        $this->withMiddleware();

        // Seed roles and permissions first
        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\RolesAndPermissionsSeeder']);

        // Seed tenants (creates GRNMA and other tenants with proper domains)
        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\TenantSeeder']);

        // Get GRNMA tenant from seeder
        $this->tenant = Tenant::where('name', 'GRNMA')->first();

        // Create authenticated user with association-administrator role
        $this->user = User::factory()->create();
        $adminRole = Role::firstOrCreate(['name' => 'association-administrator', 'guard_name' => 'api']);
        $this->user->assignRole($adminRole);

        // Use GRNMA tenant domain from seeder
        $this->tenantUrl = 'http://shop.grnmainfonet.test';

        // Get the main collection group from seeded data
        $this->collectionGroup = CollectionGroup::where('handle', 'main')->first();

        // Manually initialize tenancy for this tenant
        tenancy()->initialize($this->tenant);

        $this->actingAs($this->user, 'api');
    });

    // ==================== CATEGORIES ====================

    describe('Categories', function () {
        test('getCategories returns paginated categories', function () {
            Collection::factory()->count(5)->create([
                'tenant_id' => $this->tenant->id,
                'collection_group_id' => $this->collectionGroup->id,
            ]);

            $response = $this->getJson("{$this->tenantUrl}/api/v1/taxonomy/categories");

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'description',
                            'slug',
                            'parent_id',
                            'children_count',
                        ],
                    ],
                    'meta' => [
                        'current_page',
                        'last_page',
                        'per_page',
                        'total',
                    ],
                    'links',
                ]);
        });

        test('getCategories supports search parameter', function () {
            Collection::factory()->create([
                'collection_group_id' => $this->collectionGroup->id,
                'attribute_data' => collect([
                    'name' => new Text('Electronics'),
                ]),
            ]);
            Collection::factory()->create([
                'collection_group_id' => $this->collectionGroup->id,
                'attribute_data' => collect([
                    'name' => new Text('Books'),
                ]),
            ]);

            $response = $this->getJson("{$this->tenantUrl}/api/v1/taxonomy/categories?search=Electronics");

            $response->assertOk();
            expect($response->json('meta.total'))->toBe(1);
            expect($response->json('data.0.name'))->toBe('Electronics');
        });

        test('createCategory creates new category', function () {
            $data = [
                'name' => 'Test Category',
                'description' => 'Test Description',
                'collection_group_id' => $this->collectionGroup->id,
            ];

            $response = $this->postJson("{$this->tenantUrl}/api/v1/taxonomy/categories", $data);

            $response->assertCreated()
                ->assertJson([
                    'data' => [
                        'name' => 'Test Category',
                        'description' => 'Test Description',
                    ],
                    'message' => 'Category created successfully',
                ]);

            $this->assertDatabaseHas('collections', [
                'attribute_data->name->value->en' => 'Test Category',
            ]);
        });

        test('createCategory validates required fields', function () {
            $response = $this->postJson("{$this->tenantUrl}/api/v1/taxonomy/categories", []);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['name']);
        });

        test('getCategory returns specific category', function () {
            $category = Collection::factory()->create([
                'collection_group_id' => $this->collectionGroup->id,
            ]);

            $response = $this->getJson("{$this->tenantUrl}/api/v1/taxonomy/categories/{$category->id}");

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'name',
                        'description',
                        'parent',
                        'children',
                    ],
                ]);
        });

        test('updateCategory updates existing category', function () {
            $category = Collection::factory()->create([
                'collection_group_id' => $this->collectionGroup->id,
            ]);

            $updateData = [
                'name' => 'Updated Category',
                'description' => 'Updated Description',
            ];

            $response = $this->putJson("{$this->tenantUrl}/api/v1/taxonomy/categories/{$category->id}", $updateData);

            $response->assertOk()
                ->assertJson([
                    'data' => [
                        'name' => 'Updated Category',
                        'description' => 'Updated Description',
                    ],
                    'message' => 'Category updated successfully',
                ]);
        });

        test('deleteCategory removes category', function () {
            $category = Collection::factory()->create([
                'collection_group_id' => $this->collectionGroup->id,
            ]);

            $response = $this->deleteJson("{$this->tenantUrl}/api/v1/taxonomy/categories/{$category->id}");

            $response->assertOk()
                ->assertJson(['message' => 'Category deleted successfully']);

            $this->assertDatabaseMissing('collections', ['id' => $category->id]);
        });

        test('getCategoryTree returns hierarchical structure', function () {
            $parent = Collection::factory()->create([
                'collection_group_id' => $this->collectionGroup->id,
            ]);
            Collection::factory()->create([
                'collection_group_id' => $this->collectionGroup->id,
                'parent_id' => $parent->id,
            ]);

            $response = $this->getJson("{$this->tenantUrl}/api/v1/taxonomy/categories/tree");

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'children',
                        ],
                    ],
                ]);
        });
    });

    // ==================== BRANDS ====================

    describe('Brands', function () {
        test('getBrands returns paginated brands', function () {
            Brand::factory()->count(5)->create();

            $response = $this->getJson("{$this->tenantUrl}/api/v1/taxonomy/brands");

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                        ],
                    ],
                    'meta',
                    'links',
                ]);
        });

        test('getBrands supports search parameter', function () {
            Brand::factory()->create(['name' => 'Apple']);
            Brand::factory()->create(['name' => 'Samsung']);

            $response = $this->getJson("{$this->tenantUrl}/api/v1/taxonomy/brands?search=Apple");

            $response->assertOk();
            expect($response->json('meta.total'))->toBe(1)
                ->and($response->json('data.0.name'))->toBe('Apple');
        });

        test('createBrand creates new brand', function () {
            $data = ['name' => 'Test Brand'];

            $response = $this->postJson("{$this->tenantUrl}/api/v1/taxonomy/brands", $data);

            $response->assertCreated()
                ->assertJson([
                    'data' => ['name' => 'Test Brand'],
                    'message' => 'Brand created successfully',
                ]);

            $this->assertDatabaseHas('brands', ['name' => 'Test Brand']);
        });

        test('createBrand validates required fields', function () {
            $response = $this->postJson("{$this->tenantUrl}/api/v1/taxonomy/brands", []);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['name']);
        });

        test('getBrand returns specific brand', function () {
            $brand = Brand::factory()->create();

            $response = $this->getJson("{$this->tenantUrl}/api/v1/taxonomy/brands/{$brand->id}");

            $response->assertOk()
                ->assertJson(['data' => ['id' => $brand->id, 'name' => $brand->name]]);
        });

        test('updateBrand updates existing brand', function () {
            $brand = Brand::factory()->create(['name' => 'Old Name']);

            $updateData = ['name' => 'New Name'];

            $response = $this->putJson("{$this->tenantUrl}/api/v1/taxonomy/brands/{$brand->id}", $updateData);

            $response->assertOk()
                ->assertJson([
                    'data' => ['name' => 'New Name'],
                    'message' => 'Brand updated successfully',
                ]);
        });

        test('deleteBrand removes brand', function () {
            $brand = Brand::factory()->create();

            $response = $this->deleteJson("{$this->tenantUrl}/api/v1/taxonomy/brands/{$brand->id}");

            $response->assertOk()
                ->assertJson(['message' => 'Brand deleted successfully']);

            $this->assertDatabaseMissing('brands', ['id' => $brand->id]);
        });
    });

    // ==================== ATTRIBUTES ====================

    describe('Attributes', function () {
        test('getAttributes returns paginated attributes', function () {
            Attribute::factory()->count(5)->create();

            $response = $this->getJson("{$this->tenantUrl}/api/v1/taxonomy/attributes");

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'type',
                            'required',
                        ],
                    ],
                    'meta',
                    'links',
                ]);
        });

        test('getAttributes supports type filter', function () {
            Attribute::factory()->create(['type' => 'text']);
            Attribute::factory()->create(['type' => 'number']);

            $response = $this->getJson("{$this->tenantUrl}/api/v1/taxonomy/attributes?type=text");

            $response->assertOk();
            expect($response->json('meta.total'))->toBe(1)
                ->and($response->json('data.0.type'))->toBe('text');
        });

        test('createAttribute creates new attribute', function () {
            $data = [
                'name' => 'Test Attribute',
                'type' => 'text',
                'required' => false,
            ];

            $response = $this->postJson("{$this->tenantUrl}/api/v1/taxonomy/attributes", $data);

            $response->assertCreated()
                ->assertJson([
                    'data' => [
                        'name' => 'Test Attribute',
                        'type' => 'text',
                        'required' => false,
                    ],
                    'message' => 'Attribute created successfully',
                ]);

            $this->assertDatabaseHas('attributes', ['name' => '{"en":"Test Attribute"}']);
        });

        test('createAttribute validates required fields', function () {
            $response = $this->postJson("{$this->tenantUrl}/api/v1/taxonomy/attributes", []);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['name', 'type']);
        });

        test('getAttribute returns specific attribute', function () {
            $attribute = Attribute::factory()->create();

            $response = $this->getJson("{$this->tenantUrl}/api/v1/taxonomy/attributes/{$attribute->id}");

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'name',
                        'type',
                        'attribute_group',
                    ],
                ]);
        });

        test('updateAttribute updates existing attribute', function () {
            $attribute = Attribute::factory()->create([
                'name' => ['en' => 'Old Name'],
            ]);

            $updateData = [
                'name' => 'New Name',
                'type' => 'number',
                'required' => true,
            ];

            $response = $this->putJson("{$this->tenantUrl}/api/v1/taxonomy/attributes/{$attribute->id}", $updateData);

            $response->assertOk()
                ->assertJson([
                    'data' => [
                        'name' => 'New Name',
                        'type' => 'number',
                        'required' => true,
                    ],
                    'message' => 'Attribute updated successfully',
                ]);
        });

        test('deleteAttribute removes attribute', function () {
            $attribute = Attribute::factory()->create();

            $response = $this->deleteJson("{$this->tenantUrl}/api/v1/taxonomy/attributes/{$attribute->id}");

            $response->assertOk()
                ->assertJson(['message' => 'Attribute deleted successfully']);

            $this->assertDatabaseMissing('attributes', ['id' => $attribute->id]);
        });
    });

    // ==================== TAGS ====================

    describe('Tags', function () {
        test('getTags returns paginated tags', function () {
            Tag::factory()->count(5)->create();

            $response = $this->getJson("{$this->tenantUrl}/api/v1/taxonomy/tags");

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'value',
                        ],
                    ],
                    'meta',
                    'links',
                ]);
        });

        test('getTags supports search parameter', function () {
            Tag::factory()->create(['value' => 'electronics']);
            Tag::factory()->create(['value' => 'books']);

            $response = $this->getJson("{$this->tenantUrl}/api/v1/taxonomy/tags?search=electronics");

            $response->assertOk();
            expect($response->json('meta.total'))->toBe(1)
                ->and($response->json('data.0.value'))->toBe('electronics');
        });

        test('createTag creates new tag', function () {
            $data = ['value' => 'test-tag'];

            $response = $this->postJson("{$this->tenantUrl}/api/v1/taxonomy/tags", $data);

            $response->assertCreated()
                ->assertJson([
                    'data' => ['value' => 'test-tag'],
                    'message' => 'Tag created successfully',
                ]);

            $this->assertDatabaseHas('tags', ['value' => 'test-tag']);
        });

        test('createTag validates required fields', function () {
            $response = $this->postJson("{$this->tenantUrl}/api/v1/taxonomy/tags", []);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['value']);
        });

        test('getTag returns specific tag', function () {
            $tag = Tag::factory()->create();

            $response = $this->getJson("{$this->tenantUrl}/api/v1/taxonomy/tags/{$tag->id}");

            $response->assertOk()
                ->assertJson(['data' => ['id' => $tag->id, 'value' => $tag->value]]);
        });

        test('updateTag updates existing tag', function () {
            $tag = Tag::factory()->create(['value' => 'old-tag']);

            $updateData = ['value' => 'new-tag'];

            $response = $this->putJson("{$this->tenantUrl}/api/v1/taxonomy/tags/{$tag->id}", $updateData);

            $response->assertOk()
                ->assertJson([
                    'data' => ['value' => 'new-tag'],
                    'message' => 'Tag updated successfully',
                ]);
        });

        test('deleteTag removes tag', function () {
            $tag = Tag::factory()->create();

            $response = $this->deleteJson("{$this->tenantUrl}/api/v1/taxonomy/tags/{$tag->id}");

            $response->assertOk()
                ->assertJson(['message' => 'Tag deleted successfully']);

            $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
        });
    });
});

// ==================== AUTHORIZATION ====================

describe('Authorization', function () {
    beforeEach(function () {
        // Re-enable tenant middleware for feature tests (disabled globally in TestCase)
        $this->withMiddleware();

        // Seed roles and permissions first
        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\RolesAndPermissionsSeeder']);

        // Seed tenants (creates GRNMA and other tenants with proper domains)
        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\TenantSeeder']);

        // Get GRNMA tenant from seeder
        $this->tenant = Tenant::where('name', 'GRNMA')->first();

        // Use GRNMA tenant domain from seeder
        $this->tenantUrl = 'http://shop.grnmainfonet.test';
    });

    test('requires authentication', function () {
        // Make request without authentication by not calling actingAs
        // The middleware should be enabled from the main beforeEach
        $response = $this->getJson("{$this->tenantUrl}/api/v1/taxonomy/categories");

        $response->assertUnauthorized();
    });

    test('requires admin role', function () {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');

        $response = $this->getJson("{$this->tenantUrl}/api/v1/taxonomy/categories");

        $response->assertForbidden();
    });
});
