<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource for Vendor model.
 *
 * Transforms vendor data into a consistent JSON structure for API responses.
 * Includes vendor details, status, commission configuration, and relationships.
 *
 * @property \App\Models\Vendor $resource
 */
final class VendorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'tenant_id' => $this->resource->tenant_id,
            'user_id' => $this->resource->user_id,
            'business_name' => $this->resource->business_name,
            'slug' => $this->resource->slug,
            'contact_name' => $this->resource->contact_name,
            'email' => $this->resource->email,
            'phone' => $this->resource->phone,
            'address' => $this->resource->address,
            'city' => $this->resource->city,
            'state' => $this->resource->state,
            'country' => $this->resource->country,
            'postal_code' => $this->resource->postal_code,
            'status' => $this->resource->status,
            'commission_rate' => $this->resource->commission_rate,
            'commission_type' => $this->resource->commission_type,
            'business_registration_number' => $this->resource->business_registration_number,
            'tax_identification_number' => $this->resource->tax_identification_number,
            'bank_details' => $this->resource->bank_details,
            'logo_url' => $this->resource->logo_url,
            'banner_url' => $this->resource->banner_url,
            'description' => $this->resource->description,
            'return_policy' => $this->resource->return_policy,
            'shipping_policy' => $this->resource->shipping_policy,
            'metadata' => $this->resource->metadata,
            'approved_at' => $this->resource->approved_at?->toISOString(),
            'rejected_at' => $this->resource->rejected_at?->toISOString(),
            'created_at' => $this->resource->created_at->toISOString(),
            'updated_at' => $this->resource->updated_at->toISOString(),

            // Calculated fields for frontend display
            'total_products' => $this->resource->products_count ?? $this->resource->products()->count(),
            'rating' => 4.5, // TODO: Implement actual rating calculation
            'total_orders' => 0, // TODO: Implement order count calculation
            'total_revenue' => 0, // TODO: Implement revenue calculation

            // Conditional relationships
            'user' => $this->whenLoaded('user'),
            'tenant' => $this->whenLoaded('tenant'),
        ];
    }
}
