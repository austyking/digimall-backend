<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\TenantConfigResource;
use Illuminate\Http\JsonResponse;

final class ShowTenantConfigController extends Controller
{
    /**
     * Get current tenant configuration.
     */
    public function __invoke(): JsonResponse|TenantConfigResource
    {
        $tenant = tenancy()->tenant;

        if (! $tenant) {
            return response()->json([
                'error' => 'No tenant context found',
            ], 400);
        }

        return new TenantConfigResource($tenant);
    }
}
