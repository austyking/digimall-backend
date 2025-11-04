<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\DTOs\CreateTenantDTO;
use App\DTOs\UpdateTenantDTO;
use App\DTOs\UpdateTenantSettingsDTO;
use App\Http\Controllers\Controller;
use App\Http\Resources\TenantConfigResource;
use App\Http\Resources\TenantResource;
use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class TenantController extends Controller
{
    public function __construct(
        private TenantService $tenantService
    ) {}

    /**
     * Get current tenant configuration.
     */
    public function config(): JsonResponse|TenantConfigResource
    {
        $tenant = tenancy()->tenant;

        if (! $tenant) {
            return response()->json([
                'error' => 'No tenant context found',
            ], 400);
        }

        return new TenantConfigResource($tenant);
    }

    /**
     * Get tenant branding configuration.
     */
    public function branding(): JsonResponse
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

    /**
     * Get list of all tenants.
     */
    public function index(): AnonymousResourceCollection
    {
        $tenants = $this->tenantService->getAllTenants();

        return TenantResource::collection($tenants);
    }

    /**
     * Get a specific tenant.
     */
    public function show(string $id): JsonResponse|TenantResource
    {
        $tenant = $this->tenantService->findTenant($id);

        if (! $tenant) {
            return response()->json([
                'error' => 'Tenant not found',
            ], 404);
        }

        return new TenantResource($tenant);
    }

    /**
     * Create a new tenant.
     */
    public function store(Request $request): JsonResponse|TenantResource
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:tenants,name',
            'display_name' => 'required|string|max:255',
            'subdomain' => 'required|string|max:255|unique:tenants,subdomain',
            'description' => 'nullable|string',
            'active' => 'boolean',
            'settings' => 'array',
        ]);

        $dto = CreateTenantDTO::fromRequest($request);
        $tenant = $this->tenantService->createTenant($dto);

        return (new TenantResource($tenant))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update tenant information.
     */
    public function update(Request $request, string $id): JsonResponse|TenantResource
    {
        $request->validate([
            'display_name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'active' => 'boolean',
        ]);

        $tenant = $this->tenantService->findTenant($id);

        if (! $tenant) {
            return response()->json([
                'error' => 'Tenant not found',
            ], 404);
        }

        $dto = UpdateTenantDTO::fromRequest($request);
        $updatedTenant = $this->tenantService->updateTenant($tenant, $dto);

        return new TenantResource($updatedTenant);
    }

    /**
     * Update tenant settings.
     */
    public function updateSettings(Request $request, string $id): JsonResponse|TenantResource
    {
        $request->validate([
            'settings' => 'required|array',
        ]);

        $tenant = $this->tenantService->findTenant($id);

        if (! $tenant) {
            return response()->json([
                'error' => 'Tenant not found',
            ], 404);
        }

        $dto = UpdateTenantSettingsDTO::fromRequest($request);
        $updatedTenant = $this->tenantService->updateSettings($tenant, $dto);

        return new TenantResource($updatedTenant);
    }

    /**
     * Delete a tenant.
     */
    public function destroy(string $id): JsonResponse
    {
        $tenant = $this->tenantService->findTenant($id);

        if (! $tenant) {
            return response()->json([
                'error' => 'Tenant not found',
            ], 404);
        }

        $this->tenantService->deleteTenant($tenant);

        return response()->json([
            'message' => 'Tenant deleted successfully',
        ], 200);
    }

    /**
     * Search tenants.
     */
    public function search(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'q' => 'required|string|min:1',
        ]);

        $tenants = $this->tenantService->searchTenants($request->input('q'));

        return TenantResource::collection($tenants);
    }
}
