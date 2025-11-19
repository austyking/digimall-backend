<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource for Category (Collection) model.
 *
 * Transforms Lunar collection data into a consistent JSON structure for API responses.
 * Used for taxonomy category management.
 *
 * @property \Lunar\Models\Collection $resource
 */
final class CategoryResource extends JsonResource
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
            'description' => $this->resource->translateAttribute('description'),
            'slug' => $this->resource->defaultUrl?->slug,
            'type' => $this->resource->type,
            'sort' => $this->resource->sort,
            'parent_id' => $this->resource->parent_id,
            'collection_group_id' => $this->resource->collection_group_id,

            // Relationships
            'parent' => $this->whenLoaded('parent', function () {
                return [
                    'id' => $this->resource->parent->id,
                    'name' => $this->resource->parent->translateAttribute('name'),
                ];
            }),

            'children' => $this->whenLoaded('children', function () {
                return $this->resource->children->map(function ($child) {
                    return [
                        'id' => $child->id,
                        'name' => $child->translateAttribute('name'),
                        'sort' => $child->sort,
                    ];
                });
            }),

            'children_count' => $this->resource->children()->count(),

            'collection_group' => $this->whenLoaded('collectionGroup', function () {
                return [
                    'id' => $this->resource->collectionGroup->id,
                    'name' => $this->resource->collectionGroup->name,
                ];
            }),

            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
