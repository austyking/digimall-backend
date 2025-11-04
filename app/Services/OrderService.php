<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Support\Collection;
use Lunar\Models\Order;

final readonly class OrderService
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository
    ) {}

    /**
     * Find an order by ID.
     */
    public function findById(string $id): ?Order
    {
        return $this->orderRepository->find($id);
    }

    /**
     * Find order by reference.
     */
    public function findByReference(string $reference): ?Order
    {
        return $this->orderRepository->findByReference($reference);
    }

    /**
     * Get orders by customer.
     */
    public function getByCustomer(string $customerId, ?int $limit = null): Collection
    {
        return $this->orderRepository->getByCustomer($customerId, $limit);
    }

    /**
     * Get orders by vendor.
     */
    public function getByVendor(string $vendorId, ?int $limit = null): Collection
    {
        return $this->orderRepository->getByVendor($vendorId, $limit);
    }

    /**
     * Get orders by status.
     */
    public function getByStatus(string $status, ?int $limit = null): Collection
    {
        return $this->orderRepository->getByStatus($status, $limit);
    }

    /**
     * Get orders within a date range.
     */
    public function getByDateRange(string $startDate, string $endDate, ?int $limit = null): Collection
    {
        return $this->orderRepository->getByDateRange($startDate, $endDate, $limit);
    }

    /**
     * Get recent orders.
     */
    public function getRecentOrders(?int $limit = 10): Collection
    {
        return $this->orderRepository->getRecent($limit);
    }

    /**
     * Search orders by query.
     */
    public function searchOrders(string $query, ?int $limit = null): Collection
    {
        return $this->orderRepository->search($query, $limit);
    }

    /**
     * Get pending orders.
     */
    public function getPendingOrders(?int $limit = null): Collection
    {
        return $this->orderRepository->getPending($limit);
    }

    /**
     * Update order status.
     */
    public function updateStatus(string $orderId, string $status): Order
    {
        return $this->orderRepository->updateStatus($orderId, $status);
    }

    /**
     * Calculate total sales.
     */
    public function calculateTotalSales(?string $startDate = null, ?string $endDate = null): float
    {
        return $this->orderRepository->getTotalSales($startDate, $endDate);
    }

    /**
     * Get order statistics.
     */
    public function getOrderStatistics(?string $startDate = null, ?string $endDate = null): array
    {
        return [
            'total_sales' => $this->orderRepository->getTotalSales($startDate, $endDate),
            'total_orders' => $this->orderRepository->getByDateRange($startDate ?? '1970-01-01', $endDate ?? now()->toDateString())->count(),
            'pending_orders' => $this->orderRepository->getPending()->count(),
            'recent_orders' => $this->orderRepository->getRecent(5),
        ];
    }
}
