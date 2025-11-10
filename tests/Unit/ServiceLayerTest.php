<?php

declare(strict_types=1);

use App\Services\CustomerService;
use App\Services\OrderService;
use App\Services\ProductService;
use App\Services\VendorService;

describe('Service Layer - Dependency Injection', function () {
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
