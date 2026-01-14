<?php

declare(strict_types=1);

namespace App\Domain\Order\Exceptions;

use App\Domain\Order\Enums\OrderStatus;
use Exception;

final class OrderNotDeletableException extends Exception
{
    public function __construct(int $orderId, OrderStatus $status)
    {
        parent::__construct(
            sprintf(
                'Order %d cannot be deleted because it has status "%s". Only pending orders can be deleted.',
                $orderId,
                $status->value,
            ),
        );
    }
}
