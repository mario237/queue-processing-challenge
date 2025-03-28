<?php

namespace App\Repositories\Order;

use Illuminate\Database\Eloquent\Collection;

interface OrderRepositoryInterface
{
    /**
     * Get order counts grouped by status.
     *
     * @return array
     */
    public function getCountsByStatus(): array;

    /**
     * Get the most recent orders.
     *
     * @param int $limit
     * @return Collection
     */
    public function getRecentOrders(int $limit): Collection;

    /**
     * Get the total count of orders.
     *
     * @return int
     */
    public function getTotalCount(): int;

    /**
     * Get the total amount of all orders.
     *
     * @return float
     */
    public function getTotalAmount(): float;

    /**
     * Get the average order amount.
     *
     * @return float
     */
    public function getAverageAmount(): float;
}
