<?php

namespace App\Repositories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class OrderRepository implements OrderRepositoryInterface
{
    /**
     * @inheritDoc
     */
    public function getCountsByStatus(): array
    {
        return Order::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->pluck('total', 'status')
            ->toArray();
    }

    /**
     * @inheritDoc
     */
    public function getRecentOrders(int $limit): Collection
    {
        return Order::with('user')
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * @inheritDoc
     */
    public function getTotalCount(): int
    {
        return Order::count();
    }

    /**
     * @inheritDoc
     */
    public function getTotalAmount(): float
    {
        return (float) Order::sum('amount');
    }

    /**
     * @inheritDoc
     */
    public function getAverageAmount(): float
    {
        return (float) Order::avg('amount');
    }
}
