<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TenantConfigService;
use Illuminate\Http\JsonResponse;

class TenantConfigController extends Controller
{
    public function __construct(
        protected TenantConfigService $tenantConfigService
    ) {}

    /**
     * Get tenant configuration including branding and features.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->tenantConfigService->getApiConfig(),
        ]);
    }

    /**
     * Get tenant branding configuration only.
     */
    public function branding(): JsonResponse
    {
        return response()->json([
            'data' => $this->tenantConfigService->getBrandingConfig(),
        ]);
    }

    /**
     * Get payment gateway configuration.
     */
    public function paymentGateways(): JsonResponse
    {
        return response()->json([
            'data' => $this->tenantConfigService->getPaymentGateways(),
        ]);
    }
}
