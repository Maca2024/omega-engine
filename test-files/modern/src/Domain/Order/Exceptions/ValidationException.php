<?php

declare(strict_types=1);

namespace App\Domain\Order\Exceptions;

use Exception;

/**
 * Exception voor validatie fouten.
 */
final class ValidationException extends Exception
{
    /**
     * @param array<string, array<string>> $errors
     */
    public function __construct(
        public readonly array $errors,
        string $message = 'Validatie gefaald'
    ) {
        parent::__construct($message, 422);
    }
}
