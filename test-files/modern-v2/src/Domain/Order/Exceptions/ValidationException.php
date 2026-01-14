<?php

declare(strict_types=1);

namespace App\Domain\Order\Exceptions;

use Exception;

final class ValidationException extends Exception
{
    /**
     * @param array<string, string> $errors
     */
    private function __construct(
        string $message,
        private readonly array $errors = [],
    ) {
        parent::__construct($message);
    }

    /**
     * @param array<string, string> $errors
     */
    public static function withErrors(array $errors): self
    {
        return new self(
            'Validation failed',
            $errors,
        );
    }

    /**
     * @return array<string, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
