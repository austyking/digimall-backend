<?php

declare(strict_types=1);

use App\DTOs\AttachProductsToCollectionDTO;

describe('AttachProductsToCollectionDTO', function () {
    test('creates DTO with collection ID and product IDs', function () {
        $dto = new AttachProductsToCollectionDTO(
            collectionId: 123,
            productIds: [1, 2, 3]
        );

        expect($dto->collectionId)->toBe(123)
            ->and($dto->productIds)->toBe([1, 2, 3])
            ->and($dto->productIds)->toBeArray()
            ->and($dto->productIds)->toHaveCount(3);
    });

    test('accepts empty product IDs array', function () {
        $dto = new AttachProductsToCollectionDTO(
            collectionId: 456,
            productIds: []
        );

        expect($dto->productIds)->toBeEmpty();
    });

    test('accepts single product ID', function () {
        $dto = new AttachProductsToCollectionDTO(
            collectionId: 789,
            productIds: [10]
        );

        expect($dto->productIds)->toHaveCount(1)
            ->and($dto->productIds[0])->toBe(10);
    });
});
