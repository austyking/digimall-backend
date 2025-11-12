<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\RegisterVendorDTO;
use App\DTOs\UpdateVendorDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterVendorRequest;
use App\Http\Requests\UpdateVendorRequest;
use App\Http\Resources\VendorResource;
use App\Services\VendorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Controller for vendor-related operations.
 *
 * Handles vendor registration, profile management, and administrative actions.
 * All business logic is delegated to VendorService.
 *
 * @see VendorService
 */
final class VendorController extends Controller
{
    public function __construct(
        private readonly VendorService $vendorService,
    ) {}

    /**
     * Register a new vendor.
     *
     * Creates a new vendor account with pending status that requires admin approval.
     * The vendor will be associated with the current tenant and authenticated user.
     *
     * @param  RegisterVendorRequest  $request  Validated registration data
     * @return JsonResponse HTTP 201 with vendor resource
     */
    public function register(RegisterVendorRequest $request): JsonResponse
    {
        $dto = RegisterVendorDTO::fromRequest($request->validated());

        $vendor = $this->vendorService->registerVendor($dto);

        return (new VendorResource($vendor))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Get vendor details by ID.
     *
     * @param  string  $id  Vendor UUID
     * @return VendorResource Vendor resource
     */
    public function show(string $id): VendorResource
    {
        $vendor = $this->vendorService->findById($id);

        if (! $vendor) {
            abort(Response::HTTP_NOT_FOUND, 'Vendor not found.');
        }

        return new VendorResource($vendor);
    }

    /**
     * Update vendor profile.
     *
     * @param  string  $id  Vendor UUID
     * @param  UpdateVendorRequest  $request  Validated update data
     * @return VendorResource Updated vendor resource
     */
    public function update(string $id, UpdateVendorRequest $request): VendorResource
    {
        $dto = UpdateVendorDTO::fromRequest($request->validated());

        $vendor = $this->vendorService->updateVendor($id, $dto);

        return new VendorResource($vendor);
    }

    /**
     * Get all vendors for the current tenant.
     */
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $perPage = (int) $request->query('per_page', 15);
        $status = $request->query('status');

        // Get current tenant ID from tenancy helper
        $tenantId = tenancy()->tenant?->id;

        $vendors = $status
            ? $this->vendorService->getByStatus($status, $perPage)
            : ($tenantId ? $this->vendorService->getAllForTenant($tenantId, $perPage) : collect());

        return VendorResource::collection($vendors);
    }

    /**
     * Approve a pending vendor.
     *
     * Changes vendor status from pending to approved, allowing them to sell products.
     *
     * @param  string  $id  Vendor UUID
     * @return VendorResource Approved vendor resource
     */
    public function approve(string $id): VendorResource
    {
        $vendor = $this->vendorService->approveVendor($id);

        return new VendorResource($vendor);
    }

    /**
     * Reject a pending vendor.
     *
     * Changes vendor status from pending to rejected with optional reason.
     *
     * @param  string  $id  Vendor UUID
     * @return VendorResource Rejected vendor resource
     */
    public function reject(string $id, Request $request): VendorResource
    {
        $reason = $request->input('reason');
        $vendor = $this->vendorService->rejectVendor($id, $reason);

        return new VendorResource($vendor);
    }

    /**
     * Suspend an active vendor.
     *
     * Temporarily disables vendor account, preventing sales and product management.
     *
     * @param  string  $id  Vendor UUID
     * @return VendorResource Suspended vendor resource
     */
    public function suspend(string $id, Request $request): VendorResource
    {
        $reason = $request->input('reason');
        $vendor = $this->vendorService->suspendVendor($id, $reason);

        return new VendorResource($vendor);
    }
}
