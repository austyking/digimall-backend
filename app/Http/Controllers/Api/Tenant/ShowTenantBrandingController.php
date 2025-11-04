<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;

final class ShowTenantBrandingController extends Controller
{
    public function __construct(
        private readonly TenantService $tenantService
    ) {}

    /**
     * Get tenant branding configuration.
     */
    public function __invoke(): JsonResponse
    {
        $tenant = tenancy()->tenant;

        if (! $tenant) {
            return response()->json([
                'error' => 'No tenant context found',
            ], 400);
        }

        return response()->json([
            'branding' => $this->tenantService->getBrandingConfig($tenant),
        ]);
    }
}
