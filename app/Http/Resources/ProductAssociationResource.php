<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Lunar\Models\ProductAssociation;

/**
 * API Resource for ProductAssociation model.
 *
 * @property ProductAssociation $resource
 */
final class ProductAssociationResource extends JsonResource
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
            'product_parent_id' => $this->resource->product_parent_id,
            'product_target_id' => $this->resource->product_target_id,
            'type' => $this->resource->type,

            // Target product details
            'target_product' => $this->whenLoaded('target', function () {
                return [
                    'id' => $this->resource->target->id,
                    'name' => $this->resource->target->translateAttribute('name'),
                    'slug' => $this->resource->target->urls->first()?->slug,
                    'status' => $this->resource->target->status,

                    'brand' => $this->resource->target->brand ? [
                        'id' => $this->resource->target->brand->id,
                        'name' => $this->resource->target->brand->name,
                    ] : null,

                    'product_type' => $this->resource->target->productType ? [
                        'id' => $this->resource->target->productType->id,
                        'name' => $this->resource->target->productType->name,
                    ] : null,

                    'primary_image' => $this->resource->target->images->first() ? [
                        'url' => $this->resource->target->images->first()->getUrl(),
                        'thumbnail' => $this->resource->target->images->first()->getUrl('thumbnail'),
                        'alt' => $this->resource->target->images->first()->alt,
                    ] : null,

                    'price' => $this->resource->target->variants->first()?->price?->decimal,
                ];
            }),

            'created_at' => $this->resource->created_at?->toISOString(),
            'updated_at' => $this->resource->updated_at?->toISOString(),
        ];
    }
}
