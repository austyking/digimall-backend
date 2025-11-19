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

describe('Taxonomy DTOs', function () {
    describe('CreateCategoryDTO', function () {
        test('creates DTO with required fields', function () {
            $dto = new CreateCategoryDTO('Test Category', 'Test Description');

            expect ($dto->name)->toBe ('Test Category')
                ->and ($dto->description)->toBe ('Test Description')
                ->and ($dto->collectionGroupId)->toBeNull ();
        });

        test('creates DTO with collection group ID', function () {
            $dto = new CreateCategoryDTO('Test Category', 'Test Description', null, 1);

            expect($dto->collectionGroupId)->toBe(1);
        });

        test('toArray returns correct structure for Lunar', function () {
            $dto = new CreateCategoryDTO('Test Category', 'Test Description', 1, 1);

            $array = $dto->toArray();

            expect ($array)->toHaveKey ('collection_group_id', 1)
                ->and ($array)->toHaveKey ('attribute_data')
                ->and ($array['attribute_data']['name'])->toBeInstanceOf (\Lunar\FieldTypes\TranslatedText::class)
                ->and ($array['attribute_data']['description'])->toBeInstanceOf (\Lunar\FieldTypes\TranslatedText::class);
        });

        test('fromRequest creates DTO from request data', function () {
            $request = Mockery::mock(\Illuminate\Http\Request::class);
            $request->shouldReceive('input')->with('name')->andReturn('Request Category');
            $request->shouldReceive('input')->with('description')->andReturn('Request Description');
            $request->shouldReceive('input')->with('parent_id')->andReturn(123);
            $request->shouldReceive('input')->with('collection_group_id')->andReturn(2);

            $dto = CreateCategoryDTO::fromRequest($request);

            expect ($dto->name)->toBe ('Request Category')
                ->and ($dto->description)->toBe ('Request Description')
                ->and ($dto->parentId)->toBe (123)
                ->and ($dto->collectionGroupId)->toBe (2)
                ->and ($dto->collectionGroupId)->toBe (2);
        });
    });

    describe('UpdateCategoryDTO', function () {
        test('creates DTO with required fields', function () {
            $dto = new UpdateCategoryDTO('Updated Category', 'Updated Description');

            expect ($dto->name)->toBe ('Updated Category')
                ->and ($dto->description)->toBe ('Updated Description')
                ->and ($dto->collectionGroupId)->toBeNull ();
        });

        test('toArray returns correct structure for Lunar', function () {
            $dto = new UpdateCategoryDTO('Updated Category', 'Updated Description', 2, '2');

            $array = $dto->toArray();

            expect ($array)->toHaveKey ('collection_group_id', '2')
                ->and ($array)->toHaveKey ('attribute_data')
                ->and ($array['attribute_data']['name'])->toBeInstanceOf (\Lunar\FieldTypes\TranslatedText::class)
                ->and ($array['attribute_data']['description'])->toBeInstanceOf (\Lunar\FieldTypes\TranslatedText::class);
        });

        test('fromRequest creates DTO from request data', function () {
            $request = Mockery::mock(\Illuminate\Http\Request::class);
            $request->shouldReceive('input')->with('name')->andReturn('Updated Category');
            $request->shouldReceive('input')->with('description')->andReturn('Updated Description');
            $request->shouldReceive('input')->with('parent_id')->andReturn(456);
            $request->shouldReceive('input')->with('collection_group_id')->andReturn('3');

            $dto = UpdateCategoryDTO::fromRequest($request);

            expect ($dto->name)->toBe ('Updated Category')
                ->and ($dto->description)->toBe ('Updated Description')
                ->and ($dto->parentId)->toBe (456)
                ->and ($dto->collectionGroupId)->toBe ('3');
        });
    });

    describe('CreateBrandDTO', function () {
        test('creates DTO with name', function () {
            $dto = new CreateBrandDTO('Test Brand');

            expect($dto->name)->toBe('Test Brand');
        });

        test('toArray returns correct structure', function () {
            $dto = new CreateBrandDTO('Test Brand');

            $array = $dto->toArray();

            expect($array)->toHaveKey('name', 'Test Brand');
        });

        test('fromRequest creates DTO from request data', function () {
            $request = Mockery::mock(\Illuminate\Http\Request::class);
            $request->shouldReceive('input')->with('name')->andReturn('Request Brand');

            $dto = CreateBrandDTO::fromRequest($request);

            expect($dto->name)->toBe('Request Brand');
        });
    });

    describe('UpdateBrandDTO', function () {
        test('creates DTO with name', function () {
            $dto = new UpdateBrandDTO('Updated Brand');

            expect($dto->name)->toBe('Updated Brand');
        });

        test('toArray returns correct structure', function () {
            $dto = new UpdateBrandDTO('Updated Brand');

            $array = $dto->toArray();

            expect($array)->toHaveKey('name', 'Updated Brand');
        });

        test('fromRequest creates DTO from request data', function () {
            $request = Mockery::mock(\Illuminate\Http\Request::class);
            $request->shouldReceive('input')->with('name')->andReturn('Updated Brand');

            $dto = UpdateBrandDTO::fromRequest($request);

            expect($dto->name)->toBe('Updated Brand');
        });
    });

    describe('CreateAttributeDTO', function () {
        test('creates DTO with required fields', function () {
            $dto = new CreateAttributeDTO('Test Attribute', 'text', null, null, null, false);

            expect ($dto->name)->toBe ('Test Attribute')
                ->and ($dto->type)->toBe ('text')
                ->and ($dto->required)->toBe (false);
        });

        test('fromRequest creates DTO from request data', function () {
            $request = Mockery::mock(\Illuminate\Http\Request::class);
            $request->shouldReceive('input')->with('name')->andReturn('Request Attribute');
            $request->shouldReceive('input')->with('type')->andReturn('number');
            $request->shouldReceive('input')->with('handle')->andReturn('test_handle');
            $request->shouldReceive('input')->with('section')->andReturn('main');
            $request->shouldReceive('input')->with('position')->andReturn(5);
            $request->shouldReceive('boolean')->with('required')->andReturn(true);
            $request->shouldReceive('boolean')->with('system')->andReturn(false);
            $request->shouldReceive('input')->with('attribute_group_id')->andReturn('group-123');
            $request->shouldReceive('input')->with('configuration')->andReturn(['key' => 'value']);
            $request->shouldReceive('input')->with('attribute_type')->andReturn('product');

            $dto = CreateAttributeDTO::fromRequest($request);

            expect ($dto->name)->toBe ('Request Attribute')
                ->and ($dto->type)->toBe ('number')
                ->and ($dto->handle)->toBe ('test_handle')
                ->and ($dto->required)->toBe (true);
        });
    });

    describe('UpdateAttributeDTO', function () {
        test('creates DTO with required fields', function () {
            $dto = new UpdateAttributeDTO('Updated Attribute', 'number', null, null, null, true);

            expect ($dto->name)->toBe ('Updated Attribute')
                ->and ($dto->type)->toBe ('number')
                ->and ($dto->required)->toBe (true);
        });

        test('toArray returns correct structure', function () {
            $dto = new UpdateAttributeDTO('Updated Attribute', 'number', null, null, null, true);

            $array = $dto->toArray();

            expect ($array)->toHaveKey ('name')
                ->and ($array)->toHaveKey ('type')
                ->and ($array)->toHaveKey ('required', true);
        });

        test('fromRequest creates DTO from request data', function () {
            $request = Mockery::mock(\Illuminate\Http\Request::class);
            $request->shouldReceive('input')->with('name')->andReturn('Updated Attribute');
            $request->shouldReceive('input')->with('type')->andReturn('text');
            $request->shouldReceive('input')->with('handle')->andReturn('updated_handle');
            $request->shouldReceive('input')->with('section')->andReturn('details');
            $request->shouldReceive('input')->with('position')->andReturn(10);
            $request->shouldReceive('boolean')->with('required')->andReturn(false);
            $request->shouldReceive('boolean')->with('system')->andReturn(true);
            $request->shouldReceive('input')->with('attribute_group_id')->andReturn('group-456');
            $request->shouldReceive('input')->with('configuration')->andReturn(['updated' => 'config']);
            $request->shouldReceive('input')->with('attribute_type')->andReturn('variant');

            $dto = UpdateAttributeDTO::fromRequest($request);

            expect ($dto->name)->toBe ('Updated Attribute')
                ->and ($dto->type)->toBe ('text')
                ->and ($dto->handle)->toBe ('updated_handle')
                ->and ($dto->required)->toBe (false);
        });
    });

    describe('CreateTagDTO', function () {
        test('creates DTO with value', function () {
            $dto = new CreateTagDTO('test-tag');

            expect($dto->value)->toBe('test-tag');
        });

        test('toArray returns correct structure', function () {
            $dto = new CreateTagDTO('test-tag');

            $array = $dto->toArray();

            expect($array)->toHaveKey('value', 'test-tag');
        });

        test('fromRequest creates DTO from request data', function () {
            $request = Mockery::mock(\Illuminate\Http\Request::class);
            $request->shouldReceive('input')->with('value')->andReturn('request-tag');

            $dto = CreateTagDTO::fromRequest($request);

            expect($dto->value)->toBe('request-tag');
        });
    });

    describe('UpdateTagDTO', function () {
        test('creates DTO with value', function () {
            $dto = new UpdateTagDTO('updated-tag');

            expect($dto->value)->toBe('updated-tag');
        });

        test('toArray returns correct structure', function () {
            $dto = new UpdateTagDTO('updated-tag');

            $array = $dto->toArray();

            expect($array)->toHaveKey('value', 'updated-tag');
        });

        test('fromRequest creates DTO from request data', function () {
            $request = Mockery::mock(\Illuminate\Http\Request::class);
            $request->shouldReceive('input')->with('value')->andReturn('updated-tag');

            $dto = UpdateTagDTO::fromRequest($request);

            expect($dto->value)->toBe('updated-tag');
        });
    });
});
