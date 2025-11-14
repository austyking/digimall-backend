<?php

declare(strict_types=1);

use App\DTOs\CreateProductVariantDTO;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Lunar\Models\Language;
use Lunar\Models\TaxClass;

uses(RefreshDatabase::class);

describe('CreateProductVariantDTO', function () {
    beforeEach(function () {
        Language::factory()->create(['code' => 'en', 'default' => true]);
    });

    test('creates DTO with all required fields', function () {
        $dto = new CreateProductVariantDTO(
            sku: 'TEST-SKU-001',
            stock: 100,
            purchasable: 'always',
            price: 99.99,
            unitQuantity: 1,
            taxClassId: null,
            backorder: 0,
            values: null
        );

        expect($dto->sku)->toBe('TEST-SKU-001')
            ->and($dto->stock)->toBe(100)
            ->and($dto->purchasable)->toBe('always')
            ->and($dto->price)->toBe(99.99)
            ->and($dto->unitQuantity)->toBe(1)
            ->and($dto->backorder)->toBe(0);
    });

    test('creates DTO from valid request data', function () {
        $taxClass = TaxClass::factory()->create();

        $data = [
            'sku' => 'TEST-SKU-002',
            'stock' => 50,
            'purchasable' => 'in_stock',
            'price' => 149.99,
            'unit_quantity' => 2,
            'tax_class_id' => $taxClass->id,
            'backorder' => 10,
            'values' => [],
        ];

        $dto = CreateProductVariantDTO::fromRequest($data);

        expect($dto->sku)->toBe('TEST-SKU-002')
            ->and($dto->stock)->toBe(50)
            ->and($dto->purchasable)->toBe('in_stock')
            ->and($dto->price)->toBe(149.99)
            ->and($dto->unitQuantity)->toBe(2)
            ->and($dto->taxClassId)->toBe($taxClass->id)
            ->and($dto->backorder)->toBe(10)
            ->and($dto->values)->toBe([]);
    });

    test('validates purchasable enum values', function () {
        $data = [
            'sku' => 'TEST-SKU-003',
            'stock' => 50,
            'purchasable' => 'invalid_value',
            'price' => 149.99,
        ];

        CreateProductVariantDTO::fromRequest($data);
    })->throws(ValidationException::class);

    test('validates stock is not negative', function () {
        $data = [
            'sku' => 'TEST-SKU-004',
            'stock' => -5,
            'purchasable' => 'always',
            'price' => 149.99,
        ];

        CreateProductVariantDTO::fromRequest($data);
    })->throws(ValidationException::class);

    test('validates price is not negative', function () {
        $data = [
            'sku' => 'TEST-SKU-005',
            'stock' => 50,
            'purchasable' => 'always',
            'price' => -10.00,
        ];

        CreateProductVariantDTO::fromRequest($data);
    })->throws(ValidationException::class);

    test('validates backorder is not negative', function () {
        $data = [
            'sku' => 'TEST-SKU-006',
            'stock' => 50,
            'purchasable' => 'backorder',
            'price' => 149.99,
            'backorder' => -5,
        ];

        CreateProductVariantDTO::fromRequest($data);
    })->throws(ValidationException::class);

    test('converts DTO to array', function () {
        $dto = new CreateProductVariantDTO(
            sku: 'TEST-SKU-007',
            stock: 75,
            purchasable: 'backorder',
            price: 199.99,
            unitQuantity: 3,
            taxClassId: 2,
            backorder: 20,
            values: [4, 5]
        );

        $array = $dto->toArray();

        expect($array)->toBe([
            'sku' => 'TEST-SKU-007',
            'stock' => 75,
            'purchasable' => 'backorder',
            'price' => 199.99,
            'unit_quantity' => 3,
            'tax_class_id' => 2,
            'backorder' => 20,
            'values' => [4, 5],
            'currency_id' => null,
        ]);
    });

    test('uses default values when optional fields are omitted', function () {
        $data = [
            'sku' => 'TEST-SKU-008',
            'stock' => 30,
            'purchasable' => 'always',
            'price' => 59.99,
        ];

        $dto = CreateProductVariantDTO::fromRequest($data);

        expect($dto->unitQuantity)->toBe(1)
            ->and($dto->taxClassId)->toBeNull()
            ->and($dto->backorder)->toBe(0)
            ->and($dto->values)->toBeNull();
    });
});
