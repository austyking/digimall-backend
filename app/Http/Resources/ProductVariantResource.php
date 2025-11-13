<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Lunar\Models\ProductVariant;

/**
 * API Resource for ProductVariant model.
 *
 * @property ProductVariant $resource
 */
final class ProductVariantResource extends JsonResource
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
            'product_id' => $this->resource->product_id,
            'sku' => $this->resource->sku,
            'stock' => $this->resource->stock,
            'purchasable' => $this->resource->purchasable,
            'unit_quantity' => $this->resource->unit_quantity,
            'tax_class_id' => $this->resource->tax_class_id,
            'backorder' => $this->resource->backorder,
            'shippable' => $this->resource->shippable,
            'length_value' => $this->resource->length_value,
            'length_unit' => $this->resource->length_unit,
            'width_value' => $this->resource->width_value,
            'width_unit' => $this->resource->width_unit,
            'height_value' => $this->resource->height_value,
            'height_unit' => $this->resource->height_unit,
            'weight_value' => $this->resource->weight_value,
            'weight_unit' => $this->resource->weight_unit,
            'volume_value' => $this->resource->volume_value,
            'volume_unit' => $this->resource->volume_unit,

            // Prices
            'prices' => $this->whenLoaded('prices', function () {
                return $this->resource->prices->map(function ($price) {
                    return [
                        'id' => $price->id,
                        'price' => $price->price,
                        'price_formatted' => $price->decimal,
                        'currency' => [
                            'id' => $price->currency->id,
                            'code' => $price->currency->code,
                            'name' => $price->currency->name,
                            'exchange_rate' => $price->currency->exchange_rate,
                        ],
                        'min_quantity' => $price->min_quantity,
                        'customer_group_id' => $price->customer_group_id,
                    ];
                });
            }),

            // Option Values (size, color, etc.)
            'values' => $this->whenLoaded('values', function () {
                return $this->resource->values->map(function ($value) {
                    return [
                        'id' => $value->id,
                        'product_option_id' => $value->product_option_id,
                        'name' => $value->translate('name'),
                        'option_name' => $value->option->translate('name'),
                        'position' => $value->position,
                    ];
                });
            }),

            // Tax Class
            'tax_class' => $this->whenLoaded('taxClass', function () {
                return $this->resource->taxClass ? [
                    'id' => $this->resource->taxClass->id,
                    'name' => $this->resource->taxClass->name,
                ] : null;
            }),

            // Images
            'images' => $this->whenLoaded('images', function () {
                return $this->resource->images->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'url' => $image->getUrl(),
                        'thumbnail' => $image->getUrl('thumbnail'),
                        'alt' => $image->alt,
                        'position' => $image->position,
                    ];
                });
            }),

            'created_at' => $this->resource->created_at?->toISOString(),
            'updated_at' => $this->resource->updated_at?->toISOString(),
            'deleted_at' => $this->resource->deleted_at?->toISOString(),
        ];
    }
}
