<?php

declare(strict_types=1);

use App\DTOs\CreateAttributeDTO;
use App\DTOs\CreateBrandDTO;
use App\DTOs\CreateCategoryDTO;
use App\DTOs\CreateTagDTO;
use App\DTOs\UpdateAttributeDTO;
use App\DTOs\UpdateBrandDTO;
use App\DTOs\UpdateCategoryDTO;
use App\DTOs\UpdateTagDTO;
use App\Repositories\Contracts\TaxonomyRepositoryInterface;
use App\Services\TaxonomyService;
use Lunar\Models\Attribute;
use Lunar\Models\Brand;
use Lunar\Models\Collection;
use Lunar\Models\Tag;
use Mockery;

describe('TaxonomyService', function () {
    beforeEach(function () {
        $this->mockRepository = Mockery::mock(TaxonomyRepositoryInterface::class);
        $this->service = new TaxonomyService($this->mockRepository);
    });

    afterEach(function () {
        Mockery::close();
    });

    // ==================== CATEGORIES ====================

    describe('Categories', function () {
        test('getCategoriesPaginated delegates to repository', function () {
            $paginator = Mockery::mock(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class);

            $this->mockRepository
                ->shouldReceive('getCategoriesPaginated')
                ->once()
                ->with(1, 15, 'search', 'name', 'asc')
                ->andReturn($paginator);

            $result = $this->service->getCategoriesPaginated(1, 15, 'search', 'name', 'asc');

            expect($result)->toBe($paginator);
        });

        test('createCategory creates category via repository', function () {
            $dto = new CreateCategoryDTO('Test Category', 'Test Description', null, 1);
            $category = Mockery::mock(Collection::class);

            $this->mockRepository
                ->shouldReceive('createCategory')
                ->once()
                ->with($dto->toArray())
                ->andReturn($category);

            $result = $this->service->createCategory($dto);

            expect($result)->toBe($category);
        });

        test('updateCategory updates category via repository', function () {
            $category = Mockery::mock(Collection::class);
            $dto = new UpdateCategoryDTO('Updated Category', 'Updated Description');
            $updatedCategory = Mockery::mock(Collection::class);

            $this->mockRepository
                ->shouldReceive('updateCategory')
                ->once()
                ->with($category, $dto->toArray())
                ->andReturn($updatedCategory);

            $result = $this->service->updateCategory($category, $dto);

            expect($result)->toBe($updatedCategory);
        });

        test('deleteCategory throws exception when category has children', function () {
            $category = Mockery::mock(Collection::class);
            $category->shouldReceive('children->exists')->andReturn(true);

            expect(fn () => $this->service->deleteCategory($category))
                ->toThrow(\Exception::class, 'Cannot delete category with child categories. Please reassign or delete child categories first.');
        });

        test('deleteCategory throws exception when category is used by products', function () {
            $category = Mockery::mock(Collection::class);
            $category->shouldReceive('children->exists')->andReturn(false);
            $category->shouldReceive('products->exists')->andReturn(true);

            expect(fn () => $this->service->deleteCategory($category))
                ->toThrow(\Exception::class, 'Cannot delete category that is assigned to products. Please reassign products first.');
        });

        test('deleteCategory succeeds when no dependencies', function () {
            $category = Mockery::mock(Collection::class);
            $category->shouldReceive('children->exists')->andReturn(false);
            $category->shouldReceive('products->exists')->andReturn(false);

            $this->mockRepository
                ->shouldReceive('deleteCategory')
                ->once()
                ->with($category)
                ->andReturn(true);

            $result = $this->service->deleteCategory($category);

            expect($result)->toBeTrue();
        });

        test('getCategoryTree delegates to repository', function () {
            $collection = Mockery::mock(\Illuminate\Database\Eloquent\Collection::class);

            $this->mockRepository
                ->shouldReceive('getCategoryTree')
                ->once()
                ->andReturn($collection);

            $result = $this->service->getCategoryTree();

            expect($result)->toBe($collection);
        });
    });

    // ==================== BRANDS ====================

    describe('Brands', function () {
        test('getBrandsPaginated delegates to repository', function () {
            $paginator = Mockery::mock(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class);

            $this->mockRepository
                ->shouldReceive('getBrandsPaginated')
                ->once()
                ->with(1, 15, 'search', 'name', 'asc')
                ->andReturn($paginator);

            $result = $this->service->getBrandsPaginated(1, 15, 'search', 'name', 'asc');

            expect($result)->toBe($paginator);
        });

        test('createBrand creates brand via repository', function () {
            $dto = new CreateBrandDTO('Test Brand');
            $brand = Mockery::mock(Brand::class);

            $this->mockRepository
                ->shouldReceive('createBrand')
                ->once()
                ->with($dto->toArray())
                ->andReturn($brand);

            $result = $this->service->createBrand($dto);

            expect($result)->toBe($brand);
        });

        test('updateBrand updates brand via repository', function () {
            $brand = Mockery::mock(Brand::class);
            $dto = new UpdateBrandDTO('Updated Brand');
            $updatedBrand = Mockery::mock(Brand::class);

            $this->mockRepository
                ->shouldReceive('updateBrand')
                ->once()
                ->with($brand, $dto->toArray())
                ->andReturn($updatedBrand);

            $result = $this->service->updateBrand($brand, $dto);

            expect($result)->toBe($updatedBrand);
        });

        test('deleteBrand throws exception when brand is used by products', function () {
            $brand = Mockery::mock(Brand::class);
            $brand->shouldReceive('products->exists')->andReturn(true);

            expect(fn () => $this->service->deleteBrand($brand))
                ->toThrow(\Exception::class, 'Cannot delete brand that is assigned to products. Please reassign products first.');
        });

        test('deleteBrand succeeds when no dependencies', function () {
            $brand = Mockery::mock(Brand::class);
            $brand->shouldReceive('products->exists')->andReturn(false);

            $this->mockRepository
                ->shouldReceive('deleteBrand')
                ->once()
                ->with($brand)
                ->andReturn(true);

            $result = $this->service->deleteBrand($brand);

            expect($result)->toBeTrue();
        });
    });

    // ==================== ATTRIBUTES ====================

    describe('Attributes', function () {
        test('getAttributesPaginated delegates to repository', function () {
            $paginator = Mockery::mock(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class);

            $this->mockRepository
                ->shouldReceive('getAttributesPaginated')
                ->once()
                ->with(1, 15, 'search', 'text', 'name', 'asc')
                ->andReturn($paginator);

            $result = $this->service->getAttributesPaginated(1, 15, 'search', 'text', 'name', 'asc');

            expect($result)->toBe($paginator);
        });

        test('createAttribute creates attribute via repository', function () {
            $dto = new CreateAttributeDTO('Test Attribute', 'text', 'test_handle', 'main', 1, false, false, '1');
            $attribute = Mockery::mock(Attribute::class);

            $this->mockRepository
                ->shouldReceive('createAttribute')
                ->once()
                ->with($dto->toArray())
                ->andReturn($attribute);

            $result = $this->service->createAttribute($dto);

            expect($result)->toBe($attribute);
        });

        test('updateAttribute updates attribute via repository', function () {
            $attribute = Mockery::mock(Attribute::class);
            $dto = new UpdateAttributeDTO('Updated Attribute', 'number', 'updated_handle', 'main', 2, true, false);
            $updatedAttribute = Mockery::mock(Attribute::class);

            $this->mockRepository
                ->shouldReceive('updateAttribute')
                ->once()
                ->with($attribute, $dto->toArray())
                ->andReturn($updatedAttribute);

            $result = $this->service->updateAttribute($attribute, $dto);

            expect($result)->toBe($updatedAttribute);
        });

        test('deleteAttribute throws exception when attribute is used by products', function () {
            $attribute = Mockery::mock(Attribute::class)->makePartial();
            $attribute->id = 1;

            $this->mockRepository
                ->shouldReceive('isAttributeUsed')
                ->once()
                ->with($attribute)
                ->andReturn(true);

            expect(fn () => $this->service->deleteAttribute($attribute))
                ->toThrow(\Exception::class, 'Cannot delete attribute that is assigned to items. Please remove attribute assignments first.');
        });

        test('deleteAttribute throws exception when attribute is used by variants', function () {
            $attribute = Mockery::mock(Attribute::class)->makePartial();
            $attribute->id = 2;

            $this->mockRepository
                ->shouldReceive('isAttributeUsed')
                ->once()
                ->with($attribute)
                ->andReturn(true);

            expect(fn () => $this->service->deleteAttribute($attribute))
                ->toThrow(\Exception::class, 'Cannot delete attribute that is assigned to items. Please remove attribute assignments first.');
        });

        test('deleteAttribute succeeds when no dependencies', function () {
            $attribute = Mockery::mock(Attribute::class)->makePartial();
            $attribute->id = 3;

            $this->mockRepository
                ->shouldReceive('isAttributeUsed')
                ->once()
                ->with($attribute)
                ->andReturn(false);

            $this->mockRepository
                ->shouldReceive('deleteAttribute')
                ->once()
                ->with($attribute)
                ->andReturn(true);

            $result = $this->service->deleteAttribute($attribute);

            expect($result)->toBeTrue();
        });
    });

    // ==================== TAGS ====================

    describe('Tags', function () {
        test('getTagsPaginated delegates to repository', function () {
            $paginator = Mockery::mock(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class);

            $this->mockRepository
                ->shouldReceive('getTagsPaginated')
                ->once()
                ->with(1, 15, 'search', 'value', 'asc')
                ->andReturn($paginator);

            $result = $this->service->getTagsPaginated(1, 15, 'search', 'value', 'asc');

            expect($result)->toBe($paginator);
        });

        test('createTag creates tag via repository', function () {
            $dto = new CreateTagDTO('test-tag');
            $tag = Mockery::mock(Tag::class);

            $this->mockRepository
                ->shouldReceive('createTag')
                ->once()
                ->with($dto->toArray())
                ->andReturn($tag);

            $result = $this->service->createTag($dto);

            expect($result)->toBe($tag);
        });

        test('updateTag updates tag via repository', function () {
            $tag = Mockery::mock(Tag::class);
            $dto = new UpdateTagDTO('updated-tag');
            $updatedTag = Mockery::mock(Tag::class);

            $this->mockRepository
                ->shouldReceive('updateTag')
                ->once()
                ->with($tag, $dto->toArray())
                ->andReturn($updatedTag);

            $result = $this->service->updateTag($tag, $dto);

            expect($result)->toBe($updatedTag);
        });

        test('deleteTag throws exception when tag is used by products', function () {
            $tag = Mockery::mock(Tag::class)->makePartial();
            $tag->id = 1;

            $this->mockRepository
                ->shouldReceive('isTagUsed')
                ->once()
                ->with($tag)
                ->andReturn(true);

            expect(fn () => $this->service->deleteTag($tag))
                ->toThrow(\Exception::class, 'Cannot delete tag that is assigned to items. Please remove tag assignments first.');
        });

        test('deleteTag succeeds when no dependencies', function () {
            $tag = Mockery::mock(Tag::class)->makePartial();
            $tag->id = 2;

            $this->mockRepository
                ->shouldReceive('isTagUsed')
                ->once()
                ->with($tag)
                ->andReturn(false);

            $this->mockRepository
                ->shouldReceive('deleteTag')
                ->once()
                ->with($tag)
                ->andReturn(true);

            $result = $this->service->deleteTag($tag);

            expect($result)->toBeTrue();
        });
    });
});
