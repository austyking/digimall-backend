<?php

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;

// API v1 routes with tenant middleware
Route::prefix('v1')->middleware([InitializeTenancyByDomain::class])->group(function () {

    // Tenant configuration endpoint
    Route::get('/config', function () {
        $tenant = tenancy()->tenant;

        if (! $tenant) {
            return response()->json([
                'error' => 'No tenant context found',
            ], 400);
        }

        return response()->json([
            'tenant' => [
                'name' => $tenant->name,
                'display_name' => $tenant->display_name,
                'logo_url' => $tenant->logo_url,
                'theme' => $tenant->settings['theme'] ?? [
                    'primary_color' => '#1976d2',
                    'secondary_color' => '#dc004e',
                ],
            ],
            'features' => $tenant->settings['features'] ?? [],
            'payment_gateways' => $tenant->settings['payment_gateways'] ?? [],
        ]);
    });

    // Health check endpoint
    Route::get('/health', function () {
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
