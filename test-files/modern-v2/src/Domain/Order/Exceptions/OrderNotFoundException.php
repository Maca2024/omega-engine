<?php

declare(strict_types=1);

namespace App\Domain\Order\Exceptions;

use Exception;

final class OrderNotFoundException extends Exception
{
    public function __construct(int $orderId)
    {
        parent::__construct(
            sprintf('Order with ID %d was not found', $orderId),
        );
    }
}
