<?php

namespace App\Services\Dashboard;

use App\Enums\OrderStatus;
use App\Repositories\Order\OrderRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class DashboardDataService
{

    protected OrderRepositoryInterface $orderRepository;
    public function __construct(OrderRepositoryInterface $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    /**
     * Get all data required for the dashboard.
     *
     * @return array
     */
    public function getDashboardData(): array
    {
        return [
            'orderCounts' => $this->getOrderCountsByStatus(),
            'recentOrders' => $this->getRecentOrders(),
            'stats' => $this->getOrderStatistics(),
            'statuses' => OrderStatus::cases(),
        ];
    }

    /**
     * Get order counts by status.
     *
     * @return array
     */
    protected function getOrderCountsByStatus(): array
    {
        $orderCounts = $this->orderRepository->getCountsByStatus();

        // Ensure all statuses have a count (default to 0)
        $result = [];
        foreach (OrderStatus::cases() as $status) {
            $statusValue = $status->value;
            $result[$statusValue] = $orderCounts[$statusValue] ?? 0;
        }

        return $result;
    }

    /**
     * Get recent orders.
     *
     * @param int $limit
     * @return Collection
     */
    protected function getRecentOrders(int $limit = 10): Collection
    {
        return $this->orderRepository->getRecentOrders($limit);
    }

    /**
     * Get order statistics.
     *
     * @return array
     */
    protected function getOrderStatistics(): array
    {
        $orderCounts = $this->getOrderCountsByStatus();
        $totalCompleted = $orderCounts[OrderStatus::COMPLETED->value] ?? 0;
        $totalFailed = $orderCounts[OrderStatus::FAILED->value] ?? 0;
        $completedAndFailed = $totalCompleted + $totalFailed;

        return [
            'total' => $this->orderRepository->getTotalCount(),
            'total_amount' => $this->orderRepository->getTotalAmount(),
            'avg_amount' => $this->orderRepository->getAverageAmount(),
            'success_rate' => $completedAndFailed > 0
                ? round(($totalCompleted / $completedAndFailed) * 100, 2)
                : 0,
        ];
    }
}
