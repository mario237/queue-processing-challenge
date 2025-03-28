<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'amount',
        'status',
    ];

    /**
     * Get the user that owns the order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the order is in pending status.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the order is in processing status.
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Check if the order is in completed status.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the order is in failed status.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Mark the order as processing.
     */
    public function markAsProcessing(): self
    {
        $this->update([
            'status' => 'processing',
        ]);

        return $this;
    }

    /**
     * Mark the order as completed.
     */
    public function markAsCompleted(): self
    {
        $this->update([
            'status' => 'completed',
        ]);

        return $this;
    }

    /**
     * Mark the order as failed.
     */
    public function markAsFailed(): self
    {
        $this->update([
            'status' => 'failed',
        ]);

        return $this;
    }
}
