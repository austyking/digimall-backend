<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\DTOs\ActivateTenantDTO;
use App\DTOs\AdminUpdateTenantDTO;
use App\DTOs\DeactivateTenantDTO;
use App\DTOs\TenantFilterDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ActivateTenantRequest;
use App\Http\Requests\Admin\BulkTenantActionRequest;
use App\Http\Requests\Admin\DeactivateTenantRequest;
use App\Http\Requests\Admin\GetFilteredTenantsRequest;
use App\Http\Requests\Admin\UpdateTenantRequest;
use App\Http\Resources\Admin\AdminTenantResource;
use App\Services\AdminTenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class AdminTenantController extends Controller
{
    public function __construct(
        private readonly AdminTenantService $tenantService
    ) {}

    /**
     * Get filtered and paginated list of tenants.
     */
    public function index(GetFilteredTenantsRequest $request): AnonymousResourceCollection
    {
        $dto = TenantFilterDTO::fromRequest($request);
        $tenants = $this->tenantService->getFilteredTenants($dto);

        return AdminTenantResource::collection($tenants);
    }

    /**
     * Get a single tenant by ID.
     */
    public function show(string $id): JsonResponse
    {
        $tenant = $this->tenantService->getTenant($id);

        if (! $tenant) {
            return response()->json([
                'message' => 'Tenant not found',
            ], 404);
        }

        return response()->json([
            'data' => new AdminTenantResource($tenant),
        ]);
    }

    /**
     * Update tenant details.
     */
    public function update(UpdateTenantRequest $request, string $id): JsonResponse
    {
        $dto = AdminUpdateTenantDTO::fromRequest($request);
        $tenant = $this->tenantService->updateTenant($id, $dto);

        return response()->json([
            'message' => 'Tenant updated successfully',
            'data' => new AdminTenantResource($tenant),
        ]);
    }

    /**
     * Activate a tenant.
     */
    public function activate(ActivateTenantRequest $request, string $id): JsonResponse
    {
        $dto = ActivateTenantDTO::fromRequest($request, $id);
        $tenant = $this->tenantService->activateTenant($dto);

        return response()->json([
            'message' => 'Tenant activated successfully',
            'data' => new AdminTenantResource($tenant),
        ]);
    }

    /**
     * Deactivate a tenant.
     */
    public function deactivate(DeactivateTenantRequest $request, string $id): JsonResponse
    {
        $dto = DeactivateTenantDTO::fromRequest($request, $id);
        $tenant = $this->tenantService->deactivateTenant($dto);

        return response()->json([
            'message' => 'Tenant deactivated successfully',
            'data' => new AdminTenantResource($tenant),
        ]);
    }

    /**
     * Bulk activate tenants.
     */
    public function bulkActivate(BulkTenantActionRequest $request): JsonResponse
    {
        $activatedCount = $this->tenantService->bulkActivateTenants(
            $request->validated('tenant_ids')
        );

        return response()->json([
            'message' => "Successfully activated {$activatedCount} tenant(s)",
            'data' => [
                'activated_count' => $activatedCount,
            ],
        ]);
    }

    /**
     * Bulk deactivate tenants.
     */
    public function bulkDeactivate(BulkTenantActionRequest $request): JsonResponse
    {
        $deactivatedCount = $this->tenantService->bulkDeactivateTenants(
            $request->validated('tenant_ids')
        );

        return response()->json([
            'message' => "Successfully deactivated {$deactivatedCount} tenant(s)",
            'data' => [
                'deactivated_count' => $deactivatedCount,
            ],
        ]);
    }

    /**
     * Get all inactive tenants.
     */
    public function inactive(): AnonymousResourceCollection
    {
        $tenants = $this->tenantService->getInactiveTenants();

        return AdminTenantResource::collection($tenants);
    }
}
