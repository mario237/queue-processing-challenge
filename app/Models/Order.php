<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Traits\OrderStatusManagement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory, OrderStatusManagement;

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
        return $this->status === OrderStatus::PENDING->value;
    }

    public function scopePending($query)
    {
        return $query->where('status', OrderStatus::PENDING->value);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', OrderStatus::FAILED->value);
    }
}
