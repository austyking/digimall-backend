<?php

declare(strict_types=1);

use App\DTOs\AttachProductsToCollectionDTO;
use App\DTOs\CreateProductVariantDTO;
use App\DTOs\UpdateProductVariantDTO;
use App\Models\Product;
use App\Repositories\Contracts\PriceRepositoryInterface;
use App\Repositories\Contracts\ProductCollectionRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\ProductVariantRepositoryInterface;
use App\Services\ProductService;
use Lunar\Models\Currency;
use Lunar\Models\Price;
use Lunar\Models\ProductVariant;
use Mockery;

describe('ProductService', function () {
    beforeEach(function () {
        $this->productRepo = Mockery::mock(ProductRepositoryInterface::class);
        $this->variantRepo = Mockery::mock(ProductVariantRepositoryInterface::class);
        $this->collectionRepo = Mockery::mock(ProductCollectionRepositoryInterface::class);
        $this->priceRepo = Mockery::mock(PriceRepositoryInterface::class);

        $this->service = new ProductService(
            $this->productRepo,
            $this->variantRepo,
            $this->collectionRepo,
            $this->priceRepo
        );
    });

    afterEach(function () {
        Mockery::close();
    });

    test('attaches products to collection using DTO', function () {
        $dto = new AttachProductsToCollectionDTO(
            collectionId: 123,
            productIds: [1, 2]
        );

        $this->collectionRepo->shouldReceive('attachProducts')
            ->once()
            ->with(123, [1, 2])
            ->andReturnNull();

        $result = $this->service->attachToCollection($dto);

        expect($result)->toBeNull();
    });

    test('detaches products from collection', function () {
        $this->collectionRepo->shouldReceive('detachProducts')
            ->once()
            ->with(456, [3])
            ->andReturnNull();

        $result = $this->service->detachFromCollection(456, [3]);

        expect($result)->toBeNull();
    });

    test('creates variant with price', function () {
        $product = Mockery::mock(Product::class)->makePartial();
        $product->id = 123;
        $variant = Mockery::mock(ProductVariant::class)->makePartial();
        $variant->id = 456;

        // Mock currency instead of creating in DB
        $currency = Mockery::mock(Currency::class)->makePartial();
        $currency->id = 1;

        $dto = new CreateProductVariantDTO(
            sku: 'TEST-SKU',
            stock: 100,
            purchasable: 'always',
            price: 99.99,
            unitQuantity: 1,
            taxClassId: null,
            backorder: 0,
            values: null
        );

        $this->productRepo->shouldReceive('find')
            ->once()
            ->with(123)
            ->andReturn($product);

        $this->variantRepo->shouldReceive('create')
            ->once()
            ->with([
                'product_id' => 123,
                'sku' => 'TEST-SKU',
                'stock' => 100,
                'purchasable' => 'always',
                'unit_quantity' => 1,
                'tax_class_id' => null,
                'backorder' => 0,
            ])
            ->andReturn($variant);

        $this->priceRepo->shouldReceive('getDefaultCurrency')
            ->once()
            ->andReturn($currency);

        $price = Mockery::mock(Price::class);
        $this->priceRepo->shouldReceive('createForPriceable')
            ->once()
            ->with(456, ProductVariant::class, 99.99, $currency->id)
            ->andReturn($price);

        // Mock the prices relationship on the variant
        $pricesRelation = Mockery::mock('Illuminate\Database\Eloquent\Relations\MorphMany');
        $pricesRelation->shouldReceive('create')
            ->once()
            ->with([
                'price' => 99.99,
                'currency_id' => 1,
            ])
            ->andReturn($price);

        $variant->shouldReceive('prices')
            ->once()
            ->andReturn($pricesRelation);

        $variant->shouldReceive('fresh')
            ->once()
            ->with(['prices.currency', 'values.option'])
            ->andReturn($variant);

        $result = $this->service->createVariant(123, $dto);

        expect($result)->toBe($variant);
    });

    test('updates variant without price change', function () {
        $product = Mockery::mock(Product::class)->makePartial();
        $product->id = 123;
        $variant = Mockery::mock(ProductVariant::class)->makePartial();
        $variant->id = 456;
        $variant->product_id = 123;

        $dto = new UpdateProductVariantDTO(
            sku: 'UPDATED-SKU',
            stock: 150,
            purchasable: null,
            price: null
        );

        $this->productRepo->shouldReceive('find')
            ->once()
            ->with(123)
            ->andReturn($product);

        $this->variantRepo->shouldReceive('find')
            ->once()
            ->with(456)
            ->andReturn($variant);

        $this->variantRepo->shouldReceive('update')
            ->once()
            ->with(456, [
                'sku' => 'UPDATED-SKU',
                'stock' => 150,
            ])
            ->andReturn($variant);

        $variant->shouldReceive('fresh')
            ->once()
            ->with(['prices.currency', 'values.option'])
            ->andReturn($variant);

        $result = $this->service->updateVariant(123, 456, $dto);

        expect($result)->toBe($variant);
    });

    test('updates variant with price change', function () {
        $product = Mockery::mock(Product::class)->makePartial();
        $product->id = 123;
        $variant = Mockery::mock(ProductVariant::class)->makePartial();
        $variant->id = 456;
        $variant->product_id = 123;
        $currency = Mockery::mock(Currency::class)->makePartial();
        $currency->id = 1;

        $dto = new UpdateProductVariantDTO(
            sku: null,
            stock: null,
            purchasable: null,
            price: 149.99
        );

        $this->productRepo->shouldReceive('find')
            ->once()
            ->with(123)
            ->andReturn($product);

        $this->variantRepo->shouldReceive('find')
            ->once()
            ->with(456)
            ->andReturn($variant);

        $this->priceRepo->shouldReceive('getDefaultCurrency')
            ->once()
            ->andReturn($currency);

        $this->priceRepo->shouldReceive('findByPriceableAndCurrency')
            ->once()
            ->with(456, ProductVariant::class, 1, 1)
            ->andReturn(null);

        $price = Mockery::mock(Price::class);
        $this->priceRepo->shouldReceive('createForPriceable')
            ->once()
            ->with(456, ProductVariant::class, 149.99, 1, 1)
            ->andReturn($price);

        $variant->shouldReceive('fresh')
            ->once()
            ->with(['prices.currency', 'values.option'])
            ->andReturn($variant);

        $result = $this->service->updateVariant(123, 456, $dto);

        expect($result)->toBe($variant);
    });

    test('deletes variant', function () {
        $product = Mockery::mock(Product::class)->makePartial();
        $product->id = 123;
        $variant = Mockery::mock(ProductVariant::class)->makePartial();
        $variant->id = 789;
        $variant->product_id = 123;

        $this->productRepo->shouldReceive('find')
            ->once()
            ->with(123)
            ->andReturn($product);

        $this->variantRepo->shouldReceive('find')
            ->once()
            ->with(789)
            ->andReturn($variant);

        $this->variantRepo->shouldReceive('delete')
            ->once()
            ->with(789)
            ->andReturn(true);

        $result = $this->service->deleteVariant(123, 789);

        expect($result)->toBeTrue();
    });

    test('finds product by ID', function () {
        $product = Mockery::mock(Product::class)->makePartial();
        $product->id = 999;

        $this->productRepo->shouldReceive('find')
            ->once()
            ->with(999)
            ->andReturn($product);

        $result = $this->service->findById(999);

        expect($result)->toBe($product);
    });
});
