<?php

declare(strict_types=1);

use App\Repositories\Contracts\CustomerRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\VendorRepositoryInterface;
use App\Services\CustomerService;
use App\Services\OrderService;
use App\Services\ProductService;
use App\Services\VendorService;
use Tests\TestCase;

uses(TestCase::class);

describe('Service Layer - Dependency Injection', function () {
    test('ProductService resolves with ProductRepository injected', function () {
        $service = app(ProductService::class);

        expect($service)->toBeInstanceOf(ProductService::class);

        // Verify the service has the repository injected via reflection
        $reflection = new ReflectionClass($service);
        $property = $reflection->getProperty('productRepository');
        $property->setAccessible(true);
        $repository = $property->getValue($service);

        expect($repository)->toBeInstanceOf(app(ProductRepositoryInterface::class)::class);
    });

    test('OrderService resolves with OrderRepository injected', function () {
        $service = app(OrderService::class);

        expect($service)->toBeInstanceOf(OrderService::class);

        $reflection = new ReflectionClass($service);
        $property = $reflection->getProperty('orderRepository');
        $property->setAccessible(true);
        $repository = $property->getValue($service);

        expect($repository)->toBeInstanceOf(app(OrderRepositoryInterface::class)::class);
    });

    test('VendorService resolves with VendorRepository injected', function () {
        $service = app(VendorService::class);

        expect($service)->toBeInstanceOf(VendorService::class);

        $reflection = new ReflectionClass($service);
        $property = $reflection->getProperty('vendorRepository');
        $property->setAccessible(true);
        $repository = $property->getValue($service);

        expect($repository)->toBeInstanceOf(app(VendorRepositoryInterface::class)::class);
    });

    test('CustomerService resolves with CustomerRepository injected', function () {
        $service = app(CustomerService::class);

        expect($service)->toBeInstanceOf(CustomerService::class);

        $reflection = new ReflectionClass($service);
        $property = $reflection->getProperty('customerRepository');
        $property->setAccessible(true);
        $repository = $property->getValue($service);

        expect($repository)->toBeInstanceOf(app(CustomerRepositoryInterface::class)::class);
    });

    test('all services can be resolved from container', function () {
        $services = [
            ProductService::class,
            OrderService::class,
            VendorService::class,
            CustomerService::class,
        ];

        foreach ($services as $serviceClass) {
            $service = app($serviceClass);
            expect($service)->toBeInstanceOf($serviceClass);
        }
    });
});

describe('Service Layer - Method Contracts', function () {
    test('OrderService can call repository methods without errors', function () {
        $service = app(OrderService::class);

        // These should not throw method not found errors
        expect(method_exists($service, 'calculateTotalSales'))->toBeTrue()
            ->and(method_exists($service, 'getOrderStatistics'))->toBeTrue()
            ->and(method_exists($service, 'updateStatus'))->toBeTrue()
            ->and(method_exists($service, 'getPendingOrders'))->toBeTrue();

        // Verify repository has the methods the service needs
        $repository = app(OrderRepositoryInterface::class);
        expect(method_exists($repository, 'getTotalSales'))->toBeTrue()
            ->and(method_exists($repository, 'getByDateRange'))->toBeTrue()
            ->and(method_exists($repository, 'getPending'))->toBeTrue()
            ->and(method_exists($repository, 'getRecent'))->toBeTrue()
            ->and(method_exists($repository, 'updateStatus'))->toBeTrue();
    });

    test('ProductService can call repository methods without errors', function () {
        $service = app(ProductService::class);

        expect(method_exists($service, 'findById'))->toBeTrue()
            ->and(method_exists($service, 'searchProducts'))->toBeTrue()
            ->and(method_exists($service, 'updateStock'))->toBeTrue();

        $repository = app(ProductRepositoryInterface::class);
        expect(method_exists($repository, 'find'))->toBeTrue()
            ->and(method_exists($repository, 'search'))->toBeTrue()
            ->and(method_exists($repository, 'updateStock'))->toBeTrue();
    });

    test('VendorService can call repository methods without errors', function () {
        $service = app(VendorService::class);

        expect(method_exists($service, 'findById'))->toBeTrue()
            ->and(method_exists($service, 'approveVendor'))->toBeTrue()
            ->and(method_exists($service, 'getVendorStatistics'))->toBeTrue();

        $repository = app(VendorRepositoryInterface::class);
        expect(method_exists($repository, 'find'))->toBeTrue()
            ->and(method_exists($repository, 'approve'))->toBeTrue()
            ->and(method_exists($repository, 'getStatistics'))->toBeTrue();
    });

    test('CustomerService can call repository methods without errors', function () {
        $service = app(CustomerService::class);

        expect(method_exists($service, 'findById'))->toBeTrue()
            ->and(method_exists($service, 'verifyWithAssociation'))->toBeTrue()
            ->and(method_exists($service, 'getHirePurchaseEligibility'))->toBeTrue();

        $repository = app(CustomerRepositoryInterface::class);
        expect(method_exists($repository, 'find'))->toBeTrue()
            ->and(method_exists($repository, 'verifyWithAssociation'))->toBeTrue()
            ->and(method_exists($repository, 'getHirePurchaseEligibility'))->toBeTrue();
    });
});
