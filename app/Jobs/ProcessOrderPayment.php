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
use Throwable;

class ProcessOrderPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public int $backoff = 5;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public int $maxExceptions = 3;

    /**
     * Determine the time at which the job should timeout.
     *
     * @var int
     */
    public int $timeout = 60;

    /**
     * Specify the queue this job should be sent to.
     *
     * @var string|null
     */
    public $queue = 'orders';

    /**
     * The order instance.
     *
     * @var Order
     */
    protected Order $order;

    /**
     * Create a new job instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
        $this->onQueue('orders'); // Ensure all order processing jobs are on the same queue
    }

    /**
     * Execute the job.
     * @throws Exception
     */
    public function handle(): void
    {
        // Prevent running the same job multiple times by checking if order is already processed
        if (!$this->order->isPending()) {
            Log::info("Order {$this->order->id} is already being processed or has been processed.", [
                'order_id' => $this->order->id,
                'status' => $this->order->status,
                'job_id' => $this->job->getJobId()
            ]);
            return;
        }

        // Mark order as processing
        $this->order->markAsProcessing();
        Log::info("Order {$this->order->id} status changed to processing.", [
            'order_id' => $this->order->id,
            'job_id' => $this->job->getJobId()
        ]);

        try {
            // Add some context information for tracking
            Log::info("Starting payment processing for order {$this->order->id}", [
                'order_id' => $this->order->id,
                'amount' => $this->order->amount,
                'user_id' => $this->order->user_id,
                'attempt' => $this->attempts(),
                'job_id' => $this->job->getJobId()
            ]);

            // Simulate an external payment API call with timeout protection
            $startTime = microtime(true);
            sleep(2); // Simulate API delay
            $endTime = microtime(true);

            Log::info("Payment API call completed", [
                'order_id' => $this->order->id,
                'duration' => round($endTime - $startTime, 2) . 's'
            ]);

            // Simulate random success or failure (70% success rate)
            if (rand(1, 10) <= 7) {
                // Mark the order as completed
                $this->order->markAsCompleted();
                Log::info("Order {$this->order->id} has been completed successfully.", [
                    'order_id' => $this->order->id,
                    'job_id' => $this->job->getJobId()
                ]);
            } else {
                // Simulate a failure
                throw new Exception("Payment processing failed for order {$this->order->id}");
            }
        } catch (Exception $e) {
            // Mark order as failed - but only if this is the last retry
            if ($this->attempts() >= $this->tries) {
                $this->order->markAsFailed();
            }

            Log::error("Order {$this->order->id} failed: {$e->getMessage()}", [
                'order_id' => $this->order->id,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'attempt' => $this->attempts(),
                'max_attempts' => $this->tries,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'job_id' => $this->job->getJobId()
            ]);

            // Re-throw the exception to trigger job retry
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param  Throwable  $exception
     * @return void
     */
    public function failed(Throwable $exception): void
    {
        // Ensure the order is marked as failed when all retries are exhausted
        $this->order->markAsFailed();

        Log::error("All retries exhausted for Order {$this->order->id}: {$exception->getMessage()}", [
            'order_id' => $this->order->id,
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'attempts' => $this->attempts(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'job_id' => $this->job->getJobId() ?? 'unknown'
        ]);

        // Potentially trigger a notification here for failed orders
        // \Notification::route('mail', 'admin@example.com')
        //    ->notify(new OrderFailedNotification($this->order, $exception));
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array
     */
    public function tags(): array
    {
        return [
            'order',
            'order:'.$this->order->id,
            'user:'.$this->order->user_id,
            'amount:'.$this->order->amount
        ];
    }

    /**
     * Determine if the job should be encrypted.
     *
     * @return bool
     */
    public function shouldBeEncrypted(): bool
    {
        return true; // Encrypt the job payload for security
    }
}
