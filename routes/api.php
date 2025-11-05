<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Tenant\ShowTenantBrandingController;
use App\Http\Controllers\Api\Tenant\ShowTenantConfigController;
use App\Http\Controllers\Api\TenantController;
use App\Http\Controllers\Api\V1\Admin\AdminTenantController;
use App\Http\Controllers\Api\V1\Admin\TenantStatisticsController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;

// API v1 routes with tenant middleware
Route::prefix('v1')->middleware([InitializeTenancyByDomain::class])->group(function (): void {

    // Authentication routes (public)
    Route::post('/auth/login', LoginController::class)->name('auth.login');

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

    // Additional API routes will go here
    // Route::prefix('products')->group(base_path('routes/api/products.php'));
    // Route::prefix('cart')->group(base_path('routes/api/cart.php'));
    // Route::prefix('orders')->group(base_path('routes/api/orders.php'));
});

// Admin routes for tenant management (without tenant middleware)
Route::prefix('v1/admin')->middleware(['auth:api'])->group(function (): void {
    // Tenant management endpoints
    Route::prefix('tenants')->group(function (): void {
        // List and filter tenants
        Route::get('/', [AdminTenantController::class, 'index'])->name('admin.tenants.index');

        // Get inactive tenants
        Route::get('/inactive', [AdminTenantController::class, 'inactive'])->name('admin.tenants.inactive');

        // Single tenant operations
        Route::get('/{id}', [AdminTenantController::class, 'show'])->name('admin.tenants.show');
        Route::put('/{id}', [AdminTenantController::class, 'update'])->name('admin.tenants.update');

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

    // Legacy tenant routes (keeping for backward compatibility)
    Route::apiResource('tenants-legacy', TenantController::class);
    Route::post('tenants-legacy/{id}/settings', [TenantController::class, 'updateSettings']);
    Route::get('tenants-legacy/search', [TenantController::class, 'search']);
});
