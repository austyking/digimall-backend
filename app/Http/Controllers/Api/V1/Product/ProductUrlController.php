<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Product;

use App\DTOs\CreateProductUrlDTO;
use App\DTOs\UpdateProductUrlDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateProductUrlRequest;
use App\Http\Requests\GenerateProductSlugRequest;
use App\Http\Requests\UpdateProductUrlRequest;
use App\Http\Resources\ProductUrlResource;
use App\Services\LanguageService;
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
        private readonly ProductUrlService $urlService,
        private readonly LanguageService $languageService
    ) {}

    /**
     * Get all URLs for a product.
     */
    public function index(int $productId): AnonymousResourceCollection
    {
        $product = $this->productService->findById($productId);

        if (! $product) {
            abort(404, 'Product not found');
        }

        $urls = $this->urlService->getUrlsForProduct($productId);

        return ProductUrlResource::collection($urls);
    }

    /**
     * Create a new URL for a product.
     */
    public function store(CreateProductUrlRequest $request, int $productId): JsonResponse
    {
        $validated = $request->validated();
        $dto = CreateProductUrlDTO::fromArray($validated);

        $product = $this->productService->findById($productId);

        if (! $product) {
            abort(404, 'Product not found');
        }

//        $this->authorize('update', $product);

        try {
            $url = $this->urlService->createUrl($productId, $dto);

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
    public function show(int $productId, int $urlId): JsonResponse
    {
        $product = $this->productService->findById($productId);

        if (! $product) {
            abort(404, 'Product not found');
        }

        $urls = $this->urlService->getUrlsForProduct($productId);
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
    public function update(UpdateProductUrlRequest $request, int $productId, int $urlId): JsonResponse
    {
        $validated = $request->validated();
        $dto = UpdateProductUrlDTO::fromArray ($validated);

        $product = $this->productService->findById($productId);

        if (! $product) {
            abort(404, 'Product not found');
        }

//        $this->authorize('update', $product);

        try {
            $url = $this->urlService->updateUrl($urlId, $dto);

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
    public function destroy(int $productId, int $urlId): JsonResponse
    {
        $product = $this->productService->findById($productId);

        if (! $product) {
            abort(404, 'Product not found');
        }

//        $this->authorize('update', $product);

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
    public function setDefault(int $productId, int $urlId): JsonResponse
    {
        $product = $this->productService->findById($productId);

        if (! $product) {
            abort(404, 'Product not found');
        }

//        $this->authorize('update', $product);

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
    public function getDefault(Request $request, int $productId): JsonResponse
    {
        $product = $this->productService->findById($productId);

        if (! $product) {
            abort(404, 'Product not found');
        }

        if(! $request->has('language_code')) {
            abort (400, 'Language code is required');
        }

        $language = $this->languageService->findByCode($request->query('language_code'));
        if (! $language) {
            abort(404, 'Language not found');
        }

        $url = $this->urlService->getDefaultUrl($productId, $language->id);

        if (! $url) {
            return response()->json([
                'message' => 'No default URL found',
                'data' => null
            ], 404);
        }

        return (new ProductUrlResource($url))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Generate a unique slug for a product.
     */
    public function generateSlug(GenerateProductSlugRequest $request, int $productId): JsonResponse
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
