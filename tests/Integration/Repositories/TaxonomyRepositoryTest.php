<?php

declare(strict_types=1);

use App\Repositories\TaxonomyRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\FieldTypes\TranslatedText;
use Lunar\Models\Attribute;
use Lunar\Models\AttributeGroup;
use Lunar\Models\Brand;
use Lunar\Models\Collection;
use Lunar\Models\CollectionGroup;
use Lunar\Models\Language;
use Lunar\Models\Tag;
use Stancl\Tenancy\Database\Models\Tenant;
use Stancl\Tenancy\Facades\Tenancy;

uses(RefreshDatabase::class);

describe('TaxonomyRepository', function () {
    beforeEach(function () {
        $this->repository = app(TaxonomyRepository::class);

        // Create tenant using factory like in TenantRepositoryTest
        $this->tenant = \App\Models\Tenant::factory()->create(['name' => 'GRNMA']);

        // Initialize tenancy for this tenant
        tenancy()->initialize($this->tenant);

        $this->collectionGroup = CollectionGroup::factory()->create();
        $this->attributeGroup = AttributeGroup::factory()->create([
            'attributable_type' => 'product',
        ]);

        // Create default language required by Lunar
        Language::factory()->create([
            'code' => 'en',
            'name' => 'English',
            'default' => true,
        ]);
    });

    // ==================== CATEGORIES (COLLECTIONS) ====================

    describe('Categories', function () {
        test('getCategoriesPaginated returns paginated results', function () {
            Collection::factory()->count(5)->create([
                'collection_group_id' => $this->collectionGroup->id,
                'attribute_data' => [
                    'name' => new TranslatedText(['en' => 'Category']),
                    'description' => new TranslatedText(['en' => 'Description']),
                ],
            ]);

            $result = $this->repository->getCategoriesPaginated(1, 3);

            expect ($result)->toBeInstanceOf (LengthAwarePaginator::class)
                ->and ($result->total ())->toBe (5)
                ->and ($result->perPage ())->toBe (3)
                ->and ($result->currentPage ())->toBe (1);
        });

        test('getCategoriesPaginated filters by search term', function () {
            Collection::factory()->create([
                'collection_group_id' => $this->collectionGroup->id,
                'attribute_data' => [
                    'name' => new TranslatedText(['en' => 'Electronics']),
                    'description' => new TranslatedText(['en' => 'Electronic items']),
                ],
            ]);
            Collection::factory()->create([
                'collection_group_id' => $this->collectionGroup->id,
                'attribute_data' => [
                    'name' => new TranslatedText(['en' => 'Books']),
                    'description' => new TranslatedText(['en' => 'Reading materials']),
                ],
            ]);

            $result = $this->repository->getCategoriesPaginated(1, 10, 'Electronics');

            expect ($result->total ())->toBe (1)
                ->and ($result->items ()[0]->translateAttribute ('name'))->toBe ('Electronics');
        });

        test('findCategory returns category with relationships', function () {
            $category = Collection::factory()->create([
                'collection_group_id' => $this->collectionGroup->id,
                'attribute_data' => [
                    'name' => new TranslatedText(['en' => 'Test Category']),
                ],
            ]);

            $found = $this->repository->findCategory($category->id);

            expect ($found)->toBeInstanceOf (Collection::class)
                ->and ($found->id)->toBe ($category->id);
        });

        test('createCategory creates new category', function () {
            $data = [
                'collection_group_id' => $this->collectionGroup->id,
                'attribute_data' => [
                    'name' => new TranslatedText(['en' => 'Test Category']),
                    'description' => new TranslatedText(['en' => 'Test Description']),
                ],
            ];

            $category = $this->repository->createCategory($data);

            expect ($category)->toBeInstanceOf (Collection::class)
                ->and ($category->translateAttribute ('name'))->toBe ('Test Category')
                ->and ($category->translateAttribute ('description'))->toBe ('Test Description');
        });

        test('updateCategory updates existing category', function () {
            $category = Collection::factory()->create([
                'collection_group_id' => $this->collectionGroup->id,
                'attribute_data' => [
                    'name' => new TranslatedText(['en' => 'Old Name']),
                ],
            ]);

            $updateData = [
                'attribute_data' => [
                    'name' => new TranslatedText(['en' => 'Updated Category']),
                ],
            ];

            $updated = $this->repository->updateCategory($category, $updateData);

            expect($updated->translateAttribute('name'))->toBe('Updated Category');
        });

        test('deleteCategory removes category', function () {
            $category = Collection::factory()->create([
                'collection_group_id' => $this->collectionGroup->id,
                'attribute_data' => [
                    'name' => new TranslatedText(['en' => 'Test']),
                ],
            ]);

            $result = $this->repository->deleteCategory($category);

            expect ($result)->toBeTrue ()
                ->and (Collection::find ($category->id))->toBeNull ();
        });

        test('getCategoryTree returns hierarchical structure', function () {
            $parent = Collection::factory()->create([
                'collection_group_id' => $this->collectionGroup->id,
                'attribute_data' => [
                    'name' => new TranslatedText(['en' => 'Parent']),
                ],
            ]);
            $child = Collection::factory()->create([
                'collection_group_id' => $this->collectionGroup->id,
                'parent_id' => $parent->id,
                'attribute_data' => [
                    'name' => new TranslatedText(['en' => 'Child']),
                ],
            ]);

            $tree = $this->repository->getCategoryTree();

            expect ($tree)->toBeInstanceOf (\Illuminate\Database\Eloquent\Collection::class)
                ->and ($tree->count ())->toBeGreaterThan (0);
        });
    });

    // ==================== BRANDS ====================

    describe('Brands', function () {
        test('getBrandsPaginated returns paginated results', function () {
            Brand::factory()->count(5)->create();

            $result = $this->repository->getBrandsPaginated(1, 3);

            expect ($result)->toBeInstanceOf (LengthAwarePaginator::class)
                ->and ($result->total ())->toBe (5);
        });

        test('getBrandsPaginated filters by search term', function () {
            Brand::factory()->create(['name' => 'Apple']);
            Brand::factory()->create(['name' => 'Samsung']);

            $result = $this->repository->getBrandsPaginated(1, 10, 'Apple');

            expect ($result->total ())->toBe (1)
                ->and ($result->items ()[0]->name)->toBe ('Apple');
        });

        test('createBrand creates new brand', function () {
            $data = ['name' => 'Test Brand'];

            $brand = $this->repository->createBrand($data);

            expect ($brand)->toBeInstanceOf (Brand::class)
                ->and ($brand->name)->toBe ('Test Brand');
        });

        test('updateBrand updates existing brand', function () {
            $brand = Brand::factory()->create(['name' => 'Old Name']);

            $updateData = ['name' => 'New Name'];

            $updated = $this->repository->updateBrand($brand, $updateData);

            expect($updated->name)->toBe('New Name');
        });

        test('deleteBrand removes brand', function () {
            $brand = Brand::factory()->create();

            $result = $this->repository->deleteBrand($brand);

            expect ($result)->toBeTrue ()
                ->and (Brand::find ($brand->id))->toBeNull ();
        });
    });

    // ==================== ATTRIBUTES ====================

    describe('Attributes', function () {
        test('getAttributesPaginated returns paginated results', function () {
            Attribute::factory()->count(5)->create([
                'attribute_type' => 'product',
                'attribute_group_id' => $this->attributeGroup->id,
            ]);

            $result = $this->repository->getAttributesPaginated(1, 3);

            expect ($result)->toBeInstanceOf (LengthAwarePaginator::class)
                ->and ($result->total ())->toBe (5);
        });

        test('getAttributesPaginated filters by type', function () {
            Attribute::factory()->create([
                'attribute_type' => 'product',
                'attribute_group_id' => $this->attributeGroup->id,
                'type' => 'Lunar\\FieldTypes\\Text',
            ]);
            Attribute::factory()->create([
                'attribute_type' => 'product',
                'attribute_group_id' => $this->attributeGroup->id,
                'type' => 'Lunar\\FieldTypes\\Number',
            ]);

            $result = $this->repository->getAttributesPaginated(1, 10, null, 'Lunar\\FieldTypes\\Text');

            expect ($result->total ())->toBe (1)
                ->and ($result->items ()[0]->type)->toBe ('Lunar\\FieldTypes\\Text');
        });

        test('createAttribute creates new attribute', function () {
            $data = [
                'attribute_type' => 'product',
                'attribute_group_id' => $this->attributeGroup->id,
                'name' => ['en' => 'Test Attribute'],
                'handle' => 'test_attribute',
                'type' => 'Lunar\\FieldTypes\\Text',
                'required' => false,
                'position' => 1,
                'section' => 'main',
                'default_value' => null,
                'configuration' => [],
                'system' => false,
                'description' => ['en' => 'Test description'],
            ];

            $attribute = $this->repository->createAttribute($data);

            expect ($attribute)->toBeInstanceOf (Attribute::class)
                ->and ($attribute->name)->toHaveKey ('en', 'Test Attribute');
        });

        test('updateAttribute updates existing attribute', function () {
            $attribute = Attribute::factory()->create([
                'attribute_type' => 'product',
                'attribute_group_id' => $this->attributeGroup->id,
                'name' => ['en' => 'Old Name'],
            ]);

            $updateData = ['name' => ['en' => 'New Name']];

            $updated = $this->repository->updateAttribute($attribute, $updateData);

            expect($updated->name)->toHaveKey('en', 'New Name');
        });

        test('deleteAttribute removes attribute', function () {
            $attribute = Attribute::factory()->create([
                'attribute_type' => 'product',
                'attribute_group_id' => $this->attributeGroup->id,
            ]);

            $result = $this->repository->deleteAttribute($attribute);

            expect ($result)->toBeTrue ()
                ->and (Attribute::find ($attribute->id))->toBeNull ();
        });
    });

    // ==================== TAGS ====================

    describe('Tags', function () {
        test('getTagsPaginated returns paginated results', function () {
            Tag::factory()->count(5)->create();

            $result = $this->repository->getTagsPaginated(1, 3);

            expect ($result)->toBeInstanceOf (LengthAwarePaginator::class)
                ->and ($result->total ())->toBe (5);
        });

        test('getTagsPaginated filters by search term', function () {
            Tag::factory()->create(['value' => 'electronics']);
            Tag::factory()->create(['value' => 'books']);

            $result = $this->repository->getTagsPaginated(1, 10, 'electronics');

            expect ($result->total ())->toBe (1)
                ->and ($result->items ()[0]->value)->toBe ('electronics');
        });

        test('createTag creates new tag', function () {
            $data = ['value' => 'test-tag'];

            $tag = $this->repository->createTag($data);

            expect ($tag)->toBeInstanceOf (Tag::class)
                ->and ($tag->value)->toBe ('test-tag');
        });

        test('updateTag updates existing tag', function () {
            $tag = Tag::factory()->create(['value' => 'old-tag']);

            $updateData = ['value' => 'new-tag'];

            $updated = $this->repository->updateTag($tag, $updateData);

            expect($updated->value)->toBe('new-tag');
        });

        test('deleteTag removes tag', function () {
            $tag = Tag::factory()->create();

            $result = $this->repository->deleteTag($tag);

            expect ($result)->toBeTrue ()
                ->and (Tag::find ($tag->id))->toBeNull ();
        });
    });
});
