<?php

declare(strict_types=1);

namespace App\Domain\Order\Exceptions;

use Exception;

/**
 * Exception bij ongeautoriseerde toegang tot een order.
 */
final class UnauthorizedOrderAccessException extends Exception
{
    public static function forOrder(int $orderId): self
    {
        return new self(
            message: "Geen toegang tot order {$orderId} of order bestaat niet",
            code: 403
        );
    }
}
