<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * API Resource for Product Media (Spatie MediaLibrary).
 *
 * @property Media $resource
 */
final class ProductMediaResource extends JsonResource
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
            'uuid' => $this->resource->uuid,
            'name' => $this->resource->name,
            'file_name' => $this->resource->file_name,
            'mime_type' => $this->resource->mime_type,
            'size' => $this->resource->size,
            'collection_name' => $this->resource->collection_name,
            'order_column' => $this->resource->order_column,
            'custom_properties' => $this->resource->custom_properties,

            // URLs
            'url' => $this->resource->getUrl(),
            'thumbnail' => $this->resource->hasGeneratedConversion('thumbnail')
                ? $this->resource->getUrl('thumbnail')
                : null,
            'medium' => $this->resource->hasGeneratedConversion('medium')
                ? $this->resource->getUrl('medium')
                : null,
            'large' => $this->resource->hasGeneratedConversion('large')
                ? $this->resource->getUrl('large')
                : null,

            'created_at' => $this->resource->created_at?->toISOString(),
            'updated_at' => $this->resource->updated_at?->toISOString(),
        ];
    }
}
