<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Tenant\ShowTenantBrandingController;
use App\Http\Controllers\Api\Tenant\ShowTenantConfigController;
use App\Http\Controllers\Api\V1\Admin\AdminTenantController;
use App\Http\Controllers\Api\V1\Admin\TenantStatisticsController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Product\ProductAssociationController;
use App\Http\Controllers\Api\V1\Product\ProductAvailabilityController;
use App\Http\Controllers\Api\V1\Product\ProductCollectionController;
use App\Http\Controllers\Api\V1\Product\ProductController;
use App\Http\Controllers\Api\V1\Product\ProductInventoryController;
use App\Http\Controllers\Api\V1\Product\ProductMediaController;
use App\Http\Controllers\Api\V1\Product\ProductUrlController;
use App\Http\Controllers\Api\V1\Product\ProductVariantController;
use App\Http\Controllers\Api\V1\VendorController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;

// Authentication routes (public, no tenant middleware for admin login)
Route::prefix('v1/auth')->group(function (): void {
    Route::post('/login', LoginController::class)->name('auth.login');
});

// Backwards-compatible vendor login alias (some frontends call /vendor/login)
Route::post('v1/vendor/login', LoginController::class)->name('vendor.login');

// API v1 routes with tenant middleware
Route::prefix('v1')->middleware([InitializeTenancyByDomain::class])->group(function (): void {

    // Tenant configuration endpoints
    Route::get('/config', ShowTenantConfigController::class);
    Route::get('/branding', ShowTenantBrandingController::class);

    // Health check endpoint
    Route::get('/health', function (): JsonResponse {
        $tenant = tenancy()->tenant;

        return response()->json([
            'status' => 'ok',
            'tenant_id' => $tenant?->id,
            'tenant_name' => $tenant?->name,
        ]);
    });

    // Vendor endpoints
    Route::prefix('vendors')->group(function (): void {
        // Public registration (no authentication required)
        Route::post('/register', [VendorController::class, 'register'])
            ->name('vendors.register');

        // Vendor listing and details
        Route::get('/', [VendorController::class, 'index'])
            ->middleware(['auth:api'])
            ->name('vendors.index');

        Route::get('/{id}', [VendorController::class, 'show'])
            ->name('vendors.show');

        // Vendor profile management (vendor only)
        Route::put('/{id}', [VendorController::class, 'update'])
            ->middleware(['auth:api'])
            ->name('vendors.update');

        // Admin actions (admin/system-administrator only)
        Route::middleware(['auth:api'])->group(function (): void {
            Route::post('/{id}/approve', [VendorController::class, 'approve'])
                ->name('vendors.approve');

            Route::post('/{id}/reject', [VendorController::class, 'reject'])
                ->name('vendors.reject');

            Route::post('/{id}/suspend', [VendorController::class, 'suspend'])
                ->name('vendors.suspend');
        });
    });

    // Product endpoints
    Route::prefix('products')->group(function (): void {
        // Public product listing and details
        Route::get('/', [ProductController::class, 'index'])
            ->name('products.index');

        Route::get('/{productId}', [ProductController::class, 'show'])
            ->name('products.show');

        // Product management (vendor/admin only)
        Route::middleware(['auth:api'])->group(function (): void {
            Route::post('/', [ProductController::class, 'store'])
                ->name('products.store');

            Route::put('/{productId}', [ProductController::class, 'update'])
                ->name('products.update');

            Route::delete('/{productId}', [ProductController::class, 'destroy'])
                ->name('products.delete');

            // Product collections
            Route::prefix('/{productId}/collections')->group(function (): void {
                Route::get('/', [ProductCollectionController::class, 'collections'])
                    ->name('products.collections.index');
                Route::post('/attach', [ProductCollectionController::class, 'attach'])
                    ->name('products.collections.attach');
                Route::post('/detach', [ProductCollectionController::class, 'detach'])
                    ->name('products.collections.detach');
            });

            // Product availability
            Route::prefix('/{productId}/availability')->group(function (): void {
                Route::get('/', [ProductAvailabilityController::class, 'show'])
                    ->name('products.availability.show');
                Route::put('/', [ProductAvailabilityController::class, 'update'])
                    ->name('products.availability.update');
            });

            // Product inventory
            Route::prefix('/{productId}/inventory')->group(function (): void {
                Route::get('/', [ProductInventoryController::class, 'show'])
                    ->name('products.inventory.show');
                Route::put('/', [ProductInventoryController::class, 'update'])
                    ->name('products.inventory.update');
            });

            // Product variants
            Route::prefix('/{productId}/variants')->group(function (): void {
                Route::get('/', [ProductVariantController::class, 'index'])
                    ->name('products.variants.index');
                Route::post('/', [ProductVariantController::class, 'store'])
                    ->name('products.variants.store');
                Route::put('/{variantId}', [ProductVariantController::class, 'update'])
                    ->name('products.variants.update');
                Route::delete('/{variantId}', [ProductVariantController::class, 'destroy'])
                    ->name('products.variants.delete');
            });

            // Product associations (cross-sell, up-sell, alternate)
            Route::prefix('/{productId}/associations')->group(function (): void {
                Route::get('/', [ProductAssociationController::class, 'index'])
                    ->name('products.associations.index');
                Route::get('/cross-sell', [ProductAssociationController::class, 'getCrossSell'])
                    ->name('products.associations.cross-sell');
                Route::get('/up-sell', [ProductAssociationController::class, 'getUpSell'])
                    ->name('products.associations.up-sell');
                Route::get('/alternate', [ProductAssociationController::class, 'getAlternate'])
                    ->name('products.associations.alternate');
                Route::post('/cross-sell', [ProductAssociationController::class, 'attachCrossSell'])
                    ->name('products.associations.attach-cross-sell');
                Route::post('/up-sell', [ProductAssociationController::class, 'attachUpSell'])
                    ->name('products.associations.attach-up-sell');
                Route::post('/alternate', [ProductAssociationController::class, 'attachAlternate'])
                    ->name('products.associations.attach-alternate');
                Route::post('/detach', [ProductAssociationController::class, 'detach'])
                    ->name('products.associations.detach');
            });

            // Product media (images/files)
            Route::prefix('/{productId}/media')->group(function (): void {
                Route::get('/', [ProductMediaController::class, 'index'])
                    ->name('products.media.index');
                Route::post('/', [ProductMediaController::class, 'store'])
                    ->name('products.media.store');
                Route::post('/multiple', [ProductMediaController::class, 'storeMultiple'])
                    ->name('products.media.store-multiple');
                Route::get('/primary', [ProductMediaController::class, 'getPrimary'])
                    ->name('products.media.primary');
                Route::get('/{mediaId}', [ProductMediaController::class, 'show'])
                    ->name('products.media.show');
                Route::put('/{mediaId}', [ProductMediaController::class, 'update'])
                    ->name('products.media.update');
                Route::delete('/{mediaId}', [ProductMediaController::class, 'destroy'])
                    ->name('products.media.delete');
                Route::post('/reorder', [ProductMediaController::class, 'reorder'])
                    ->name('products.media.reorder');
            });

            // Product URLs (SEO-friendly slugs)
            Route::prefix('/{productId}/urls')->group(function (): void {
                Route::get('/', [ProductUrlController::class, 'index'])
                    ->name('products.urls.index');
                Route::post('/', [ProductUrlController::class, 'store'])
                    ->name('products.urls.store');
                Route::get('/default', [ProductUrlController::class, 'getDefault'])
                    ->name('products.urls.default');
                Route::post('/generate-slug', [ProductUrlController::class, 'generateSlug'])
                    ->name('products.urls.generate-slug');
                Route::get('/{urlId}', [ProductUrlController::class, 'show'])
                    ->name('products.urls.show');
                Route::put('/{urlId}', [ProductUrlController::class, 'update'])
                    ->name('products.urls.update');
                Route::delete('/{urlId}', [ProductUrlController::class, 'destroy'])
                    ->name('products.urls.delete');
                Route::post('/{urlId}/set-default', [ProductUrlController::class, 'setDefault'])
                    ->name('products.urls.set-default');
            });
        });

        // Low stock products (vendor-specific)
        Route::get('/low-stock', [ProductInventoryController::class, 'lowStock'])
            ->middleware(['auth:api'])
            ->name('products.low-stock');
    });

    // Additional API routes will go here
    // Route::prefix('cart')->group(base_path('routes/api/cart.php'));
    // Route::prefix('orders')->group(base_path('routes/api/orders.php'));
});

