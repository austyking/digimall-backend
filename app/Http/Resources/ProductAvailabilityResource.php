<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Product Availability Resource
 *
 * Formats product availability data consistently for API responses.
 * Includes stock levels, status, and variant availability.
 */
class ProductAvailabilityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'product_id' => $this->id,
            'is_available' => $this->is_available ?? false,
            'available_quantity' => $this->available_quantity ?? 0,
            'status' => $this->status,
            'variants' => $this->whenLoaded('variants', function () {
                return $this->variants->map(function ($variant) {
                    // Determine availability based on purchasable mode
                    $isAvailable = match ($variant->purchasable) {
                        'always' => true,
                        'in_stock' => $variant->stock > 0,
                        'backorder' => $variant->stock > 0 || $variant->backorder > 0,
                        default => false,
                    };

                    return [
                        'id' => $variant->id,
                        'sku' => $variant->sku,
                        'stock' => $variant->stock,
                        'purchasable' => $variant->purchasable,
                        'backorder' => $variant->backorder,
                        'is_available' => $isAvailable,
                    ];
                });
            }),
        ];
    }
}
