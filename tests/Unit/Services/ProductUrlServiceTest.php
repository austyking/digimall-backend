<?php

declare(strict_types=1);

use App\DTOs\CreateProductUrlDTO;
use App\DTOs\UpdateProductUrlDTO;
use App\Repositories\Contracts\UrlRepositoryInterface;
use App\Services\ProductUrlService;
use Illuminate\Support\Collection;
use Lunar\Models\Product;
use Lunar\Models\Url;
use Mockery;

describe('ProductUrlService', function () {
    beforeEach(function () {
        $this->urlRepository = Mockery::mock(UrlRepositoryInterface::class);

        $this->service = new ProductUrlService(
            $this->urlRepository
        );
    });

    afterEach(function () {
        Mockery::close();
    });

    test('creates URL for product', function () {
        $product = Mockery::mock(Product::class)->makePartial();
        $product->id = 123;
        $language = Mockery::mock(Language::class)->makePartial();
        $language->id = 1;
        $language->code = 'en';

        $dto = new CreateProductUrlDTO(
            slug: 'test-product',
            languageId: 1,
            default: true
        );

        $expectedUrl = Mockery::mock(Url::class)->makePartial();
        $expectedUrl->slug = 'test-product';
        $expectedUrl->element_type = Product::class;
        $expectedUrl->element_id = 123;
        $expectedUrl->language_id = 1;
        $expectedUrl->default = true;

        // Mock slug exists check
        $this->urlRepository
            ->shouldReceive('slugExists')
            ->once()
            ->with('test-product', 1)
            ->andReturn(false);

        // Mock unset defaults (since default is true)
        $this->urlRepository
            ->shouldReceive('findByElementAndLanguage')
            ->once()
            ->with(123, Product::class, 1)
            ->andReturn(collect([]));

        $this->urlRepository
            ->shouldReceive('create')
            ->once()
            ->with([
                'slug' => 'test-product',
                'element_type' => Product::class,
                'element_id' => 123,
                'language_id' => 1,
                'default' => true,
            ])
            ->andReturn($expectedUrl);

        $result = $this->service->createUrl(123, $dto);

        expect($result)->toBeInstanceOf(Url::class)
            ->and($result->slug)->toBe('test-product');
    });

    test('updates URL', function () {
        $url = Mockery::mock(Url::class)->makePartial();
        $url->id = 1;
        $url->slug = 'old-slug';
        $url->element_id = 123;
        $url->language_id = 1;
        $url->default = false;

        $dto = new UpdateProductUrlDTO(
            slug: 'new-slug',
            default: true
        );

        $updatedUrl = Mockery::mock(Url::class)->makePartial();
        $updatedUrl->id = 1;
        $updatedUrl->slug = 'new-slug';
        $updatedUrl->default = true;

        // Mock find URL
        $this->urlRepository
            ->shouldReceive('find')
            ->once()
            ->with(1)
            ->andReturn($url);

        // Mock slug exists check (excluding current URL)
        $this->urlRepository
            ->shouldReceive('slugExists')
            ->once()
            ->with('new-slug', 1, 1)
            ->andReturn(false);

        // Mock unset defaults (since setting to default)
        $this->urlRepository
            ->shouldReceive('findByElementAndLanguage')
            ->once()
            ->with(123, Product::class, 1)
            ->andReturn(collect([]));

        $this->urlRepository
            ->shouldReceive('update')
            ->once()
            ->with(1, [
                'slug' => 'new-slug',
                'default' => true,
            ])
            ->andReturn($updatedUrl);

        $result = $this->service->updateUrl(1, $dto);

        expect($result)->toBeInstanceOf(Url::class)
            ->and($result->slug)->toBe('new-slug')
            ->and($result->default)->toBeTrue();
    });

    test('deletes URL and promotes another to default', function () {
        $url1 = Mockery::mock(Url::class)->makePartial();
        $url1->id = 1;
        $url1->element_id = 123;
        $url1->language_id = 1;
        $url1->default = true;

        $url2 = Mockery::mock(Url::class)->makePartial();
        $url2->id = 2;
        $url2->element_id = 123;
        $url2->language_id = 1;
        $url2->default = false;

        $this->urlRepository
            ->shouldReceive('find')
            ->once()
            ->with(1)
            ->andReturn($url1);

        $this->urlRepository
            ->shouldReceive('findByElementAndLanguage')
            ->once()
            ->with(123, Product::class, 1)
            ->andReturn(collect([$url1, $url2]));

        $this->urlRepository
            ->shouldReceive('update')
            ->once()
            ->with(2, ['default' => true]);

        $this->urlRepository
            ->shouldReceive('delete')
            ->once()
            ->with(1)
            ->andReturn(true);

        $result = $this->service->deleteUrl(1);

        expect($result)->toBeTrue();
    });

    test('generates unique slug from name', function () {
        $this->urlRepository
            ->shouldReceive('slugExists')
            ->once()
            ->with('test-product', 1)
            ->andReturn(false);

        $slug = $this->service->generateSlug('Test Product', 1);

        expect($slug)->toBe('test-product');
    });

    test('appends number to slug when duplicate exists', function () {
        $this->urlRepository
            ->shouldReceive('slugExists')
            ->once()
            ->with('test-product', 1)
            ->andReturn(true);

        $this->urlRepository
            ->shouldReceive('slugExists')
            ->once()
            ->with('test-product-1', 1)
            ->andReturn(false);

        $slug = $this->service->generateSlug('Test Product', 1);

        expect($slug)->toBe('test-product-1');
    });

    test('sets URL as default for language', function () {
        $url = Mockery::mock(Url::class)->makePartial();
        $url->id = 1;
        $url->element_id = 123;
        $url->language_id = 1;
        $url->default = false;

        $otherUrl = Mockery::mock(Url::class)->makePartial();
        $otherUrl->id = 2;
        $otherUrl->element_id = 123;
        $otherUrl->language_id = 1;
        $otherUrl->default = true;

        $this->urlRepository
            ->shouldReceive('find')
            ->once()
            ->with(1)
            ->andReturn($url);

        $this->urlRepository
            ->shouldReceive('findByElementAndLanguage')
            ->once()
            ->with(123, Product::class, 1)
            ->andReturn(collect([$url, $otherUrl]));

        // Should update other URL to not default
        $this->urlRepository
            ->shouldReceive('update')
            ->once()
            ->with(2, ['default' => false]);

        // Should update target URL to default
        $this->urlRepository
            ->shouldReceive('update')
            ->once()
            ->with(1, ['default' => true]);

        $this->service->setAsDefault(1);

        expect(true)->toBeTrue(); // Method returns void
    });

    test('gets default URL for language', function () {
        $url = Mockery::mock(Url::class)->makePartial();
        $url->slug = 'test-product';
        $url->default = true;

        $this->urlRepository
            ->shouldReceive('getDefaultUrl')
            ->once()
            ->with(123, Product::class, 1)
            ->andReturn($url);

        $result = $this->service->getDefaultUrl(123, 1);

        expect($result)->toBeInstanceOf(Url::class)
            ->and($result->slug)->toBe('test-product');
    });

    test('gets all URLs for product', function () {
        $url1 = Mockery::mock(Url::class)->makePartial();
        $url1->slug = 'test-product-en';
        $url2 = Mockery::mock(Url::class)->makePartial();
        $url2->slug = 'test-produit-fr';
        $urls = collect([$url1, $url2]);

        $this->urlRepository
            ->shouldReceive('findByElement')
            ->once()
            ->with(123, Product::class)
            ->andReturn($urls);

        $result = $this->service->getUrlsForProduct(123);

        expect($result)->toBeInstanceOf(Collection::class)
            ->and($result)->toHaveCount(2);
    });

    test('validates slug uniqueness within language', function () {
        $this->urlRepository
            ->shouldReceive('slugExists')
            ->once()
            ->with('existing-slug', 1, null)
            ->andReturn(true);

        $result = $this->service->isSlugUnique('existing-slug', 1);

        expect($result)->toBeFalse();
    });

    test('allows existing slug for same URL when updating', function () {
        $this->urlRepository
            ->shouldReceive('slugExists')
            ->once()
            ->with('existing-slug', 1, 1)
            ->andReturn(false);

        $result = $this->service->isSlugUnique('existing-slug', 1, 1);

        expect($result)->toBeTrue();
    });
});
