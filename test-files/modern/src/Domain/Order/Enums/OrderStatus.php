<?php

declare(strict_types=1);

namespace App\Domain\Order\Enums;

/**
 * Order status lifecycle.
 */
enum OrderStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case PROCESSING = 'processing';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';

    /**
     * Check of de order nog gewijzigd mag worden.
     */
    public function isModifiable(): bool
    {
        return match ($this) {
            self::PENDING, self::CONFIRMED => true,
            default => false,
        };
    }
}
