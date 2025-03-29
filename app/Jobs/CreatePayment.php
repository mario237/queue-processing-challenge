<?php

namespace App\Jobs;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\PayPalPaymentService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Throwable;

class CreatePayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Increased retry to handle transient failures
    public int $tries = 3;

    // Exponential backoff for retries
    public int $backoff = 2;

    // Longer timeout for payment processing
    public int $timeout = 120;

    protected Order $order;
    protected string $jobUniqueId;

    /**
     * Create a new job instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;

        // Generate a unique job identifier
        $this->jobUniqueId = 'paypal-payment-' . $order->id . '-' . uniqid();

        // Ensure the job is queued in the 'paypal' queue
        $this->onQueue('paypal');
    }

    /**
     * Execute the job.
     */
    public function handle(PayPalPaymentService $paypalService): ?array
    {
        // Begin a database transaction for atomicity
        DB::beginTransaction();

        try {
            // Prevent processing an already processed order
            if ($this->order->payment_status === 'created') {
                Log::info("PayPal payment already created for Order {$this->order->id}", [
                    'order_id' => $this->order->id,
                    'existing_payment_status' => $this->order->payment_status
                ]);

                DB::rollBack();
                return null;
            }

            // Create PayPal payment
            $paymentResult = $paypalService->createPayment($this->order);

            // Validate payment creation
            if (!$paymentResult['success']) {
                throw new Exception("Failed to create PayPal payment for Order {$this->order->id}");
            }

            // Update order with PayPal details
            $this->order->update([
                'payment_gateway' => 'paypal',
                'payment_id' => $paymentResult['paypal_order_id'],
                'payment_status' => 'created'
            ]);

            // Log successful payment creation
            Log::info("PayPal payment created for Order {$this->order->id}", [
                'order_id' => $this->order->id,
                'paypal_order_id' => $paymentResult['paypal_order_id']
            ]);

            // Commit the transaction
            DB::commit();

            // Return full payment result
            return [
                'success' => true,
                'order_id' => $this->order->id,
                'paypal_order_id' => $paymentResult['paypal_order_id'],
                'approval_url' => $paymentResult['approval_url'] ?? null
            ];
        } catch (Exception $e) {
            // Rollback the transaction
            DB::rollBack();

            // Log the error
            Log::error("PayPal payment creation failed for Order {$this->order->id}", [
                'order_id' => $this->order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'attempt' => $this->attempts()
            ]);

            // Mark order as failed on last attempt
            if ($this->attempts() >= $this->tries) {
                $this->order->update([
                    'payment_status' =>  OrderStatus::FAILED->value,
                    'status' =>  OrderStatus::FAILED->value
                ]);
            }

            // Rethrow to trigger retry
            throw $e;
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(Throwable $exception): void
    {
        // Ensure order is marked as failed
        $this->order->update([
            'payment_status' =>  OrderStatus::FAILED->value,
            'status' =>  OrderStatus::FAILED->value
        ]);

        // Log comprehensive failure details
        Log::error("PayPal payment job failed for Order {$this->order->id}", [
            'order_id' => $this->order->id,
            'error' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'attempts' => $this->attempts()
        ]);
    }

    /**
     * Get the tags for the job.
     */
    public function tags(): array
    {
        return [
            'paypal-payment',
            'order:' . $this->order->id,
            'user:' . $this->order->user_id,
            'amount:' . $this->order->amount,
            'job:' . $this->jobUniqueId,
            'queue:paypal'
        ];
    }
}