// Admin routes for tenant management (without tenant middleware)
Route::prefix('v1/admin')->middleware(['auth:api'])->group(function (): void {
    // Tenant management endpoints
    Route::prefix('tenants')->group(function (): void {
        // List and filter tenants
        Route::get('/', [AdminTenantController::class, 'index'])->name('admin.tenants.index');

        // Create tenant
        Route::post('/', [AdminTenantController::class, 'store'])->name('admin.tenants.store');

        // Get inactive tenants
        Route::get('/inactive', [AdminTenantController::class, 'inactive'])->name('admin.tenants.inactive');

        // Single tenant operations
        Route::get('/{id}', [AdminTenantController::class, 'show'])->name('admin.tenants.show');
        Route::put('/{id}', [AdminTenantController::class, 'update'])->name('admin.tenants.update');
        Route::delete('/{id}', [AdminTenantController::class, 'destroy'])->name('admin.tenants.destroy');

        // Activation/Deactivation
        Route::post('/{id}/activate', [AdminTenantController::class, 'activate'])->name('admin.tenants.activate');
        Route::post('/{id}/deactivate', [AdminTenantController::class, 'deactivate'])->name('admin.tenants.deactivate');

        // Bulk operations
        Route::post('/bulk/activate', [AdminTenantController::class, 'bulkActivate'])->name('admin.tenants.bulk-activate');
        Route::post('/bulk/deactivate', [AdminTenantController::class, 'bulkDeactivate'])->name('admin.tenants.bulk-deactivate');
    });

    // Tenant statistics endpoints
    Route::prefix('statistics')->group(function (): void {
        Route::get('/summary', [TenantStatisticsController::class, 'summary'])->name('admin.statistics.summary');
        Route::get('/growth', [TenantStatisticsController::class, 'growth'])->name('admin.statistics.growth');
        Route::get('/distribution', [TenantStatisticsController::class, 'distribution'])->name('admin.statistics.distribution');
    });
});
