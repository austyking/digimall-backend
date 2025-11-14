<?php

declare(strict_types=1);

use App\DTOs\UpdateProductVariantDTO;
use Illuminate\Validation\ValidationException;

describe('UpdateProductVariantDTO', function () {
    test('creates DTO with all fields nullable', function () {
        $dto = new UpdateProductVariantDTO(
            sku: 'UPDATED-SKU',
            stock: 200,
            purchasable: 'in_stock',
            price: 299.99,
            unitQuantity: 5,
            taxClassId: 3,
            backorder: 15,
            values: [6, 7, 8]
        );

        expect($dto->sku)->toBe('UPDATED-SKU')
            ->and($dto->stock)->toBe(200)
            ->and($dto->purchasable)->toBe('in_stock')
            ->and($dto->price)->toBe(299.99)
            ->and($dto->unitQuantity)->toBe(5)
            ->and($dto->taxClassId)->toBe(3)
            ->and($dto->backorder)->toBe(15)
            ->and($dto->values)->toBe([6, 7, 8]);
    });

    test('creates DTO from partial request data', function () {
        $data = [
            'sku' => 'PARTIAL-UPDATE',
            'price' => 89.99,
        ];

        $dto = UpdateProductVariantDTO::fromRequest($data);

        expect($dto->sku)->toBe('PARTIAL-UPDATE')
            ->and($dto->price)->toBe(89.99)
            ->and($dto->stock)->toBeNull()
            ->and($dto->purchasable)->toBeNull()
            ->and($dto->unitQuantity)->toBeNull();
    });

    test('validates purchasable enum when provided', function () {
        $data = [
            'purchasable' => 'bad_enum_value',
        ];

        UpdateProductVariantDTO::fromRequest($data);
    })->throws(ValidationException::class);

    test('validates stock is not negative when provided', function () {
        $data = [
            'stock' => -20,
        ];

        UpdateProductVariantDTO::fromRequest($data);
    })->throws(ValidationException::class);

    test('validates price is not negative when provided', function () {
        $data = [
            'price' => -50.00,
        ];

        UpdateProductVariantDTO::fromRequest($data);
    })->throws(ValidationException::class);

    test('validates backorder is not negative when provided', function () {
        $data = [
            'backorder' => -10,
        ];

        UpdateProductVariantDTO::fromRequest($data);
    })->throws(ValidationException::class);

    test('toArray filters out null values', function () {
        $dto = new UpdateProductVariantDTO(
            sku: 'FILTERED-SKU',
            stock: null,
            purchasable: 'always',
            price: null,
            unitQuantity: null,
            taxClassId: 5,
            backorder: null,
            values: null
        );

        $array = $dto->toArray();

        expect($array)->toBe([
            'sku' => 'FILTERED-SKU',
            'purchasable' => 'always',
            'tax_class_id' => 5,
        ])
            ->and($array)->not()->toHaveKey('stock')
            ->and($array)->not()->toHaveKey('price')
            ->and($array)->not()->toHaveKey('backorder');
    });

    test('allows all null values for partial updates', function () {
        $dto = new UpdateProductVariantDTO;

        expect($dto->sku)->toBeNull()
            ->and($dto->stock)->toBeNull()
            ->and($dto->purchasable)->toBeNull()
            ->and($dto->price)->toBeNull()
            ->and($dto->unitQuantity)->toBeNull()
            ->and($dto->taxClassId)->toBeNull()
            ->and($dto->backorder)->toBeNull()
            ->and($dto->values)->toBeNull();
    });

    test('toArray returns empty array when all values are null', function () {
        $dto = new UpdateProductVariantDTO;

        $array = $dto->toArray();

        expect($array)->toBeEmpty();
    });
});
