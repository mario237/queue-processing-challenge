<?php

namespace App\Services\Dashboard;

use App\Repositories\OrderRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class DashboardDataService
{
    /**
     * The order repository instance.
     *
     * @var OrderRepositoryInterface
     */
    protected OrderRepositoryInterface $orderRepository;

    /**
     * Create a new service instance.
     *
     * @param OrderRepositoryInterface $orderRepository
     */
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
        $statuses = ['pending', 'processing', 'completed', 'failed'];
        foreach ($statuses as $status) {
            if (!isset($orderCounts[$status])) {
                $orderCounts[$status] = 0;
            }
        }

        return $orderCounts;
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
        $totalCompleted = $orderCounts['completed'] ?? 0;
        $totalFailed = $orderCounts['failed'] ?? 0;
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
