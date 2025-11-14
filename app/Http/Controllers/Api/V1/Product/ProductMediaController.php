<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReorderProductMediaRequest;
use App\Http\Requests\UpdateProductMediaRequest;
use App\Http\Requests\UploadMultipleProductImagesRequest;
use App\Http\Requests\UploadProductImageRequest;
use App\Http\Resources\ProductMediaResource;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Product Media Controller
 *
 * Handles product media management (images, files) using Spatie MediaLibrary.
 * Implements API v1 endpoints for Lunar's media features.
 */
class ProductMediaController extends Controller
{
    public function __construct(
        private readonly ProductService $productService
    ) {}

    /**
     * Get all media for a product.
     */
    public function index(int $productId): AnonymousResourceCollection
    {
        $product = $this->productService->findById($productId);

        if (! $product) {
            abort(404, 'Product not found');
        }

        $media = $product->getMedia('images');

        return ProductMediaResource::collection($media);
    }

    /**
     * Upload a single product image.
     */
    public function store(UploadProductImageRequest $request, int $productId): JsonResponse
    {
        $validated = $request->validated();

        $product = $this->productService->findById($productId);

        if (! $product) {
            abort(404, 'Product not found');
        }

//        $this->authorize('update', $product);

        $collection = $validated['collection'] ?? 'images';

        $mediaItem = $product
            ->addMedia($request->file('image'))
            ->withCustomProperties($validated['custom_properties'] ?? [])
            ->toMediaCollection($collection);

        return (new ProductMediaResource($mediaItem))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Upload multiple product images.
     */
    public function storeMultiple(UploadMultipleProductImagesRequest $request, int $productId): JsonResponse
    {
        $validated = $request->validated();

        $product = $this->productService->findById($productId);

        if (! $product) {
            abort(404, 'Product not found');
        }

//        $this->authorize('update', $product);

        $collection = $validated['collection'] ?? 'images';
        $uploadedMedia = [];

        foreach ($request->file('images') as $image) {
            $mediaItem = $product
                ->addMedia($image)
                ->toMediaCollection($collection);

            $uploadedMedia[] = new ProductMediaResource($mediaItem);
        }

        return response()->json([
            'message' => count($uploadedMedia).' media files uploaded successfully',
            'data' => $uploadedMedia,
        ], 201);
    }

    /**
     * Get a specific media item.
     */
    public function show(int $productId, int $mediaId): JsonResponse
    {
        $product = $this->productService->findById($productId);

        if (! $product) {
            abort(404, 'Product not found');
        }

        $mediaItem = $product->getMedia('images')->where('id', $mediaId)->first();

        if (! $mediaItem) {
            abort(404, 'Media not found');
        }

        return (new ProductMediaResource($mediaItem))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Update media properties.
     */
    public function update(UpdateProductMediaRequest $request, int $productId, int $mediaId): JsonResponse
    {
        $validated = $request->validated();

        $product = $this->productService->findById($productId);

        if (! $product) {
            abort(404, 'Product not found');
        }

//        $this->authorize('update', $product);

        $mediaItem = $product->getMedia('images')->where('id', $mediaId)->first();

        if (! $mediaItem) {
            abort(404, 'Media not found');
        }

        if (isset($validated['name'])) {
            $mediaItem->name = $validated['name'];
        }

        if (isset($validated['custom_properties'])) {
            $mediaItem->custom_properties = $validated['custom_properties'];
        }

        $mediaItem->save();

        return (new ProductMediaResource($mediaItem))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Delete a media item.
     */
    public function destroy(int $productId, int $mediaId): JsonResponse
    {
        $product = $this->productService->findById($productId);

        if (! $product) {
            abort(404, 'Product not found');
        }

//        $this->authorize('update', $product);

        $mediaItem = $product->getMedia('images')->where('id', $mediaId)->first();

        if (! $mediaItem) {
            abort(404, 'Media not found');
        }

        $mediaItem->delete();

        return response()->json([
            'message' => 'Media deleted successfully',
        ]);
    }

    /**
     * Reorder product media.
     */
    public function reorder(ReorderProductMediaRequest $request, int $productId): JsonResponse
    {
        $validated = $request->validated();

        $product = $this->productService->findById($productId);

        if (! $product) {
            abort(404, 'Product not found');
        }

//        $this->authorize('update', $product);

        foreach ($validated['order'] as $index => $mediaId) {
            $product->getMedia('images')
                ->where('id', $mediaId)
                ->first()
                ?->update(['order_column' => $index + 1]);
        }

        return response()->json([
            'message' => 'Media reordered successfully',
        ]);
    }

    /**
     * Get the primary/first image for a product.
     */
    public function getPrimary(int $productId): JsonResponse
    {
        $product = $this->productService->findById($productId);

        if (! $product) {
            abort(404, 'Product not found');
        }

        $url = $product->getFirstMediaUrl('images');
        $path = $product->getFirstMediaPath('images');

        if (! $url) {
            return response()->json([
                'message' => 'No primary image found',
                'data' => [
                    'url' => config('lunar.media.fallback.url'),
                    'path' => config('lunar.media.fallback.path'),
                ],
            ], 404);
        }

        return response()->json([
            'data' => [
                'url' => $url,
                'path' => $path,
            ],
        ]);
    }
}
