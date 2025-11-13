<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource for Product model.
 *
 * Transforms Lunar product data into a consistent JSON structure for API responses.
 * Includes product details, attributes, pricing, inventory, and relationships.
 *
 * @property \Lunar\Models\Product $resource
 */
final class ProductResource extends JsonResource
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
            'sku' => $this->resource->translateAttribute('sku'),
            'status' => $this->resource->status,

            // Product type and brand
            'product_type' => $this->whenLoaded('productType', function () {
                return [
                    'id' => $this->resource->productType->id,
                    'name' => $this->resource->productType->name,
                ];
            }),

            'brand' => $this->whenLoaded('brand', function () {
                return [
                    'id' => $this->resource->brand->id,
                    'name' => $this->resource->brand->name,
                ];
            }),

            // Variants and pricing
            'variants' => $this->whenLoaded('variants', function () {
                return $this->resource->variants->map(function ($variant) {
                    return [
                        'id' => $variant->id,
                        'sku' => $variant->sku,
                        'price' => $variant->price?->decimal,
                        'stock' => $variant->stock,
                        'purchasable' => $variant->purchasable,
                        'values' => $variant->values->map(function ($value) {
                            return [
                                'id' => $value->id,
                                'name' => $value->name,
                                'value' => $value->value,
                            ];
                        }),
                    ];
                });
            }),

            // Collections
            'collections' => $this->whenLoaded('collections', function () {
                return $this->resource->collections->map(function ($collection) {
                    return [
                        'id' => $collection->id,
                        'name' => $collection->translateAttribute('name'),
                        'slug' => $collection->slug,
                    ];
                });
            }),

            // Tags
            'tags' => $this->whenLoaded('tags', function () {
                return $this->resource->tags->map(function ($tag) {
                    return [
                        'id' => $tag->id,
                        'value' => $tag->value,
                    ];
                });
            }),

            // Images
            'images' => $this->whenLoaded('images', function () {
                return $this->resource->images->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'url' => $image->getUrl(),
                        'alt' => $image->alt,
                        'position' => $image->position,
                    ];
                });
            }),

            // Pricing information (from base variant)
            'pricing' => $this->whenLoaded('variants', function () {
                $baseVariant = $this->resource->variants->first();

                return $baseVariant ? [
                    'price' => $baseVariant->price?->decimal,
                    'currency' => $baseVariant->price?->currency->code ?? 'GHS',
                    'formatted_price' => $baseVariant->price?->formatted ?? 'GHS 0.00',
                ] : null;
            }),

            // Inventory information
            'inventory' => $this->whenLoaded('variants', function () {
                $totalStock = $this->resource->variants->sum('stock');

                return [
                    'total_stock' => $totalStock,
                    'in_stock' => $totalStock > 0,
                    'low_stock' => $totalStock > 0 && $totalStock <= 10,
                ];
            }),

            // URLs
            'urls' => $this->whenLoaded('urls', function () {
                return $this->resource->urls->map(function ($url) {
                    return [
                        'id' => $url->id,
                        'slug' => $url->slug,
                        'url' => $url->url,
                        'default' => $url->default,
                    ];
                });
            }),

            // Metadata
            'attribute_data' => $this->resource->attribute_data,
            'metadata' => $this->resource->getMeta(),

            // Timestamps
            'created_at' => $this->resource->created_at->toISOString(),
            'updated_at' => $this->resource->updated_at->toISOString(),
        ];
    }
}
