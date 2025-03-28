<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    /**
     * Get all status values as an array.
     *
     * @return array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get a human-readable label for the status.
     *
     * @return string
     */
    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::PROCESSING => 'Processing',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
        };
    }
    /**
     * Get the CSS class associated with this status.
     *
     * @return string
     */
    public function cssClass(): string
    {
        return match($this) {
            self::PENDING => 'bg-secondary',
            self::PROCESSING => 'bg-primary',
            self::COMPLETED => 'bg-success',
            self::FAILED => 'bg-danger',
        };
    }
}
