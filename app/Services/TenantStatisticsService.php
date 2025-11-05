<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\TenantRepositoryInterface;

/**
 * Service for tenant statistics and analytics.
 */
final class TenantStatisticsService
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository
    ) {}

    /**
     * Get total tenant count.
     */
    public function getTotalCount(): int
    {
        return $this->tenantRepository->count();
    }

    /**
     * Get active tenant count.
     */
    public function getActiveCount(): int
    {
        return $this->tenantRepository->countActive();
    }

    /**
     * Get inactive tenant count.
     */
    public function getInactiveCount(): int
    {
        return $this->tenantRepository->countInactive();
    }

    /**
     * Get tenant activation rate percentage.
     */
    public function getActivationRate(): float
    {
        $total = $this->getTotalCount();

        if ($total === 0) {
            return 0.0;
        }

        return round(($this->getActiveCount() / $total) * 100, 2);
    }

    /**
     * Get tenant growth metrics over time periods.
     *
     * @return array{today: int, week: int, month: int, year: int}
     */
    public function getGrowthMetrics(): array
    {
        return [
            'today' => $this->getTenantsCreatedSince(now()->startOfDay()),
            'week' => $this->getTenantsCreatedSince(now()->startOfWeek()),
            'month' => $this->getTenantsCreatedSince(now()->startOfMonth()),
            'year' => $this->getTenantsCreatedSince(now()->startOfYear()),
        ];
    }

    /**
     * Get summary statistics for admin dashboard.
     *
     * @return array{total: int, active: int, inactive: int, activation_rate: float, growth: array}
     */
    public function getSummaryStatistics(): array
    {
        return [
            'total' => $this->getTotalCount(),
            'active' => $this->getActiveCount(),
            'inactive' => $this->getInactiveCount(),
            'activation_rate' => $this->getActivationRate(),
            'growth' => $this->getGrowthMetrics(),
        ];
    }

    /**
     * Get tenant distribution by creation date (for charts).
     *
     * @param  int  $days  Number of days to look back
     * @return array<string, int> Date => count mapping
     */
    public function getTenantDistributionByDate(int $days = 30): array
    {
        return $this->tenantRepository->getDistributionByDate($days);
    }

    /**
     * Get count of tenants created since a specific date.
     */
    private function getTenantsCreatedSince(\DateTimeInterface $date): int
    {
        return $this->tenantRepository->countCreatedSince($date);
    }
}
