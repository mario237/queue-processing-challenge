<?php

namespace App\Traits;

use App\Models\Order;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

/* @mixin Order */
trait OrderStatusManagement
{
    /**
     * Check if the order is in a pending state
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Mark the order as processing
     *
     * @return void
     * @throws Exception
     */
    public function markAsProcessing(): void
    {
        try {
            $this->status = 'processing';
            $this->processing_started_at = now();
            $this->save();

            Log::info("Order $this->id marked as processing", [
                'order_id' => $this->id,
                'previous_status' => $this->getOriginal('status')]);
        } catch (Exception $e) {
            Log::error("Failed to mark order as processing", [
                'order_id' => $this->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }


    /**
     * Mark the order as completed
     *
     * @return void
     * @throws Exception
     */
    public function markAsCompleted(): void
    {
        try {
            $this->status = 'completed';
            $this->completed_at = now();
            $this->save();

            Log::info("Order $this->id marked as completed", [
                'order_id' => $this->id,
                'previous_status' => $this->getOriginal('status'),
                'processing_duration' => $this->processing_started_at
                    ? now()->diffInSeconds($this->processing_started_at)
                    : null
            ]);
        } catch (Exception $e) {
            Log::error("Failed to mark order as completed", [
                'order_id' => $this->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Mark the order as failed
     *
     * @param string|null $reason Optional reason for failure
     * @return void
     * @throws Exception
     */
    public function markAsFailed(?string $reason = null): void
    {
        try {
            $this->status = 'failed';
            $this->failed_at = now();
            $this->failure_reason = $reason;
            $this->save();

            Log::error("Order $this->id marked as failed", [
                'order_id' => $this->id,
                'previous_status' => $this->getOriginal('status'),
                'failure_reason' => $reason
            ]);
        } catch (Exception $e) {
            Log::error("Failed to mark order as failed", [
                'order_id' => $this->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Retrieve orders based on their status
     *
     * @param Builder $query
     * @param string $status
     * @return Builder
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Check if the order can be processed
     *
     * @return bool
     */
    public function canBeProcessed(): bool
    {
        // List of statuses that allow processing
        $processableStatuses = ['pending', 'processing'];

        return in_array($this->status, $processableStatuses);
    }

    /**
     * Get a human-readable status description
     *
     * @return string
     */
    public function getStatusDescription(): string
    {
        $statusDescriptions = [
            'pending' => 'Waiting to be processed',
            'processing' => 'Currently being processed',
            'completed' => 'Successfully completed',
            'failed' => 'Processing failed',
            'cancelled' => 'Order cancelled'
        ];

        return $statusDescriptions[$this->status] ?? 'Unknown status';
    }
}
