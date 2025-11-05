<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\TenantStatisticsResource;
use App\Services\TenantStatisticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TenantStatisticsController extends Controller
{
    public function __construct(
        private readonly TenantStatisticsService $statisticsService
    ) {}

    /**
     * Get summary statistics for all tenants.
     */
    public function summary(): JsonResponse
    {
        $statistics = $this->statisticsService->getSummaryStatistics();

        return response()->json([
            'data' => new TenantStatisticsResource($statistics),
        ]);
    }

    /**
     * Get tenant growth metrics.
     */
    public function growth(Request $request): JsonResponse
    {
        $days = (int) $request->query('days', 30);
        $metrics = $this->statisticsService->getGrowthMetrics($days);

        return response()->json([
            'data' => $metrics,
        ]);
    }

    /**
     * Get tenant distribution by date.
     */
    public function distribution(Request $request): JsonResponse
    {
        $days = (int) $request->query('days', 30);
        $distribution = $this->statisticsService->getTenantDistributionByDate($days);

        return response()->json([
            'data' => $distribution,
        ]);
    }
}
