<?php

declare(strict_types=1);

use App\Repositories\Contracts\CustomerRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Repositories\Contracts\VendorRepositoryInterface;
use App\Repositories\CustomerRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Repositories\TenantRepository;
use App\Repositories\VendorRepository;
use Tests\TestCase;

uses(TestCase::class);

describe('Repository Bindings', function () {
    test('resolves TenantRepository from container', function () {
        $repository = $this->app->make(TenantRepositoryInterface::class);

        expect($repository)->toBeInstanceOf(TenantRepository::class);
    });

    test('resolves ProductRepository from container', function () {
        $repository = $this->app->make(ProductRepositoryInterface::class);

        expect($repository)->toBeInstanceOf(ProductRepository::class);
    });

    test('resolves OrderRepository from container', function () {
        $repository = $this->app->make(OrderRepositoryInterface::class);

        expect($repository)->toBeInstanceOf(OrderRepository::class);
    });

    test('resolves VendorRepository from container', function () {
        $repository = $this->app->make(VendorRepositoryInterface::class);

        expect($repository)->toBeInstanceOf(VendorRepository::class);
    });

    test('resolves CustomerRepository from container', function () {
        $repository = $this->app->make(CustomerRepositoryInterface::class);

        expect($repository)->toBeInstanceOf(CustomerRepository::class);
    });

    test('bindings create new instances each time', function () {
        $repository1 = $this->app->make(ProductRepositoryInterface::class);
        $repository2 = $this->app->make(ProductRepositoryInterface::class);

        expect($repository1)->not->toBe($repository2)
            ->and($repository1)->toBeInstanceOf(ProductRepository::class)
            ->and($repository2)->toBeInstanceOf(ProductRepository::class);
    });

    test('all repository interfaces are bound', function () {
        $interfaces = [
            TenantRepositoryInterface::class,
            ProductRepositoryInterface::class,
            OrderRepositoryInterface::class,
            VendorRepositoryInterface::class,
            CustomerRepositoryInterface::class,
        ];

        foreach ($interfaces as $interface) {
            expect($this->app->bound($interface))->toBeTrue("Interface {$interface} should be bound");
        }
    });

    test('repository interfaces resolve to correct implementations', function () {
        $bindings = [
            TenantRepositoryInterface::class => TenantRepository::class,
            ProductRepositoryInterface::class => ProductRepository::class,
            OrderRepositoryInterface::class => OrderRepository::class,
            VendorRepositoryInterface::class => VendorRepository::class,
            CustomerRepositoryInterface::class => CustomerRepository::class,
        ];

        foreach ($bindings as $interface => $implementation) {
            $repository = $this->app->make($interface);
            expect($repository)->toBeInstanceOf($implementation);
        }
    });
});
