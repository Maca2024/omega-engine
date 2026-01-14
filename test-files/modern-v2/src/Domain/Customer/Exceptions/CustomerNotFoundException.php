<?php

declare(strict_types=1);

namespace App\Domain\Customer\Exceptions;

use Exception;

final class CustomerNotFoundException extends Exception
{
    public function __construct(int $customerId)
    {
        parent::__construct(
            sprintf('Customer with ID %d was not found', $customerId),
        );
    }
}
