<?php

declare(strict_types=1);

namespace App\Domain\Order\Events;

/**
 * Event dispatched when an order is deleted.
 */
final readonly class OrderDeletedEvent
{
    public function __construct(
        public int $orderId,
        public int $customerId,
    ) {}
}
