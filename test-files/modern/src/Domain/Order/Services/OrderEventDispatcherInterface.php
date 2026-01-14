<?php

declare(strict_types=1);

namespace App\Domain\Order\Services;

/**
 * Event dispatcher interface voor async verwerking.
 */
interface OrderEventDispatcherInterface
{
    public function dispatch(OrderCreatedEvent $event): void;
}
