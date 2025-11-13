<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Lunar\Models\Url;

/**
 * API Resource for Product URL model.
 *
 * @property Url $resource
 */
final class ProductUrlResource extends JsonResource
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
            'slug' => $this->resource->slug,
            'default' => $this->resource->default,

            // Language
            'language' => $this->whenLoaded('language', function () {
                return [
                    'id' => $this->resource->language->id,
                    'code' => $this->resource->language->code,
                    'name' => $this->resource->language->name,
                ];
            }),

            // Element (Product)
            'element_type' => $this->resource->element_type,
            'element_id' => $this->resource->element_id,

            'created_at' => $this->resource->created_at?->toISOString(),
            'updated_at' => $this->resource->updated_at?->toISOString(),
        ];
    }
}
