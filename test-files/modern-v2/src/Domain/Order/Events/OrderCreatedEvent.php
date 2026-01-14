<?php

declare(strict_types=1);

namespace App\Domain\Order\Events;

use App\Domain\Customer\DTOs\CustomerDTO;
use App\Domain\Order\DTOs\OrderDTO;

/**
 * Event dispatched when a new order is created.
 */
final readonly class OrderCreatedEvent
{
    public function __construct(
        public OrderDTO $order,
        public CustomerDTO $customer,
    ) {}
}
