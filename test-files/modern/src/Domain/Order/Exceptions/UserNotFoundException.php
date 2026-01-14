<?php

declare(strict_types=1);

namespace App\Domain\Order\Exceptions;

use Exception;

/**
 * Exception wanneer een user niet gevonden wordt.
 */
final class UserNotFoundException extends Exception
{
    public static function withId(int $userId): self
    {
        return new self(
            message: "User met ID {$userId} niet gevonden",
            code: 404
        );
    }
}
