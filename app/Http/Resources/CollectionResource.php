<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource for Collection model.
 *
 * Transforms Lunar collection data into a consistent JSON structure for API responses.
 *
 * @property \Lunar\Models\Collection $resource
 */
final class CollectionResource extends JsonResource
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
            'name' => $this->resource->translateAttribute('name'),
            'type' => $this->resource->type,
            'sort' => $this->resource->sort,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
