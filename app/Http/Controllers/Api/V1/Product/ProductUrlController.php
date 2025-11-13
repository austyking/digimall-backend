<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateProductUrlRequest;
use App\Http\Requests\GenerateProductSlugRequest;
use App\Http\Requests\UpdateProductUrlRequest;
use App\Http\Resources\ProductUrlResource;
use App\Services\ProductService;
use App\Services\ProductUrlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Product URL Controller
 *
 * Handles product URL/slug management for SEO-friendly URLs.
 * Implements API v1 endpoints for Lunar's URL features.
 */
class ProductUrlController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly ProductUrlService $urlService
    ) {}

    /**
     * Get all URLs for a product.
     */
    public function index(string $productId): AnonymousResourceCollection
    {
        $product = $this->productService->findById($productId);

        if (! $product) {
            abort(404, 'Product not found');
        }

        $urls = $this->urlService->getProductUrls($productId);

        return ProductUrlResource::collection($urls);
    }

    /**
     * Create a new URL for a product.
     */
    public function store(CreateProductUrlRequest $request, string $productId): JsonResponse
    {
        $validated = $request->validated();

        $product = $this->productService->findById($productId);

        if (! $product) {
            abort(404, 'Product not found');
        }

        $this->authorize('update', $product);

        try {
            $url = $this->urlService->createUrl($productId, $validated);

            return (new ProductUrlResource($url))
                ->response()
                ->setStatusCode(201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => [
                    'slug' => [$e->getMessage()],
                ],
            ], 422);
        }
    }

    /**
     * Get a specific URL.
     */
    public function show(string $productId, int $urlId): JsonResponse
    {
        $product = $this->productService->findById($productId);

        if (! $product) {
            abort(404, 'Product not found');
        }

        $urls = $this->urlService->getProductUrls($productId);
        $url = $urls->firstWhere('id', $urlId);

        if (! $url) {
            abort(404, 'URL not found');
        }

        return (new ProductUrlResource($url))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Update a URL.
     */
    public function update(UpdateProductUrlRequest $request, string $productId, int $urlId): JsonResponse
    {
        $validated = $request->validated();

        $product = $this->productService->findById($productId);

        if (! $product) {
            abort(404, 'Product not found');
        }

        $this->authorize('update', $product);

        try {
            $url = $this->urlService->updateUrl($urlId, $validated);

            return (new ProductUrlResource($url))
                ->response()
                ->setStatusCode(200);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => [
                    'slug' => [$e->getMessage()],
                ],
            ], 422);
        }
    }

    /**
     * Delete a URL.
     */
    public function destroy(string $productId, int $urlId): JsonResponse
    {
        $product = $this->productService->findById($productId);

        if (! $product) {
            abort(404, 'Product not found');
        }

        $this->authorize('update', $product);

        try {
            $this->urlService->deleteUrl($urlId);

            return response()->json([
                'message' => 'URL deleted successfully. If this was the default URL, another URL has been promoted to default.',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            abort(404, 'URL not found');
        }
    }

    /**
     * Set a URL as the default for its language.
     */
    public function setDefault(string $productId, int $urlId): JsonResponse
    {
        $product = $this->productService->findById($productId);

        if (! $product) {
            abort(404, 'Product not found');
        }

        $this->authorize('update', $product);

        try {
            $url = $this->urlService->setAsDefault($urlId);

            return (new ProductUrlResource($url))
                ->response()
                ->setStatusCode(200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            abort(404, 'URL not found');
        }
    }

    /**
     * Get the default URL for a product by language.
     */
    public function getDefault(Request $request, string $productId): JsonResponse
    {
        $product = $this->productService->findById($productId);

        if (! $product) {
            abort(404, 'Product not found');
        }

        $languageCode = $request->query('language_code');

        $url = $this->urlService->getDefaultUrl($productId, $languageCode);

        if (! $url) {
            return response()->json([
                'message' => 'No default URL found',
            ], 404);
        }

        return (new ProductUrlResource($url))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Generate a unique slug for a product.
     */
    public function generateSlug(GenerateProductSlugRequest $request, string $productId): JsonResponse
    {
        $validated = $request->validated();

        $product = $this->productService->findById($productId);

        if (! $product) {
            abort(404, 'Product not found');
        }

        $slug = $this->urlService->generateSlug($validated['name'], $validated['language_id']);

        return response()->json([
            'data' => [
                'slug' => $slug,
            ],
        ]);
    }
}
