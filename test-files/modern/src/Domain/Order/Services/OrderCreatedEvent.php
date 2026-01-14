<?php

declare(strict_types=1);

namespace App\Domain\Order\Services;

use App\Domain\Order\DTOs\UserDTO;

/**
 * Event voor wanneer een order is aangemaakt.
 */
final readonly class OrderCreatedEvent
{
    public function __construct(
        public int $orderId,
        public UserDTO $user,
    ) {}
}
