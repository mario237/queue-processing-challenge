<?php

namespace App\Jobs;

use App\Models\Order;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProcessOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Increased retry to handle transient failures
    public int $tries = 5;

    // Exponential backoff for retries
    public int $backoff = 2;

    // Longer timeout for payment processing
    public int $timeout = 120;
    protected string $batchId;

    protected Order $order;
    protected string $jobUniqueId;

    /**
     * Create a new job instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;

        // Generate a unique job identifier
        $this->jobUniqueId = 'order-' . $order->id . '-' . uniqid();

        // Ensure the job is queued in the 'orders' queue
        $this->onQueue("orders");
    }

    /**
     * Execute the job.
     * @throws Exception
     * @throws Throwable
     */
    public function handle()
    {
        // Begin a database transaction for atomicity
        DB::beginTransaction();

        try {
            // Prevent processing an already processed order
            if (!$this->order->isPending()) {
                Log::info("Order {$this->order->id} is already being processed or has been processed.", [
                    'order_id' => $this->order->id,
                    'status' => $this->order->status,
                    'job_id' => $this->job?->getJobId()
                ]);

                DB::rollBack();
                return;
            }

            // Verbose logging
            Log::info('Single Order Processing Started', [
                'order_id' => $this->order->id
            ]);

            // Mark order as processing
            $this->order->markAsProcessing();
            Log::info("Order {$this->order->id} status changed to processing.", [
                'order_id' => $this->order->id,
                'job_id' => $this->job?->getJobId()
            ]);

           CreatePayment::dispatch($this->order);

        } catch (Exception $e) {
            // Rollback the transaction
            DB::rollBack();

            // Mark order as failed only on the last retry
            if ($this->attempts() >= $this->tries) {
                $this->order->markAsFailed($e->getMessage());
            }

            Log::error("Order {$this->order->id} failed: {$e->getMessage()}", [
                'order_id' => $this->order->id,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'attempt' => $this->attempts(),
                'max_attempts' => $this->tries,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'job_id' => $this->job?->getJobId()
            ]);

            // Re-throw the exception to trigger job retry
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param Throwable $exception
     * @return void
     * @throws Exception
     */
    public function failed(Throwable $exception): void
    {
        // Ensure the order is marked as failed when all retries are exhausted
        $this->order->markAsFailed($exception->getMessage());

        Log::error("All retries exhausted for Order {$this->order->id}: {$exception->getMessage()}", [
            'order_id' => $this->order->id,
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'attempts' => $this->attempts(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'job_id' => $this->job?->getJobId() ?? 'unknown'
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
            'order-processing',
            'order:' . $this->order->id,
            'user:' . $this->order->user_id,
            'amount:' . $this->order->amount,
            'status:' . $this->order->status,
            'job:' . $this->jobUniqueId,
            'attempt:' . $this->attempts(),
            'queue:orders'
        ];
    }
}
