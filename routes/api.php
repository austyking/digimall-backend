<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Tenant\ShowTenantBrandingController;
use App\Http\Controllers\Api\Tenant\ShowTenantConfigController;
use App\Http\Controllers\Api\TenantController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;

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

    // Additional API routes will go here
    // Route::prefix('products')->group(base_path('routes/api/products.php'));
    // Route::prefix('cart')->group(base_path('routes/api/cart.php'));
    // Route::prefix('orders')->group(base_path('routes/api/orders.php'));
});

// Admin routes for tenant management (without tenant middleware)
Route::prefix('v1/admin')->group(function (): void {
    Route::apiResource('tenants', TenantController::class);
    Route::post('tenants/{id}/settings', [TenantController::class, 'updateSettings']);
    Route::get('tenants/search', [TenantController::class, 'search']);
});
