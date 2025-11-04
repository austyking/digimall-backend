<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Lunar\Models\Order;

final class EloquentOrderRepository implements OrderRepositoryInterface
{
    /**
     * Find an order by ID.
     */
    public function find(string $id): ?Order
    {
        return Order::query()->find($id);
    }

    /**
     * Find an order by reference.
     */
    public function findByReference(string $reference): ?Order
    {
        return Order::query()->where('reference', $reference)->first();
    }

    /**
     * Get all orders.
     */
    public function all(): Collection
    {
        return Order::query()->get();
    }

    /**
     * Get orders with pagination.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Order::query()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get orders by customer ID.
     */
    public function getByCustomer(string $customerId): Collection
    {
        return Order::query()
            ->where('customer_id', $customerId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get orders by vendor ID.
     */
    public function getByVendor(string $vendorId): Collection
    {
        return Order::query()
            ->whereHas('lines.purchasable.product', function ($query) use ($vendorId): void {
                $query->where('vendor_id', $vendorId);
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get orders by status.
     */
    public function getByStatus(string $status): Collection
    {
        return Order::query()
            ->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get pending orders.
     */
    public function getPending(): Collection
    {
        return $this->getByStatus('pending');
    }

    /**
     * Get completed orders.
     */
    public function getCompleted(): Collection
    {
        return $this->getByStatus('completed');
    }

    /**
     * Get orders within date range.
     */
    public function getByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): Collection
    {
        return Order::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Create a new order.
     */
    public function create(array $data): Order
    {
        return Order::query()->create($data);
    }

    /**
     * Update an order.
     */
    public function update(string $id, array $data): Order
    {
        $order = $this->find($id);

        if ($order === null) {
            throw new \RuntimeException("Order with ID {$id} not found");
        }

        $order->update($data);

        return $order->refresh();
    }

    /**
     * Update order status.
     */
    public function updateStatus(string $id, string $status): Order
    {
        return $this->update($id, ['status' => $status]);
    }

    /**
     * Delete an order.
     */
    public function delete(string $id): bool
    {
        $order = $this->find($id);

        if ($order === null) {
            return false;
        }

        return (bool) $order->delete();
    }

    /**
     * Get order with relationships loaded.
     */
    public function findWithRelations(string $id, array $relations = []): ?Order
    {
        return Order::query()
            ->with($relations)
            ->find($id);
    }

    /**
     * Search orders by query.
     */
    public function search(string $query): Collection
    {
        return Order::query()
            ->where('reference', 'like', "%{$query}%")
            ->orWhereHas('customer', function ($q) use ($query): void {
                $q->where('first_name', 'like', "%{$query}%")
                    ->orWhere('last_name', 'like', "%{$query}%");
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get order totals for a period.
     */
    public function getTotalsByPeriod(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        $orders = $this->getByDateRange($startDate, $endDate);

        return [
            'count' => $orders->count(),
            'total' => $orders->sum('total'),
            'sub_total' => $orders->sum('sub_total'),
            'tax_total' => $orders->sum('tax_total'),
            'shipping_total' => $orders->sum('shipping_total'),
        ];
    }

    /**
     * Get recent orders.
     */
    public function getRecent(int $limit = 10): Collection
    {
        return Order::query()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Check if order exists by ID.
     */
    public function exists(string $id): bool
    {
        return Order::query()->where('id', $id)->exists();
    }

    /**
     * Get orders requiring attention (pending payment, processing, etc.).
     */
    public function getRequiringAttention(): Collection
    {
        return Order::query()
            ->whereIn('status', ['pending', 'payment_pending', 'processing'])
            ->orderBy('created_at', 'asc')
            ->get();
    }
}
