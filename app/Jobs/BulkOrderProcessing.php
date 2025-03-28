<?php

namespace App\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class BulkOrderProcessing implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600; // 1 hour maximum execution time
    public int $tries = 3; // Number of times to retry the entire job

    protected Collection $orders;
    protected string $batchId;

    public function __construct(Collection $orders)
    {
        $this->orders = $orders;
        $this->batchId = 'bulk-' . uniqid(); // Create a unique batch identifier
        $this->onQueue('bulk-orders');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Verbose logging
        Log::info('Bulk Order Processing Started', [
            'total_orders' => $this->orders->count(),
            'order_ids' => $this->orders->pluck('id')->toArray(),
            'batch_id' => $this->batchId
        ]);

        $processedCount = 0;
        $failedCount = 0;

        // Process each order individually
        foreach ($this->orders as $order) {
            try {
                // Dispatch individual order processing job
                ProcessOrder::dispatch($order)
                    ->onQueue("orders");
            } catch (Exception $e) {
                Log::error('Failed to dispatch order processing job', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage()
                ]);
                $failedCount++;
            }
        }

        // Log processing summary
        Log::info('Bulk Order Processing Completed', [
            'total_orders' => $this->orders->count(),
            'processed_orders' => $processedCount,
            'failed_orders' => $failedCount,
            'batch_id' => $this->batchId
        ]);
    }

    /**
     * Handle job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Bulk Order Processing Job Failed', [
            'error' => $exception->getMessage(),
            'order_ids' => $this->orders->pluck('id')->toArray(),
            'batch_id' => $this->batchId
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array
     */
    public function tags(): array
    {
        return [
            'bulk-processing',
            'batch:' . $this->batchId,
            'orders:' . $this->orders->pluck('id')->implode(','),
            'total_orders:' . $this->orders->count(),
            'queue:bulk-orders'
        ];
    }
}
