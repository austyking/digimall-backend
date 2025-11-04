<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Lunar\Models\Order;

interface OrderRepositoryInterface
{
    /**
     * Find an order by ID.
     */
    public function find(string $id): ?Order;

    /**
     * Find an order by reference.
     */
    public function findByReference(string $reference): ?Order;

    /**
     * Get all orders.
     */
    public function all(): Collection;

    /**
     * Get orders with pagination.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate(int $perPage = 15);

    /**
     * Get orders by customer ID.
     */
    public function getByCustomer(string $customerId): Collection;

    /**
     * Get orders by vendor ID.
     */
    public function getByVendor(string $vendorId): Collection;

    /**
     * Get orders by status.
     */
    public function getByStatus(string $status): Collection;

    /**
     * Get pending orders.
     */
    public function getPending(): Collection;

    /**
     * Get completed orders.
     */
    public function getCompleted(): Collection;

    /**
     * Get orders within date range.
     */
    public function getByDateRange(string $startDate, string $endDate, ?int $limit): Collection;

    /**
     * Create a new order.
     */
    public function create(array $data): Order;

    /**
     * Update an order.
     */
    public function update(string $id, array $data): Order;

    /**
     * Update order status.
     */
    public function updateStatus(string $id, string $status): Order;

    /**
     * Delete an order.
     */
    public function delete(string $id): bool;

    /**
     * Get order with relationships loaded.
     */
    public function findWithRelations(string $id, array $relations = []): ?Order;

    /**
     * Search orders by query.
     */
    public function search(string $query): Collection;

    /**
     * Get order totals for a period.
     */
    public function getTotalsByPeriod(string $startDate, string $endDate): array;

    /**
     * Get recent orders.
     */
    public function getRecent(int $limit = 10): Collection;

    /**
     * Get total sales amount.
     */
    public function getTotalSales(?string $startDate = null, ?string $endDate = null): float;

    /**
     * Check if order exists by ID.
     */
    public function exists(string $id): bool;

    /**
     * Get orders requiring attention (pending payment, processing, etc.).
     */
    public function getRequiringAttention(): Collection;
}
