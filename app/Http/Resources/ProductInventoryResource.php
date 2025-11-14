<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource for Product Inventory.
 *
 * Transforms product inventory data into a consistent JSON structure for API responses.
 */
final class ProductInventoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'product_id' => $this->resource['product_id'],
            'total_stock' => $this->resource['total_stock'],
            'low_stock_threshold' => $this->resource['low_stock_threshold'],
            'is_low_stock' => $this->resource['is_low_stock'],
            'variants' => $this->resource['variants'],
        ];
    }
}
