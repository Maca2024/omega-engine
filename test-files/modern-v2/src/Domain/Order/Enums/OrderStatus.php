<?php

declare(strict_types=1);

namespace App\Domain\Order\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case PAID = 'paid';
    case PROCESSING = 'processing';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';

    public function isModifiable(): bool
    {
        return match ($this) {
            self::PENDING, self::CONFIRMED => true,
            default => false,
        };
    }

    public function isDeletable(): bool
    {
        return $this === self::PENDING;
    }

    public function canTransitionTo(self $newStatus): bool
    {
        return match ($this) {
            self::PENDING => in_array($newStatus, [self::CONFIRMED, self::CANCELLED], true),
            self::CONFIRMED => in_array($newStatus, [self::PAID, self::CANCELLED], true),
            self::PAID => $newStatus === self::PROCESSING,
            self::PROCESSING => $newStatus === self::SHIPPED,
            self::SHIPPED => $newStatus === self::DELIVERED,
            self::DELIVERED, self::CANCELLED => false,
        };
    }
}
