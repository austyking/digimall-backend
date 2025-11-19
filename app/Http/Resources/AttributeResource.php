<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource for Attribute model.
 *
 * Transforms Lunar attribute data into a consistent JSON structure for API responses.
 * Used for taxonomy attribute management.
 *
 * @property \Lunar\Models\Attribute $resource
 */
final class AttributeResource extends JsonResource
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
            'name' => $this->resource->name->get('en') ?? $this->resource->name,
            'handle' => $this->resource->handle,
            'type' => $this->convertTypeToString($this->resource->type),
            'section' => $this->resource->section,
            'required' => (bool) $this->resource->required,
            'default_value' => $this->resource->default_value,
            'configuration' => $this->resource->configuration,
            'system' => (bool) $this->resource->system,
            'position' => $this->resource->position,

            // Relationships
            'attribute_group' => $this->whenLoaded('attributeGroup', function () {
                return [
                    'id' => $this->resource->attributeGroup->id,
                    'name' => $this->resource->attributeGroup->name,
                    'handle' => $this->resource->attributeGroup->handle,
                ];
            }),

            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }

    /**
     * Convert field type class name to type string
     */
    private function convertTypeToString(string $typeClass): string
    {
        return match ($typeClass) {
            \Lunar\FieldTypes\Text::class => 'text',
            \Lunar\FieldTypes\Number::class => 'number',
            \Lunar\FieldTypes\Toggle::class => 'boolean',
            \Lunar\FieldTypes\Dropdown::class => 'select',
            \Lunar\FieldTypes\ListField::class => 'multiselect',
            \Lunar\FieldTypes\TranslatedText::class => 'richtext',
            \Lunar\FieldTypes\File::class => 'file',
            default => 'text', // Default fallback
        };
    }
}
