<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\AuthRepository;
use App\Repositories\Contracts\AuthRepositoryInterface;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use App\Repositories\Contracts\LanguageRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\PriceRepositoryInterface;
use App\Repositories\Contracts\ProductCollectionRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\ProductVariantRepositoryInterface;
use App\Repositories\Contracts\TaxonomyRepositoryInterface;
use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Repositories\Contracts\UrlRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\VendorRepositoryInterface;
use App\Repositories\CustomerRepository;
use App\Repositories\LanguageRepository;
use App\Repositories\OrderRepository;
use App\Repositories\PriceRepository;
use App\Repositories\ProductCollectionRepository;
use App\Repositories\ProductRepository;
use App\Repositories\ProductVariantRepository;
use App\Repositories\TaxonomyRepository;
use App\Repositories\TenantRepository;
use App\Repositories\UrlRepository;
use App\Repositories\UserRepository;
use App\Repositories\VendorRepository;
use App\Services\AdminTenantService;
use App\Services\Contracts\FileUploadServiceInterface;
use App\Services\Contracts\UserServiceInterface;
use App\Services\FileUploadService;
use App\Services\TaxonomyService;
use App\Services\TenantStatisticsService;
use App\Services\UserService;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
use Lunar\Admin\Support\Facades\LunarPanel;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        LunarPanel::register();

        // Register repository bindings
        $this->app->bind(AuthRepositoryInterface::class, AuthRepository::class);
        $this->app->bind(TenantRepositoryInterface::class, TenantRepository::class);
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(ProductVariantRepositoryInterface::class, ProductVariantRepository::class);
        $this->app->bind(ProductCollectionRepositoryInterface::class, ProductCollectionRepository::class);
        $this->app->bind(PriceRepositoryInterface::class, PriceRepository::class);
        $this->app->bind(UrlRepositoryInterface::class, UrlRepository::class);
        $this->app->bind(LanguageRepositoryInterface::class, LanguageRepository::class);
        $this->app->bind(OrderRepositoryInterface::class, OrderRepository::class);
        $this->app->bind(VendorRepositoryInterface::class, VendorRepository::class);
        $this->app->bind(CustomerRepositoryInterface::class, CustomerRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(TaxonomyRepositoryInterface::class, TaxonomyRepository::class);

        // Register service bindings
        $this->app->bind(FileUploadServiceInterface::class, FileUploadService::class);
        $this->app->bind(UserServiceInterface::class, UserService::class);
        $this->app->bind(TaxonomyService::class);

        // Register Admin services
        $this->app->singleton(AdminTenantService::class);
        $this->app->singleton(TenantStatisticsService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Lunar\Facades\Telemetry::optOut();

        // Register custom Lunar models
        // This tells Lunar to use our custom Product model throughout the ecosystem
        \Lunar\Facades\ModelManifest::replace(
            \Lunar\Models\Contracts\Product::class,
            \App\Models\Product::class,
        );

        \Lunar\Facades\ModelManifest::replace(
            \Lunar\Models\Contracts\Collection::class,
            \App\Models\Collection::class,
        );

        \Lunar\Facades\ModelManifest::replace(
            \Lunar\Models\Contracts\Brand::class,
            \App\Models\Brand::class,
        );

        \Lunar\Facades\ModelManifest::replace(
            \Lunar\Models\Contracts\Tag::class,
            \App\Models\Tag::class,
        );

        \Lunar\Facades\ModelManifest::replace(
            \Lunar\Models\Contracts\Attribute::class,
            \App\Models\Attribute::class,
        );

        // Configure Passport encryption keys
        Passport::loadKeysFrom(storage_path());

        // Configure Passport token lifetimes
        Passport::tokensExpireIn(now()->addDays(1));
        Passport::refreshTokensExpireIn(now()->addDays(30));
        Passport::personalAccessTokensExpireIn(now()->addMonths(6));
    }
}
